# Banorte MCP — Development Roadmap

## 1. Purpose

This document defines the development roadmap for Banorte MCP.

The project is organized into milestones that progressively build the prototype from its technical foundation to the final product demonstration.

The milestones are designed to minimize coupling between components and allow individual capabilities to be developed and tested before full integration.

---

## 2. Milestone Overview

| Milestone | Name | Main Question | Status |
|---|---|---|---|
| M0 | Project Foundation | Where does the system live? | Completed |
| M1 | Financial Domain | What exists? | Completed |
| M2 | Financial Services | What can the system do? | Completed |
| M3 | MCP Server | How can external agents use it? | Completed |
| M4 | External Market Data | Where does market data come from? | Completed |
| M5 | AI / Agents | Who uses these capabilities? | Completed |
| M6 | Financial Education | How does the system create user value? | In Progress |
| M7 | Security & Hardening | How is the system protected? | Completed |
| M8 | Product & Demo | How is the complete solution demonstrated? | Planned |

Milestone statuses are expected to change throughout development.

---

## 3. M0 — Project Foundation

### Objective

Establish the technical foundation of Banorte MCP.

### Main Components

- Laravel application;
- PHP environment;
- relational database;
- Git repository;
- authentication infrastructure;
- Laravel Sanctum;
- Laravel MCP;
- environment configuration;
- initial project structure;
- GitHub Actions CI (Pint + PHPUnit on every pull request and push to `master`).

### Expected Result

A functional Laravel project capable of supporting the remaining milestones.

### Status

**Completed**

---

## 4. M1 — Financial Domain

### Objective

Define the entities and relationships required to represent the financial domain.

### Core Entities

- `User`
- `FinancialProfile`
- `FinancialGoal` — no `priority` column, unlike the original plan; just `name`, `target_amount`, `current_amount`, `target_day`, `goal_type`. Its factory was missing until a recent audit fix (see M2) — until then the model had zero test coverage.
- `Portfolio`
- `Holding`
- `Asset`
- `EducationalTopic`
- `EducationalProgress` — implemented as the `educational_topic_user` pivot (`completed_at` timestamp) rather than a dedicated model; `EducationalTopic::users()` exposes it via `belongsToMany()->withPivot('completed_at')`.

All entities above exist with migrations and (except `EducationalTopic`, which uses raw `::create()` in tests) factories.

### Expected Result

The application has a coherent financial domain represented through models, migrations, relationships, and appropriate validation.

### Status

**Completed**

---

## 5. M2 — Financial Services

### Objective

Implement the application's core business capabilities independently from MCP and external interfaces.

### Services

