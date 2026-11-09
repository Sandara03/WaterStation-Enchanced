param([ValidateRange(1024,65535)][int]$Port=8000)
$ErrorActionPreference = 'Stop'
Push-Location (Join-Path $PSScriptRoot 'backend')
try {
    Write-Host 'Keep the phone and computer on the same Wi-Fi. Keep this terminal open.'
    Write-Host "In the APK, use http://YOUR_COMPUTER_WIFI_IP:$Port/api."
    Write-Host 'This Laravel development server listens on the local network for the classroom demo.'
    php artisan serve --host=0.0.0.0 --port=$Port
} finally { Pop-Location }
