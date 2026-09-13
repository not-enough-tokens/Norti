# ADR 007: Decouple the M5 Agent Layer From Any Single Model Provider

## Status

Accepted

## Context

The first version of the M5 agent demonstration (`mcp:demo-agent`) was implemented by hand-rolling HTTP calls directly against Anthropic's Messages API: a custom tool-schema mapper (`Tool → {"name","description","input_schema"}`), a manual `tool_use`/`tool_result` conversation loop, and Anthropic-specific request/response shapes baked directly into the command.

This made the agent layer provider-locked. Testing another model (OpenAI, Gemini, etc.) would have required rewriting the tool mapping and the entire conversation loop, since each provider's tool-calling protocol differs. It also duplicated logic ([laravel/ai](https://github.com/laravel/ai)) already provides in a provider-agnostic way.

## Decision

The AI agent layer uses the [Laravel AI SDK](https://laravel.com/docs/ai-sdk) (`laravel/ai`). The model provider is a configuration/runtime concern, never something the agent or MCP layers know about.

The architecture is:

```text
AI Agent (Laravel AI SDK)
       ↓
MCP Client (Laravel\Mcp\Client)
       ↓
Banorte MCP Server
       ↓
MCP Tool
       ↓
Application Service / Provider chain (unchanged, see ADR 003)
```

`App\Ai\Agents\BanorteMcpAgent` implements `Laravel\Ai\Contracts\Agent` + `HasTools`. Its `tools()` method spreads the MCP client's tool collection directly (`[...$this->mcpClient->tools()]`); the SDK wraps each MCP `Tool` automatically (`Laravel\Ai\Tools\McpTool`), translating it into whatever tool-calling format the active provider requires. The agent's own code never references a provider-specific schema or message format.

The default provider is OpenAI, set via `#[Provider(Lab::OpenAI)]` on the agent class. It is not hardcoded behavior: `mcp:demo-agent` accepts `--provider`/`--model` to override it per run (`Agent::prompt()` accepts `provider`/`model` arguments directly), and `config/ai.php` (published by `laravel/ai`) lists every supported provider (`openai`, `anthropic`, `gemini`, ...) with its own credential, so swapping providers is a config/CLI change, never a code change.

## Consequences

### Positive

- Zero provider-specific code in the agent or MCP layers — swapping models is a `--provider`/`--model` flag or a `.env` key, not a rewrite.
- The SDK's built-in tool-calling loop (`MaxSteps`, failover, streaming) replaces ~80 lines of hand-rolled conversation-loop code that only worked for one provider.
- `BanorteMcpAgent::fake()` / `assertPrompted()` become available for testing agent behavior without hitting a real provider (though a live MCP connection is still needed to gather `tools()`, since tool schemas are resolved before the fake/real gateway is invoked).

### Negative

- Adds a dependency (`laravel/ai`) and its own conversation-storage migration (`agent_conversations`), unused by this stateless agent but part of the package's standard installation.
- One more layer to understand (`Agent` contract, `Promptable` trait, `Lab` enum) versus a raw HTTP call.

## Alternatives Considered

### Keep the Hand-Rolled Anthropic Client, Add an OpenAI Branch

Rejected: doubling (and eventually N-tupling) a hand-rolled tool-schema mapper and conversation loop per provider is exactly the coupling this ADR removes.

### A Different Agent Framework (LangChain-style, custom abstraction)

Rejected: `laravel/ai` is the first-party Laravel package for this, already integrates natively with `laravel/mcp`'s client (automatic MCP tool wrapping), and avoids introducing a second, unrelated agent framework into the project.

## Architectural Constraint

Agent classes (`app/Ai/Agents/*`) must not contain provider-specific request/response handling. Provider selection happens only through the `Provider`/`Model` attributes (as a default) or the `provider`/`model` arguments to `prompt()`/`stream()` (as an override) — never a provider-specific HTTP call inside the agent.
