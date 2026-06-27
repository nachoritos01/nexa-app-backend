# Copilot Code Review Instructions

## Project

Laravel 12 + Livewire 4.1 + Filament 3.3 + PostgreSQL 15 monolith for DTF t-shirt ordering.

## Rules to enforce

- Never use `env()` outside `config/*.php` files — always use `config()` in models, controllers, and services
- Seeders must be idempotent: use `firstOrCreate` or `updateOrCreate`, never raw `create`
- User model has `'password' => 'hashed'` cast — do not use `bcrypt()` when creating via Eloquent
- Use `$fillable` explicitly on models, never `$guarded = []`
- Validate with Form Request classes, not inline `$request->validate()`
- No raw SQL queries without parameter binding (SQL injection risk)
- Escape Blade output with `{{ }}`, only use `{!! !!}` for trusted HTML
- Livewire: no heavy logic in `render()`, use `#[Computed]` properties
- No `env()` calls that would break under `config:cache`

## Code style

- PHP 8.5, strict types preferred
- PHPStan level 5 compliance
- Follow existing patterns in the codebase
- Prefer editing existing files over creating new ones

## Commits

- Conventional commits in English (feat/fix/docs/refactor/test/build)
- No "Co-Authored-By" or "Generated with" lines
