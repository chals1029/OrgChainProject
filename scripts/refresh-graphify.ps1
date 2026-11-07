# Local, unattended Graphify code refresh. No hooks or network access.
$ErrorActionPreference = 'Stop'
$repo = [IO.Path]::GetFullPath((Join-Path $PSScriptRoot '..'))
$homeDir = Join-Path $env:USERPROFILE '.graphify\orgchain'
$graphDir = Join-Path $homeDir 'graphify-out'
$vault = Join-Path $repo 'ORGCHAIN'
$exportDir = Join-Path $vault 'Graphify Generated'
$exe = Join-Path $env:USERPROFILE '.local\bin\graphify.exe'
$statePath = Join-Path $homeDir 'refresh-state.json'
$logPath = Join-Path $homeDir 'refresh.log'
$work = $null
$lock = $null

function Log([string]$message) {
    $line = '{0} {1}' -f (Get-Date -Format o), $message
    Add-Content -LiteralPath $logPath -Value $line -Encoding UTF8
    Write-Host $line
}

# Read NUL-delimited paths as bytes: PowerShell's native pipeline cannot preserve NULs.
function GitPaths([string[]]$gitArgs) {
    $p = New-Object Diagnostics.Process
    $p.StartInfo.FileName = 'git.exe'
    $p.StartInfo.WorkingDirectory = $repo
    $p.StartInfo.Arguments = ($gitArgs -join ' ')
    $p.StartInfo.UseShellExecute = $false
    $p.StartInfo.RedirectStandardOutput = $true
    $p.StartInfo.RedirectStandardError = $true
    [void]$p.Start()
    $memory = New-Object IO.MemoryStream
    $p.StandardOutput.BaseStream.CopyTo($memory)
    $err = $p.StandardError.ReadToEnd()
    $p.WaitForExit()
    if ($p.ExitCode -ne 0) { throw "git failed ($($p.ExitCode)): $err" }
    $text = [Text.Encoding]::UTF8.GetString($memory.ToArray())
    $memory.Dispose()
    $p.Dispose()
    return @($text.Split([char]0, [StringSplitOptions]::RemoveEmptyEntries))
}

function RunGraphify([string[]]$arguments, [string]$directory) {
    Push-Location -LiteralPath $directory
    try {
        # Native stderr is diagnostic output, not by itself a failure on Windows PowerShell 5.1.
        $oldPreference = $ErrorActionPreference
        $ErrorActionPreference = 'Continue'
        try { & $exe @arguments 2>&1 | ForEach-Object { Add-Content -LiteralPath $logPath -Value "$_" -Encoding UTF8 }; $code = $LASTEXITCODE }
        finally { $ErrorActionPreference = $oldPreference }
        if ($code -ne 0) { throw "Graphify failed with exit code ${code}: $($arguments -join ' ')" }
    } finally { Pop-Location }
}

function ReadManifest([string]$directory) {
    $path = Join-Path $directory '.graphify_obsidian_manifest.json'
    if (-not (Test-Path -LiteralPath $path -PathType Leaf)) { throw "Missing export manifest: $path" }
    $manifest = Get-Content -LiteralPath $path -Raw -Encoding UTF8 | ConvertFrom-Json
    if ($null -eq $manifest.files) { throw "Invalid export manifest: $path" }
    return @($manifest.files)
}

