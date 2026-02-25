# Smart Support Ticket System

A prototype Helpdesk backend built with Laravel 11 and PHP 8.4, running entirely in Docker.

## Setup

1. Copy `.env.example` to `.env`
2. Start the Docker containers:
   ```bash
   docker compose up -d --build
   ```
3. Install Laravel dependencies inside the container:
   ```bash
   docker compose exec app composer install
   ```
4. Generate the application key:
   ```bash
   docker compose exec app php artisan key:generate
   ```
5. Run the database migrations:
   ```bash
   docker compose exec app php artisan migrate
   ```
6. Set up directory permissions securely:
   ```bash
   docker compose exec app chmod -R 775 storage bootstrap/cache
   ```
7. Check if the app is running by calling the API health endpoint:
   ```bash
   curl http://localhost:8080/api/health
   # Expected output: {"status":"ok"}
   ```

## Optional: connect to MySQL using DBeaver / GUI client (dev only)

By default, MySQL is not exposed to the host to avoid port conflicts and keep the setup minimal.  
If you want to connect from a GUI client (e.g. DBeaver), create a local override file (do **not** commit it):

Create `docker-compose.override.yml`:
```yaml
services:
  mysql:
    ports:
      - "3307:3306"
```
Restart containers:
```bash
docker compose up -d
```
Then connect with:
- Host: `127.0.0.1`
- Port: `3307`
- Database: `smart_support`
- User: `user`
- Password: `secret`

## Prompt Strategy
*(To be filled in later steps)*

## Running Artisan Commands
Always run artisan commands inside the container:
```bash
docker compose exec app php artisan migrate
```
