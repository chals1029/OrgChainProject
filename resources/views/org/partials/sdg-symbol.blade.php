@if ($number === 16)
    <svg width="20" height="20" viewBox="0 0 72 72" fill="currentColor" focusable="false" aria-hidden="true">
        <path d="M8 17C24 13 29 35 43 23C48 18 51 18 54 24L59 28L54 29C46 29 47 36 46 43C44 53 33 58 20 56C13 56 9 51 6 49C16 53 18 48 24 45C12 43 16 23 8 17Z"/>
        <path d="M54 34C51 27 53 23 62 13M26 54V61M31 54V61" fill="none" stroke="currentColor" stroke-width="1.8"/>
        <path d="M62 13C59 9 60 6 64 4C65 9 65 12 62 13ZM58 18C54 16 53 13 54 10C58 11 60 14 58 18ZM61 18C63 14 66 14 68 15C66 19 64 20 61 18ZM55 23C51 21 50 18 51 15C55 17 57 20 55 23ZM57 24C60 21 63 21 65 24C61 27 59 27 57 24Z"/>
        <path d="M4 60L42 61V64L4 65Z"/>
        <rect x="0" y="59" width="3" height="7" rx="1.5"/>
        <rect x="42" y="57" width="14" height="11" rx="1"/>
        <rect x="41" y="53" width="16" height="3" rx="1.5"/>
        <rect x="41" y="69" width="16" height="3" rx="1.5"/>
    </svg>
@else
    <i class="bi bi-{{ $goalIcon ?? 'globe2' }}" aria-hidden="true"></i>
@endif
