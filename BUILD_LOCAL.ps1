$ErrorActionPreference = 'Stop'
# Optional: serve the compiled UI and API together on Laravel's local port.
$previousBuildFlag = $env:AHY_COMBINED_BUILD
Push-Location (Join-Path $PSScriptRoot 'frontend')
try {
    npm.cmd run typecheck
    if ($LASTEXITCODE -ne 0) { throw 'Type checking failed.' }
    npm.cmd test
    if ($LASTEXITCODE -ne 0) { throw 'Checkout tests failed.' }
    $env:AHY_COMBINED_BUILD = '1'
    npm.cmd run build
    if ($LASTEXITCODE -ne 0) { throw 'Frontend build failed.' }
    $destination = Join-Path $PSScriptRoot 'backend/public/app'
    New-Item -ItemType Directory -Path $destination -Force | Out-Null
    Copy-Item -Path 'dist/spa/*' -Destination $destination -Recurse -Force
    Write-Host 'Built UI copied. For a phone, run START_PHONE_BACKEND.ps1 from the root, or php artisan serve --host=0.0.0.0 --port=8001 in backend.'
    Write-Host 'On the phone, open http://YOUR_COMPUTER_WIFI_IP:8001/ in Safari (the website URL, without /api).'
} finally { $env:AHY_COMBINED_BUILD = $previousBuildFlag; Pop-Location }