- `ProfileService` (`FinancialProfileService`'s actual name) — `createOrUpdate()`, `monthlySavingsCapacity()`. Wrapped by `EloquentFinancialProfileService` (`app/Services/Financial/`) for the M3 contract, which buckets the savings rate into `low`/`moderate`/`high` instead of exposing exact income/expenses to the model by default.
- `FinancialGoalService` — never built as a named class. `InvestmentSimulationService::projectForGoal()`/`evaluateGoal()` cover the same need (project a goal, detect if it's overdue, verify the goal and profile belong to the same user) and have test coverage, but **nothing in the app calls them** — no MCP tool, route, or view. [ADR 006](../decisions/006-get-financial-goals-tool.md) proposes a 7th, read-only MCP tool for this; pending a decision, since the 6-tool catalog was a closed decision and the new tool would touch exact amounts (see the sensitive-data policy in `CLAUDE.md`).
- `PortfolioService` — portfolio creation (`createForUser()`, allocates holdings per the user's risk profile) and aggregate stats (`totalInvested()`, `currentDistribution()`). Wrapped by `EloquentPortfolioService` for the M3 read model, which enriches holdings with live market prices — cash (`efectivo`) is valued at face value instead of queried from Twelve Data (it isn't a quotable instrument).
- `RiskAnalysisService` — asset allocation and expected-return recommendations per risk profile. `risk_tolerance` is a free string column with no DB-level enum; callers must normalize it through `suggestRiskProfile()` rather than pass it raw, or an unrecognized value throws.
- `InvestmentSimulationService` — month-by-month compound-interest projection (`project()`), plus the goal-projection methods above. Wrapped by `InvestmentSimulationServiceAdapter` for the M3 contract.
- `FinancialEducationService` — `getLearningPath()` returns every `EducationalTopic` with an `is_completed` flag for the given user. No difficulty progression or recommendation logic beyond that flag (see M6).

Market data ended up without a dedicated service: MCP tools and `MarketDataController` depend on `MarketDataProviderContract` (M4) directly — see ADR 003.

A recurring theme across the M3-contract adapters: they wrap the real M2 service rather than reimplementing it (e.g. `RiskAnalysisServiceAdapter` calls into `RiskAnalysisService`, `InvestmentSimulationServiceAdapter` into `InvestmentSimulationService`) — a deliberate pattern to avoid the kind of duplicate-implementation problem M4 had before its consolidation (ADR 003).

Several defects in this layer (asset-type vocabulary split between English config keys and Spanish DB values, an unrecognized `risk_tolerance` crashing `analyze_portfolio`, an overdue goal reporting a valid projection, a missing `FinancialGoal` factory, cross-user goal/profile mixing, cash being queried as a market symbol) were found and fixed — see `docs/development/auditoria.md` for the full list with verification evidence.

### Architectural Rule

Business logic belongs in services.

Controllers, MCP tools, and AI agents should not become the primary location for financial business rules.

### Expected Result

Core financial operations can be executed independently of MCP.

### Status

**Completed** — except financial-goal exposure (see `FinancialGoalService` above and ADR 006), which is scoped as a follow-up, not a blocker.

---

## 6. M3 — MCP Server

### Objective

Expose selected application capabilities through the Model Context Protocol.

### Implemented Tools

- `get_financial_profile`
- `get_portfolio`
- `analyze_portfolio`
- `get_asset_information`
- `get_market_snapshot`
- `simulate_investment`

Registered in `App\Mcp\Servers\BanorteServer`, exposed at `/mcp/banorte` (`routes/ai.php`), protected by Passport (`auth:api` + scopes `mcp:read`/`mcp:simulate`) and `throttle:mcp`. `get_market_snapshot` and `get_asset_information` cover the market-data capability described in M4 — the earlier example name `get_market_quote` was dropped as redundant during the M4 consolidation (see ADR 003's update note).

### Architectural Flow

```text
MCP Tool
    ↓
Application Service (or MarketDataProviderContract directly, for market data)
    ↓
Domain / Provider
    ↓
Structured Result
```

### Expected Result

An MCP client or AI agent can discover and invoke selected Banorte MCP capabilities through standardized tools.

### Status

**Completed**

---

## 7. M4 — External Market Data

### Objective

Integrate external market information into Banorte MCP.

### Initial Provider

Twelve Data.

### Architecture

```text
MCP Tool / MarketDataController
        ↓
MarketDataProviderContract
        ↑
TwelveDataMarketDataProvider
        ↓
TwelveDataClient
        ↓
Twelve Data API
```

### Capabilities

- current asset quotes (`quote()`, cached 60s);
- historical prices (`timeSeries()`, normalized into typed bars);
- basic asset profiles (`profile()`, cached 60s);
- provider-specific error handling (`TwelveDataException` → `MarketDataUnavailableException`).

`quote()`/`profile()` currently pass Twelve Data's response through close to unmodified rather than normalizing every field; only `timeSeries()` is normalized. See ADR 003 for the full rationale, including a note on the two parallel implementations that were consolidated into this one.

### Testing

`MockMarketDataProvider` (`app/Services/MarketData/`) implements `MarketDataProviderContract` with fixed data, for deterministic tests that avoid unnecessary external API calls.

### Expected Result

MCP tools and `MarketDataController` can request market data through the internal `MarketDataProviderContract` without depending directly on Twelve Data.

### Status

**Completed.** Verified against the real Supabase Postgres database (2026-09-13): `migrate` runs clean, Passport issues tokens (required creating the personal-access client with `passport:client --personal`; `passport:install` republishes duplicate OAuth migrations if the project already has its own, so avoid it), and `audit_logs.input` is confirmed real `jsonb`. Connected via the Transaction pooler (port 6543) rather than the Session pooler (5432) recommended in `docs/deployment/supabase-setup.md` — worked for this check, but watch for "prepared statement already exists" errors under concurrency. The test suite itself was not run against this database (it uses `RefreshDatabase`, which would wipe the real data already there).

---

## 8. M5 — AI / Agents

### Objective

Connect an AI agent to the Banorte MCP server and demonstrate tool discovery and invocation.

### Expected Flow

```text
User
 ↓
AI Agent
 ↓
MCP Tool Discovery
 ↓
Tool Selection
 ↓
MCP Tool
 ↓
Application Service
 ↓
Structured Result
 ↓
AI Interpretation
 ↓
User
```

### AI Responsibilities

The AI agent should:

- interpret user intent;
- determine which capability is relevant;
- discover available tools;
- invoke appropriate tools;
- explain returned information;
- provide educational context when appropriate.

### Backend Responsibilities

The backend remains responsible for:

- data retrieval;
- calculations;
- business rules;
- validation;
- authorization;
- provider communication;
- structured results.

### Expected Result

A user can interact naturally with the AI agent while the agent accesses controlled financial capabilities through MCP.

### Incremental Approach

M5 is being built as a vertical slice, verified step by step before adding any LLM/agent behavior:

1. **MCP client connectivity (done)** — `php artisan mcp:client-tools` connects to `/mcp/banorte` as a plain MCP client (`Laravel\Mcp\Client`, part of the already-installed `laravel/mcp` package — no new dependency), authenticates with a Passport token, and calls `list_tools()`. Verified end-to-end against a real running server: all 6 tools returned with name, description, and inputSchema.
2. **Manual `call_tool()` (done)** — `php artisan mcp:client-call <tool> --arguments=<json>` calls a specific tool with real arguments and prints the `ToolResult` (text + structuredContent). Verified end-to-end with `mcp:client-call get_market_snapshot --arguments='{"symbols":["AAPL"]}'` against the real Twelve Data API (not mocked): the request went through the unmodified chain `MCP Tool → MarketDataProviderContract → TwelveDataMarketDataProvider → TwelveDataClient → Twelve Data`, returning a real AAPL quote via `Response::structured()`.
3. **Real AI agent (done, provider-decoupled, fully verified)** — `php artisan mcp:demo-agent "<pregunta>"` runs the full expected flow with a real agent built on the [Laravel AI SDK](https://laravel.com/docs/ai-sdk) (`laravel/ai`), not a hand-rolled provider client. `App\Ai\Agents\BanorteMcpAgent` implements `Agent` + `HasTools`; its `tools()` spreads the MCP client's tool collection directly and the SDK wraps/translates each tool for whichever provider is active — no provider-specific code in the agent. Default provider is OpenAI (`#[Provider(Lab::OpenAI)]`), overridable per run with `--provider`/`--model`. See [ADR 007](../decisions/007-ai-agent-provider-decoupling.md) for the full rationale (this replaced an earlier Anthropic-only implementation). **Verified end-to-end with a real OpenAI key**: `mcp:demo-agent "¿Cuál es la cotización actual de AAPL?"` connected to `/mcp/banorte`, the model chose a market-data tool on its own (no manual tool selection), the call went through `MCP Tool → MarketDataProviderContract → TwelveDataMarketDataProvider → TwelveDataClient → Twelve Data`, and the agent answered in natural language with the real quote (price, change, day range) matching the raw data seen in step 2. Requires `php artisan serve` running and `OPENAI_API_KEY` (or another configured provider) set.

All three steps of the vertical slice are now verified end-to-end against real services (Twelve Data, OpenAI) — no mocks. Remaining M5-adjacent work (a chat UI, streaming responses, persisted conversations) is out of this milestone's core scope; see M8.

### Status

**Completed** and merged into `master`.

---

## 9. M6 — Financial Education

### Objective

Extend financial intelligence into personalized financial education.

The system should not only provide financial information or analysis, but also help users understand the concepts behind that information.

### Capabilities

- educational topics ✅ — `EducationalTopic` model, seeded content, `GET /education` and `GET /education/{educationalTopic}` (auth-protected, plain unstyled Blade views).
- learning paths ⚠️ partial — `FinancialEducationService::getLearningPath()` returns every topic ordered by `id` with an `is_completed` flag; it's a fixed linear list, not an adaptive path.
- educational progress ⚠️ partial — readable (`educational_topic_user.completed_at`, surfaced as `is_completed`) but **not writable anywhere in the app**: no route, controller action, or MCP tool sets `completed_at`. Current tests write it directly via `$user->educationalTopics()->attach($topic, ['completed_at' => now()])`, which isn't reachable from the actual product.
- personalized topic recommendations ❌ — not implemented; recommendation today is "not yet completed", not based on the user's financial situation.
- contextual explanations ❌ — `FinancialEducationIntegrationService::getFinancialContext()` (profile + goals + portfolios) exists but is **not called anywhere**. It used to be built and discarded unused on every `/education` load (three wasted queries, plus a `FinancialProfile` with exact amounts sitting in the Blade view's scope); that dead call was removed. The service itself was kept because connecting financial context to education is M6's actual objective, just not built yet.
- identification of relevant knowledge gaps ❌ — not implemented.

A separate, unmerged branch (`feature/education-mcp`) adds four MCP tools (`get_educational_topic`, `get_learning_path`, `get_learning_progress`, `mark_topic_completed`) that would close the progress-writing gap and expose this milestone through MCP like the other five milestones — evaluate merging it (after rebasing onto current `master`) before treating M6 as blocked on new work.

### Example Flow

```text
User Financial Context
        +
Portfolio / Analysis
        ↓
Relevant Concept
        ↓
Educational Topic
        ↓
Explanation / Learning Path
```

### Product Principle

The objective is not to create an AI that simply tells the user what financial decision to make.

The system should help the user understand the information and make more informed decisions.

### Expected Result

The prototype demonstrates a clear connection between financial intelligence and financial education.

### Status

**In Progress** — topics, a basic learning path, and auth-protected views exist; the financial-intelligence connection and progress-writing are not built yet (see Capabilities above).

---

## 10. M7 — Security & Hardening

### Objective

Strengthen the prototype's security boundaries before final integration and demonstration.

Security is considered throughout development. M7 consolidates and verifies the security mechanisms implemented across previous milestones.

### Areas

- authentication;
- authorization;
- user data isolation;
- input validation;
- API-key protection;
- environment-secret management;
- rate limiting;
- MCP tool permissions;
- audit logging where appropriate;
- error handling;
- prevention of sensitive information leakage.

### Security Principle

AI-generated input must never be considered trusted input.

The backend must independently enforce authorization and validation.

### Explicitly Excluded from MVP

- real money transfers;
- real trade execution;
- custody;
- production KYC/AML infrastructure;
- production regulatory compliance.

### Expected Result

The prototype has clear and demonstrable security boundaries appropriate for the hackathon scope.

### Status

**Completed.** Audit logging (`audit_logs` table, logged per MCP tool call) and rate limiting (`throttle:mcp`, `throttle:market-data`) are active. Gap: rate limiting is verified at the config/unit level but lacks an HTTP-level test confirming `throttle:mcp` actually returns 429.

---

## 11. M8 — Product & Demo

### Objective

Integrate the completed capabilities into a coherent product experience and demonstrate the complete architecture.

### Demonstration Flow

The preferred demonstration should communicate the project's complete value chain:

```text
User Question
      ↓
AI Agent
      ↓
MCP Tool Discovery
      ↓
Financial Capability
      ↓
Market / Financial Data
      ↓
Analysis
      ↓
Personalized Education
      ↓
Informed Decision Support
```

### Demo Requirements

The final prototype should demonstrate:

1. An AI agent receiving a natural-language financial question.
2. Discovery of an appropriate MCP capability.
3. Invocation of the MCP tool.
4. Execution of backend business logic.
5. Retrieval or use of relevant financial data.
6. A structured result returned to the AI.
7. An understandable response to the user.
8. Educational context where appropriate.

### Product Principle

The demonstration should make MCP's value visible.

The objective is not merely to show that an MCP server exists, but to demonstrate how standardized interoperability enables an AI agent to use controlled financial capabilities.

### Expected Result

A coherent end-to-end prototype suitable for the hackathon presentation.

### Status

**Planned**

---

## 12. Team Workstreams

Development is divided into four primary workstreams.

### Workstream A — Financial Core

Responsible for:

- M1 Financial Domain;
- M2 Financial Services.

Primary focus:

- models;
- relationships;
- financial business logic;
- portfolio and goal operations.

---

### Workstream B — MCP / Backend Infrastructure

Responsible for:

- M0 Project Foundation;
- M3 MCP Server;
- M7 Security & Hardening.

Primary focus:

- MCP infrastructure;
- tool definitions;
- backend integration;
- authentication and authorization;
- security boundaries.

---

### Workstream C — Data & AI

Responsible for:

- M4 External Market Data;
- M5 AI / Agents.

Primary focus:

- Twelve Data integration;
- provider abstraction;
- market-data normalization;
- AI-agent integration;
- MCP client interaction.

---

### Workstream D — Education & Product

Responsible for:

- M6 Financial Education;
- M8 Product & Demo.

Primary focus:

- educational content and flows;
- personalization;
- user experience;
- product narrative;
- final demonstration.

---

## 13. Integration Strategy

Milestones should not be treated as completely isolated phases.

The team should integrate incrementally.

Recommended integration sequence:

```text
M0
 ↓
M1
 ↓
M2
 ├───────────────┐
 ↓               ↓
M3              M4
 ↓               ↓
 └───────┬───────┘
         ↓
        M5
         ↓
        M6
         ↓
        M7
         ↓
        M8
```

M3 and M4 can progress in parallel once the project foundation and relevant contracts are available.

M5 depends on a usable MCP interface and at least one meaningful capability exposed through it.

M6 can begin independently but should eventually consume financial context generated by previous milestones.

M7 is transversal and should be addressed throughout development rather than postponed entirely until the end.

M8 is the final integration and presentation milestone.

---

## 14. Definition of Done

A milestone should not be considered complete solely because its code exists.

Whenever practical, completion should include:

- implementation;
- basic automated tests;
- error handling;
- integration with relevant previous milestones;
- documentation of important decisions;
- verification through a reproducible example or demonstration.

A milestone may be marked complete when its required capability works reliably within the defined MVP scope.

---

## 15. Current Development Priority

M0–M4 and M7 are complete (see the Milestone Overview table and `CLAUDE.md`'s ownership section for the authoritative, frequently-updated status). The M4 vertical slice that used to be tracked here — provider contract → client integration → quote → tests → historical data → asset profiles → mock provider → MCP integration — is done; see ADR 003 for how it ended up consolidated with the M3 MCP work.

The current priority is AI-agent integration (M5) and financial education (M6), building on the completed MCP server and market-data layers.

---

## 16. Scope Control

The hackathon timeline requires strict scope control.

When considering a new feature, the team should ask:

1. Does it directly support the core financial-intelligence or financial-education flow?
2. Is it required for the MVP?
3. Can it be implemented without destabilizing existing architecture?
4. Does it provide meaningful value in the final demonstration?

Features that do not satisfy these criteria should generally be deferred.

The architecture should remain extensible, but the MVP should remain intentionally limited.