param([switch]$Demo)
$ErrorActionPreference = 'Stop'
$projectDirectory = Join-Path $PSScriptRoot 'app'
Push-Location -LiteralPath $projectDirectory
try {
    if (!(Get-Command php -ErrorAction SilentlyContinue) -or !(Get-Command composer -ErrorAction SilentlyContinue)) { throw 'PHP 8.2+ and Composer 2 are required. Run this from Laragon Terminal.' }
    & composer install --no-interaction --prefer-dist
    if ($LASTEXITCODE -ne 0) { throw 'Composer install failed.' }
    if (!(Test-Path -LiteralPath '.env')) { Copy-Item -LiteralPath '.env.example' -Destination '.env' }
    $configuration = Get-Content -LiteralPath '.env' -Raw
    if ($configuration -match '(?m)^APP_KEY=\s*$') { & php artisan key:generate; if ($LASTEXITCODE -ne 0) { throw 'Key generation failed.' } }
    & php artisan config:clear
    & php scripts/create-database.php
    if ($LASTEXITCODE -ne 0) { throw 'Database setup failed. Update app/.env with your Laragon credentials.' }
    & php artisan migrate --seed --force
    if ($LASTEXITCODE -ne 0) { throw 'Migrations failed.' }
    if ($Demo) { & php artisan db:seed --class=DemoSeeder; if ($LASTEXITCODE -ne 0) { throw 'Demo seeding failed.' } }
    Write-Host 'Ready. Start with ./start.ps1. For a fresh business, create an admin with: php artisan workspace:admin your@email.com (inside app).'
} finally { Pop-Location }
