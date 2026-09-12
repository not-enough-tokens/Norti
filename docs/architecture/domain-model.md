# Financial Domain Model

## 1. Purpose

This document defines the conceptual financial domain used by Banorte MCP.

The domain model describes the entities and relationships required by the MVP.

Implementation details such as exact database columns may evolve during development.

---

## 2. Core Entities

### User

Represents a system user.

A user may have:

- one financial profile;
- multiple financial goals;
- one or more portfolios;
- educational progress.

---

### FinancialProfile

Represents the user's financial context.

It may contain information such as:

- income;
- expenses;
- savings;
- risk profile;
- financial experience;
- relevant financial preferences.

The financial profile should provide context for financial analysis and educational personalization.

---

### FinancialGoal

Represents a financial objective.

Examples include:

- emergency fund;
- saving for education;
- saving for a vehicle;
- long-term investment;
- retirement planning.

A financial goal should contain enough information to evaluate progress and support financial analysis.

---

### Portfolio

Represents a collection of financial holdings belonging to a user.

A portfolio may contain multiple holdings.

A user may have multiple portfolios depending on the final implementation.

---

### Holding

Represents ownership of a quantity of a specific asset within a portfolio.

Conceptually:

```text
Portfolio
    │
    ├── Holding
    │      └── Asset
    │
    ├── Holding
    │      └── Asset
    │
    └── Holding
           └── Asset
```

A holding may include:

- quantity;
- acquisition price;
- current valuation;
- allocation information.

The MVP should treat holdings as represented financial data rather than real custodial positions.

---

### Asset

Represents a financial instrument that can be referenced by the system.

Examples:

- stocks;
- ETFs;
- other supported market instruments.

An asset may have:

- symbol;
- name;
- exchange;
- currency;
- asset type.

Market information associated with an asset is obtained through the market-data provider layer.

---

### EducationalTopic

Represents a financial education topic.

Examples:

- diversification;
- risk and return;
- compound interest;
- portfolio allocation;
- inflation;
- ETFs;
- stock-market fundamentals.

Topics can be associated with user needs and financial analysis.

---

### EducationalProgress

Represents a user's progress through financial education content.

It may track:

- topic;
- completion status;
- progress;
- assessment results where applicable.

Educational progress can later be used to personalize future educational recommendations.

---

## 3. Relationships

The main conceptual relationships are:

```text
User
 ├── FinancialProfile
 ├── FinancialGoal *
 ├── Portfolio *
 └── EducationalProgress *

Portfolio
 └── Holding *

Holding
 └── Asset

EducationalProgress
 └── EducationalTopic
```

`*` indicates a one-to-many relationship.

---

## 4. Financial Analysis Context

Financial analysis should combine relevant domain information.

For example:

```text
FinancialProfile
        +
FinancialGoals
        +
Portfolio
        +
MarketData
        ↓
Financial Analysis
```

The analysis layer should not modify the user's financial data merely as a consequence of producing an analysis.

---

## 5. Market Data and Domain Data

The system should distinguish between persistent domain data and external market information.

Persistent domain data includes:

- user information;
- financial profiles;
- financial goals;
- portfolios;
- holdings;
- educational progress.

External market data includes:

- current prices;
- historical prices;
- market metadata;
- other provider-supported information.

Market data should not be tightly coupled to the persistence model unless a later requirement explicitly calls for caching or historical storage.

---

## 6. Ownership and Data Isolation

Financial information is user-specific.

Services and MCP tools must ensure that a user can only access data they are authorized to access.

A tool should never accept an arbitrary user identifier as sufficient authorization to access another user's financial information.

Authorization must be enforced independently of AI-generated input.

---

## 7. Domain Boundaries

The following boundaries should remain conceptually separate:

### Financial Domain

Profiles, goals, portfolios, holdings, and assets.

### Market Data Domain

External market information and provider integrations.

### Education Domain

Topics, learning progress, and educational recommendations.

### AI / Interoperability

MCP tools and AI-agent interaction.

These domains may interact through services, but should not become unnecessarily coupled.

---

## 8. Future Extensions

The following may be added after the MVP:

- transactions;
- historical portfolio valuations;
- asset classes beyond the initial scope;
- educational assessments;
- recommendation history;
- market-data caching;
- financial events;
- notifications.

These extensions should not be assumed to be part of the MVP unless explicitly added to the project scope.
