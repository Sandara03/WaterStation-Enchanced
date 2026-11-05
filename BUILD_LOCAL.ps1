$ErrorActionPreference = 'Stop'
# Optional: serve the compiled UI and API together on Laravel's local port.
$previousBuildFlag = $env:AHY_COMBINED_BUILD
Push-Location (Join-Path $PSScriptRoot 'frontend')
try {
    npm.cmd run typecheck
    if ($LASTEXITCODE -ne 0) { throw 'Type checking failed.' }
    $env:AHY_COMBINED_BUILD = '1'
    npm.cmd run build
    if ($LASTEXITCODE -ne 0) { throw 'Frontend build failed.' }
    $destination = Join-Path $PSScriptRoot 'backend/public/app'
    New-Item -ItemType Directory -Path $destination -Force | Out-Null
    Copy-Item -Path 'dist/spa/*' -Destination $destination -Recurse -Force
    Write-Host 'Built UI copied. Run START_BACKEND.ps1 and open http://127.0.0.1:8000.'
} finally { $env:AHY_COMBINED_BUILD = $previousBuildFlag; Pop-Location }
