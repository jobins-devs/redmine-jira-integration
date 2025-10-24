# Redmine Jira Integration Setup Guide

This guide will walk you through setting up the bi-directional Redmine Jira Integration application from scratch.

## Prerequisites

Before you begin, ensure you have:
- Access to a Redmine instance with API access
- Access to a Jira instance with API access
- Server with PHP 8.2+, Composer, and Node.js installed
- Basic knowledge of webhooks and REST APIs

## Step 1: Initial Setup

### Install the Application

```bash
cd ~/redmine-jira-integration

# Install dependencies
composer install
pnpm install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Create database
php artisan migrate

# Build assets
npm run build
```

### Start the Application

```bash
# Terminal 1: Start web server
php artisan serve

# Terminal 2: Start queue worker
php artisan queue:work --tries=3
```

Access the application at `http://localhost:8000`

## Step 2: Configure Redmine Connection

### Get Redmine API Key

1. Log in to your Redmine instance
2. Go to **My Account** (top right corner)
3. Click **Show** under **API access key** section
4. Copy the API key

### Add Redmine Connection

1. In the integration app, navigate to **Connections**
2. Click **Add Connection**
3. Fill in the form:
   - **Type**: Redmine
   - **Name**: My Redmine Server (or any descriptive name)
   - **URL**: https://your-redmine-server.com
   - **API Key**: Paste the API key from Redmine
4. Click **Create**
5. Click **Test** to verify the connection
6. Connection should show as "connected"

## Step 3: Configure Jira Connection

### Get Jira API Token

