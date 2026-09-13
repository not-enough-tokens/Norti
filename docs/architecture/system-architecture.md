# System Architecture

## 1. Architectural Philosophy

The system follows a layered architecture with explicit separation between domain logic, application services, infrastructure integrations, and AI interoperability.

The main architectural rule is:

> AI and MCP provide controlled access to application capabilities; they do not replace the application's business logic.

The system should remain functional and testable independently of the AI agent.

---

## 2. High-Level Architecture

```text
┌──────────────────────────────────────────────────────┐
│                    AI Agent                          │
│                                                      │
│  Understands user intent and selects available tools │
└────────────────────────┬─────────────────────────────┘
                         │
                         │ MCP
                         ↓
┌──────────────────────────────────────────────────────┐
│                    MCP Layer                         │
│                                                      │
│  Tool definitions, schemas, input/output contracts   │
└────────────────────────┬─────────────────────────────┘
                         │
                         ↓
┌──────────────────────────────────────────────────────┐
│                 Application Layer                    │
│                                                      │
│  FinancialProfileService                             │
│  FinancialGoalService                                │
│  PortfolioService                                    │
│  RiskAnalysisService                                 │
│  InvestmentSimulationService                         │
│  FinancialEducationService                           │
└───────────────┬───────────────────────┬──────────────┘
                ↓                       ↓
┌─────────────────────────┐   ┌────────────────────────┐
│      Domain/Data        │   │     Infrastructure     │
│                         │   │                        │
│ Models / Database       │   │ Market Data Providers  │
└─────────────────────────┘   └───────────┬────────────┘
                                          │
                                          ↓
                                  ┌─────────────────┐
                                  │   Twelve Data   │
                                  └─────────────────┘
```

Market data is the one capability without a dedicated Application Service: MCP tools and `MarketDataController` depend directly on `MarketDataProviderContract` (see §3.4). There is no `MarketDataService`.

---

## 3. Application Layers

### 3.1 MCP Layer

The MCP layer exposes application capabilities as tools.

Responsibilities:

- define tool names;
- define input schemas;
- validate tool inputs;
- authenticate/authorize requests where applicable;
- invoke application services;
- return structured responses;
- translate application errors into appropriate tool errors.

The MCP layer must not contain financial business logic.

Example:

```text
MCP Tool
   ↓
PortfolioService
   ↓
Portfolio Model / Repository
   ↓
Database
```

Incorrect architecture:

```text
MCP Tool
   ↓
Database query
   ↓
Financial calculations
   ↓
Response
```

---

### 3.2 Application Services

Services contain reusable business behavior.

Examples:

- `FinancialProfileService`
- `FinancialGoalService`
- `PortfolioService`
- `RiskAnalysisService`
- `InvestmentSimulationService`
- `FinancialEducationService`

Market data is the exception: MCP tools and `MarketDataController` depend on `MarketDataProviderContract` directly (§3.4) rather than through a dedicated service, since Twelve Data's provider layer already returns application-ready data with no further business logic to apply.

Services should be callable independently from MCP.

This allows the same business capability to be reused by:

- MCP tools;
- HTTP controllers;
- console commands;
- automated tests;
- future application interfaces.

---

### 3.3 Domain/Data Layer

Laravel Eloquent models represent persistent financial entities.

Core entities include:

- `User`
- `FinancialProfile`
- `FinancialGoal`
- `Portfolio`
- `Holding`
- `Asset`
- `EducationalTopic`
- `EducationalProgress`

Models are responsible for representing data and relationships.

Complex business workflows should not be implemented directly inside MCP tools or controllers.

---

### 3.4 Market Data Layer

External financial APIs must not be called directly from financial business logic.

The application defines an internal contract:

```php
interface MarketDataProviderContract
{
    public function quote(string $symbol, array $parameters = []): array;

    public function profile(string $symbol, array $parameters = []): array;

    public function timeSeries(
        string $symbol,
        string $interval,
        array $parameters = []
    ): array;
}
```

The initial (and only) implementation is:

```text
MarketDataProviderContract
        ↑
TwelveDataMarketDataProvider
        ↓
TwelveDataClient
        ↓
Twelve Data API
```

`MarketDataProviderContract` defines what the application needs.

