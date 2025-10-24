# Deployment Guide - Redmine Jira Integration

This guide provides step-by-step instructions for deploying the Redmine Jira Integration application to production.

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Server Setup](#server-setup)
3. [GitHub Configuration](#github-configuration)
4. [Deployer Configuration](#deployer-configuration)
5. [First Deployment](#first-deployment)
6. [Automated Deployments](#automated-deployments)
7. [Rollback Procedure](#rollback-procedure)
8. [Monitoring & Maintenance](#monitoring--maintenance)
9. [Troubleshooting](#troubleshooting)

---

## Prerequisites

### Local Requirements
- PHP 8.2 or higher
- Composer 2.x
- Node.js 20.x or higher
- Git

### Server Requirements
- Ubuntu 22.04 LTS (recommended) or similar Linux distribution
- PHP 8.2-FPM
- Nginx or Apache
- MySQL 8.0+ or PostgreSQL 14+
- Redis 7.0+
- Supervisor (for queue workers)
- Git

---

## Server Setup

### 1. Create Deployment User

```bash
# Create deployer user
sudo adduser deployer
sudo usermod -aG www-data deployer

# Switch to deployer user
sudo su - deployer

# Generate SSH key
ssh-keygen -t ed25519 -C "deployer@your-server"

# Display public key (add this to your GitHub repository as a deploy key)
cat ~/.ssh/id_ed25519.pub
```

### 2. Install Required Software

```bash
# Update system
sudo apt update && sudo apt upgrade -y

# Install PHP 8.2 and extensions
sudo apt install -y php8.2-fpm php8.2-cli php8.2-mysql php8.2-redis \
    php8.2-mbstring php8.2-xml php8.2-curl php8.2-zip php8.2-bcmath \
    php8.2-intl php8.2-gd

# Install MySQL
sudo apt install -y mysql-server
sudo mysql_secure_installation

# Install Redis
sudo apt install -y redis-server
sudo systemctl enable redis-server
sudo systemctl start redis-server

# Install Nginx
sudo apt install -y nginx
sudo systemctl enable nginx
sudo systemctl start nginx

# Install Supervisor
sudo apt install -y supervisor
sudo systemctl enable supervisor
sudo systemctl start supervisor

# Install Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Install Node.js 20.x
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
```

### 3. Configure MySQL Database

```bash
# Login to MySQL
sudo mysql

# Create database and user
CREATE DATABASE rdi_production CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'rdi_user'@'localhost' IDENTIFIED BY 'STRONG_PASSWORD_HERE';
GRANT ALL PRIVILEGES ON rdi_production.* TO 'rdi_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 4. Configure Nginx

Create `/etc/nginx/sites-available/redmine-jira-integration`:

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name your-domain.com;
    
    # Redirect to HTTPS
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name your-domain.com;
    
    root /var/www/redmine-jira-integration/current/public;
    index index.php;
    
    # SSL Configuration (use Let's Encrypt)
    ssl_certificate /etc/letsencrypt/live/your-domain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/your-domain.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    
    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    
    # Logging
    access_log /var/log/nginx/redmine-jira-integration-access.log;
    error_log /var/log/nginx/redmine-jira-integration-error.log;
    
    # Client max body size
    client_max_body_size 20M;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }
    
    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Enable the site:

```bash
sudo ln -s /etc/nginx/sites-available/redmine-jira-integration /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### 5. Configure Queue Worker (Supervisor)

Create `/etc/supervisor/conf.d/redmine-jira-integration-queue-worker.conf`:

```ini
[program:redmine-jira-integration-queue-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/redmine-jira-integration/current/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=deployer
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/redmine-jira-integration/current/storage/logs/queue-worker.log
stopwaitsecs=3600
```

Reload Supervisor:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start redmine-jira-integration-queue-worker:*
```

### 6. Setup Deployment Directory

```bash
# Create deployment directory
sudo mkdir -p /var/www/redmine-jira-integration
sudo chown deployer:www-data /var/www/redmine-jira-integration
sudo chmod 755 /var/www/redmine-jira-integration

# Create shared .env file
mkdir -p /var/www/redmine-jira-integration/shared
nano /var/www/redmine-jira-integration/shared/.env
```

Add production environment variables to `/var/www/redmine-jira-integration/shared/.env`:

```env
APP_NAME="Redmine Jira Integration"
APP_ENV=production
APP_KEY=base64:GENERATE_WITH_php_artisan_key:generate
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=rdi_production
DB_USERNAME=rdi_user
DB_PASSWORD=STRONG_PASSWORD_HERE

QUEUE_CONNECTION=redis
CACHE_STORE=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

REDMINE_WEBHOOK_SECRET=GENERATE_RANDOM_64_CHAR_STRING
JIRA_WEBHOOK_SECRET=GENERATE_RANDOM_64_CHAR_STRING
```

---

## GitHub Configuration

### 1. Add Deploy Key to GitHub

1. Go to your GitHub repository
2. Navigate to **Settings** → **Deploy keys**
3. Click **Add deploy key**
4. Paste the public key from `/home/deployer/.ssh/id_ed25519.pub`
5. Check **Allow write access** (if needed)
6. Click **Add key**

### 2. Configure GitHub Secrets

Navigate to **Settings** → **Secrets and variables** → **Actions** and add:

| Secret Name | Description | Example Value |
|-------------|-------------|---------------|
| `SSH_PRIVATE_KEY` | Deployer user's private SSH key | Contents of `~/.ssh/id_ed25519` |
| `SERVER_HOST` | Production server hostname/IP | `123.45.67.89` or `server.example.com` |
| `APP_URL` | Application URL | `https://your-domain.com` |
| `DOT_ENV` | Production .env file (optional) | Full .env contents |

---

## Deployer Configuration

### 1. Update deploy.php

Edit `deploy.php` in your project root:

```php
// Update repository URL
set('repository', 'git@github.com:YOUR_USERNAME/YOUR_REPO.git');

// Update production host
host('production')
    ->setHostname('YOUR_SERVER_IP')  // e.g., '123.45.67.89'
    ->setPort(22)
    ->setRemoteUser('deployer')
    ->setDeployPath('/var/www/redmine-jira-integration');
```

### 2. Test SSH Connection

```bash
# From your local machine
ssh deployer@YOUR_SERVER_IP

# If successful, you should be logged in to the server
```

---

## First Deployment

### Manual Deployment (First Time)

```bash
# From your local machine, in the project directory
vendor/bin/dep deploy production

# If deployment fails, unlock and try again
vendor/bin/dep deploy:unlock production
vendor/bin/dep deploy production
```

The first deployment will:
1. Clone the repository
2. Install Composer dependencies
3. Install NPM dependencies
4. Build frontend assets
5. Run database migrations
6. Cache configurations
7. Create symbolic links
8. Restart queue workers

---

## Automated Deployments

### Automatic Deployment on Push

Once GitHub Actions is configured, deployments happen automatically:

1. Push code to `main` or `production` branch
2. GitHub Actions runs tests and quality checks
3. If tests pass, code is automatically deployed
4. Health check verifies deployment success

### Manual Deployment via GitHub Actions

1. Go to **Actions** tab in GitHub
2. Select **Deploy to Production** workflow
3. Click **Run workflow**
4. Select environment (production/staging)
5. Click **Run workflow**

---

## Rollback Procedure

### Using Deployer

```bash
# Rollback to previous release
vendor/bin/dep rollback production

# Check rollback status
vendor/bin/dep ssh production
cd /var/www/redmine-jira-integration/current
php artisan --version
```

### Manual Rollback

```bash
# SSH to server
ssh deployer@YOUR_SERVER_IP

# List releases
ls -la /var/www/redmine-jira-integration/releases/

# Update symlink to previous release
ln -sfn /var/www/redmine-jira-integration/releases/PREVIOUS_RELEASE /var/www/redmine-jira-integration/current

# Clear caches
cd /var/www/redmine-jira-integration/current
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Restart services
sudo systemctl reload php8.2-fpm
sudo supervisorctl restart redmine-jira-integration-queue-worker:*
```

---

## Monitoring & Maintenance

### View Application Logs

```bash
# From local machine
vendor/bin/dep logs:show production

# Or SSH to server
ssh deployer@YOUR_SERVER_IP
tail -f /var/www/redmine-jira-integration/current/storage/logs/laravel.log
```

### Check Queue Worker Status

```bash
vendor/bin/dep queue:status production

# Or on server
sudo supervisorctl status redmine-jira-integration-queue-worker:*
```

### Restart Queue Workers

```bash
vendor/bin/dep queue:restart-service production

# Or on server
sudo supervisorctl restart redmine-jira-integration-queue-worker:*
```

### Clear Caches

```bash
vendor/bin/dep cache:clear production
```

---

## Troubleshooting

### Deployment Fails

```bash
# Unlock deployment
vendor/bin/dep deploy:unlock production

# Check server logs
vendor/bin/dep ssh production
tail -f /var/www/redmine-jira-integration/current/storage/logs/laravel.log
```

### Permission Issues

```bash
# Fix storage permissions
sudo chown -R deployer:www-data /var/www/redmine-jira-integration/shared/storage
sudo chmod -R 775 /var/www/redmine-jira-integration/shared/storage
```

### Queue Workers Not Running

```bash
# Check supervisor status
sudo supervisorctl status

# Restart queue workers
sudo supervisorctl restart redmine-jira-integration-queue-worker:*

# Check logs
tail -f /var/www/redmine-jira-integration/current/storage/logs/queue-worker.log
```

### Database Connection Issues

```bash
# Test database connection
php artisan tinker
DB::connection()->getPdo();
```

---

## Security Checklist

- [ ] `APP_DEBUG=false` in production
- [ ] Strong database passwords
- [ ] HTTPS enabled with valid SSL certificate
- [ ] Firewall configured (UFW or iptables)
- [ ] SSH key-based authentication only
- [ ] Regular security updates applied
- [ ] Webhook secrets properly configured
- [ ] File permissions correctly set
- [ ] Logs monitored regularly
- [ ] Backups configured and tested

---

## Support

For issues or questions, please contact the development team or create an issue in the GitHub repository.

