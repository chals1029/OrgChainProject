<span class="org-workflow-status" data-workflow-status="{{ $status }}">
    <span class="org-workflow-status-dot" aria-hidden="true"></span>
    {{ $label ?? app(\App\Services\OrgWorkflowService::class)->label($status) }}
</span>
