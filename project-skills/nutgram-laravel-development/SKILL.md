---
name: nutgram-laravel-development
description: Builds and maintains Telegram bots with nutgram/laravel in this Laravel project. Use when working on Nutgram configuration, routes/telegram.php, Telegram commands, handlers, conversations, polling, webhooks, message persistence, group chat bot behavior, or Nutgram tests.
---

# Nutgram Laravel Development

## Documentation First

Before changing Nutgram code, check the version-specific Nutgram docs for the feature in use:

- Laravel integration: `https://nutgram.dev/docs/configuration/laravel`
- Handlers: `https://nutgram.dev/docs/usage/handlers`
- Conversations: `https://nutgram.dev/docs/usage/conversations`
- Testing: `https://nutgram.dev/docs/testing/introduction`

Also use the project skills for Laravel best practices, Pest testing, and Laravel AI when bot behavior touches persistence, tests, queues, or AI agents.

## Project Conventions

- Keep `routes/telegram.php` thin. It should register handlers and commands, not contain business logic.
- Put Nutgram command classes in `app/Telegram/Commands`.
- Put generic update/message handlers in `app/Telegram/Handlers`.
- Keep `app/Actions/Telegram` for synchronous operations that must return a result to the Nutgram handler, primarily `StoreTelegramMessage`.
- Put post-persistence side effects in `app/Listeners/Telegram` behind a domain event such as `TelegramMessageCreated`; listeners can later implement `ShouldQueue` without changing handlers.
- Put shared Telegram support/query helpers in `app/Telegram/Support`.
- Put slow AI work in `app/Jobs/Telegram` so polling and future webhooks return quickly.
- Put Laravel AI agents in `app/Ai/Agents`.
- Persist Telegram data with Eloquent models in `app/Models` and factories in `database/factories`.

## Registration Pattern

Prefer class-based handlers over closures:

```php
use App\Telegram\Commands\StartCommand;
use App\Telegram\Handlers\MessageHandler;

$bot->registerCommand(StartCommand::class);
$bot->onMessage(MessageHandler::class);
```

Use `registerCommand()` for commands that should expose metadata and be registered with Telegram. Use `onCommand()` only when command parameters or route constraints make it clearer.

## Local Polling

During local development, run:

```bash
php artisan nutgram:run
```

Use `php artisan nutgram:listen` when automatic reloads are helpful.

## Future Webhooks

For production, add an API route that calls `Nutgram $bot => $bot->run()`, keep it outside auth and CSRF protection, then register it with:

```bash
php artisan nutgram:hook:set https://your-domain.com/api/telegram/webhook
```

When safe mode is enabled in production, set the webhook through Nutgram commands so incoming updates include the expected secret validation.

## Group Chat Bot Rules

- Store every relevant group message first, then dispatch a `TelegramMessageCreated` event.
- Keep immediate word-trigger replies in listeners separate from slow AI analysis.
- Use thresholds for AI summary generation: daily windows and message-count windows are separate triggers.
- For recent-message analysis, select messages by `chat_id` and `sent_at`, then reverse into chronological order before prompting AI.
- Store raw Telegram payloads only as JSON context, not as the primary querying surface.

## Laravel AI Usage

- Use dedicated agents for each behavior: daily summaries, recent-message roast/analysis, and question answers.
- Default local provider should be Ollama via `config/ai.php`.
- Never call real AI providers in tests. Use Laravel AI fakes and assertions.
- Queue AI work unless the feature explicitly requires a short synchronous reply.

## Testing

Use Pest. Resolve the Laravel-bound fake Nutgram instance or `Nutgram::fake()` and drive updates with `hearText(...)->reply()`.

Test at least:

- Commands reply as expected.
- Incoming group messages create/update Telegram chat and user records.
- Message text is persisted with Telegram message id and sent timestamp.
- Word triggers send immediate replies.
- Threshold checks dispatch summary jobs.
- AI agents are faked and never hit real providers.

Run targeted tests:

```bash
php artisan test --compact tests/Feature/Telegram tests/Unit/Telegram
```
