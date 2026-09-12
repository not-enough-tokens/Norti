# ADR 002: Use Twelve Data as the Initial Market Data Provider

## Status

Accepted

## Context

Banorte MCP requires external market data for financial analysis and AI-assisted financial education.

The MVP needs a provider capable of supplying market information through an API while remaining practical for rapid prototype development.

Several providers were considered, including:

- Twelve Data;
- Finnhub;
- Alpha Vantage;
- Massive / Polygon;
- Financial Modeling Prep;
- Yahoo Finance / yfinance.

The project prioritizes API simplicity, JSON-based responses, broad market coverage, and suitability for a hackathon prototype.

## Decision

Twelve Data is the initial external market-data provider.

The application will use Twelve Data for capabilities such as:

- current quotes;
- historical market prices;
- basic asset information;
- additional market information when required by the MVP.

The provider is accessed through the application's internal market-data abstraction.

## Architecture

Twelve Data must not be accessed directly by application services.

The intended flow is:

```text
MarketDataService
       ↓
MarketDataProvider
       ↓
TwelveDataProvider
       ↓
TwelveDataClient
       ↓
Twelve Data API
```

## Consequences

### Positive

- Simple REST/JSON integration.
- Suitable for rapid development.
- Provides the market-data capabilities required by the MVP.
- Allows the team to build a functional vertical quickly.
- The provider can later be replaced without changing application services if the abstraction is preserved.

### Negative

- The project depends operationally on an external provider.
- API limits may affect development and demonstrations.
- Provider-specific limitations may restrict some future financial features.
- External API availability is outside the application's control.

## Alternatives Considered

### Finnhub

Considered a viable alternative with broad financial-market coverage, but Twelve Data was selected for the MVP.

### Alpha Vantage

Considered, but its API limits and development experience were less suitable for the project's immediate needs.

### Massive / Polygon

Considered for its market-data capabilities, but considered more than necessary for the initial prototype.

### Yahoo Finance / yfinance

Not selected as the backend provider.

`yfinance` is useful for research, notebooks, experimentation, and data analysis, but the MVP requires a conventional external API integration behind a backend provider abstraction.

## Architectural Constraint

Application code outside the provider layer must not depend on Twelve Data-specific field names or response structures.

Changing the market-data provider should primarily require implementing another `MarketDataProvider`.
