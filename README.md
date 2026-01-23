# Laravel Nova Turbo

[![Latest Version on Packagist](https://img.shields.io/packagist/v/shahabzebare/laravel-nova-turbo.svg?style=flat-square)](https://packagist.org/packages/shahabzebare/laravel-nova-turbo)
[![Tests](https://github.com/shahabzebare/laravel-nova-turbo/actions/workflows/tests.yml/badge.svg)](https://github.com/shahabzebare/laravel-nova-turbo/actions/workflows/tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/shahabzebare/laravel-nova-turbo.svg?style=flat-square)](https://packagist.org/packages/shahabzebare/laravel-nova-turbo)
[![License](https://img.shields.io/packagist/l/shahabzebare/laravel-nova-turbo.svg?style=flat-square)](https://packagist.org/packages/shahabzebare/laravel-nova-turbo)

🚀 **Turbocharge Laravel Nova** by lazy loading resources.

If you have 50+ resources, Nova can become slow because it loads and runs authorization checks for ALL resources on every page load. This package fixes that by only loading the resources needed for the current page.

## The Problem

Nova's default behavior on every page load:
- Registers **all** resources (e.g., 100 resources)
- Runs `authorizedToCreate()` for **each** resource
- Generates metadata for **all** resources

With Nova Turbo:
- Only loads **1-5** resources per page (current resource + relationships)
- Dramatically improves page load times

## Installation

```bash
composer require shahabzebare/laravel-nova-turbo
```

## Setup

### Step 1: Add the trait to your NovaServiceProvider

```php
<?php

namespace App\Providers;

use Laravel\Nova\NovaApplicationServiceProvider;
use Shahabzebare\NovaTurbo\Traits\TurboLoadsResources;

class NovaServiceProvider extends NovaApplicationServiceProvider
{
    use TurboLoadsResources;

    // ... rest of your provider
}
```

### Step 2: Generate the cache

```bash
php artisan nova:turbo-cache
```

This creates a cache file at `bootstrap/cache/nova-turbo.php`.

### Step 3 (Optional): Publish config

```bash
php artisan vendor:publish --tag=nova-turbo-config
```

## Commands

```bash
# Generate/refresh cache
php artisan nova:turbo-cache

# Clear cache
php artisan nova:turbo-cache --clear
```

## Configuration

```php
// config/nova-turbo.php

return [
    // Skip lazy loading in local environment for development
    'auto_refresh_in_dev' => true,

    // Paths to scan for Nova resources
    'resource_paths' => [
        app_path('Nova'),
    ],
];
```

## External Resources

If you have resources outside `app/Nova` (e.g., in modules), register them:

```php
// In your AppServiceProvider or a module service provider
use Shahabzebare\NovaTurbo\NovaTurbo;

public function boot()
{
    NovaTurbo::resources([
        \Modules\Order\Nova\Order::class,
        \Modules\Customer\Nova\Customer::class,
    ]);
}
```

Then re-run `php artisan nova:turbo-cache`.

## Deployment

Add to your deployment script:

```bash
php artisan nova:turbo-cache
```

Consider adding it after `config:cache`:

```bash
php artisan config:cache
php artisan route:cache
php artisan nova:turbo-cache
```

## How It Works

1. The artisan command scans all resources and their relationship fields
2. It builds a dependency map and caches it as a PHP array file
3. On page load, only the needed resources are registered
4. The cached metadata is sent to the frontend to prevent JavaScript errors

## Development Mode

By default, lazy loading is **disabled** in local environment (`auto_refresh_in_dev = true`). This means you can add/modify resources without running the cache command.

For production-like testing locally:
```bash
NOVA_TURBO_AUTO_REFRESH=false php artisan serve
```

## License

MIT License. See [LICENSE](LICENSE) for details.

## Credits

- [Shahab Zebari](https://github.com/shahabzebare)
