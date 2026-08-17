#Requires -Version 5.1

[CmdletBinding()]
param(
    [Parameter(Mandatory = $true)]
    [string] $PhpPath,

    [ValidateRange(1, 250)]
    [int] $Limit = 25
)

$ErrorActionPreference = 'Stop'

$projectRoot = Split-Path -Parent $PSScriptRoot
$workerPath = Join-Path $PSScriptRoot 'process-mail-queue.php'
$logDirectory = Join-Path $projectRoot 'storage\logs'
$logPath = Join-Path $logDirectory 'mail-worker.log'

function Write-WorkerLog {
    param(
        [Parameter(Mandatory = $true)]
        [string] $Message
    )

    if (-not (Test-Path -LiteralPath $logDirectory -PathType Container)) {
        New-Item -ItemType Directory -Path $logDirectory -Force | Out-Null
    }

    $timestamp = Get-Date -Format 'dd/MM/yyyy HH:mm:ss'
    Add-Content -LiteralPath $logPath -Encoding UTF8 -Value "[$timestamp] $Message"
}

try {
    $resolvedPhpPath = (Resolve-Path -LiteralPath $PhpPath).Path

    if (-not (Test-Path -LiteralPath $resolvedPhpPath -PathType Leaf)) {
        throw 'Het opgegeven PHP-programma bestaat niet.'
    }

    if (-not (Test-Path -LiteralPath $workerPath -PathType Leaf)) {
        throw 'De AEFS-mailworker ontbreekt.'
    }

    $output = @(
        & $resolvedPhpPath $workerPath "--limit=$Limit" 2>&1
    ) | ForEach-Object { $_.ToString() }
    $exitCode = $LASTEXITCODE
    $summary = ($output -join ' ').Trim()

    if ($exitCode -ne 0) {
        throw "De mailworker eindigde met exitcode $exitCode. $summary"
    }

    if ($summary -notmatch 'Mailqueue verwerkt:\s+0 behandeld') {
        Write-WorkerLog -Message $summary
    }

    exit 0
} catch {
    Write-WorkerLog -Message ('FOUT: ' + $_.Exception.Message)
    exit 1
}
