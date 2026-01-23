# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-01-24

### 🚀 Initial Release

First public release of Laravel Nova Turbo - a performance optimization package for Laravel Nova.

### Added

- **Lazy Loading Resources**: Only load resources needed for the current page instead of all resources
- **Resource Relationship Mapping**: Automatically detect and cache resource relationships (BelongsTo, HasMany, MorphTo, etc.)
- **PHP Array File Caching**: Optimal caching using PHP array files with opcache support
- **Cache Versioning**: Automatic cache invalidation when package is updated
- **Auto-regeneration on Deploy**: Cache automatically regenerates when `cache:clear` is run
- **External Resource Support**: Register resources from modules or packages via `NovaTurbo::resources()`
- **Nova 4 & 5 Compatibility**: Works with both Laravel Nova 4.x and 5.x
- **Laravel 10, 11, 12 Support**: Compatible with latest Laravel versions
- **Development Mode**: Auto-refresh option to disable lazy loading in local environment

### Configuration Options

- `auto_refresh_in_dev` - Skip lazy loading in local environment
- `resource_paths` - Paths to scan for Nova resources
- `regenerate_on_cache_clear` - Auto-regenerate cache on `cache:clear`

### Commands

- `php artisan nova:turbo-cache` - Generate cache
- `php artisan nova:turbo-cache --clear` - Clear cache

### Performance

- **59% faster** server response times on 100+ resource installations
- **95% reduction** in resources loaded per page (from ~100 to 1-5)

---

[1.0.0]: https://github.com/shahabzebare/laravel-nova-turbo/releases/tag/v1.0.0
