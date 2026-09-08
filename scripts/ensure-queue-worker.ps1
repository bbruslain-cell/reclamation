$ErrorActionPreference = 'Stop'

$projectRoot = Split-Path -Parent $PSScriptRoot
$phpPath = (Get-Command php).Source
$outLog = Join-Path $projectRoot 'storage\logs\queue-worker.out.log'
$errLog = Join-Path $projectRoot 'storage\logs\queue-worker.err.log'
$managerLog = Join-Path $projectRoot 'storage\logs\queue-worker-manager.log'

function Write-ManagerLog {
    param(
        [string] $Message
    )

    $timestamp = Get-Date -Format 'yyyy-MM-dd HH:mm:ss'
    Add-Content -Path $managerLog -Value "$timestamp $Message"
}

$runningWorkers = Get-CimInstance Win32_Process -Filter "name = 'php.exe'" |
    Where-Object {
        $_.CommandLine -like '*artisan queue:work*' -and
        $_.ExecutablePath -eq $phpPath
    } |
    Sort-Object CreationDate

$runningWorker = $runningWorkers | Select-Object -First 1

if ($null -ne $runningWorker) {
    Write-ManagerLog "Worker already running (PID $($runningWorker.ProcessId))."
    exit 0
}

$arguments = @(
    'artisan',
    'queue:work',
    '--tries=3',
    '--sleep=1',
    '--timeout=120'
)

$process = Start-Process `
    -FilePath $phpPath `
    -ArgumentList $arguments `
    -WorkingDirectory $projectRoot `
    -WindowStyle Hidden `
    -RedirectStandardOutput $outLog `
    -RedirectStandardError $errLog `
    -PassThru

Write-ManagerLog "Started worker PID $($process.Id)."
