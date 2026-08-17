#Requires -Version 5.1

[CmdletBinding()]
param(
    [Parameter(Mandatory = $true)]
    [string] $PhpPath,

    [ValidateRange(1, 60)]
    [int] $IntervalMinutes = 1,

    [ValidateRange(1, 250)]
    [int] $Limit = 25,

    [ValidateNotNullOrEmpty()]
    [string] $TaskName = 'AEFS v2 Mail Queue (Local)'
)

$ErrorActionPreference = 'Stop'

$resolvedPhpPath = (Resolve-Path -LiteralPath $PhpPath).Path
$projectRoot = Split-Path -Parent $PSScriptRoot
$runnerPath = Join-Path $PSScriptRoot 'run-mail-worker.ps1'

if (-not (Test-Path -LiteralPath $resolvedPhpPath -PathType Leaf)) {
    throw 'Het opgegeven PHP-programma bestaat niet.'
}

if (-not (Test-Path -LiteralPath $runnerPath -PathType Leaf)) {
    throw 'Het PowerShell-startscript voor de mailworker ontbreekt.'
}

$powerShellPath = (Get-Command powershell.exe -ErrorAction Stop).Source
$arguments = @(
    '-NoProfile'
    '-NonInteractive'
    '-ExecutionPolicy Bypass'
    '-File "' + $runnerPath + '"'
    '-PhpPath "' + $resolvedPhpPath + '"'
    '-Limit ' + $Limit
) -join ' '

$actionParameters = @{
    Execute = $powerShellPath
    Argument = $arguments
    WorkingDirectory = $projectRoot
}
$action = New-ScheduledTaskAction @actionParameters

$triggerParameters = @{
    Once = $true
    At = (Get-Date).AddMinutes(1)
    RepetitionInterval = (New-TimeSpan -Minutes $IntervalMinutes)
    RepetitionDuration = (New-TimeSpan -Days 3650)
}
$trigger = New-ScheduledTaskTrigger @triggerParameters

$settingsParameters = @{
    MultipleInstances = 'IgnoreNew'
    StartWhenAvailable = $true
    AllowStartIfOnBatteries = $true
    DontStopIfGoingOnBatteries = $true
    ExecutionTimeLimit = (New-TimeSpan -Minutes 10)
}
$settings = New-ScheduledTaskSettingsSet @settingsParameters

$currentUser = [System.Security.Principal.WindowsIdentity]::GetCurrent().Name
$principalParameters = @{
    UserId = $currentUser
    LogonType = 'Interactive'
    RunLevel = 'Limited'
}
$principal = New-ScheduledTaskPrincipal @principalParameters

$taskParameters = @{
    TaskName = $TaskName
    Action = $action
    Trigger = $trigger
    Settings = $settings
    Principal = $principal
    Description = 'Verwerkt de lokale AEFS-mailwachtrij zolang de ingestelde Windows-gebruiker is aangemeld.'
    Force = $true
}
$task = Register-ScheduledTask @taskParameters

Write-Output (
    'Windows-taak "{0}" geregistreerd voor {1}; interval {2} minuut/minuten, maximaal {3} ontvangers per run.' -f
        $task.TaskName,
        $currentUser,
        $IntervalMinutes,
        $Limit
)
