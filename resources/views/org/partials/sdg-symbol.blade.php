@if ($number >= 1 && $number <= 17)
    <img src="{{ asset('images/sdg/goal-'.str_pad($number, 2, '0', STR_PAD_LEFT).'.png') }}" alt="" width="64" height="64" style="display:block;width:100%;height:100%;object-fit:contain" loading="lazy">
@else
    <i class="bi bi-{{ $goalIcon ?? 'globe2' }}" aria-hidden="true"></i>
@endif