`TwelveDataMarketDataProvider` adapts Twelve Data's API into that contract: `quote()`/`profile()` pass the response through close to unmodified (cached 60s to respect Twelve Data's free-tier rate limit), while `timeSeries()` normalizes each entry into a typed price bar. It also wraps `TwelveDataException` into a provider-agnostic `MarketDataUnavailableException`.

`TwelveDataClient` is responsible for HTTP communication with Twelve Data.

---

## 4. Provider Architecture

The provider architecture follows an adapter-style approach.

```text
                    ┌───────────────────────────┐
                    │ MarketDataProviderContract│
                    │        interface          │
                    └─────────────┬─────────────┘
                                  │
                   ┌──────────────┴──────────────┐
                   ↓                             ↓
      ┌─────────────────────────────┐  ┌────────────────────┐
      │ TwelveDataMarketDataProvider│  │  FutureProvider    │
      └──────────────┬──────────────┘  └────────────────────┘
                      ↓
          ┌────────────────────┐
          │ TwelveDataClient   │
          └─────────┬──────────┘
                    ↓
          ┌────────────────────┐
          │ Twelve Data API    │
          └────────────────────┘
```

This prevents Twelve Data-specific response structures from leaking into the rest of the application.

A future provider should be implementable without modifying financial services.

---

## 5. Dependency Direction

Dependencies should point toward abstractions where appropriate.

For example:

```text
MCP Tool / MarketDataController
       ↓
MarketDataProviderContract
       ↑
TwelveDataMarketDataProvider
       ↓
TwelveDataClient
       ↓
External API
```

MCP tools and `MarketDataController` should not depend directly on `TwelveDataClient`.

This allows tests to replace the real provider with:

```text
MockMarketDataProvider
```

without making network requests.

---

## 6. AI Architecture

The AI agent is treated as a consumer of application capabilities.

The expected flow is:

```text
User
 ↓
AI Agent
 ↓
Tool discovery
 ↓
Tool selection
 ↓
MCP Tool
 ↓
Application Service
 ↓
Data / Provider
 ↓
Structured result
 ↓
AI interpretation
 ↓
User
```

The AI should receive structured data whenever possible.

For example:

```json
{
    "symbol": "AAPL",
    "price": 123.45,
    "currency": "USD",
    "exchange": "NASDAQ"
}
```

The AI can explain the result, but the backend remains responsible for retrieving and calculating the underlying data.

---

## 7. Deterministic vs. Generative Responsibilities

The system should distinguish between deterministic operations and generative interpretation.

### Deterministic

The backend should handle:

- arithmetic calculations;
- portfolio allocation;
- percentage calculations;
- simulation formulas;
- statistical calculations;
- data validation;
- authorization;
- market-data retrieval;
- persistence.

### Generative

The AI may handle:

- natural-language interpretation;
- explanation;
- educational framing;
- summarization;
- tool selection;
- contextual presentation.

This separation reduces the risk of inconsistent financial calculations.

---

## 8. Security Boundaries

Sensitive operations should remain behind explicit backend controls.

The AI agent must not have direct access to:

- database credentials;
- API keys;
- `.env` values;
- arbitrary SQL;
- internal filesystem operations;
- unrestricted HTTP requests.

External API credentials are stored in environment configuration and accessed only by the relevant infrastructure layer.

High-risk financial actions are excluded from the MVP.

---

## 9. Error Handling

Errors should be handled at the layer where they originate and translated at architectural boundaries.

Example:

```text
Twelve Data API
      ↓
TwelveDataClient
      ↓
TwelveDataException
      ↓
MarketDataUnavailableException (TwelveDataMarketDataProvider)
      ↓
MCP Tool / MarketDataController
      ↓
Structured MCP error / HTTP error response
```

External provider errors should not expose secrets or unnecessary implementation details to the AI agent or end user.

---

## 10. Testing Strategy

Testing should exist at multiple levels.

### Unit Tests

Test individual services and providers using mocks where appropriate.

### Integration Tests

Verify communication between application components.

Examples:

```text
MarketDataController
        ↓
MockMarketDataProvider
```

and:

```text
TwelveDataMarketDataProvider
        ↓
TwelveDataClient
```

### MCP Tests

Verify:

- tool registration;
- input validation;
- tool execution;
- expected output structure;
- error handling.

The architecture should allow most tests to run without external API calls.