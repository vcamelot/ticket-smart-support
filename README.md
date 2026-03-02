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
6. (Optional) If you hit permission issues for `storage/` or `bootstrap/cache`:
   ```bash
   docker compose exec app chmod -R 775 storage bootstrap/cache
   ```
7. Check if the app is running by calling the API health endpoint:
   ```bash
   curl -s -H "Accept: application/json" http://localhost:8080/api/health
   # Expected output: {"status":"ok"}
   ```

### Clean reset (optional)
If you want to start from scratch (drops DB volume):
```bash
docker compose down -v
docker compose up -d --build
docker compose exec app php artisan migrate
```

## Architecture & Async Enrichment

Ticket creation is designed to be fast and non-blocking. When a ticket is created via the API, it is immediately persisted to the database and a `201 Created` response is returned. The AI fields (category, sentiment, urgency, suggested_reply) will initially be `null`.

At the same time, an asynchronous job (`EnrichTicketWithAi`) is dispatched to the Laravel database queue. The `worker` container automatically picks up this job, sends the ticket data to the configured AI provider, and updates the ticket in the background.

## API Usage

### 1. Create a Ticket
```bash
curl -s -X POST http://localhost:8080/api/tickets \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"title":"Cannot login","description":"I forgot my password and cannot reset it. I am very frustrated!"}'
```
*Note: The initial response will show `null` for the AI-enriched fields.*

### 2. View the Ticket (Wait 1-2 seconds)
```bash
# Replace '1' with the ID returned from the POST request
curl -s -H "Accept: application/json" http://localhost:8080/api/tickets/1
```
*Note: The AI fields should now be populated.*

## Queue Worker

The queue worker runs automatically in a dedicated Docker container (`smart_support_worker`). 
To view the worker logs and see jobs being processed:
```bash
docker compose logs worker -f
```

## AI Providers

The system is designed with a provider-agnostic AI contract (`AiClientInterface`).

- **Fake Provider (Default):** Uses deterministic keyword matching to simulate AI behavior. This is the default setting out-of-the-box so the app can be run without any external API keys.
- **Gemini Provider:** Integrates with Google's Gemini API.

To switch to Gemini, update your `.env` file:
```ini
AI_PROVIDER=gemini
GEMINI_API_KEY=your_actual_api_key_here
GEMINI_MODEL=gemini-1.5-pro
```
*After changing the `.env`, clear caches: `docker compose exec app php artisan optimize:clear`*

## Prompt Strategy

The application uses a strict and robust prompt strategy to ensure reliable data extraction from the LLM:
- **System Instructions:** The LLM is instructed to act as a "Helpful Customer Support Agent" and strictly output **only** valid JSON. No markdown formatting or explanations are permitted.
- **Strict JSON Schema:** The prompt explicitly defines the expected JSON structure and limits the allowed enum values for `category`, `sentiment`, and `urgency`.
- **Prompt Injection Resistance:** The system prompt explicitly commands the LLM to ignore any instructions inside the user's ticket text that attempt to override the system instructions.
- **Robust JSON Extraction & DTO Validation:** Even if the LLM returns extra text (e.g. markdown/code fences), the `GeminiAiClient` strips surrounding text to extract the JSON. The payload is then strictly validated through the `AiTicketAnalysisDTO` layer before touching the database.

## Running Tests

To execute the automated test suite (which uses the Fake provider by default to avoid external API calls during testing):
```bash
docker compose exec app php artisan test
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

## Running Artisan Commands
Always run artisan commands inside the container:
```bash
docker compose exec app php artisan list
