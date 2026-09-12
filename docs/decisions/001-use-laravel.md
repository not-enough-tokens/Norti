# ADR 001: Use Laravel as the Backend Framework

## Status

Accepted

## Context

Banorte MCP requires a backend capable of:

- exposing application services;
- managing persistent financial data;
- implementing authentication and authorization;
- integrating external APIs;
- exposing MCP capabilities;
- supporting automated testing;
- providing a maintainable structure for rapid prototype development.

The project requires a framework that supports these capabilities without requiring the team to build common backend infrastructure from scratch.

## Decision

Laravel is the primary backend framework for Banorte MCP.

The project uses Laravel for:

- HTTP/API infrastructure;
- database access and migrations;
- authentication;
- dependency injection;
- service organization;
- validation;
- testing infrastructure;
- MCP integration;
- application configuration.

The application should follow established Laravel conventions unless there is a concrete architectural reason to deviate.

## Consequences

### Positive

- Provides mature backend infrastructure.
- Reduces development time.
- Provides dependency injection and service-container support.
- Integrates naturally with relational databases.
- Provides established testing conventions.
- Supports the project's MCP integration.

### Negative

- The project becomes coupled to the Laravel ecosystem.
- Developers need to follow Laravel-specific conventions.
- Some abstractions may be unnecessary for a small prototype.

## Alternatives Considered

### Plain PHP

Rejected because it would require implementing or configuring significantly more infrastructure manually.

### Node.js / Express

Rejected for the MVP because the project has already been established around Laravel and PHP.

### Other PHP Frameworks

Not selected because Laravel already provides the required infrastructure and is the project's established foundation.

## Architectural Constraint

Future changes to the backend framework should require an explicit architectural decision.

Do not introduce a second backend framework into the project without justification.
