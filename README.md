# RMSwing DanceEvent API

Based on:
    - Lumen Framework
    - MariaDB or MySQL
    - PHP 7.4 or 8.0

# Requirements
  - MariaDB or MySQL installed and running
  - [Composer](https://getcomposer.org/)
  - PHP 7.4 or 8.0 installed and in Path

# How to get started
  - Have code checked out
  - Insert your DB Settings into .env
  - Navigate into `eventserver-lumen-php` folder
  - `composer install`
  - `php artisan migrate`
  - `php -S localhost:8088 public/index.php` will run a local dev server
  - `tail -f storage/logs/*` will monitor logfiles
  - `php artisan events:import` will import events from calendar sources
  - Have fun!

# Todos
- Create new Tables/Models for Cities, Locations and Creators
- Generate Google Maps Links for all Locations
- Cleanup
- Add Unittests (maybe?)
