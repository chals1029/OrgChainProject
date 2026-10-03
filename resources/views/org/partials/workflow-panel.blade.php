{{-- Shared workflow + urgency widgets for pre-oral demo --}}
@php
    $role = $office->office_role ?? 'so';
    $workflow = $workflowStages ?? ['created','college_review','oso_review','sdo_review','ovcaa_review','oc_review','oc_approved'];
@endphp

<section class="org-panel liquid-glass" style="margin-bottom:1.25rem;">
    <div class="org-panel-head">
        <h2><i class="bi bi-diagram-3"></i> Desk Overview</h2>
        <span>System-checked</span>
    </div>

    @if (($role ?? '') === 'so' && !empty($fundAccount))
        <form method="post" action="{{ route('office.funds.update', $fundAccount) }}" style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr)) auto;gap:0.75rem;align-items:end;margin-bottom:1rem;">
            @csrf
            <label style="display:grid;gap:0.25rem;font-size:0.78rem;font-weight:800;">
                Total Funds
                <input type="number" name="total_funds" value="{{ $fundAccount->total_funds }}" min="0" style="padding:0.55rem 0.7rem;border-radius:10px;border:1px solid #e8dedf;">
            </label>
            <label style="display:grid;gap:0.25rem;font-size:0.78rem;font-weight:800;">
                Beginning Balance
                <input type="number" name="beginning_balance" value="{{ $fundAccount->beginning_balance }}" min="0" style="padding:0.55rem 0.7rem;border-radius:10px;border:1px solid #e8dedf;">
            </label>
            <label style="display:grid;gap:0.25rem;font-size:0.78rem;font-weight:800;">
                Total Funds Received
                <input type="number" name="total_funds_received" value="{{ $fundAccount->total_funds_received }}" min="0" style="padding:0.55rem 0.7rem;border-radius:10px;border:1px solid #e8dedf;">
            </label>
            <button type="submit" class="org-btn org-btn-primary">Update Funds</button>
        </form>
    @elseif (!empty($transparency['total_funds']))
        <p style="margin:0 0 1rem;font-weight:700;">Total Funds: Php {{ number_format($transparency['total_funds'], 2) }}
            · Beginning: Php {{ number_format($transparency['beginning_balance'] ?? 0, 2) }}</p>
    @endif

    @if (($role ?? '') === 'oso' && !empty($urgencyQueue) && count($urgencyQueue))
        <h3 style="font-size:0.95rem;margin:0 0 0.65rem;">Urgency / SLA — Recent Transactions Requiring Action</h3>
        <ul style="list-style:none;margin:0;padding:0;display:grid;gap:0.55rem;">
            @foreach ($urgencyQueue as $item)
                <li style="display:flex;justify-content:space-between;gap:0.75rem;align-items:center;padding:0.65rem 0.8rem;border:1px solid #f0e6e8;border-radius:12px;">
                    <div>
                        <strong>{{ $item['title'] }}</strong>
                        <div style="font-size:0.78rem;color:#7a7074;">{{ $item['organization'] }} · {{ $item['status'] }} · Due {{ $item['due'] }}</div>
                    </div>
                    <form method="post" action="{{ route('office.oso.remind', $item['id']) }}">
                        @csrf
                        <button type="submit" class="org-btn" style="font-size:0.78rem;">Email SO</button>
                    </form>
                </li>
            @endforeach
        </ul>
    @endif

    @if (!empty($tracker))
        <div style="margin-top:1rem;display:grid;gap:0.65rem;">
            @foreach ($tracker as $row)
                <div style="border:1px solid #f0e6e8;border-radius:12px;padding:0.75rem 0.9rem;display:flex;justify-content:space-between;gap:0.75rem;flex-wrap:wrap;">
                    <div>
                        <strong>{{ $row['title'] }}</strong>
                        <div style="font-size:0.78rem;color:#7a7074;">{{ $row['status'] }}@if(!empty($row['returned_to'])) · return to {{ $row['returned_to'] }}@endif</div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    @if (($role ?? '') === 'oso' && !empty($studentFeedback) && count($studentFeedback))
        <div style="display:flex;align-items:center;justify-content:space-between;margin:1rem 0 0.65rem;">
            <h3 style="font-size:0.95rem;margin:0;">Student Voice</h3>
            <a href="{{ route('office.student-reports') }}" style="font-size:0.75rem;font-weight:800;color:#8b1828;text-decoration:none;">Open verification queue &rarr;</a>
        </div>
        <ul style="list-style:none;margin:0;padding:0;display:grid;gap:0.45rem;">
            @foreach ($studentFeedback as $fb)
                <li style="padding:0.6rem 0.75rem;border:1px solid #f0e6e8;border-radius:12px;font-size:0.82rem;">
                    <strong>{{ $fb->is_anonymous ? 'Anonymous' : ($fb->author_name ?: 'Student') }}</strong>
                    <span style="color:#7a7074;"> · {{ $fb->topic }}@if(!empty($fb->college)) · {{ $fb->college }}@endif</span>
                    <div>{{ \Illuminate\Support\Str::limit($fb->body, 140) }}</div>
                </li>
            @endforeach
        </ul>
    @endif
</section>

@if (!empty($chartPayload))
<script>
    window.orgDeskChartPayload = @json($chartPayload);
</script>
@endif
