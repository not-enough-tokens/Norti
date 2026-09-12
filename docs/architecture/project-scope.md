# Project Scope

## 1. Project Overview

Banorte MCP is a financial intelligence and financial education prototype built around the Model Context Protocol (MCP).

The project explores how MCP can provide a standardized interoperability layer between financial capabilities and AI agents. The system is designed to allow an AI agent to discover and invoke controlled financial tools without directly accessing databases, application internals, or external API credentials.

The prototype focuses on informed financial decision support rather than autonomous financial actions.

The central product flow is:

```text
Financial Data
      ↓
Financial Analysis
      ↓
AI Interpretation
      ↓
Personalized Education
      ↓
Informed Decision Support
```

MCP provides the interoperability layer between the AI agent and the application's financial capabilities.

---

## 2. Problem Statement

Financial information is often fragmented across different sources and presented without sufficient context for inexperienced users.

An AI assistant can help users understand financial information, but unrestricted access to financial systems creates architectural, security, and reliability concerns.

This project explores an architecture where:

- financial logic remains inside controlled backend services;
- external financial data is accessed through provider abstractions;
- AI agents interact with the application through MCP tools;
- financial calculations are performed by deterministic application logic;
- educational content can be adapted to the user's financial context.

---

## 3. Objectives

### Primary Objective

Build a working prototype demonstrating how MCP can connect an AI agent with financial intelligence and financial education capabilities.

### Technical Objectives

- Implement a Laravel-based backend.
- Model the core financial domain.
- Implement reusable financial services.
- Expose selected capabilities through MCP tools.
- Integrate an external market-data provider.
- Provide an abstraction over the external market-data provider.
- Connect an AI agent to the MCP server.
- Implement personalized financial education capabilities.
- Apply authentication, authorization, validation, and basic auditing.
- Demonstrate the complete end-to-end architecture.

---

## 4. Functional Scope

The MVP should support the following conceptual capabilities:

### Financial Profile

The system can represent a user's financial profile and relevant financial information.

### Financial Goals

Users can have financial goals that can be analyzed in relation to their financial situation.

### Portfolio

The system can represent portfolios containing holdings and financial assets.

### Market Data

The system can retrieve external market information through the application's market-data abstraction.

The initial external provider is Twelve Data.

### Financial Analysis

The system can perform deterministic analyses such as:

- portfolio composition;
- basic risk analysis;
- asset information;
- investment simulations;
- relationships between financial goals and available resources.

### Financial Education

The system can provide educational topics and learning paths related to the user's financial context.

### AI Interaction

An AI agent can discover and invoke selected capabilities through MCP.

The AI agent is responsible for interpreting the user's intent and selecting appropriate tools.

The backend remains responsible for executing the actual business logic.

---

## 5. Non-Functional Requirements

The system should prioritize:

- modularity;
- separation of concerns;
- testability;
- provider independence;
- controlled AI access;
- secure handling of credentials;
- deterministic financial calculations;
- clear error handling;
- understandable architecture suitable for demonstration.

---

## 6. Out of Scope

The following capabilities are explicitly outside the MVP:

- real bank account integration;
- real money transfers;
- real brokerage integration;
- real trading execution;
- custody of financial assets;
- KYC/AML infrastructure;
- production-grade regulatory compliance;
- derivatives trading;
- high-frequency trading;
- autonomous financial transactions;
- custom LLM training;
- production-scale Banorte infrastructure.

The project may simulate financial operations for educational purposes, but simulations must not be represented as real transactions.

---

## 7. Architectural Principles

### Business Logic

Business logic belongs in application services.

MCP tools must not implement financial business logic.

### MCP

MCP is an interoperability and interface layer.

It exposes controlled capabilities of the application to AI agents.

### External Providers

External APIs must be isolated behind internal provider abstractions.

Application services should depend on internal contracts rather than directly on third-party APIs.

### AI

AI agents may interpret requests and select tools, but they should not:

- access the database directly;
- access application secrets;
- bypass authorization;
- perform financial calculations that should be deterministic;
- execute high-risk financial actions.

### Security

Security is transversal across the entire architecture.

Authentication, authorization, input validation, data isolation, rate limiting, secret management, and auditing should be considered whenever a new capability is implemented.

---

## 8. Target Architecture

```text
                    ┌──────────────────┐
                    │    AI Agent      │
                    └────────┬─────────┘
                             │ MCP
                             ↓
                    ┌──────────────────┐
                    │    MCP Tools     │
                    └────────┬─────────┘
                             │
                             ↓
                    ┌──────────────────┐
                    │ Financial        │
                    │ Services         │
                    └───────┬──────────┘
                            │
             ┌──────────────┼──────────────┐
             ↓              ↓              ↓
        ┌─────────┐    ┌──────────┐   ┌───────────┐
        │ Models  │    │ Database │   │ Providers │
        └─────────┘    └──────────┘   └─────┬─────┘
                                             │
                                             ↓
                                      ┌──────────────┐
                                      │ Twelve Data  │
                                      └──────────────┘
```

---

## 9. Success Criteria

The MVP is considered technically successful when:

1. The Laravel application runs successfully.
2. Core financial entities are represented.
3. Financial capabilities are implemented through services.
4. Market data can be retrieved through `MarketDataProvider`.
5. Twelve Data can be used as the initial provider.
6. MCP tools expose selected financial capabilities.
7. An AI agent can discover and invoke those tools.
8. Tool execution passes through the application's business logic.
9. The system can provide at least one personalized educational response.
10. The complete data → analysis → AI → education flow can be demonstrated.

---

## 10. Development Milestones

The project is divided into the following milestones:

- M0 — Project Foundation
- M1 — Financial Domain
- M2 — Financial Services
- M3 — MCP Server
- M4 — External Market Data
- M5 — AI / Agents
- M6 — Financial Education
- M7 — Security and Hardening
- M8 — Product and Demonstration

Each milestone should produce an increment that can be tested independently whenever possible.
