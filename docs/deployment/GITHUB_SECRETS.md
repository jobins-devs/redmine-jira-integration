# GitHub Secrets Configuration Guide

This guide explains how to configure GitHub Secrets for automated deployments.

## Required Secrets

Navigate to your GitHub repository → **Settings** → **Secrets and variables** → **Actions** → **New repository secret**

### 1. SSH_PRIVATE_KEY

**Description**: Private SSH key for the deployer user on your production server.

**How to get it**:
```bash
# On your production server, as the deployer user
cat ~/.ssh/id_ed25519
```

**Value**: Copy the entire private key, including the header and footer:
```
-----BEGIN OPENSSH PRIVATE KEY-----
b3BlbnNzaC1rZXktdjEAAAAABG5vbmUAAAAEbm9uZQAAAAAAAAABAAAAMwAAAAtzc2gtZW
...
(multiple lines)
...
-----END OPENSSH PRIVATE KEY-----
```

**⚠️ IMPORTANT**: 
- Never share this key publicly
- Keep the private key secure
- Only add the public key (`id_ed25519.pub`) to GitHub as a deploy key

---

### 2. SERVER_HOST

**Description**: The hostname or IP address of your production server.

**Examples**:
- IP address: `123.45.67.89`
- Domain: `server.example.com`
- Hostname: `production-server-01`

**Value**: 
```
123.45.67.89
```

---

### 3. APP_URL

**Description**: The full URL of your production application.

**Examples**:
- `https://redmine-jira-integration.example.com`
- `https://redmine-jira.company.com`

**Value**:
```
https://your-domain.com
```

**⚠️ IMPORTANT**: 
- Must include `https://` protocol
- Should match the `APP_URL` in your production `.env` file

---

### 4. DOT_ENV (Optional)

**Description**: Complete production `.env` file contents. This is optional but recommended for automated deployments.

**How to create it**:
```bash
# On your production server
cat /var/www/redmine-jira-integration/shared/.env
```

**Value**: Copy the entire `.env` file contents:
```env
APP_NAME="Redmine Jira Integration"
APP_ENV=production
APP_KEY=base64:YOUR_GENERATED_KEY_HERE
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=rdi_production
DB_USERNAME=rdi_user
DB_PASSWORD=YOUR_STRONG_PASSWORD

QUEUE_CONNECTION=redis
CACHE_STORE=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

REDMINE_WEBHOOK_SECRET=YOUR_64_CHAR_SECRET
JIRA_WEBHOOK_SECRET=YOUR_64_CHAR_SECRET
```

**⚠️ IMPORTANT**: 
- This contains sensitive credentials
- Only use if you want GitHub Actions to manage your `.env` file
- Alternatively, manually manage `.env` on the server

---

## Step-by-Step Setup

### Step 1: Access GitHub Secrets

1. Go to your GitHub repository
2. Click **Settings** (top menu)
3. In the left sidebar, click **Secrets and variables** → **Actions**
4. Click **New repository secret**

### Step 2: Add Each Secret

For each secret listed above:

1. Click **New repository secret**
2. Enter the **Name** (exactly as shown above, case-sensitive)
3. Paste the **Value**
4. Click **Add secret**

### Step 3: Verify Secrets

After adding all secrets, you should see:

- ✅ `SSH_PRIVATE_KEY`
- ✅ `SERVER_HOST`
- ✅ `APP_URL`
- ✅ `DOT_ENV` (optional)

### Step 4: Test Deployment

1. Go to **Actions** tab
2. Select **Deploy to Production** workflow
3. Click **Run workflow**
4. Select **production** environment
5. Click **Run workflow**
6. Monitor the deployment progress

---

## Environment-Specific Secrets

If you have multiple environments (staging, production), you can use **Environment secrets**:

### Create Environment

1. Go to **Settings** → **Environments**
2. Click **New environment**
3. Enter name: `production`
4. Click **Configure environment**

### Add Environment Secrets

1. In the environment configuration, scroll to **Environment secrets**
2. Click **Add secret**
3. Add the same secrets as above, but specific to this environment

**Benefits**:
- Different credentials for staging vs production
- Deployment protection rules
- Required reviewers before deployment

---

## Security Best Practices

### ✅ DO:
- Use strong, unique passwords for all credentials
- Rotate secrets regularly (every 90 days)
- Use environment-specific secrets for staging/production
- Enable two-factor authentication on GitHub
- Limit repository access to necessary team members
- Use deployment protection rules for production

### ❌ DON'T:
- Commit secrets to the repository
- Share secrets via email or chat
- Use the same passwords across environments
- Give unnecessary people access to secrets
- Store secrets in plain text files

---

## Troubleshooting

### "Permission denied (publickey)" Error

**Problem**: GitHub Actions can't connect to the server.

**Solution**:
1. Verify `SSH_PRIVATE_KEY` is correct
2. Ensure the corresponding public key is added to `~/.ssh/authorized_keys` on the server
3. Check server firewall allows SSH connections

### "Host key verification failed" Error

**Problem**: Server's SSH host key not recognized.

**Solution**: The workflow includes `ssh-keyscan` to automatically add the host key. If this fails:
1. Verify `SERVER_HOST` is correct
2. Ensure the server is accessible from GitHub Actions runners

### Deployment Fails with "Permission denied" on Files

**Problem**: Deployer user doesn't have write permissions.

**Solution**:
```bash
# On server
sudo chown -R deployer:www-data /var/www/redmine-jira-integration
sudo chmod -R 775 /var/www/redmine-jira-integration
```

---

## Updating Secrets

To update a secret:

1. Go to **Settings** → **Secrets and variables** → **Actions**
2. Click on the secret name
3. Click **Update secret**
4. Enter the new value
5. Click **Update secret**

**Note**: You cannot view existing secret values. You can only update or delete them.

---

## Removing Secrets

To remove a secret:

1. Go to **Settings** → **Secrets and variables** → **Actions**
2. Click on the secret name
3. Click **Remove secret**
4. Confirm deletion

---

## Additional Resources

- [GitHub Actions Documentation](https://docs.github.com/en/actions)
- [GitHub Encrypted Secrets](https://docs.github.com/en/actions/security-guides/encrypted-secrets)
- [Deployer Documentation](https://deployer.org/docs/7.x/getting-started)

---

**Last Updated**: 2025-10-24