function SafeExportPath([string]$base, [string]$relative) {
    if ([IO.Path]::IsPathRooted($relative)) { throw "Unsafe export path: $relative" }
    $full = [IO.Path]::GetFullPath((Join-Path $base $relative))
    $prefix = [IO.Path]::GetFullPath($base).TrimEnd('\') + '\'
    if (-not $full.StartsWith($prefix, [StringComparison]::OrdinalIgnoreCase)) { throw "Unsafe export path: $relative" }
    return $full
}


try {
    [void](New-Item -ItemType Directory -Force -Path $homeDir)
    try { $lock = [IO.File]::Open((Join-Path $homeDir 'refresh.lock'), 'OpenOrCreate', 'ReadWrite', 'None') }
    catch {
        $cause = $_.Exception
        while ($cause.InnerException) { $cause = $cause.InnerException }
        $win32Code = $cause.HResult -band 0xFFFF
        if ($cause -is [IO.IOException] -and $win32Code -in @(32, 33)) {
            Write-Host 'Graphify refresh already running; skipped.'
            exit 0
        }
        throw
    }
    if (-not (Test-Path -LiteralPath $exe -PathType Leaf)) { throw "Graphify executable not found: $exe" }
    if (-not (Test-Path -LiteralPath $vault -PathType Container)) { throw "Obsidian vault not found: $vault" }

    # Stage only allowed code/config and non-private Markdown in the vault.
    $ignored = New-Object 'System.Collections.Generic.HashSet[string]' ([StringComparer]::OrdinalIgnoreCase)
    $excludeArgs = @()
    if (Test-Path -LiteralPath (Join-Path $repo '.graphifyignore') -PathType Leaf) { $excludeArgs = @('--exclude-from=.graphifyignore') }
    foreach ($path in (GitPaths (@('ls-files','-z','-c','-i','--exclude-standard') + $excludeArgs))) { [void]$ignored.Add($path) }
    $paths = @(GitPaths (@('ls-files','-z','--cached','--others','--exclude-standard') + $excludeArgs) | Sort-Object -Unique)
    $eligible = New-Object 'System.Collections.Generic.List[string]'
    foreach ($path in $paths) {
        if ($ignored.Contains($path) -or $path -match '(^|/)\.' -or $path -match '(?i)(^|/)(graphify generated|private|secrets?|credentials?)(/|$)' -or $path -notmatch '(?i)\.(php|js|mjs|cjs|ts|tsx|jsx|sol|py|ps1|sh|css|json|yaml|yml)$') { continue }
        $full = Join-Path $repo $path
        if (-not (Test-Path -LiteralPath $full -PathType Leaf)) { continue }
        $item = Get-Item -LiteralPath $full -Force
        if (($item.Attributes -band ([IO.FileAttributes]::ReparsePoint -bor [IO.FileAttributes]::Hidden)) -ne 0) { continue }
        $parent = $item.Directory
        $linked = $false
        while ($parent -and $parent.FullName -ne $repo) {
            if (($parent.Attributes -band ([IO.FileAttributes]::ReparsePoint -bor [IO.FileAttributes]::Hidden)) -ne 0) { $linked = $true; break }
            $parent = $parent.Parent
        }
        if (-not $linked) { $eligible.Add($path) }
    }

    # A private staged source tree ensures Graphify never traverses ignored data.
    $work = Join-Path ([IO.Path]::GetTempPath()) ('orgchain-graphify-' + [guid]::NewGuid().ToString('N'))
    $source = Join-Path $work 'source'
    [void](New-Item -ItemType Directory -Force -Path $source)
    $records = New-Object 'System.Collections.Generic.List[string]'
    foreach ($control in @('.gitignore', '.graphifyignore')) {
        $controlPath = Join-Path $repo $control
        if (Test-Path -LiteralPath $controlPath -PathType Leaf) {
            $records.Add(($control + "`0" + (Get-FileHash -LiteralPath $controlPath -Algorithm SHA256).Hash))
        } else {
            $records.Add(($control + "`0" + '<missing>'))
        }
    }
    foreach ($path in $eligible) {
        $target = Join-Path $source $path
        [void](New-Item -ItemType Directory -Force -Path (Split-Path -Parent $target))
        Copy-Item -LiteralPath (Join-Path $repo $path) -Destination $target
        $hash = (Get-FileHash -LiteralPath $target -Algorithm SHA256).Hash
        $records.Add(($path + "`0" + $hash))
    }
    $bytes = [Text.Encoding]::UTF8.GetBytes(($records -join "`n"))
    $sha = [Security.Cryptography.SHA256]::Create()
    try { $fingerprint = [BitConverter]::ToString($sha.ComputeHash($bytes)).Replace('-', '') } finally { $sha.Dispose() }
    $complete = $true
    foreach ($name in @('graph.json', 'graph.html', 'GRAPH_REPORT.md')) {
        if (-not (Test-Path -LiteralPath (Join-Path $graphDir $name) -PathType Leaf)) { $complete = $false; break }
    }
    if (-not (Test-Path -LiteralPath (Join-Path $exportDir 'graph.canvas') -PathType Leaf)) { $complete = $false }
    if ($complete) {
        try {
            foreach ($entry in (ReadManifest $exportDir)) {
                if (-not (Test-Path -LiteralPath (SafeExportPath $exportDir $entry) -PathType Leaf)) { $complete = $false; break }
            }
        } catch { $complete = $false }
    }
    $previous = $null
    if (Test-Path -LiteralPath $statePath -PathType Leaf) {
        try { $previous = (Get-Content -LiteralPath $statePath -Raw -Encoding UTF8 | ConvertFrom-Json).fingerprint } catch { $previous = $null }
    }
    if ($complete -and $previous -eq $fingerprint) { Log 'No eligible source changes; graph and export are present.'; exit 0 }

    $existingGraph = Join-Path $graphDir 'graph.json'
    if ((Test-Path -LiteralPath $existingGraph -PathType Leaf) -and ((Get-Content -LiteralPath $existingGraph -Raw -Encoding UTF8) -match '"_origin"\s*:\s*"semantic"')) {
        throw 'Existing graph contains semantic data; refusing a code-only refresh that would discard it.'
    }
    Log 'Refreshing local code graph and Obsidian export.'
    $build = Join-Path $work 'build'
    $newGraph = Join-Path $build 'graphify-out'
    $freshExport = Join-Path $work 'export'
    [void](New-Item -ItemType Directory -Force -Path $build)
    RunGraphify @('extract', '.', '--code-only', '--out', $build, '--max-workers', '4') $source
    RunGraphify @('cluster-only', $build, '--no-label') $source
    if (-not (Test-Path -LiteralPath (Join-Path $newGraph 'graph.json') -PathType Leaf)) { throw 'Graphify did not produce graph.json.' }
    RunGraphify @('export', 'obsidian', '--graph', (Join-Path $newGraph 'graph.json'), '--dir', $freshExport) $source
    $newFiles = @(ReadManifest $freshExport)
    if (Test-Path -LiteralPath (Join-Path $freshExport 'graph.canvas') -PathType Leaf) { $newFiles += 'graph.canvas' }

    # Prepare a sibling directory, retaining everything not owned by the old manifest.
    # Refuse to replace a user file that happens to have a newly generated name.
    $prepared = Join-Path $vault ('.graphify-export-' + [guid]::NewGuid().ToString('N'))
    $exportBackup = Join-Path $vault ('.graphify-backup-' + [guid]::NewGuid().ToString('N'))
    $graphBackup = Join-Path $homeDir ('graphify-backup-' + [guid]::NewGuid().ToString('N'))
    [void](New-Item -ItemType Directory -Path $prepared)
    $oldFiles = New-Object 'System.Collections.Generic.HashSet[string]' ([StringComparer]::OrdinalIgnoreCase)
    try {
        if (Test-Path -LiteralPath $exportDir) {
            foreach ($entry in (ReadManifest $exportDir)) { [void](SafeExportPath $exportDir $entry); [void]$oldFiles.Add($entry.Replace('/', '\')) }
            if (Test-Path -LiteralPath (Join-Path $exportDir 'graph.canvas') -PathType Leaf) { [void]$oldFiles.Add('graph.canvas') }
            Copy-Item -Path (Join-Path $exportDir '*') -Destination $prepared -Recurse -Force
            # Copy-Item wildcard does not reliably include hidden files.
            Get-ChildItem -LiteralPath $exportDir -Force | Where-Object { $_.Attributes -band [IO.FileAttributes]::Hidden } | ForEach-Object { Copy-Item -LiteralPath $_.FullName -Destination $prepared -Recurse -Force }
        }
        foreach ($entry in $newFiles) {
            $dest = SafeExportPath $prepared $entry
            if ((Test-Path -LiteralPath $dest) -and -not $oldFiles.Contains($entry.Replace('/', '\'))) { throw "Export would overwrite an unowned file: $entry" }
        }
        foreach ($entry in $oldFiles) {
            $dest = SafeExportPath $prepared $entry
            if (Test-Path -LiteralPath $dest -PathType Leaf) { Remove-Item -LiteralPath $dest -Force }
        }
        foreach ($entry in $newFiles) {
            $dest = SafeExportPath $prepared $entry
            $from = SafeExportPath $freshExport $entry
            if (-not (Test-Path -LiteralPath $from -PathType Leaf)) { throw "Generated export is incomplete: $entry" }
            [void](New-Item -ItemType Directory -Force -Path (Split-Path -Parent $dest))
            Copy-Item -LiteralPath $from -Destination $dest -Force
        }
        Copy-Item -LiteralPath (Join-Path $freshExport '.graphify_obsidian_manifest.json') -Destination (Join-Path $prepared '.graphify_obsidian_manifest.json') -Force

        $graphMoved = $false; $exportMoved = $false; $graphInstalled = $false; $exportInstalled = $false
        try {
            if (Test-Path -LiteralPath $graphDir) { Move-Item -LiteralPath $graphDir -Destination $graphBackup; $graphMoved = $true }
            Move-Item -LiteralPath $newGraph -Destination $graphDir; $graphInstalled = $true
            if (Test-Path -LiteralPath $exportDir) { Move-Item -LiteralPath $exportDir -Destination $exportBackup; $exportMoved = $true }
            Move-Item -LiteralPath $prepared -Destination $exportDir; $exportInstalled = $true
            $stateTemp = Join-Path $homeDir ('refresh-state-' + [guid]::NewGuid().ToString('N') + '.json')
            @{ fingerprint = $fingerprint; refreshed = (Get-Date -Format o) } | ConvertTo-Json | Set-Content -LiteralPath $stateTemp -Encoding UTF8
            Move-Item -LiteralPath $stateTemp -Destination $statePath -Force
        } catch {
            if ($exportInstalled) { Remove-Item -LiteralPath $exportDir -Recurse -Force }
            if ($exportMoved) { Move-Item -LiteralPath $exportBackup -Destination $exportDir }
            if ($graphInstalled) { Remove-Item -LiteralPath $graphDir -Recurse -Force }
            if ($graphMoved) { Move-Item -LiteralPath $graphBackup -Destination $graphDir }
            throw
        }
        if ($exportMoved) { Remove-Item -LiteralPath $exportBackup -Recurse -Force }
        if ($graphMoved) { Remove-Item -LiteralPath $graphBackup -Recurse -Force }
    } finally {
        if (Test-Path -LiteralPath $prepared) { Remove-Item -LiteralPath $prepared -Recurse -Force }
    }
    Log 'Refresh completed.'
} catch {
    $message = "Refresh failed: $($_.Exception.Message)"
    try { Log $message } catch { Write-Error $message -ErrorAction Continue }
    exit 1
} finally {
    if ($work -and (Test-Path -LiteralPath $work)) { Remove-Item -LiteralPath $work -Recurse -Force -ErrorAction Continue }
    if ($lock) { $lock.Dispose() }
}
