# RMSwing DanceEvent API

Based on:
    - Lumen Framework
    - MariaDB or MySQL
    - PHP 7.4 (older might work, use at your own risk, slippery when wet)

# How to get started
  - Have code checked out
  - Insert your DB Settings into .env
  - `composer install`
  - `php artisan migrate`
  - `php -S localhost:8088 public/index.php` will run a local dev server
  - `tail -f storage/logs/*` will monitor logfiles
  - Have fun!

# Todos
- Create new Tables/Models for Cities, Locations and Creators
- Generate Google Maps Links for all Locations
- Cleanup
- Add Unittests (maybe?)