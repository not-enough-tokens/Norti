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
│  MarketDataService                                   │
└───────────────┬───────────────────────┬──────────────┘
                │                       │
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
- `MarketDataService`

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
interface MarketDataProvider
{
    public function getQuote(string $symbol): array;

    public function getHistoricalPrices(
        string $symbol,
        string $interval = '1day'
    ): array;

    public function getAssetProfile(string $symbol): array;
}
```

The initial implementation is:

```text
MarketDataProvider
        ↑
TwelveDataProvider
        ↓
TwelveDataClient
        ↓
Twelve Data API
```

`MarketDataProvider` defines what the application needs.

`TwelveDataProvider` translates Twelve Data's API response into the application's internal format.

`TwelveDataClient` is responsible for HTTP communication with Twelve Data.

---

## 4. Provider Architecture

The provider architecture follows an adapter-style approach.

```text
                         ┌─────────────────────┐
                         │ MarketDataProvider  │
                         │      interface     │
                         └──────────┬──────────┘
                                    │
                     ┌──────────────┴──────────────┐
                     ↓                             ↓
          ┌────────────────────┐        ┌────────────────────┐
          │ TwelveDataProvider │        │  FutureProvider    │
          └─────────┬──────────┘        └────────────────────┘
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
MarketDataService
       ↓
MarketDataProvider
       ↑
TwelveDataProvider
       ↓
TwelveDataClient
       ↓
External API
```

`MarketDataService` should not depend directly on `TwelveDataClient`.

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
MarketDataService
      ↓
MCP Tool
      ↓
Structured MCP error
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
MarketDataService
        ↓
MockMarketDataProvider
```

and:

```text
TwelveDataProvider
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