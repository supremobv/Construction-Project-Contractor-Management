$ErrorActionPreference = 'Stop'
Push-Location -LiteralPath (Join-Path $PSScriptRoot 'app')
try { & php artisan serve --host=127.0.0.1 --port=8000 } finally { Pop-Location }
