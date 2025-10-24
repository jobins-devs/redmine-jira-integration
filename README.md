<div align="center">

# 🔄 Redmine Jira Integration

### Seamless bi-directional synchronization between Redmine and Jira issue tracking systems

[![Build Status](https://img.shields.io/github/actions/workflow/status/jobins-devs/redmine-jira-integration/deploy.yml?branch=main&style=flat-square)](https://github.com/jobins-devs/redmine-jira-integration/actions)
[![PHP Version](https://img.shields.io/badge/PHP-8.2%2B-777BB4?style=flat-square&logo=php)](https://www.php.net/)
[![Laravel Version](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=flat-square&logo=laravel)](https://laravel.com)
[![License](https://img.shields.io/badge/License-MIT-green?style=flat-square)](LICENSE)
[![Code Style](https://img.shields.io/badge/Code%20Style-Laravel%20Pint-orange?style=flat-square)](https://laravel.com/docs/pint)

[Features](#-key-features) • [Installation](#-quick-start) • [Documentation](#-documentation) • [Deployment](#-deployment)

</div>

---

## 📖 About

**Redmine Jira Integration** is a production-ready, standalone Laravel application that enables seamless bi-directional synchronization between Redmine and Jira issue tracking systems. Built with modern web technologies, it provides a robust bridge between development teams using Redmine and QA teams using Jira, ensuring real-time data consistency across both platforms.

Unlike plugin-based solutions that require installation into Redmine or Jira (which can cause performance issues, version compatibility problems, and maintenance headaches), this application operates as an independent microservice. It communicates with both systems exclusively through their official REST APIs, making it more resilient, scalable, and easier to maintain.

The application features a modern web-based management interface built with Inertia.js and Vue 3, allowing teams to configure connections, define field mappings, and monitor synchronization activity in real-time. With webhook support for instant updates, queue-based async processing, and comprehensive error handling, it's designed for enterprise-grade reliability.

---

## ✨ Key Features

- 🔄 **Bi-directional Synchronization** - Real-time sync between Redmine ↔ Jira with configurable sync direction
- ⚡ **Webhook Support** - Instant updates via webhooks from both Redmine and Jira
- 🎯 **Flexible Field Mapping** - Map trackers, statuses, priorities, users, and custom fields between systems
- 🎨 **Project-Level Configuration** - Configure sync settings per project with granular control
- 📊 **Sync Monitoring Dashboard** - Real-time statistics, activity logs, and error tracking
- 🔐 **Secure Credential Storage** - Encrypted API credentials with webhook signature verification
- 🚀 **Queue-Based Processing** - Async job processing with automatic retry logic and exponential backoff
- 🛡️ **Idempotency Protection** - Prevents duplicate syncs from redundant webhook deliveries
- 🎛️ **Modern Web UI** - Intuitive management interface built with Inertia.js and Vue 3
- 📝 **Comprehensive Logging** - Detailed sync logs with error tracking and manual retry capability
- 🔧 **No Plugins Required** - Standalone application that doesn't modify Redmine or Jira installations
- 🌐 **Multi-Connection Support** - Manage multiple Redmine and Jira instances from a single application

---

## 🛠️ Tech Stack

| Category | Technologies |
|----------|-------------|
| **Backend** | Laravel 12, PHP 8.2+ |
| **Frontend** | Vue 3, Inertia.js, Tailwind CSS 4 |
| **Package Manager** | pnpm 9 |
| **Database** | MySQL 8.0+ / PostgreSQL 13+ / SQLite 3 |
| **Queue** | Redis / Database |
| **Cache** | Redis / Database |
| **Deployment** | Deployer, GitHub Actions CI/CD |
| **Code Quality** | PHPStan (Level 5), Laravel Pint, Rector |
| **Testing** | PHPUnit, Laravel Dusk (optional) |

---

## 📋 Prerequisites

Before installing, ensure you have the following:

| Requirement | Version | Notes |
|------------|---------|-------|
| **PHP** | 8.2+ | With extensions: mbstring, xml, ctype, json, bcmath, pdo |
| **Composer** | 2.x | PHP dependency manager |
| **Node.js** | 20.x+ | JavaScript runtime |
| **pnpm** | 9.x | ⚠️ **Required** - Not npm or yarn |
| **Database** | MySQL 8.0+ / PostgreSQL 13+ / SQLite 3 | SQLite for development, MySQL/PostgreSQL for production |
| **Redis** | 6.x+ | Recommended for production (cache & queues) |
| **Redmine** | 4.x+ | With API access enabled and API key |
| **Jira** | Cloud/Server | With API access and API token |

### Installing pnpm

If you don't have pnpm installed:

```bash
# Using npm
npm install -g pnpm

# Using Homebrew (macOS)
brew install pnpm

# Using standalone script
curl -fsSL https://get.pnpm.io/install.sh | sh -
```

---

## 🚀 Quick Start

### 1. Clone the Repository

```bash
git clone https://github.com/jobins-devs/redmine-jira-integration.git
cd redmine-jira-integration
```

### 2. Install Dependencies

```bash
# Install PHP dependencies
composer install

# Install JavaScript dependencies with pnpm (NOT npm!)
pnpm install
```

### 3. Configure Environment

```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Edit .env and configure your database
# For development, SQLite is pre-configured
# For production, use MySQL or PostgreSQL
```

### 4. Set Up Database

```bash
# Run migrations
php artisan migrate

# (Optional) Seed sample data for testing
php artisan db:seed
```

### 5. Build Frontend Assets

```bash
# For production
pnpm run build

# For development with hot reload
pnpm run dev
```

### 6. Start the Application

```bash
# Terminal 1: Start web server
php artisan serve

# Terminal 2: Start queue worker
php artisan queue:work --tries=3
```

The application will be available at **http://localhost:8000**

---

## ⚙️ Configuration

### Connecting to Redmine and Jira

The application requires API access to both Redmine and Jira instances. Configure connections through the web interface:

1. **Redmine Connection**: Provide base URL and API key from a user with appropriate permissions
2. **Jira Connection**: Provide base URL, email, and API token from Atlassian account settings
3. **Test Connections**: Use the built-in connection tester to verify credentials

### Webhook Configuration

For real-time synchronization, configure webhooks in both systems:

- **Redmine**: Requires a webhook plugin (e.g., Redmine Webhook Plugin)
- **Jira**: Native webhook support in Settings → System → Webhooks

Webhook endpoints:
- Redmine: `https://your-domain.com/webhooks/redmine`
- Jira: `https://your-domain.com/webhooks/jira`

For detailed configuration instructions, see **[Setup Guide](docs/setup/SETUP_GUIDE.md)**.

---

## 💡 Usage

### Basic Workflow

1. **Create Connections** - Configure Redmine and Jira API connections
2. **Define Field Mappings** - Map trackers, statuses, priorities, users, and custom fields
3. **Create Project Mappings** - Select which projects to sync and set sync direction
4. **Configure Webhooks** - Set up webhooks in Redmine and Jira for real-time updates
5. **Enable Sync** - Activate project mappings to start synchronization
6. **Monitor Activity** - Track sync status, view logs, and handle errors from the dashboard

### Accessing the Web Interface

After starting the application, navigate to `http://localhost:8000` (or your configured domain) to access the management interface:

- **Dashboard** - View sync statistics and recent activity
- **Connections** - Manage Redmine and Jira connections
- **Field Mappings** - Configure field mappings between systems
- **Project Mappings** - Set up project-level sync configuration

### Example Field Mappings

**Tracker Mappings:**
- Redmine "Bug" → Jira "Bug"
- Redmine "Feature" → Jira "Story"
- Redmine "Task" → Jira "Task"

**Status Mappings:**
- Redmine "New" → Jira "To Do"
- Redmine "In Progress" → Jira "In Progress"
- Redmine "Resolved" → Jira "Done"

**Priority Mappings:**
- Redmine "Low" → Jira "Low"
- Redmine "Normal" → Jira "Medium"
- Redmine "High" → Jira "High"
- Redmine "Urgent" → Jira "Highest"

For comprehensive usage instructions, see **[Implementation Guide](docs/setup/IMPLEMENTATION_GUIDE.md)**.

---

## 🚢 Deployment

This application is production-ready with automated deployment support via **Deployer** and **GitHub Actions CI/CD**.

### Quick Deployment

```bash
# Deploy to production
vendor/bin/dep deploy production

# Rollback if needed
vendor/bin/dep rollback production
```

### Deployment Features

- ✅ **Zero-downtime deployments** with atomic symlink switching
- ✅ **Automated CI/CD pipeline** via GitHub Actions
- ✅ **Health checks** after deployment
- ✅ **Automatic rollback** on failure
- ✅ **Queue worker management** with automatic restart
- ✅ **Asset compilation** during deployment
- ✅ **Database migrations** with safety checks

### Production Requirements

- Ubuntu 22.04 LTS (recommended) or similar Linux distribution
- Nginx or Apache web server
- PHP 8.2-FPM
- MySQL 8.0+ or PostgreSQL 13+
- Redis 6.x+ (for cache and queues)
- Supervisor (for queue workers)
- SSL certificate (Let's Encrypt recommended)

### Documentation

- **[Deployment Guide](docs/deployment/DEPLOYMENT.md)** - Complete deployment instructions
- **[Production Checklist](docs/production/PRODUCTION_CHECKLIST.md)** - Pre-deployment verification
- **[GitHub Secrets Setup](docs/deployment/GITHUB_SECRETS.md)** - CI/CD configuration
- **[Production Summary](docs/production/PRODUCTION_SUMMARY.md)** - Production readiness overview

---

## 📚 Documentation

Comprehensive documentation is available in the `docs/` directory:

### Getting Started
- **[Project Summary](docs/PROJECT_SUMMARY.md)** - High-level project overview
- **[Setup Guide](docs/setup/SETUP_GUIDE.md)** - Step-by-step installation and configuration
- **[Implementation Guide](docs/setup/IMPLEMENTATION_GUIDE.md)** - Detailed implementation instructions

### Deployment & Production
- **[Deployment Guide](docs/deployment/DEPLOYMENT.md)** - Production deployment procedures
- **[GitHub Secrets](docs/deployment/GITHUB_SECRETS.md)** - CI/CD secrets configuration
- **[Production Checklist](docs/production/PRODUCTION_CHECKLIST.md)** - Pre-deployment checklist
- **[Production Summary](docs/production/PRODUCTION_SUMMARY.md)** - Production preparation summary

### Development
- **[FQCN Refactoring](docs/development/FQCN_REFACTORING_SUMMARY.md)** - Code quality improvements

### Research & Architecture
- **[APIs and Architecture](docs/research/apis_and_architecture.md)** - API analysis and design decisions
- **[Integration Patterns](docs/research/integration_patterns.md)** - Synchronization patterns

### Quick Links
- **[Documentation Index](docs/README.md)** - Complete documentation navigation

---

## 📁 Project Structure

```
.
├── app/                          # Application code
│   ├── Http/Controllers/        # Web and API controllers
│   ├── Jobs/                    # Queue jobs for async processing
│   ├── Models/                  # Eloquent models
│   └── Services/                # Business logic (RedmineClient, JiraClient)
├── resources/
│   ├── js/                      # Vue 3 components and Inertia pages
│   └── css/                     # Tailwind CSS styles
├── database/
│   ├── migrations/              # Database migrations
│   └── factories/               # Model factories for testing
├── docs/                        # 📚 Comprehensive documentation
│   ├── deployment/              # Deployment guides
│   ├── production/              # Production checklists
│   ├── development/             # Development guides
│   ├── setup/                   # Setup and implementation
│   └── research/                # Architecture and API research
├── routes/
│   └── web.php                  # Application routes
├── tests/                       # PHPUnit tests
├── deploy.php                   # Deployer configuration
├── .github/workflows/           # GitHub Actions CI/CD
└── README.md                    # This file
```

---

## 🧪 Development

### Running Tests

```bash
# Run all tests
php artisan test

# Run with coverage
php artisan test --coverage

# Run specific test
php artisan test --filter=ConnectionTest
```

### Code Quality

```bash
# Run PHPStan static analysis (Level 5)
./vendor/bin/phpstan analyse

# Format code with Laravel Pint
./vendor/bin/pint

# Check code style without fixing
./vendor/bin/pint --test

# Run PHP Insights
php artisan insights
```

### Building Assets

```bash
# Development build with hot reload
pnpm run dev

# Production build (optimized)
pnpm run build

# Type checking
pnpm run type-check
```

### Local Development Tips

1. **Use SQLite for development** - Pre-configured in `.env.example`
2. **Run queue worker in separate terminal** - `php artisan queue:work`
3. **Enable debug mode** - Set `APP_DEBUG=true` in `.env`
4. **Use Vite dev server** - Run `pnpm run dev` for hot module replacement
5. **Monitor logs** - `tail -f storage/logs/laravel.log`

---

## 🤝 Contributing

We welcome contributions! Please follow these guidelines:

### Code Style

- **PHP**: Follow PSR-12 standards, enforced by Laravel Pint
- **JavaScript**: Follow Vue 3 style guide
- **Commits**: Use conventional commit messages

### Quality Standards

- ✅ All tests must pass (`php artisan test`)
- ✅ PHPStan Level 5 with zero errors
- ✅ Code formatted with Laravel Pint
- ✅ No debug statements (`dd()`, `dump()`, `console.log()`)
- ✅ Comprehensive documentation for new features

### Submitting Changes

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Make your changes
4. Run tests and code quality checks
5. Commit your changes (`git commit -m 'feat: add amazing feature'`)
6. Push to the branch (`git push origin feature/amazing-feature`)
7. Open a Pull Request

---

## 📄 License

This project is licensed under the **MIT License** - see the [LICENSE](LICENSE) file for details.

---

## 🆘 Support

### Getting Help

1. **Check Documentation** - Start with [docs/README.md](docs/README.md)
2. **Review Logs** - Check `storage/logs/laravel.log` for errors
3. **Troubleshooting** - See [Deployment Guide](docs/deployment/DEPLOYMENT.md#troubleshooting)
4. **GitHub Issues** - Report bugs or request features

### Common Issues

| Issue | Solution |
|-------|----------|
| Queue not processing | Ensure `php artisan queue:work` is running |
| Webhooks not working | Verify webhook URLs are publicly accessible |
| Connection test fails | Check API credentials and network connectivity |
| Sync failures | Review field mappings and API rate limits |
| Build errors | Ensure you're using **pnpm** (not npm) |

### Useful Commands

```bash
# Clear all caches
php artisan optimize:clear

# View failed jobs
php artisan queue:failed

# Retry failed jobs
php artisan queue:retry all

# Check application status
php artisan about

# View routes
php artisan route:list
```

---

## 🙏 Acknowledgments

Built with these amazing technologies:

- **[Laravel 12](https://laravel.com)** - The PHP framework for web artisans
- **[Inertia.js](https://inertiajs.com)** - The modern monolith
- **[Vue 3](https://vuejs.org)** - The progressive JavaScript framework
- **[Tailwind CSS 4](https://tailwindcss.com)** - A utility-first CSS framework
- **[pnpm](https://pnpm.io)** - Fast, disk space efficient package manager
- **[Deployer](https://deployer.org)** - Deployment tool for PHP

---

<div align="center">

**[⬆ Back to Top](#-redmine-jira-integration)**

Made with ❤️ for seamless issue tracking integration

</div>
