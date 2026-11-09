$ErrorActionPreference = 'Stop'
Get-Command php,composer,npm.cmd -ErrorAction Stop | Out-Null
Push-Location (Join-Path $PSScriptRoot 'backend')
try {
    composer run setup
    if ($LASTEXITCODE -ne 0) { throw 'Setup failed. Review the error above before starting the servers.' }
    Write-Host 'Administrator: Admin@Email.com / Admin@123456 (fresh local database)'
    Write-Host 'Terminal 1: cd backend; php artisan serve'
    Write-Host 'Terminal 2: cd frontend; npm run dev'
    Write-Host 'Open http://127.0.0.1:9000.'
} finally { Pop-Location }
