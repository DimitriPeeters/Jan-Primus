Option Explicit

Dim shell, command
Set shell = CreateObject("WScript.Shell")
command = """C:\WINDOWS\System32\WindowsPowerShell\v1.0\powershell.exe"" -NoProfile -NonInteractive -ExecutionPolicy Bypass -File ""C:\laragon\www\aefs-v2\bin\run-mail-worker.ps1"" -PhpPath ""C:\laragon\bin\php\php-8.4.23-nts-Win32-vs17-x64\php.exe"" -Limit 25"
shell.Run command, 0, True
