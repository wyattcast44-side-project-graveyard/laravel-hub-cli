# Laravel Hub CLI

Laravel Hub CLI is a tool for composing new Laravel applications. It offers you a powerful system to quickly design and scaffold applications tailored for you. Highly insprired by the official [Laravel Installer](https://github.com/laravel/installer), [Laravel Blueprint](https://github.com/laravel-shift/blueprint) and Tighten's [Lambo](https://github.com/tightenco/lambo)

## Installation

```bash
composer global require laravel-hub-cli/installer
```

## Basic Usage

The simpliest way to use `laravel-hub` is as a stand in replacement for Laravel's own installer. This functions in exactly the same way as the official Laravel installer.

```bash
laravel-hub new application
```

## Usage

The real power of `laravel-hub` comes with using **compose** files, which are simple yaml files that you use to compose your laravel applications. 

For example the simpliest compose file you could create would look something like this:

```yaml
// app.yaml
name: Test
laravel: default
```

You would compose this application by running the following command:

```bash
laravel-hub compose app.yaml
```

This would install the default branch of Laravel, and the application name would be `Test`.

But `laravel-hub` can do much more, for example we can use `laravel-hub` to: 

- set some env variables
- install our favorite composer packages
- migrate our database

That compose file would look something like this:

```yaml
name: Test Application
laravel: default
env:
    - APP_NAME: My Application
    - APP_URL: http://application.test
    - DB_NAME: application_db
    - DB_USERNAME: root
packages:
    - spatie/once
    - laravel/telescope:
        - php artisan telescope:install
        - php artisan migrate
artisan:
    - migrate
```

## Compose File API

```yaml
name: # The name of your application
laravel: # Which version of laravel to install (default, dev, specific version, ex: 8.0.1)
env: # Allows you to set env values in your env file
    - EnvKey: ValueToSet
packages: # Allows you to add packages via composer, composer will attempt to install all listed
    - vendor/package
    - vendor/package: # You can also list commands to be ran after your package has installed
        - php artisan package:install
packages-dev: # Allows you to add packages via composer, composer will attempt to install all listed in the required-dev sectio
    - vendor/package
artisan: # Allows you to run artisan commands
    - command-name
composer: # Allows you to run composer commands
    - command
```

If you need to reference PHP, for example when running `php artisan`, you should use `@php`, `laravel-hub` will ensure that the user's proper PHP executable path is injected. 

The same applies for composer, npm, and yarn -- all should be referenced with a preceding `@` sign.

## Example Compose File

```yaml
name: Wyatt's Laravel Skeleton
laravel: default
env:
    - APP_NAME: Application
    - APP_URL: http://application.test
    - DB_NAME: application
    - DB_USERNAME: root
packages:
    - laravel/telescope
        - @php artisan telescope:install
        - @php artisan migrate
    - livewire/livewire 
    - laravel-frontend-presets/tall:
        - @php artisan ui tall --auth
        - @npm install
        - @npm dev
```