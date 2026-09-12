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
| M1 | Financial Domain | What exists? | In Progress |
| M2 | Financial Services | What can the system do? | In Progress |
| M3 | MCP Server | How can external agents use it? | Planned |
| M4 | External Market Data | Where does market data come from? | In Progress |
| M5 | AI / Agents | Who uses these capabilities? | Planned |
| M6 | Financial Education | How does the system create user value? | Planned |
| M7 | Security & Hardening | How is the system protected? | Planned |
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
- `FinancialGoal`
- `Portfolio`
- `Holding`
- `Asset`
- `EducationalTopic`
- `EducationalProgress`

### Expected Result

The application has a coherent financial domain represented through models, migrations, relationships, and appropriate validation.

### Status

**In Progress**

---

## 5. M2 — Financial Services

### Objective

Implement the application's core business capabilities independently from MCP and external interfaces.

### Planned Services

- `FinancialProfileService`
- `FinancialGoalService`
- `PortfolioService`
- `RiskAnalysisService`
- `InvestmentSimulationService`
- `FinancialEducationService`
- `MarketDataService`

### Architectural Rule

Business logic belongs in services.

Controllers, MCP tools, and AI agents should not become the primary location for financial business rules.

### Expected Result

Core financial operations can be executed independently of MCP.

### Status

**In Progress**

---

## 6. M3 — MCP Server

### Objective

Expose selected application capabilities through the Model Context Protocol.

### Example Tools

- `get_financial_profile`
- `get_financial_goals`
- `get_portfolio`
- `analyze_portfolio`
- `simulate_investment`
- `get_asset_information`
- `get_market_quote`

The final tool set may change according to the capabilities implemented during the hackathon.

### Architectural Flow

```text
MCP Tool
    ↓
Application Service
    ↓
Domain / Provider
    ↓
Structured Result
```

### Expected Result

An MCP client or AI agent can discover and invoke selected Banorte MCP capabilities through standardized tools.

### Status

**Planned**

---

## 7. M4 — External Market Data

### Objective

Integrate external market information into Banorte MCP.

### Initial Provider

Twelve Data.

### Architecture

```text
MarketDataProvider
        ↑
TwelveDataProvider
        ↓
TwelveDataClient
        ↓
Twelve Data API
```

### Planned Capabilities

- current asset quotes;
- historical prices;
- basic asset profiles;
- normalization of provider responses;
- provider-specific error handling.

### Testing

A `MockMarketDataProvider` should be used for deterministic tests and to avoid unnecessary external API calls.

### Expected Result

Financial services can request market data through the internal `MarketDataProvider` contract without depending directly on Twelve Data.

### Status

**In Progress**

### Current Focus

The initial implementation should establish the following vertical slice:

```text
TwelveDataClient
       ↓
TwelveDataProvider
       ↓
MarketDataProvider
       ↓
getQuote()
       ↓
Tests
```

Historical data and additional provider capabilities can be implemented after the quote flow is working.

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

### Status

**Planned**

---

## 9. M6 — Financial Education

### Objective

Extend financial intelligence into personalized financial education.

The system should not only provide financial information or analysis, but also help users understand the concepts behind that information.

### Planned Capabilities

- educational topics;
- personalized topic recommendations;
- learning paths;
- educational progress;
- contextual explanations;
- identification of relevant knowledge gaps.

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

**Planned**

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

**Planned**

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

The immediate technical priority is the Data & AI workstream.

The current sequence is:

```text
1. MarketDataProvider contract
2. TwelveDataProvider
3. TwelveDataClient integration
4. getQuote()
5. Tests
6. Historical market data
7. Asset profiles
8. Mock provider
9. MarketDataService
10. MCP integration
11. AI-agent integration
```

The implementation should prioritize a working vertical slice over prematurely implementing every planned capability.

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