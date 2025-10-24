<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Project Setup (Docker) — Quick Start

The following steps guide to run the app locally and hit the APIs.

- Requirements
  - Docker Desktop (with WSL2 on Windows) and Docker Compose
  - Git and a REST client (Postman or curl)

- Clone and boot
  - Clone the repository and open the project directory
  - Start the stack: `docker compose up -d --build`
  - Services: `web` (Nginx on 8080), `app` (PHP-FPM), `db` (Postgres), `redis`

- First-time application setup
  - Copy env if missing: `docker compose exec app cp .env.example .env`
  - Ensure key settings in `.env`:
    - `APP_URL=http://localhost:8080`
    - `DB_CONNECTION=pgsql`, `DB_HOST=db`, `DB_PORT=5432`
    - `DB_DATABASE=laravel`, `DB_USERNAME=laravel`, `DB_PASSWORD=secret`
  - Install Composer dependencies: `docker compose exec app composer install`
  - Generate app key: `docker compose exec app php artisan key:generate`
  - Run migrations only: `docker compose exec app php artisan migrate`

- Verify
  - Open: `http://localhost:8080` (Laravel welcome)
  - List routes: `docker compose exec app php artisan route:list`
  - Adminer (optional): `http://localhost:8081` (System: PostgreSQL, Server: `db`, DB: `laravel`, User/Pass: `laravel/secret`)

## API Usage

- Base URL: `http://localhost:8080/api`
- Create User: `POST /users`
  - Headers: `Accept: application/json`, `Content-Type: application/json`
  - Body: `{"name":"user","email":"user@mail.com","password":"user12345"}`
- Get Users: `GET /users`
  - Query params:
    - `search` (optional; name or email)
    - `page` (default 1)
    - `limit` (default 10, max 100)
    - `sortBy` (`name|email|created_at`; default `created_at`)
    - `currentRole` (`administrator|manager|user`; optional, default `user`)
    - `currentUserId` (optional; used when `currentRole=user`)
  - Response items include `orders_count` and `can_edit`.

## Seed Users with Three Roles

Create three users via API, then run the user seeder.

- Create three role users (administrator, manager, user) using API:
  - Administrator
    - `curl -s -X POST http://localhost:8080/api/users -H "Accept: application/json" -H "Content-Type: application/json" -d '{"name":"Admin","email":"admin@example.com","password":"password123","role":"administrator"}'`
  - Manager
    - `curl -s -X POST http://localhost:8080/api/users -H "Accept: application/json" -H "Content-Type: application/json" -d '{"name":"Manager","email":"manager@example.com","password":"password123","role":"manager"}'`
  - Regular user
    - `curl -s -X POST http://localhost:8080/api/users -H "Accept: application/json" -H "Content-Type: application/json" -d '{"name":"User","email":"user@example.com","password":"password123","role":"user"}'`

- Run the order seeder after creating the users:
  - `docker compose exec app php artisan db:seed --class=OrderSeeder`

Notes
- Passwords are hashed automatically by the User model cast, so plain strings in the commands above will be safely encrypted.
- If you need a clean slate: `docker compose exec app php artisan migrate:fresh --seed`

## Common Commands

- Start services: `docker compose up -d`
- Stop: `docker compose down`
- Tail Nginx logs: `docker compose logs -f web`
- Tail Laravel log: `docker compose exec app tail -f storage/logs/laravel.log`
