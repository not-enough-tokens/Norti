<?php

namespace App\Mcp\Concerns;

use App\Models\AuditLog;
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
}
