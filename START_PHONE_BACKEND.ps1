param([ValidateRange(1024,65535)][int]$Port=8001)
$ErrorActionPreference = 'Stop'
Push-Location (Join-Path $PSScriptRoot 'backend')
try {
    if (-not (Test-Path -LiteralPath '.env') -or -not (Test-Path -LiteralPath 'vendor/autoload.php')) { throw 'Run composer run setup inside backend first.' }
    if (-not (Test-Path -LiteralPath 'public/app/index.html')) { throw 'Run BUILD_LOCAL.ps1 from the project root to prepare the iPhone website first.' }
    Write-Host 'Keep the computer on and both devices on the same Wi-Fi. Keep this terminal open.'
    Write-Host "iPhone Safari: http://YOUR_COMPUTER_WIFI_IP:$Port/"
    Write-Host "Android APK API setting: http://YOUR_COMPUTER_WIFI_IP:$Port/api"
    php artisan serve --host=0.0.0.0 --port=$Port
} finally { Pop-Location }
