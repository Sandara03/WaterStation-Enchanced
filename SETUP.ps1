param([switch]$SeedDemo)
$ErrorActionPreference = 'Stop'
function Run-Step([scriptblock]$Command) {
    & $Command
    if ($LASTEXITCODE -ne 0) { throw 'Setup stopped because the previous command failed.' }
}
Get-Command php,composer,npm.cmd -ErrorAction Stop | Out-Null
Push-Location $PSScriptRoot
try {
    Set-Location backend
    $dirs = @('bootstrap/cache','storage/logs','storage/framework/cache/data','storage/framework/sessions','storage/framework/views')
    foreach ($dir in $dirs) { New-Item -ItemType Directory -Path $dir -Force | Out-Null }
    Run-Step { composer install --no-interaction --prefer-dist }
    if (-not (Test-Path '.env')) { Copy-Item -LiteralPath '.env.example' -Destination '.env' }
    $envContent = Get-Content -LiteralPath '.env' -Raw
    if ($envContent -match '(?m)^APP_KEY=\s*$') { Run-Step { php artisan key:generate } }
    if ($envContent -match '(?m)^DB_CONNECTION=sqlite\s*$' -and -not (Test-Path 'database/database.sqlite')) {
        New-Item -ItemType File -Path 'database/database.sqlite' | Out-Null
    }
    Run-Step { php artisan config:clear }
    Run-Step { php artisan migrate }
    if ($SeedDemo) { Run-Step { php artisan db:seed } }
    Set-Location ../frontend
    Run-Step { npm.cmd ci --no-audit --no-fund }
    Run-Step { npm.cmd run typecheck }
    Run-Step { npm.cmd run build }
    Write-Host 'Setup complete. Create your account: cd backend; php artisan staff:create YOUR_EMAIL --role=admin'
    Write-Host 'Then run START_BACKEND.ps1 and START_FRONTEND.ps1 in separate terminals. Open http://127.0.0.1:9000.'
} finally { Pop-Location }