1. Log in to Jira
2. Go to https://id.atlassian.com/manage-profile/security/api-tokens
3. Click **Create API token**
4. Give it a name (e.g., "Redmine Integration")
5. Copy the generated token (you won't be able to see it again!)

### Add Jira Connection

1. In the integration app, navigate to **Connections**
2. Click **Add Connection**
3. Fill in the form:
   - **Type**: Jira
   - **Name**: My Jira Server (or any descriptive name)
   - **URL**: https://your-domain.atlassian.net
   - **Email**: Your Jira account email
   - **API Token**: Paste the API token
4. Click **Create**
5. Click **Test** to verify the connection
6. Connection should show as "connected"

## Step 4: Set Up Field Mappings

Field mappings tell the system how to translate fields between Redmine and Jira. You need to map:

### Tracker Mappings (Issue Types)

Navigate to **Field Mappings** and create mappings:

| Redmine Tracker | Redmine ID | Jira Issue Type | Jira ID |
|-----------------|------------|-----------------|---------|
| Bug             | 1          | Bug             | -       |
| Feature         | 2          | Story           | -       |
| Task            | 3          | Task            | -       |

**Example:**
1. Click **Add Mapping**
2. Mapping Type: **Tracker**
3. Redmine Value: **Bug**
4. Redmine ID: **1** (get from Redmine API or UI)
5. Jira Value: **Bug**
6. Jira ID: Leave empty (Jira uses names)
7. Active: **Checked**
8. Click **Create**

Repeat for all your trackers.

### Status Mappings

| Redmine Status | Redmine ID | Jira Status | Jira ID |
|----------------|------------|-------------|---------|
| New            | 1          | To Do       | -       |
| In Progress    | 2          | In Progress | -       |
| Resolved       | 3          | Done        | -       |
| Closed         | 5          | Done        | -       |

### Priority Mappings

| Redmine Priority | Redmine ID | Jira Priority | Jira ID |
|------------------|------------|---------------|---------|
| Low              | 1          | Low           | -       |
| Normal           | 2          | Medium        | -       |
| High             | 3          | High          | -       |
| Urgent           | 4          | Highest       | -       |

### User Mappings (Optional)

Map users if you want to sync assignees:

| Redmine User     | Redmine ID | Jira User        | Jira ID                    |
|------------------|------------|------------------|----------------------------|
| John Doe         | 5          | john@example.com | 557058:abc123...           |

**Note:** Getting Jira user IDs can be complex. You may want to skip user mapping initially.

## Step 5: Set Up Project Mappings

Project mappings define which projects to sync.

### Example: Sync "Development" project

1. Navigate to **Project Mappings**
2. Click **Add Project Mapping**
3. Fill in the form:
   - **Redmine Connection**: Select your Redmine connection
   - **Redmine Project**: Select "Development" from dropdown
   - **Jira Connection**: Select your Jira connection
   - **Jira Project**: Select "DEV" from dropdown
   - **Sync Direction**: Bi-directional
   - **Enable sync immediately**: Checked
4. Click **Create**

The mapping is now active!

## Step 6: Configure Webhooks

### Redmine Webhooks

**Option A: Using Redmine Webhook Plugin (Recommended)**

1. Install the Redmine Webhook Plugin if not already installed
2. In Redmine, go to **Administration → Settings → Webhooks**
3. Click **New Webhook**
4. Configure:
   - **URL**: `https://your-app-domain.com/webhooks/redmine`
   - **Events**: Check "Issue created" and "Issue updated"
   - **Secret**: (Optional) Add your `REDMINE_WEBHOOK_SECRET` from .env
5. Click **Save**

**Option B: Manual Testing (Development)**

For testing without webhooks:
1. Create/update issues manually in Redmine
2. Manually trigger sync from the dashboard

### Jira Webhooks

1. In Jira, click **Settings** (gear icon) → **System**
2. Scroll to **Advanced** section → Click **WebHooks**
3. Click **Create a WebHook**
4. Configure:
   - **Name**: Redmine Integration
   - **Status**: Enabled
   - **URL**: `https://your-app-domain.com/webhooks/jira`
   - **Events**: Check:
     - Issue: Created
     - Issue: Updated
     - Issue: Transitioned (status changed)
   - **JQL**: (Optional) Filter specific projects
5. Click **Create**

**Important:** For webhooks to work, your application must be publicly accessible. For local development, use tools like ngrok:

```bash
# Install ngrok
npm install -g ngrok

# Expose local server
ngrok http 8000

# Use the ngrok URL in webhook configurations
```

## Step 7: Test the Integration

### Test Redmine → Jira

1. Go to your Redmine project
2. Create a new issue:
   - **Tracker**: Bug (must be mapped)
   - **Subject**: Test issue from Redmine
   - **Description**: Testing sync
   - **Status**: New
   - **Priority**: Normal
3. Submit the issue
4. In the integration app:
   - Navigate to **Dashboard**
   - Check **Recent Sync Activity**
   - You should see a new sync log with status "success"
5. Go to your Jira project
6. The issue should appear in Jira!

### Test Jira → Redmine

1. Go to your Jira project
2. Create a new issue:
   - **Issue Type**: Bug
   - **Summary**: Test issue from Jira
   - **Description**: Testing sync
3. Create the issue
4. In the integration app:
   - Navigate to **Dashboard**
   - Check **Recent Sync Activity**
   - You should see a new sync log with status "success"
5. Go to your Redmine project
6. The issue should appear in Redmine!

### Test Issue Updates

1. Update an existing synced issue in Redmine:
   - Change the status to "In Progress"
   - Update the description
2. Check Jira - changes should sync
3. Update the same issue in Jira:
   - Change priority to "High"
   - Add a comment
4. Check Redmine - changes should sync

## Step 8: Monitoring and Troubleshooting

### Dashboard Overview

The dashboard shows:
- **Total Synced**: Successfully synced issues
- **Pending**: Issues waiting to be synced
- **Failed**: Issues that failed to sync
- **Active Mappings**: Number of enabled project mappings
- **Connections**: Total active connections

### Check Sync Logs

1. Navigate to **Dashboard**
2. Review **Recent Sync Activity** table
3. Look for:
   - Type of sync (create, update, status_change)
   - Source and target systems
   - Status (success, failed, pending)
   - Timestamp

### Handle Failed Syncs

If a sync fails:

1. Check the **Recent Errors** section on the dashboard
2. Read the error message
3. Common issues:
   - Missing field mappings → Add the mapping
   - Invalid API credentials → Test and update connection
   - Network timeout → Retry the sync
4. Click **Retry** button to retry failed syncs

### View Detailed Logs

Check application logs:
```bash
tail -f storage/logs/laravel.log
```

### Common Issues

#### "Tracker mapping not found"
- **Solution**: Add the tracker mapping in Field Mappings

#### "Connection failed"
- **Solution**: Verify API credentials and network connectivity

#### "Webhook not received"
- **Solution**: Check webhook URL is publicly accessible
- For local dev, use ngrok
- Verify webhook is configured correctly in Redmine/Jira

#### Queue not processing
- **Solution**: Ensure queue worker is running
```bash
php artisan queue:work --tries=3
```

## Step 9: Production Deployment

### Security Checklist

1. Set `APP_ENV=production` in `.env`
2. Set `APP_DEBUG=false` in `.env`
3. Generate webhook secrets:
   ```bash
   php artisan tinker
   >>> Str::random(64)
   ```
4. Add secrets to `.env` and webhook configurations
5. Use HTTPS with valid SSL certificate
6. Set proper file permissions:
   ```bash
   chmod -R 755 storage bootstrap/cache
   chown -R www-data:www-data storage bootstrap/cache
   ```

### Set Up Supervisor for Queue Worker

1. Create supervisor config:
   ```bash
   sudo nano /etc/supervisor/conf.d/redmine-jira-worker.conf
   ```

2. Add configuration:
   ```ini
   [program:redmine-jira-worker]
   process_name=%(program_name)s_%(process_num)02d
   command=php /var/www/redmine-jira-integration/artisan queue:work --tries=3 --timeout=90
   autostart=true
   autorestart=true
   user=www-data
   numprocs=2
   redirect_stderr=true
   stdout_logfile=/var/www/redmine-jira-integration/storage/logs/worker.log
   ```

3. Update supervisor:
   ```bash
   sudo supervisorctl reread
   sudo supervisorctl update
   sudo supervisorctl start redmine-jira-worker:*
   ```

### Configure Web Server (Nginx Example)

```nginx
server {
    listen 80;
    server_name your-domain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name your-domain.com;
    root /var/www/redmine-jira-integration/public;

    ssl_certificate /path/to/ssl/cert.pem;
    ssl_certificate_key /path/to/ssl/key.pem;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

## Next Steps

- **Monitor regularly**: Check the dashboard for sync status
- **Add more mappings**: Map all fields you need to sync
- **Configure more projects**: Add other project mappings as needed
- **Set up backups**: Regularly backup the database
- **Review logs**: Check logs periodically for issues
- **Update credentials**: Rotate API keys/tokens regularly

## Getting Help

If you encounter issues:

1. Check the logs: `storage/logs/laravel.log`
2. Review this guide for common issues
3. Verify all mappings are configured
4. Test connections manually
5. Check webhook configurations

## Summary

You now have a fully functional bi-directional Redmine Jira Integration! Issues will automatically sync between systems based on your configured mappings and project selections.

Key points to remember:
- **Field mappings** must be complete for all fields you want to sync
- **Project mappings** define which projects sync
- **Webhooks** enable real-time synchronization
- **Queue worker** must always be running
- **Dashboard** is your central monitoring hub
