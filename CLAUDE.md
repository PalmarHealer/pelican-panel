# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Overview

Pelican Panel is an open-source game server management panel built with Laravel 12 and Filament 4. It provides a user-friendly web interface for deploying, configuring, and managing game servers using Docker containers. The panel consists of separate interfaces for regular users, server management, and administrators.

## Development Setup

### Initial Setup

This is a **Laravel + Filament PHP project** (not a Node.js project). The frontend is built with Vite but the application itself runs on PHP.

```bash
# 1. Install PHP dependencies
composer install

# 2. Install Node.js dependencies (for building frontend assets)
npm install

# 3. Build frontend assets (REQUIRED before running the dev server)
npm run build

# 4. Set up environment file (if not already done)
cp .env.example .env
php artisan key:generate

# 5. Configure database in .env, then run migrations
php artisan migrate

# 6. Seed the database (optional)
php artisan db:seed
```

### Running the Development Server

**IMPORTANT**: You must build assets with `npm run build` before running the PHP dev server, otherwise you'll get a 500 error about missing Vite manifest.

```bash
# Option 1: Build assets once, then run PHP server
npm run build
php artisan serve

# Option 2: Run Vite dev server + PHP server simultaneously (recommended for development)
# Terminal 1:
npm run dev

# Terminal 2:
php artisan serve
```

When using `npm run dev`, Vite will watch for file changes and hot-reload your assets. The Laravel app at `http://localhost:8000` will automatically use the Vite dev server.

### Common Commands

```bash
# Code formatting (Laravel Pint)
composer pint

# Static analysis (PHPStan/Larastan)
composer phpstan

# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Unit
php artisan test --testsuite=Integration

# Run single test file
php artisan test tests/Unit/SomeTest.php

# Database operations
php artisan migrate
php artisan migrate:fresh --seed
php artisan migrate:rollback

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear

# Rebuild frontend assets for production
npm run build

# Format frontend code (Prettier)
npm run prettier
```

The user can execute this command to "fix" and clear a bunch of stuff at once:
```bash
sudo systemctl restart nginx && sudo systemctl restart php8.4-fpm && php artisan config:clear && sudo chown -R www-data:www-data /var/www/pelican && sudo chmod -R u+wr /var/www/pelican && sudo php artisan cache:clear
```

### Testing

Tests are organized into three main suites:
- `tests/Unit/` - Unit tests for isolated components
- `tests/Integration/` - Integration tests for API and service layer
- `tests/Filament/` - Tests for Filament admin panel components

The project uses Pest as the testing framework.

## Architecture

### Multi-Panel Architecture

Pelican uses Filament's multi-panel feature to provide three distinct interfaces:

1. **App Panel** (`app/Filament/App/`) - Main user interface for viewing and managing servers
2. **Server Panel** (`app/Filament/Server/`) - Server-specific management interface (tenant-aware)
3. **Admin Panel** (`app/Filament/Admin/`) - Administrative interface for managing users, nodes, eggs, etc.

Each panel is registered via its own PanelProvider:
- `app/Providers/Filament/AppPanelProvider.php`
- `app/Providers/Filament/ServerPanelProvider.php`
- `app/Providers/Filament/AdminPanelProvider.php`
- `app/Providers/Filament/PanelProvider.php` (base provider with shared configuration)

### Permission System

Pelican implements a **dual permission system**:

#### 1. Admin/Role-Based Permissions (Spatie Permission)

Used for panel-wide admin access, managed via `app/Models/Role.php`:

- Based on `spatie/laravel-permission` package
- Permission format: `{prefix} {model}` (e.g., "viewList server", "create user")
- Prefixes: viewList, view, create, update, delete
- Root Admin role bypasses all permission checks

#### 2. Subuser Permissions

Used for server-level access control, managed via `app/Models/Permission.php`:

- Controls what subusers can do within a specific server
- Format: `{category}.{action}` (e.g., "control.console", "file.read")
- Categories: control, user, file, backup, allocation, startup, database, schedule, settings, activity

### Core Architecture Patterns

**Service Layer**: Business logic in `app/Services/`:
- `Services/Acl/` - Access control
- `Services/Allocations/` - IP/port management
- `Services/Backups/` - Backup operations
- `Services/Databases/` - Database management
- `Services/Servers/` - Server operations
- `Services/Subusers/` - Subuser management

