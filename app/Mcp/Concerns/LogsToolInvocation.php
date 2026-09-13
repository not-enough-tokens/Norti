<?php

namespace App\Mcp\Concerns;

use App\Models\AuditLog;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;

/**
 * laravel/mcp fires no per-tool-call event (only SessionInitialized, which is
 * per-session) -- so each Tool calls this explicitly at every exit point of
 * handle(). Never pass amounts or a full FinancialProfile in $safeInput.
 */
trait LogsToolInvocation
{
    /**
     * @param  array<string, mixed>  $safeInput
     */
    protected function logToolCall(Request $request, bool $success, array $safeInput = [], ?string $resultSummary = null): void
    {
        AuditLog::create([
            'user_id' => $request->user()?->id,
            'tool_name' => $this->name(),
            'input' => $safeInput,
            'result_summary' => $resultSummary ?? ($success ? 'ok' : 'error'),
            'ip_address' => request()?->ip(),
            'created_at' => now(),
        ]);
    }

    /**
     * $request->validate() throws before logToolCall() would otherwise run,
     * so a malformed-input attempt from an authenticated, in-scope caller
     * left no audit trail. This wraps it: log then rethrow, letting the
     * framework's own ValidationException -> Response::error() conversion
     * still happen.
     *
     * @param  array<string, mixed>  $rules
     * @return array<string, mixed>
     */
    protected function validateOrLog(Request $request, array $rules): array
    {
        try {
            return $request->validate($rules);
        } catch (ValidationException $exception) {
            $this->logToolCall($request, success: false, safeInput: [
                'failed_fields' => array_keys($exception->errors()),
            ], resultSummary: 'validation_failed');

            throw $exception;
        }
    }

    /**
     * `Response::error()` (laravel/mcp) only takes plain text -- A2UI contract
     * gap 9 asks for a `{ code, message }` shape instead, with `code` matching
     * the audit log's `result_summary`. JSON-encoding it into that same text
     * parameter satisfies both without touching laravel/mcp's error mechanism
     * (still `isError: true`) or breaking `assertHasErrors()`, which does a
     * substring match against the error text -- the plain `message` stays
     * readable inside the encoded string.
     *
     * @param  array<string, mixed>  $safeInput
     */
    protected function errorResponse(Request $request, string $code, string $message, array $safeInput = []): Response
    {
        $this->logToolCall($request, success: false, safeInput: $safeInput, resultSummary: $code);

        return Response::error(json_encode(['code' => $code, 'message' => $message], JSON_UNESCAPED_UNICODE));
    }
}
