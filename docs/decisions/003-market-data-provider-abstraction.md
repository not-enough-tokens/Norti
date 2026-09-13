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

The application defines an internal `MarketDataProviderContract` interface (`app/Services/Contracts/MarketDataProviderContract.php`).

The initial (and, after consolidation, only) implementation is `TwelveDataMarketDataProvider` (`app/Services/MarketData/TwelveDataMarketDataProvider.php`).

Communication with Twelve Data is handled by `TwelveDataClient`.

The responsibilities are separated as follows:

```text
MarketDataProviderContract
        ↑
TwelveDataMarketDataProvider
        ↓
TwelveDataClient
        ↓
Twelve Data API
```

### MarketDataProviderContract

Defines the application's required market-data capabilities: `quote()`, `profile()`, `timeSeries()`, each accepting an optional `$parameters` array for provider-specific filters (e.g. `exchange`, `country`).

It represents the contract that MCP tools and `MarketDataController` depend on directly — there is no intervening `MarketDataService`.

### TwelveDataMarketDataProvider

Acts as an adapter between the application's internal contract and Twelve Data's API, and wraps any `TwelveDataException` into a provider-agnostic `MarketDataUnavailableException` (`app/Services/Contracts/Exceptions/`).

It also caches `quote()`/`profile()` responses for 60 seconds (bypassed during tests) to stay within Twelve Data's free-tier rate limit, since repeated MCP tool calls can request the same symbol.

It is responsible for:

- translating provider errors into `MarketDataUnavailableException`;
- normalizing `timeSeries()` values into typed price bars (`open`/`high`/`low`/`close` as float, `volume` as int);
- caching `quote()`/`profile()` responses.

`quote()` and `profile()` currently pass through Twelve Data's response fields largely as-is (see below) — only `timeSeries()` is normalized into a fixed shape.

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

`quote()`/`profile()` currently return Twelve Data's response close to unmodified:

```json
{
    "symbol": "AAPL",
    "close": "123.45",
    "exchange": "NASDAQ",
    "currency": "USD"
}
```

`timeSeries()` normalizes each entry into a typed bar:

```json
{
    "datetime": "2024-01-02",
    "open": 151.0,
    "high": 152.0,
    "low": 150.5,
    "close": 151.5,
    "volume": 900000
}
```

MCP tools that assemble a UI-facing response (`get_asset_information`, `get_market_snapshot`) currently embed the raw `quote()`/`profile()` output directly in their structured content, so consumers should not assume Twelve Data's specific field names are stable long-term — normalizing `quote()`/`profile()` the same way `timeSeries()` already is remains open future work.

## Testing

A mock implementation is available:

```text
MarketDataProviderContract
       ↑
MockMarketDataProvider
```

`MockMarketDataProvider` (`app/Services/MarketData/MockMarketDataProvider.php`) returns fixed, symbol-independent data for `quote()`/`profile()`/`timeSeries()`, letting tests execute deterministically without external network requests or API-rate consumption. `MarketDataControllerMockProviderTest` demonstrates swapping it in via `$this->app->bind(MarketDataProviderContract::class, MockMarketDataProvider::class)` combined with `Http::preventStrayRequests()`.

## Update (2026-09-13): consolidation of two parallel implementations

During the hackathon, two team members independently built an implementation of this same abstraction after diverging from a common commit: one under `MarketDataProvider`/`TwelveDataProvider`/`MarketDataService` (with a `getQuote()`/`getHistoricalPrices()`/`getAssetProfile()` naming scheme and full response normalization), the other under `MarketDataProviderContract`/`TwelveDataMarketDataProvider` (with a `quote()`/`profile()`/`timeSeries()` naming scheme, already wired into the `get_asset_information`/`get_market_snapshot` MCP tools with Passport scopes and audit logging).

`MarketDataProviderContract`/`TwelveDataMarketDataProvider` was kept as the single implementation, since it was already integrated with the rest of the MCP layer (M3) and retiring it would have required rewriting two working, tested MCP tools. The other side's `timeSeries()`/`getAssetProfile()`/`getHistoricalPrices()` bar-normalization logic and its `MockMarketDataProvider` were ported over; the redundant classes and their `get_market_quote` MCP tool were deleted. See [ADR 002](002-use-twelve-data.md) for the resulting flow.

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

Application services must depend on `MarketDataProviderContract`, not on `TwelveDataClient`.

Provider-specific implementation details must remain inside the provider/infrastructure layer.