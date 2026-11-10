# Shop Assistant — AI Agents in CakePHP 5

## 1. What this is

A small CakePHP 5 application where a logged-in sales user chats with Claude about shop data: customers, orders, products and sales. Claude answers by calling a set of read-only **tools**. Each tool is a thin wrapper around a Table finder, and every finder goes through the user's Authorization scope. The model never sees data the user is not allowed to see. This repository is the companion code of the article *AI Agents in CakePHP 5, Part I: Tool Calling* ([LINK: article]).

## 2. Requirements

You can run the project in two ways. Both need an Anthropic API key ([console.anthropic.com](https://console.anthropic.com/)).

**Option A: with DDEV (recommended)**

- [DDEV](https://ddev.com/get-started/) (PHP, Composer and PostgreSQL run inside DDEV; nothing to install on the host).
- A Docker provider supported by DDEV (Docker Desktop, OrbStack, Colima, Docker CE, ...).

**Option B: without DDEV (`bin/cake server`)**

- PHP 8.2 or newer with the `intl`, `mbstring` and `pdo_pgsql` extensions.
- [Composer](https://getcomposer.org/) 2.
- PostgreSQL (tested with 18) and an empty database for the app.

## 3. Installation

```bash
git clone git@github.com:ACampanario/advent2026.git
cd advent2026
git checkout part-1-tool-calling
```

### Option A: with DDEV

```bash
ddev start
ddev composer install
```

`ddev start` creates `config/.env` from `config/.env.example` with the DDEV database settings. Do not copy `config/.env.example` over it. Edit `config/.env` and set your key:

```bash
export ANTHROPIC_API_KEY="sk-ant-..."
```

Create the schema and load the demo data:

```bash
ddev exec bin/cake migrations migrate
ddev exec bin/cake seeds run -q
```

Open <https://cakephp-ai-agents.ddev.site> and pick a profile.

### Option B: without DDEV (`bin/cake server`)

```bash
composer install
cp config/.env.example config/.env
```

Edit `config/.env` and set at least these values:

```bash
export APP_NAME="cakephp-ai-agents"
export APP_FULL_BASE_URL="http://localhost:8765"
export SECURITY_SALT="<a long random string>"
export DATABASE_URL="postgres://<user>:<password>@localhost:5432/<database>?encoding=utf8"
export ANTHROPIC_API_KEY="sk-ant-..."
```

- `APP_FULL_BASE_URL` must match the URL you open in the browser. With the default `https://example.com`, redirects go to that domain.
- `SECURITY_SALT` must be a real value. The `__SALT__` placeholder in the example file overrides the salt that `composer install` writes to `config/app_local.php`. You can generate one with `php -r 'echo bin2hex(random_bytes(32)), PHP_EOL;'`.
- `DATABASE_URL` is commented out in the example file. Without it, the app uses the connection in `config/app_local.php` (`localhost`, user `my_app`).

Create the schema, load the demo data and start the built-in server:

```bash
bin/cake migrations migrate
bin/cake seeds run -q
bin/cake server -p 8765
```

Open <http://localhost:8765> and pick a profile.

> `bin/cake server` is a single PHP process: it reads `config/.env` once. Restart it after you change `config/.env`.

> With cakephp/migrations 5.2 seeds have their own command: `bin/cake seeds run`. The older `bin/cake migrations seed` no longer exists.

## 4. Configuration

All settings live in `config/.env`. They are read once in `config/app.php` (keys `Anthropic` and `Agent`) and used only in the service factories of `Application::services()`.

| Variable | Default | Purpose |
|---|---|---|
| `ANTHROPIC_API_KEY` | (empty) | Your Anthropic API key. Required. |
| `ANTHROPIC_MODEL` | `claude-haiku-5-5` | Model used by the agent. |
| `ANTHROPIC_EFFORT` | `low` | Effort level sent to the API (`low`, `medium`, `high`). |
| `ANTHROPIC_MAX_TOKENS` | `4096` | Max output tokens per model call. |
| `AGENT_MAX_ITERATIONS` | `8` | Max model calls per user message. When reached, the UI shows a notice. |
| `AGENT_MAX_ROWS` | `20` | Max rows a tool returns (the result says `truncated` and `total`). |
| `ANTHROPIC_PRICE_INPUT` | `0.10` | USD per million input tokens. |
| `ANTHROPIC_PRICE_OUTPUT` | `0.50` | USD per million output tokens. |
| `ANTHROPIC_PRICE_CACHE_READ` | `0.01` | USD per million cache-read tokens. |
| `ANTHROPIC_PRICE_CACHE_WRITE` | `0.125` | USD per million cache-write tokens. |
| `DEBUG_KIT_IGNORE_AUTHORIZATION` | `true` | Lets the DebugKit toolbar skip the authorization check. Development only. |

**Overriding with DDEV.** Variables set in DDEV's `web_environment` take precedence over `config/.env` (the `.env` loader does not overwrite existing variables):

```bash
ddev config --web-environment-add="ANTHROPIC_MODEL=claude-sonnet-5-5"
ddev restart
```

Do not set `APP_NAME` there: when `APP_NAME` already exists, `config/bootstrap.php` skips `config/.env` entirely.

**Switching model.** Change `ANTHROPIC_MODEL` and the four `ANTHROPIC_PRICE_*` values to match it. Haiku is the cheapest and is enough for this demo; Sonnet and Opus give better answers at a higher cost.

**Pricing note.** The cost shown in the UI is an estimate computed from the `ANTHROPIC_PRICE_*` values, using the standard rate card for requests up to 100K input tokens. Check the current prices on the Anthropic pricing page.

## 5. Demo users

| Profile | Role | Sees |
|---|---|---|
| `admin` | admin | All customers and orders. |
| `sales1` | sales | Only their own customers and those customers' orders. |
| `sales2` | sales | Only their own customers and those customers' orders. |

The login page is a **profile selector without passwords**. This is a shortcut for the demo only: never use it in a real application.

## 6. How it works

```
Browser ──POST message──▶ ConversationsController::message()
                               │
                               ▼
                         AgentService::run(conversation, userText, identity, listener)
                               │  loop (max N iterations)
                               ├──▶ ClaudeGateway::createMessage(params)  ──▶  Anthropic API
                               │◀── assistant content blocks
                               ├──▶ ToolRegistry::execute(name, input, identity)
                               │        └──▶ Tool → Table finder → applyScope()
                               ├──▶ ConversationsTable / MessagesTable / ToolCallsTable (persist)
                               └──▶ AgentEventListener (no-op in Part I; SSE in Part II)
```

The agent loop is written by hand (not the SDK tool runner): while the model stops with `tool_use`, the tools run and all their results go back in one message. All AI code lives in `src/Service/Ai`.

**Tools**

| Tool | Input | Finder |
|---|---|---|
| `find_customers` | `search?`, `country?` | `CustomersTable::findForAgent()` |
| `get_customer` | `customer_id` | `CustomersTable::findForAgent()` |
| `find_orders` | `customer_id?`, `status?`, `date_from?`, `date_to?` | `OrdersTable::findForAgent()` |
| `get_order` | `order_id?` or `reference?` | `OrdersTable::findForAgent()` |
| `sales_summary` | `date_from`, `date_to`, `group_by` (`month`, `customer`, `product`, `category`) | `OrdersTable::findSalesSummary()` |

**Where authorization happens.** Not in the prompt. Each tool builds its query from a finder and applies the user's scope with `$identity->applyScope('index', $query)`, which runs `CustomersTablePolicy::scopeIndex()` or `OrdersTablePolicy::scopeIndex()`. A record outside the scope returns `{"found": false}`, exactly like a record that does not exist. Conversations are protected by `ConversationPolicy` and `ConversationsTablePolicy`.

**Details panel.** Under each answer, the "Details" link shows every model call (stop reason, tokens, cache, cost) and every tool call (input JSON and the result sent to the model).

## 7. Quality

```bash
ddev composer cs-check   # CakePHP coding standard
ddev composer cs-fix
ddev composer stan       # PHPStan level 6 on src/
```

Without DDEV, drop the `ddev` prefix: `composer cs-check`, `composer cs-fix`, `composer stan`.

## 8. Resetting data

Seed state is stored in the `cake_seeds` table, which a rollback does not touch, so reset it as well:

```bash
ddev exec bin/cake migrations rollback -t 0
ddev exec bin/cake migrations migrate
ddev exec bin/cake seeds reset      # asks for confirmation (y)
ddev exec bin/cake seeds run -q
```

Without DDEV, run the same `bin/cake` commands without `ddev exec`.

## 9. Branches

- `part-1-tool-calling`: this article (tool calling).
- `part-2-streaming-sse`: the follow-up (streaming the answer with Server-Sent Events).

## 10. Security notes

- Tools are read-only. Each one calls a single Table finder.
- Data access is limited by Authorization Policies applied inside each tool, not by the system prompt.
- The model's Markdown is rendered on the server with `league/commonmark` (`html_input: strip`, `allow_unsafe_links: false`). Everything else in the views is escaped with `h()`.
- Chat messages are rate limited: 10 per minute per user, on `POST /conversations/message`.
- Never commit `config/.env`: it contains your API key.
- The password-less profile selector is for the demo only.
- One order of `sales2` contains a note with a **deliberate prompt-injection attempt**. It is test data for the demo: do not remove it.

## 11. Known limitations

- **No HTTP timeout towards the API.** The SDK's `timeout` option is advisory and the default Guzzle transport has no timeout. If the API hangs, the request waits until nginx returns a 504 after 10 minutes. A fix is to pass a Guzzle `transporter` with a `timeout` in the `ClaudeGateway` factory.
- **The rate limit is not strict.** It uses the core default `sliding_window` strategy of `RateLimitMiddleware`, which lets more requests through later in each window. Use `RateLimitMiddleware::STRATEGY_FIXED_WINDOW` for a strict 10 per minute.
- **The rate limit answers with a redirect.** The chat form is a plain POST, so a blocked request is redirected back with a flash message ("Too many messages (HTTP 429) ..."). The browser does not receive a 429 status.
