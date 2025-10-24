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

// Deployment user
set('remote_user', 'deployer');

// Number of releases to keep
set('keep_releases', 5);

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
    ->setRemoteUser('deployer')
    ->setDeployPath('/home/jobins-rji/htdocs/rji.release.jobins.net')
    ->setLabels([
        'stage' => 'production',
    ]);

// Optional: Staging server
// host('staging')
//     ->setHostname('staging.example.com')
//     ->setPort(22)
//     ->setRemoteUser('deployer')
//     ->setDeployPath('/var/www/rdi-staging')
//     ->setLabels([
//         'stage' => 'staging',
//     ]);

// ==============================================
// CUSTOM TASKS
// ==============================================

/**
 * Build frontend assets with pnpm
 */
task('pnpm:install', function (): void {
    run('cd {{release_path}} && pnpm install --frozen-lockfile --prefer-offline');
})->desc('Install dependencies with pnpm');

task('pnpm:build', function (): void {
    run('cd {{release_path}} && pnpm run build');
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
    $response = run('curl -s -o /dev/null -w "%{http_code}" {{hostname}}/up');

    if ($response !== '200') {
        warning("Health check failed with status code: $response");
    } else {
        info('✓ Health check passed');
    }
})->desc('Check application health');

/**
 * Reload PHP-FPM
 */
task('php-fpm:reload', function (): void {
    run('sudo systemctl reload php8.2-fpm');
})->desc('Reload PHP-FPM service');

/**
 * Reload Nginx
 */
task('nginx:reload', function (): void {
    run('sudo systemctl reload nginx');
})->desc('Reload Nginx service');

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
    'pnpm:build',
    'artisan:storage:link',
    'artisan:migrate:force',
    'artisan:cache:all',
    'artisan:optimize',
    'deploy:publish',
    'artisan:queue:restart',
    'php-fpm:reload',
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
 * Rollback task
 */
task('rollback', [
    'rollback:prepare',
    'artisan:cache:clear',
    'artisan:queue:restart',
    'php-fpm:reload',
    'health:check',
])->desc('Rollback to previous release');

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

// ==============================================
// FAILURE HANDLING
// ==============================================

fail('deploy', 'deploy:unlock');
fail('rollback', 'deploy:unlock');
