param(
    [string]$ApiUrl = '',
    [string]$JavaHomePath = $env:JAVA_HOME,
    [string]$AndroidSdkPath = (Join-Path $env:LOCALAPPDATA 'Android/Sdk')
)
$ErrorActionPreference = 'Stop'
function Run-Step([scriptblock]$Command) {
    & $Command
    if ($LASTEXITCODE -ne 0) { throw 'Android build stopped. Review the error above.' }
}
if (-not $JavaHomePath -or -not (Test-Path -LiteralPath (Join-Path $JavaHomePath 'bin/java.exe'))) {
    throw 'Set JAVA_HOME to JDK 21, or pass -JavaHomePath with the JDK 21 folder.'
}
$javaVersion = (& (Join-Path $JavaHomePath 'bin/java.exe') -version 2>&1 | Out-String)
if ($javaVersion -notmatch 'version "(2[1-4])\.') { throw 'This Gradle version needs JDK 21-24. Use JDK 21; Android Studio JBR 25 is not compatible.' }
foreach ($item in @('platforms/android-36/android.jar','build-tools/35.0.0/aapt2.exe')) {
    if (-not (Test-Path -LiteralPath (Join-Path $AndroidSdkPath $item))) { throw "Install Android API 36 and Build-Tools 35.0.0 in SDK Manager, or pass -AndroidSdkPath. Missing: $item" }
}
if ($ApiUrl) {
    $serverUri = $null
    if (-not [uri]::TryCreate($ApiUrl, [UriKind]::Absolute, [ref]$serverUri) -or $serverUri.Scheme -notin 'http','https' -or $serverUri.IsLoopback -or $serverUri.UserInfo -or $serverUri.Query -or $serverUri.Fragment) {
        throw 'Use a complete reachable API address, e.g. http://192.168.1.10:8000/api. Do not use localhost.'
    }
}
$previous = @{ JAVA_HOME=$env:JAVA_HOME; ANDROID_HOME=$env:ANDROID_HOME; AHY_API_URL=$env:AHY_API_URL; AHY_COMBINED_BUILD=$env:AHY_COMBINED_BUILD }
Push-Location $PSScriptRoot
try {
    $env:JAVA_HOME = (Resolve-Path -LiteralPath $JavaHomePath).Path
    $env:ANDROID_HOME = (Resolve-Path -LiteralPath $AndroidSdkPath).Path
    $env:AHY_API_URL = $ApiUrl
    $env:AHY_COMBINED_BUILD = '0'
    Set-Location frontend
    Run-Step { npm.cmd ci --include=dev --no-audit --no-fund }
    Run-Step { npm.cmd test }
    Run-Step { npm.cmd run typecheck }
    Set-Location src-capacitor
    Run-Step { npm.cmd ci --include=dev --no-audit --no-fund }
    Set-Location ..
    Run-Step { npm.cmd exec -- quasar build -m capacitor -T android --skip-pkg }
    Set-Location src-capacitor/android
    [IO.File]::WriteAllText((Join-Path (Get-Location) 'local.properties'), ('sdk.dir='+$env:ANDROID_HOME.Replace('\','/')+"`n"))
    Run-Step { ./gradlew.bat assembleDebug --no-daemon --max-workers=2 }
    $destination = Join-Path $PSScriptRoot 'artifacts'
    New-Item -ItemType Directory -Path $destination -Force | Out-Null
    Copy-Item -LiteralPath 'app/build/outputs/apk/debug/app-debug.apk' -Destination (Join-Path $destination 'AHY_WaterStation-debug.apk') -Force
    $phoneDownload = Join-Path $PSScriptRoot 'backend/public/apk'
    New-Item -ItemType Directory -Path $phoneDownload -Force | Out-Null
    Copy-Item -LiteralPath 'app/build/outputs/apk/debug/app-debug.apk' -Destination (Join-Path $phoneDownload 'AHY_WaterStation.apk') -Force
    $appVersion = (Get-Content -LiteralPath (Join-Path $PSScriptRoot 'frontend/package.json') -Raw | ConvertFrom-Json).version
    Copy-Item -LiteralPath 'app/build/outputs/apk/debug/app-debug.apk' -Destination (Join-Path $phoneDownload "AHY_WaterStation_v$appVersion.apk") -Force
    Write-Host "APK ready: $destination/AHY_WaterStation-debug.apk"
} finally {
    foreach ($entry in $previous.GetEnumerator()) { [Environment]::SetEnvironmentVariable($entry.Key, $entry.Value, 'Process') }
    Pop-Location
}
