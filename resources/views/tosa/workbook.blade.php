<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    {{-- Keep this policy even where the application's global middleware supplies its own CSP header. --}}
    <meta http-equiv="Content-Security-Policy" content="{{ $policy }}">
    <title>{{ $preview['title'] }} · OrgChain</title>
    <style nonce="{{ $nonce }}">
        :root { color-scheme: light; font-family: system-ui, sans-serif; color: #243146; background: #f3f5f8; }
        * { box-sizing: border-box; }
        body { margin: 0; }
        main { width: min(1440px, 100%); padding: 20px; margin: auto; }
        header { display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; }
        h1 { font-size: 1.4rem; margin: 0 0 6px; }
        .subtitle { margin: 0; color: #576377; }
        .actions { display: flex; gap: 8px; flex-wrap: wrap; }
        .action, button { font: inherit; border: 1px solid #c8d0dc; border-radius: 7px; padding: 9px 14px; background: white; color: #243146; cursor: pointer; text-decoration: none; }
        .download { background: #13684d; color: white; border-color: #13684d; }
        .notices { margin: 18px 0; padding: 12px 18px 12px 34px; border: 1px solid #d8bf7d; background: #fff9e9; border-radius: 8px; line-height: 1.5; }
        .notices li + li { margin-top: 6px; }
        .tabs { display: flex; flex-wrap: wrap; gap: 6px; margin: 12px 0; }
        .tabs button[aria-selected="true"] { color: white; background: #243146; border-color: #243146; }
        button:focus-visible, a:focus-visible, .grid:focus-visible { outline: 3px solid #d08300; outline-offset: 2px; }
        .grid { max-height: 70vh; overflow: auto; overscroll-behavior: contain; border: 1px solid #c8d0dc; border-radius: 8px; background: white; }
        table { border-collapse: separate; border-spacing: 0; font-size: .86rem; width: max-content; min-width: 100%; }
        th, td { border-right: 1px solid #dfe4eb; border-bottom: 1px solid #dfe4eb; padding: 7px 10px; vertical-align: top; }
        thead th { position: sticky; top: 0; background: #e9edf3; z-index: 2; text-align: center; }
        tbody th { position: sticky; left: 0; background: #e9edf3; z-index: 1; text-align: right; min-width: 42px; }
        thead th:first-child { left: 0; z-index: 3; }
        td { min-width: 110px; max-width: 340px; white-space: pre-wrap; overflow-wrap: anywhere; }
        .formula { display: block; color: #6b466e; margin-top: 5px; font-family: ui-monospace, monospace; font-size: .8rem; }
        .cache { color: #576377; font-size: .78rem; }
        .sheet-name { font-size: 1rem; margin: 12px 0 8px; }
        footer { font-size: .85rem; color: #576377; margin-top: 12px; }
        [hidden] { display: none !important; }
        @media (max-width: 600px) { main { padding: 12px; } .grid { max-height: 65vh; } .actions { width: 100%; } }
    </style>
</head>
<body>
<main>
    <header>
        <div><h1>{{ $preview['title'] }}</h1><p class="subtitle">Read-only Excel preview · {{ $preview['reference'] ? 'Original reference' : 'Personal saved evaluation' }}</p></div>
        <nav class="actions" aria-label="Workbook actions">
            <a class="action download" href="{{ $downloadUrl }}">{{ $preview['reference'] ? 'Download original XLSX' : 'Download computed XLSX' }}</a>
            <a class="action" href="{{ $backUrl }}">Back to TOSA</a>
            <button type="button" id="close-workbook">Close</button>
        </nav>
    </header>
    <ul class="notices">@foreach($preview['notices'] as $notice)<li>{{ $notice }}</li>@endforeach</ul>
    <div class="tabs" role="tablist" aria-label="Worksheets">
        @foreach($preview['sheets'] as $index => $sheet)
            <button type="button" role="tab" id="sheet-tab-{{ $index }}" aria-controls="sheet-panel-{{ $index }}" aria-selected="{{ $index === 0 ? 'true' : 'false' }}" tabindex="{{ $index === 0 ? '0' : '-1' }}">{{ $sheet['name'] }}</button>
        @endforeach
    </div>
    @foreach($preview['sheets'] as $index => $sheet)
        <section role="tabpanel" id="sheet-panel-{{ $index }}" aria-labelledby="sheet-tab-{{ $index }}" @if($index !== 0) hidden @endif>
            <h2 class="sheet-name">{{ $sheet['name'] }}</h2>
            <div class="grid" tabindex="0" role="region" aria-label="{{ $sheet['name'] }} worksheet; scroll to see all cells">
                <table>
                    <thead><tr><th scope="col">Row</th>@foreach($sheet['columns'] as $column)<th scope="col">{{ $column }}</th>@endforeach</tr></thead>
                    <tbody>
                    @foreach($sheet['rows'] as $rowIndex => $cells)
                        <tr><th scope="row">{{ $rowIndex + 1 }}</th>
                        @foreach($cells as $cell)
                            <td>@if($cell['formula'] !== null)<span class="cache">Saved cache: {{ $cell['value'] === '' ? '(none)' : $cell['value'] }}</span><code class="formula">{{ $cell['formula'] }}</code>@else{{ $cell['value'] }}@endif</td>
                        @endforeach
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endforeach
    <footer>No editing, formula execution, links embedded in cells, or external Excel resources are enabled in this preview.</footer>
</main>
<script nonce="{{ $nonce }}">
    (() => {
        const tabs = Array.from(document.querySelectorAll('[role="tab"]'));
        const panels = Array.from(document.querySelectorAll('[role="tabpanel"]'));
        function select(index, focus) {
            tabs.forEach((tab, i) => { tab.setAttribute('aria-selected', i === index ? 'true' : 'false'); tab.tabIndex = i === index ? 0 : -1; });
            panels.forEach((panel, i) => { panel.hidden = i !== index; });
            if (focus) tabs[index].focus();
        }
        tabs.forEach((tab, index) => {
            tab.addEventListener('click', () => select(index, false));
            tab.addEventListener('keydown', event => {
                let next;
                if (event.key === 'ArrowRight') next = (index + 1) % tabs.length;
                if (event.key === 'ArrowLeft') next = (index - 1 + tabs.length) % tabs.length;
                if (event.key === 'Home') next = 0;
                if (event.key === 'End') next = tabs.length - 1;
                if (next !== undefined) { event.preventDefault(); select(next, true); }
            });
        });
        document.getElementById('close-workbook').addEventListener('click', () => {
            if (window.opener) window.close();
            if (!window.closed) window.location.assign(document.querySelector('.actions a:not(.download)').href);
        });
    })();
</script>
</body>
</html>
