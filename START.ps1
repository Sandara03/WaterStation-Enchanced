param([ValidateRange(1024,65535)][int]$Port = 8000)
$ErrorActionPreference = 'Stop'
Get-Command php -ErrorAction Stop | Out-Null
Push-Location (Join-Path $PSScriptRoot 'backend')
try {
    foreach ($requiredFile in @('.env','vendor/autoload.php','public/app/index.html')) {
        if (-not (Test-Path -LiteralPath $requiredFile)) {
            throw 'Run composer run setup in backend first, then BUILD_LOCAL.ps1 in the root for this optional combined build. For development, use php artisan serve and npm run dev instead.'
        }
    }
    $router = Join-Path (Get-Location).Path 'vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php'
    if (-not (Test-Path -LiteralPath $router)) { throw 'Laravel server files are missing. Run composer install in backend.' }
    Write-Host "Open http://127.0.0.1:$Port in your browser. Keep this terminal running; Ctrl+C stops the website."
    Set-Location public
    php -S "127.0.0.1:$Port" $router
    if ($LASTEXITCODE -ne 0) { throw 'The local server stopped with an error. Check the message above.' }
} finally { Pop-Location }