**API Controllers**: Organized by audience in `app/Http/Controllers/Api/`:
- `Application/` - Admin API for managing panel resources
- `Client/` - User-facing API for server management
- `Remote/` - API for Wings daemon to communicate with panel

**Models**: All models in `app/Models/` with relationships and business logic

**Events**: Domain events in `app/Events/` (e.g., `Server\Installed`, `ActivityLogged`)

**Helpers**: Global helper functions in `app/helpers.php` (e.g., `user()`, `convert_bytes_to_readable()`)

### Frontend Architecture

- **Vite** for asset bundling
- **Tailwind CSS 4** for styling
- **Livewire** for reactive components (`app/Livewire/`)
- **Filament** for admin UI components
- **Xterm.js** for terminal emulation (with WebGL renderer)
- Views in `resources/views/` using Blade templates

### Key Models

- `User` - Panel users with admin roles
- `Server` - Game servers (tenant in server panel)
- `Node` - Physical/virtual machines running Wings
- `Egg` - Server type configurations (similar to Docker images)
- `Allocation` - IP:Port assignments
- `Backup` - Server backups
- `Database` - Server databases
- `Schedule` / `Task` - Scheduled tasks
- `Role` - Admin roles with permissions
- `Permission` - Subuser permissions for servers

## Extension System

Pelican Panel includes a powerful extension system that allows developers to add custom functionality without modifying core files.

### Quick Overview

- Extensions live in `/extensions/<extension-id>/` directory
- Single integration point: `app/Providers/AppServiceProvider.php`
- Database-driven enable/disable mechanism
- Support for themes, language packs, and functional extensions

### Extension Types

1. **Functional Extensions** - Add pages, resources, widgets, permissions, navigation items
2. **Themes** - Custom CSS/styling via render hooks
3. **Language Packs** - Add new languages or override existing translations

### Getting Started

For detailed documentation on creating extensions, see:

- **[Extension Development Guide](docs/extensions/README.md)** - Complete guide for building extensions
- **[Theme Development](docs/extensions/creating-themes.md)** - How to create custom themes
- **[Language Pack Development](docs/extensions/creating-language-packs.md)** - How to create translation packs
- **[API Reference](docs/extensions/api-reference.md)** - Extension API documentation

## Configuration

- Laravel config in `config/`
- Panel-specific config in `config/panel.php`
- Environment variables in `.env` (see `.env.example`)
- Filament configuration via PanelProviders

## Important Files

- `app/helpers.php` - Global helper functions (auto-loaded via composer.json)
- `app/Providers/AppServiceProvider.php` - Core service registration, HTTP macros, health checks, gate policies
- `app/Providers/Filament/PanelProvider.php` - Base Filament configuration
- `bootstrap/app.php` - Application bootstrapping
- `routes/` - Route definitions (base.php, auth.php, api-*.php, docs.php)

## Coding Conventions

### Import Management

**IMPORTANT**: Do NOT manage imports manually. Always use simple class names in code:
- Write `Action::make()` instead of `\Filament\Actions\Action::make()`
- Write `BulkAction::make()` instead of `\Filament\Tables\Actions\BulkAction::make()`
- PhpStorm handles imports automatically
- Only address import issues if the developer explicitly asks

Example of correct usage:
```php
// ✅ CORRECT - Use simple class names
Action::make('scan')
    ->label('Scan')
    ->action(fn() => $this->scan());

BulkAction::make('enable')
    ->action(fn(Collection $records) => $this->enableAll($records));
```

Example of incorrect usage:
```php
// ❌ INCORRECT - Don't use fully qualified class names
\Filament\Actions\Action::make('scan')
\Filament\Tables\Actions\BulkAction::make('enable')
```

## Notes

- The panel uses Docker via Wings daemon for server isolation
- Server console uses WebSocket connections (websocket.connect permission)
- Activity logging is comprehensive via `ActivityLogged` events
- Supports multiple OAuth providers (Discord, Steam, Authentik) via Socialite
- Multi-factor authentication supported (TOTP app and email)
- Uses Sanctum for API token management (`ApiKey` model)
