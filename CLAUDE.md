# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this is

The Themosis framework: the **core APIs** package (`themosis/framework`) of a larger WordPress
development stack. It re-implements a Laravel-style application layer (container, service providers,
routing, views, validation, console) on top of WordPress, using Laravel's `illuminate/*` 8.x
components as building blocks. This repo is the framework only — the runnable app skeleton, theme,
and plugin boilerplates live in separate `themosis/*` repositories (see `.github/CONTRIBUTING.md`).

PHP code is PSR-4 autoloaded as `Themosis\` from `src/`; tests as `Themosis\Tests\` from `tests/`.
Front-end assets compile from `resources/` into `dist/` and ship with the package.

## Commands

PHP:
- `composer test` — run the PHPUnit suite (alias for `./vendor/bin/phpunit`).
- `./vendor/bin/phpunit --filter MethodOrClassName` — run a single test or test class.
- `./vendor/bin/phpunit tests/Forms/FormCreationTest.php` — run one test file.
- `composer fix` — apply PHP-CS-Fixer formatting (config: `.php-cs-fixer.dist.php`).
- `composer update --classmap-authoritative && vendor/bin/phpstan analyze` — static analysis
  (level 2, `src/` only; see `phpstan.neon.dist`).

JavaScript / TypeScript assets:
- `yarn dev` / `yarn watch` / `yarn production` — Laravel Mix (webpack) builds into `dist/js`.
- `yarn test` — Jest (ts-jest); test files match `*.test.*` / `*.spec.*` / `__tests__`.

## Architecture

**`Themosis\Core\Application` (`src/Core/Application.php`)** is the heart: it extends Illuminate's
`Container` and implements Laravel's `Foundation\Application`, `CachesConfiguration`, `CachesRoutes`,
and `HttpKernelInterface` contracts. It is the Laravel application object adapted to run inside a
WordPress request lifecycle. Almost everything is resolved through this container.

**Service providers** wire each subsystem into the container. Every top-level feature directory
under `src/` ships its own provider (e.g. `Asset/AssetServiceProvider`, `Route/RouteServiceProvider`,
`PostType/PostTypeServiceProvider`, `Field/FieldServiceProvider`, `Forms/FormServiceProvider`,
`View/ViewServiceProvider` + `BladeServiceProvider`). `src/Core/Providers/` holds the framework-level
providers; `CoreServiceProvider` is an `AggregateServiceProvider` and registers request macros.
When adding a feature, follow this pattern: a provider that `register()`s bindings and `boot()`s
behavior, often `publishes()`ing assets/views when `runningInConsole()`.

**WordPress integration via the Hookable pattern (`src/Hook/`, `src/Core/HooksRepository.php`)**:
classes extending `Themosis\Hook\Hookable` are registered through the application
(`$app->registerHook(...)`) and bound to WordPress actions/filters. `Hook`, `ActionBuilder`, and
`FilterBuilder` are the OO wrappers around WordPress's `add_action`/`add_filter`. This is the main
bridge between the Laravel-style container world and WordPress's global hook system.

**Subsystems** (each a directory under `src/` with a matching test dir under `tests/`):
- `PostType`, `Taxonomy`, `Metabox`, `Page`, `User` — OO builders for WordPress entities; these
  register custom post types, taxonomies, meta boxes, admin/option pages, and user fields.
- `Field` + `Forms` — field definitions and form building/validation (`FormBuilder`, `FormFactory`,
  data mappers and transformers). `Metabox`, `Page`, and `User` consume `Field` instances.
- `Route` — a custom `Router`/`RouteCollection` over `illuminate/routing`, including `AdminRoute`
  and WordPress-condition route matching (`Route/Matching/`, `Route/Bindings/`).
- `View` — Blade (`BladeServiceProvider`) **and** Twig (`twig/twig`) view engines.
- `Asset`, `Html`, `Ajax`, `Auth` — asset management, HTML/form helpers, AJAX endpoints, auth.

**Console / Artisan**: `src/Core/Console/` plus `ArtisanServiceProvider` / `ConsoleCoreServiceProvider`
provide Laravel-style `artisan` commands adapted for WordPress.

**Global helpers** are loaded via Composer `files` autoload from `src/Core/helpers.php` (path
helpers like `web_path`, `content_path`, `resource_path`, etc.).

## Testing conventions

Tests extend PHPUnit's `TestCase` directly (not a Laravel base TestCase) and **manually construct**
the Illuminate components they need (view factory, validation factory, events dispatcher, etc.) — see
`tests/Page/PageTest.php` and `tests/bootstrap.php`. `tests/bootstrap.php` defines WordPress-style
constants (`WP_CONTENT_DIR`, etc.) and pulls in `tests/functions.php` which stubs WordPress functions,
so tests run without a live WordPress install. `tests/deprecated` is excluded from the suite
(`phpunit.xml.dist`). Per the contribution guide, **every PR must include unit tests**.

## Branching

Current default branch is `3.0`. Bug fixes and minor backwards-compatible features target the latest
stable branch; major features target the upcoming-release branch. Do not target `main` for bug fixes
unless they fix features that exist only in the upcoming release.