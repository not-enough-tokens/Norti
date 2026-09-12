# ADR 004: Use MCP as the AI Interoperability Layer

## Status

Accepted

## Context

The project is designed around the Model Context Protocol (MCP) to allow AI agents to discover and invoke financial capabilities.

There is a risk of placing business logic directly inside MCP tools because tools are the most visible interface between the AI agent and the application.

Doing so would make the system difficult to test, reuse, and maintain.

## Decision

MCP is treated as an interoperability and interface layer, not as the business-logic layer.

MCP tools expose application capabilities and delegate execution to application services.

The intended flow is:

```text
AI Agent
    ↓
MCP Tool
    ↓
Application Service
    ↓
Domain / Provider
    ↓
Result
```

For example:

```text
AI Agent
    ↓
analyze_portfolio
    ↓
PortfolioService
    ↓
RiskAnalysisService
    ↓
Portfolio / MarketDataProvider
    ↓
Structured result
```

## MCP Tool Responsibilities

MCP tools may:

- define the tool exposed to the AI;
- define input parameters;
- validate inputs;
- enforce access controls;
- invoke application services;
- format results for MCP;
- translate errors into appropriate MCP responses.

MCP tools must not contain core financial business logic.

## Service Responsibilities

Application services are responsible for:

- financial calculations;
- business rules;
- portfolio analysis;
- investment simulations;
- financial-profile logic;
- financial-goal logic;
- educational logic;
- orchestration of domain capabilities.

Services must remain usable without MCP.

## AI Responsibilities

The AI agent may:

- interpret natural-language requests;
- identify user intent;
- discover available tools;
- select appropriate tools;
- provide explanations;
- present structured results in natural language.

The AI agent should not bypass application services or access internal infrastructure directly.

## Security Boundary

The MCP interface is a controlled boundary.

AI-generated input must not be considered trusted input.

The backend remains responsible for:

- authentication;
- authorization;
- validation;
- data isolation;
- permission checks;
- rate limiting where required;
- protection of sensitive operations.

High-risk financial operations such as real-money transfers and trade execution are outside the MVP.

## Consequences

### Positive

- Clear separation of AI interoperability and business logic.
- Services remain reusable outside MCP.
- Easier testing.
- Easier security auditing.
- MCP tools remain small and understandable.
- AI capabilities can evolve without rewriting financial logic.

### Negative

- Introduces additional architectural layers.
- Requires explicit service/tool contracts.
- Some simple operations may require more code than a direct database-access tool.

## Alternatives Considered

### Business Logic Directly Inside MCP Tools

Rejected because it couples financial logic to the MCP interface.

### AI Access to the Database

Rejected because it creates an unnecessary security and architectural boundary violation.

### AI Calling External Financial APIs Directly

Rejected because credentials, provider-specific behavior, validation, and data normalization must remain controlled by the backend.

## Architectural Constraint

MCP tools must delegate business operations to application services.

Do not implement substantial financial business logic directly inside an MCP tool merely to reduce the number of files or layers.