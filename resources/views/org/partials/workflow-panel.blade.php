{{-- Shared workflow + urgency widgets for pre-oral demo --}}
@php
    $role = $office->office_role ?? 'so';
    $workflow = $workflowStages ?? ['created','college_review','oso_review','sdo_review','ovcaa_review','oc_approved'];
@endphp

<section class="org-panel liquid-glass" style="margin-bottom:1.25rem;">
    <div class="org-panel-head">
        <h2><i class="bi bi-diagram-3"></i> Approval Workflow Pipeline</h2>
        <span>System-checked</span>
    </div>
    <div style="display:flex;flex-wrap:wrap;gap:0.5rem;margin-bottom:1rem;">
        @foreach ($workflow as $step)
            <span style="padding:0.35rem 0.75rem;border-radius:999px;background:rgba(122,18,34,0.08);border:1px solid rgba(122,18,34,0.15);font-size:0.78rem;font-weight:800;color:#7a1222;">
                {{ strtoupper(str_replace('_', ' ', $step)) }}
                @if (! $loop->last) <i class="bi bi-arrow-right"></i> @endif
            </span>
        @endforeach
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
                    @if (!empty($row['id']))
                        <div style="display:flex;gap:0.4rem;flex-wrap:wrap;">
                            <form method="post" action="{{ route('office.activities.advance', $row['id']) }}">@csrf
                                <button class="org-btn org-btn-primary" type="submit" style="font-size:0.75rem;">Advance</button>
                            </form>
                            <form method="post" action="{{ route('office.activities.return', $row['id']) }}" style="display:flex;gap:0.35rem;">@csrf
                                <select name="returned_to" style="font-size:0.75rem;border-radius:8px;border:1px solid #e8dedf;">
                                    <option value="so">SO</option>
                                    <option value="college_reviewer">College</option>
                                    <option value="oso">OSO</option>
                                    <option value="sdo">SDO</option>
                                </select>
                                <button class="org-btn" type="submit" style="font-size:0.75rem;">Return</button>
                            </form>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    @if (($role ?? '') === 'oso' && !empty($studentFeedback) && count($studentFeedback))
        <h3 style="font-size:0.95rem;margin:1rem 0 0.65rem;">Student Voice</h3>
        <ul style="list-style:none;margin:0;padding:0;display:grid;gap:0.45rem;">
            @foreach ($studentFeedback as $fb)
                <li style="padding:0.6rem 0.75rem;border:1px solid #f0e6e8;border-radius:12px;font-size:0.82rem;">
                    <strong>{{ $fb->is_anonymous ? 'Anonymous' : ($fb->author_name ?: 'Student') }}</strong>
                    <span style="color:#7a7074;"> · {{ $fb->topic }} · {{ $fb->program }}</span>
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
