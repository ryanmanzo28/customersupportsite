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
$servedOutDir = Join-Path $root 'frontend/public/pages'
$vueTempOutDir = Join-Path $root 'frontend/.startup-build'

# This is the single served folder for startup-generated frontend artifacts.
if (Test-Path $servedOutDir) {
    Remove-Item -LiteralPath $servedOutDir -Recurse -Force
}
New-Item -ItemType Directory -Force -Path $servedOutDir | Out-Null

if (-not $SkipPugCompile) {
    if (Test-Path $pagesDir) {
        Write-Host '==> Compiling Pug templates from frontend/pages to frontend/public/pages/*.html...' -ForegroundColor Cyan

        $pugFiles = Get-ChildItem -Path $pagesDir -Filter '*.pug' -Recurse -File
        if ($pugFiles.Count -gt 0) {
            $compileFailures = 0
            foreach ($pugFile in $pugFiles) {
                npx --yes pug-cli "$($pugFile.FullName)" --basedir $pagesDir --out $servedOutDir
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
        Write-Host '==> Building Vue app with Vite (temp output, then syncing to frontend/public/pages)...' -ForegroundColor Cyan
        if (Test-Path $vueTempOutDir) {
            Remove-Item -LiteralPath $vueTempOutDir -Recurse -Force
        }

        npm run build -- --outDir .startup-build --emptyOutDir true

        if (Test-Path $vueTempOutDir) {
            $vueIndex = Join-Path $vueTempOutDir 'index.html'
            if (Test-Path $vueIndex) {
                Copy-Item -Path $vueIndex -Destination (Join-Path $servedOutDir 'index.html') -Force
            }

            $vueAssetsDir = Join-Path $vueTempOutDir 'assets'
            if (Test-Path $vueAssetsDir) {
                Copy-Item -Path $vueAssetsDir -Destination (Join-Path $servedOutDir 'assets') -Recurse -Force
            }

            Remove-Item -LiteralPath $vueTempOutDir -Recurse -Force
        }
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
Write-Host ('Generated files:    ' + $servedOutDir)
