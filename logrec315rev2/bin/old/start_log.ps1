mode con: cols=50 lines=5
cd ..\logrecV4
$program = ".\php-cli.exe"
$programArgs = "recorder.php", "-f", 1
Invoke-Command -ScriptBlock { & $program $programArgs }