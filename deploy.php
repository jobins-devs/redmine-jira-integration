<?php

declare(strict_types=1);

namespace Deployer;

require 'recipe/laravel.php';

// ==============================================
// PROJECT CONFIGURATION
// ==============================================

// Project name
set('application', 'redmine-jira-integration');

// Project repository
set('repository', 'git@github.com:jobins-devs/redmine-jira-integration.git');

set('remote_user', 'jobins-rji');

set('http_user', 'jobins-rji');
set('http_group', 'jobins-rji');

// Number of releases to keep
set('keep_releases', 5);

set('bin/php', 'php8.4');

set('bin/composer', '{{bin/php}} /usr/local/bin/composer');

set('writable_mode', 'chmod');

set('writable_chmod_mode', '0775');

set('writable_use_sudo', false);

// Shared files/dirs between deploys
add('shared_files', [
    '.env',
]);

add('shared_dirs', [
    'storage',
]);

// Writable dirs by web server
add('writable_dirs', [
    'bootstrap/cache',
    'storage',
    'storage/app',
    'storage/app/public',
    'storage/framework',
    'storage/framework/cache',
    'storage/framework/sessions',
    'storage/framework/views',
    'storage/logs',
]);

// ==============================================
// HOSTS CONFIGURATION
// ==============================================

host('production')
    ->setHostname('10.10.0.110')
    ->setPort(22)
    ->setRemoteUser('jobins-rji')
    ->setDeployPath('/home/jobins-rji/htdocs/rji.release.jobins.net')
    ->setLabels([
        'stage' => 'production',
    ])
    ->set('domain', 'rji.release.jobins.net')
    ->set('php_version', '8.4');

// Optional: Staging server
// host('staging')
//     ->setHostname('staging.example.com')
//     ->setPort(22)
//     ->setRemoteUser('jobins-rji')
//     ->setDeployPath('/home/jobins-rji/htdocs/staging.domain.com')
//     ->setLabels([
//         'stage' => 'staging',
//     ])
//     ->set('domain', 'staging.domain.com')
//     ->set('php_version', '8.2');

// ==============================================
// CUSTOM TASKS
// ==============================================

/**
 * Install Node.js dependencies with pnpm
 */
task('pnpm:install', function (): void {
    run('cd {{release_path}} && pnpm install --frozen-lockfile --prefer-offline');
})->desc('Install Node.js dependencies with pnpm');

/**
 * Fix node_modules permissions
 * CloudPanel: Ensure binaries in node_modules have execute permissions
 * This is needed for esbuild and other native binaries
 */
task('pnpm:fix-permissions', function (): void {
    run('cd {{release_path}} && find node_modules -type f -name "*.node" -exec chmod +x {} \;');
    run('cd {{release_path}} && find node_modules/.bin -type f -exec chmod +x {} \; 2>/dev/null || true');
    run('cd {{release_path}} && find node_modules -type f -path "*/bin/*" -exec chmod +x {} \; 2>/dev/null || true');
    info('✓ node_modules permissions fixed');
})->desc('Fix node_modules binary permissions');

/**
 * Build frontend assets with pnpm
 */
task('pnpm:build', function (): void {
    run('cd {{release_path}} && NODE_ENV=production pnpm run build');
})->desc('Build frontend assets with pnpm');

/**
 * Optimize Laravel for production
 */
task('artisan:optimize', function (): void {
    run('cd {{release_path}} && {{bin/php}} artisan optimize');
})->desc('Optimize Laravel application');

/**
 * Cache Laravel configuration
 */
task('artisan:cache:all', function (): void {
    run('cd {{release_path}} && {{bin/php}} artisan config:cache');
    run('cd {{release_path}} && {{bin/php}} artisan route:cache');
    run('cd {{release_path}} && {{bin/php}} artisan view:cache');
    run('cd {{release_path}} && {{bin/php}} artisan event:cache');
})->desc('Cache all Laravel configurations');

/**
 * Restart queue workers
 */
task('artisan:queue:restart', function (): void {
    run('cd {{release_path}} && {{bin/php}} artisan queue:restart');
})->desc('Restart queue workers');

/**
 * Create storage link
 */
