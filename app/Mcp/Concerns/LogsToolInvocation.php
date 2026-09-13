<?php

namespace App\Mcp\Concerns;

use App\Models\AuditLog;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;

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
}
