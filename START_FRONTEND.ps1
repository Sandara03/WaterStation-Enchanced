$ErrorActionPreference = 'Stop'
Set-Location (Join-Path $PSScriptRoot 'frontend')
if (-not (Test-Path 'node_modules')) { throw 'Run SETUP.ps1 first, or follow README.md.' }
npm.cmd run dev
