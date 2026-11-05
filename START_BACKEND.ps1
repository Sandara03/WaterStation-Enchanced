$ErrorActionPreference = 'Stop'
Set-Location (Join-Path $PSScriptRoot 'backend')
if (-not (Test-Path '.env') -or -not (Test-Path 'vendor/autoload.php')) { throw 'Run SETUP.ps1 first, or follow README.md.' }
php artisan serve --host=127.0.0.1 --port=8000