task('artisan:storage:link', function (): void {
    run('cd {{release_path}} && {{bin/php}} artisan storage:link');
})->desc('Create storage symbolic link');

/**
 * Run database migrations
 */
task('artisan:migrate:force', function (): void {
    run('cd {{release_path}} && {{bin/php}} artisan migrate --force');
})->desc('Run database migrations');

/**
 * Health check
 */
task('health:check', function (): void {
    $domain = get('domain');
    $deployPath = get('deploy_path');

    info('Waiting for application to start...');
    sleep(5); // Give PHP-FPM and application more time to reload

    // First, check if the application responds locally (bypasses DNS/SSL issues)
    info('Testing application locally on server...');

    // Try HTTP first (without following redirects to see if app responds)
    $localResponse = run("curl -s -o /dev/null -w \"%{http_code}\" --max-time 10 --connect-timeout 5 -H 'Host: {$domain}' http://127.0.0.1/up 2>/dev/null || echo '000'");
    $localResponse = trim($localResponse);

    // Accept 200 (OK) or 301/302 (redirect to HTTPS) as success
    if ($localResponse === '200' || $localResponse === '301' || $localResponse === '302') {
        if ($localResponse === '200') {
            info('✓ Local health check passed (HTTP 200)');
        } else {
            info("✓ Local health check passed (HTTP {$localResponse} - redirect to HTTPS)");
        }

        // Now try external HTTPS check
        info('Testing HTTPS access...');
        $httpsResponse = run("curl -s -o /dev/null -w \"%{http_code}\" --max-time 10 --connect-timeout 5 --insecure https://{$domain}/up 2>/dev/null || echo '000'");
        $httpsResponse = trim($httpsResponse);

        if ($httpsResponse === '200') {
            info('✓ HTTPS health check passed');
        } else {
            warning("⚠ HTTPS check returned: {$httpsResponse}");
            warning('Application is running locally but HTTPS may have issues.');
            warning('This could be due to DNS, SSL certificate, or firewall configuration.');
        }
    } elseif ($localResponse === '000') {
        warning('⚠ Cannot connect to application locally');
        warning('Checking if PHP-FPM is running...');

        // Check PHP-FPM status
        $phpVersion = get('php_version');
        $fpmStatus = run("systemctl is-active php{$phpVersion}-fpm 2>/dev/null || echo 'inactive'");
        $fpmStatus = trim($fpmStatus);

        if ($fpmStatus === 'active') {
            warning('PHP-FPM is running');
        } else {
            warning("PHP-FPM status: {$fpmStatus}");
        }

        warning('Please check:');
        warning('  - Nginx configuration');
        warning('  - PHP-FPM configuration');
        warning('  - Application logs in storage/logs/');
    } else {
        warning("⚠ Local health check returned: {$localResponse}");
        warning('Application may be starting or having issues.');
        warning('Please verify manually at: https://' . $domain);
    }
})->desc('Check application health');

/**
 * Reload PHP-FPM
 */
task('php-fpm:reload', function (): void {
    $phpVersion = get('php_version');
    run("sudo systemctl reload php{$phpVersion}-fpm");
    info("✓ PHP {$phpVersion}-FPM reloaded");
})->desc('Reload PHP-FPM service');

/**
 * Reload Nginx
 */
task('nginx:reload', function (): void {
    run('sudo systemctl reload nginx');
    info('✓ Nginx reloaded');
})->desc('Reload Nginx service');

task('cloudpanel:permissions:reset', function (): void {
    $deployPath = get('deploy_path');
    $currentPath = "{$deployPath}/current";
    run("clpctl system:permissions:reset --directories=770 --files=660 --path={$currentPath}");
    run("chmod -R 775 {$currentPath}/storage {$currentPath}/bootstrap/cache");
    info('✓ CloudPanel permissions reset');
})->desc('Reset permissions using CloudPanel CLI');

// ==============================================
// DEPLOYMENT FLOW
// ==============================================

/**
 * Main deployment task
 */
