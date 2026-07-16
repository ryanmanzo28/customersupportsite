param(
    [switch]$SkipVueBuild,
    [switch]$SkipPugCompile
)

$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $MyInvocation.MyCommand.Path
Set-Location $root

Write-Host '==> Building and starting Docker services (backend/frontend/db)...' -ForegroundColor Cyan
docker compose up -d --build

$pagesDir = Join-Path $root 'frontend/pages'
$pugOutDir = Join-Path $root 'frontend/public/pages'

if (-not $SkipPugCompile) {
    if (Test-Path $pagesDir) {
        Write-Host '==> Compiling Pug templates from frontend/pages to frontend/public/pages/*.html...' -ForegroundColor Cyan
        New-Item -ItemType Directory -Force -Path $pugOutDir | Out-Null

        # Remove stale compiled pages so deleted templates do not linger.
        Get-ChildItem -Path $pugOutDir -Filter '*.html' -File -ErrorAction SilentlyContinue | Remove-Item -Force

        $pugFiles = Get-ChildItem -Path $pagesDir -Filter '*.pug' -Recurse -File
        if ($pugFiles.Count -gt 0) {
            $compileFailures = 0
            foreach ($pugFile in $pugFiles) {
                npx --yes pug-cli "$($pugFile.FullName)" --basedir $pagesDir --out $pugOutDir
                if ($LASTEXITCODE -ne 0) {
                    $compileFailures++
                    Write-Host ("Failed to compile Pug file: " + $pugFile.FullName) -ForegroundColor Yellow
                }
            }

            if ($compileFailures -gt 0) {
                Write-Host ("Pug compile completed with " + $compileFailures + " failure(s).") -ForegroundColor Yellow
            }
        } else {
            Write-Host 'No .pug files found in frontend/pages. Skipping Pug compile.' -ForegroundColor Yellow
        }
    } else {
        Write-Host 'frontend/pages does not exist. Skipping Pug compile.' -ForegroundColor Yellow
    }
}

$appEntry = Join-Path $root 'frontend/app.html'
if (Test-Path $appEntry) {
    Copy-Item -Path $appEntry -Destination (Join-Path $pugOutDir 'app.html') -Force

    $appHtml = Join-Path $pugOutDir 'app.html'
    if (Test-Path $appHtml) {
        $content = Get-Content -Path $appHtml -Raw
        $content = $content -replace '<title>[^<]*</title>', '<title>Support Dashboard</title>'
        Set-Content -Path $appHtml -Value $content -Encoding utf8
    }
}

Push-Location (Join-Path $root 'frontend')
try {
    if (Test-Path 'package-lock.json') {
        Write-Host '==> Installing frontend dependencies with npm ci...' -ForegroundColor Cyan
        npm ci
    } else {
        Write-Host '==> Installing frontend dependencies with npm install...' -ForegroundColor Cyan
        npm install
    }

    if (-not $SkipVueBuild) {
        Write-Host '==> Building Vue app with Vite...' -ForegroundColor Cyan
        npm run build
    }
}
finally {
    Pop-Location
}

Write-Host '==> Service status:' -ForegroundColor Cyan
docker compose ps

Write-Host ''
Write-Host 'Startup complete.' -ForegroundColor Green
Write-Host 'Frontend dev server: http://localhost:5173'
Write-Host 'Backend:            http://localhost:8080'
Write-Host 'API:                http://localhost:8080/api/tickets.json'
