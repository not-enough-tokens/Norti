# ADR 003: Abstract External Market Data Providers

## Status

Accepted

## Context

The application needs external market data, but financial services should not be tightly coupled to a specific third-party provider.

Directly calling Twelve Data from application services would make the architecture dependent on provider-specific:

- endpoints;
- parameters;
- response structures;
- error formats;
- naming conventions.

This would make changing providers and testing the application more difficult.

## Decision

The application defines an internal `MarketDataProvider` interface.

The initial implementation is `TwelveDataProvider`.

Communication with Twelve Data is handled by `TwelveDataClient`.

The responsibilities are separated as follows:

```text
MarketDataProvider
        ↑
TwelveDataProvider
        ↓
TwelveDataClient
        ↓
Twelve Data API
```

### MarketDataProvider

Defines the application's required market-data capabilities.

It represents the contract that application services depend on.

### TwelveDataProvider

Acts as an adapter between the application's internal contract and Twelve Data's API.

It is responsible for:

- translating provider responses;
- normalizing field names;
- converting values into expected types;
- exposing the internal application format.

### TwelveDataClient

Handles communication with Twelve Data.

It is responsible for:

- HTTP requests;
- authentication using the API key;
- connection handling;
- provider-specific API errors;
- endpoint communication.

It should remain independent from financial business logic.

## Example

The application may receive the following provider response:

```json
{
    "symbol": "AAPL",
    "close": "123.45",
    "exchange": "NASDAQ",
    "currency": "USD"
}
```

The provider adapter may normalize it to:

```json
{
    "symbol": "AAPL",
    "price": 123.45,
    "exchange": "NASDAQ",
    "currency": "USD"
}
```

Application services should use the normalized representation rather than the original provider structure.

## Testing

A mock implementation should eventually be available:

```text
MarketDataProvider
       ↑
MockMarketDataProvider
```

This allows tests to execute deterministically without external network requests or API-rate consumption.

## Consequences

### Positive

- Provider independence.
- Easier testing.
- Cleaner application services.
- Reduced coupling to external APIs.
- Easier future provider replacement.
- Clear separation between infrastructure and business logic.

### Negative

- Adds an abstraction layer.
- Requires adapter code.
- Some provider-specific capabilities may not map directly to the common interface.

## Alternatives Considered

### Direct API Calls from Services

Rejected because it couples business logic to an external provider.

### Generic HTTP Client Without a Provider Abstraction

Rejected because it does not provide a stable application-level contract.

### Multiple Providers Immediately

Rejected for the MVP because only one provider is currently required.

The architecture should support additional providers without requiring their implementation during the initial prototype.

## Architectural Constraint

Application services must depend on `MarketDataProvider`, not on `TwelveDataClient`.

Provider-specific implementation details must remain inside the provider/infrastructure layer.