task('deploy', [
    'deploy:prepare',
    'deploy:vendors',
    'pnpm:install',
    'pnpm:fix-permissions',
    'pnpm:build',
    'artisan:storage:link',
    'artisan:migrate:force',
    'artisan:cache:all',
    'artisan:optimize',
    'deploy:publish',
    'cloudpanel:permissions:reset',
    'artisan:queue:restart',
    'php-fpm:reload',
    'nginx:reload',
    'health:check',
])->desc('Deploy the application');

/**
 * Deployment hooks
 */
after('deploy:failed', 'deploy:unlock');
after('deploy:success', function (): void {
    info('✓ Deployment completed successfully!');
});

// ==============================================
// ROLLBACK CONFIGURATION
// ==============================================

/**
 * Clear caches after rollback
 */
task('rollback:cache:clear', function (): void {
    run('cd {{deploy_path}}/current && {{bin/php}} artisan cache:clear');
    run('cd {{deploy_path}}/current && {{bin/php}} artisan config:clear');
    run('cd {{deploy_path}}/current && {{bin/php}} artisan route:clear');
    run('cd {{deploy_path}}/current && {{bin/php}} artisan view:clear');
})->desc('Clear all caches after rollback');

/**
 * Restart queue after rollback
 */
task('rollback:queue:restart', function (): void {
    run('cd {{deploy_path}}/current && {{bin/php}} artisan queue:restart');
})->desc('Restart queue workers after rollback');

/**
 * Rollback hooks - extend the default rollback task from Laravel recipe
 * The rollback task is already defined in recipe/laravel.php
 */
after('rollback', 'rollback:cache:clear');
after('rollback:cache:clear', 'rollback:queue:restart');
after('rollback:queue:restart', 'php-fpm:reload');

after('rollback', function (): void {
    info('✓ Rollback completed successfully!');
});

// ==============================================
// UTILITY TASKS
// ==============================================

/**
 * Clear all caches
 */
task('cache:clear', function (): void {
    run('cd {{release_path}} && {{bin/php}} artisan cache:clear');
    run('cd {{release_path}} && {{bin/php}} artisan config:clear');
    run('cd {{release_path}} && {{bin/php}} artisan route:clear');
    run('cd {{release_path}} && {{bin/php}} artisan view:clear');
})->desc('Clear all caches');

/**
 * Show deployment logs
 */
task('logs:show', function (): void {
    run('cd {{deploy_path}}/current && tail -n 50 storage/logs/laravel.log');
})->desc('Show recent application logs');

/**
 * Show queue worker status
 */
task('queue:status', function (): void {
    run('sudo systemctl status rdi-queue-worker --no-pager');
})->desc('Show queue worker status');

/**
 * Restart queue worker service
 */
task('queue:restart-service', function (): void {
    run('sudo systemctl restart rdi-queue-worker');
})->desc('Restart queue worker systemd service');

task('cloudpanel:info', function (): void {
    $deployPath = get('deploy_path');
    $domain = get('domain');
    $phpVersion = get('php_version');
    $remoteUser = get('remote_user');

    info("CloudPanel Site Information:");
    info("  Domain: {$domain}");
    info("  Deploy Path: {$deployPath}");
    info("  Site User: {$remoteUser}");
    info("  PHP Version: {$phpVersion}");
    info("  Current Release: " . run("readlink {$deployPath}/current 2>/dev/null || echo 'Not deployed yet'"));
})->desc('Show CloudPanel site information');

task('cloudpanel:php-fpm:status', function (): void {
    $phpVersion = get('php_version');
    run("sudo systemctl status php{$phpVersion}-fpm --no-pager");
})->desc('Check PHP-FPM pool status');

task('cloudpanel:nginx:status', function (): void {
    run('sudo systemctl status nginx --no-pager');
})->desc('Check Nginx status');

task('cloudpanel:nginx:test', function (): void {
    run('sudo nginx -t');
})->desc('Test Nginx configuration');

task('cloudpanel:disk:usage', function (): void {
    $deployPath = get('deploy_path');
    info("Disk usage for deployment:");
    run("du -sh {$deployPath}/*");
})->desc('Show disk usage for deployment');

// ==============================================
// FAILURE HANDLING
// ==============================================

fail('deploy', 'deploy:unlock');
fail('rollback', 'deploy:unlock');
