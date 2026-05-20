# Build APK release + rename ke nama branded
# Usage (di mobile_fresh): powershell -ExecutionPolicy Bypass -File build_apk.ps1
Write-Host "=== Build IPL APK (release) ===" -ForegroundColor Cyan

flutter clean
if ($LASTEXITCODE -ne 0) { Write-Host "❌ flutter clean gagal" -ForegroundColor Red; exit 1 }

flutter pub get
if ($LASTEXITCODE -ne 0) { Write-Host "❌ flutter pub get gagal" -ForegroundColor Red; exit 1 }

# Regenerate launcher icon (kalau logo.png berubah)
Write-Host "→ Generate launcher icon..." -ForegroundColor Cyan
dart run flutter_launcher_icons

# Regenerate native splash screen
Write-Host "→ Generate native splash..." -ForegroundColor Cyan
dart run flutter_native_splash:create

flutter build apk --release
if ($LASTEXITCODE -ne 0) { Write-Host "❌ flutter build gagal" -ForegroundColor Red; exit 1 }

# Rename hasil APK
$apkDir = "build\app\outputs\flutter-apk"
$src = Join-Path $apkDir "app-release.apk"
$dst = Join-Path $apkDir "IPL_Griya_Pesona_Madani.apk"

if (Test-Path $src) {
    if (Test-Path $dst) { Remove-Item $dst -Force }
    Move-Item $src $dst
    Write-Host ""
    Write-Host "✅ APK siap di: $dst" -ForegroundColor Green
    Write-Host "   Ukuran: $((Get-Item $dst).Length / 1MB) MB" -ForegroundColor Gray
} elseif (Test-Path $dst) {
    # Variant API sudah rename — sudah benar
    Write-Host ""
    Write-Host "✅ APK siap di: $dst" -ForegroundColor Green
    Write-Host "   Ukuran: $((Get-Item $dst).Length / 1MB) MB" -ForegroundColor Gray
} else {
    Write-Host "⚠️  APK tidak ditemukan di $apkDir" -ForegroundColor Yellow
    Get-ChildItem $apkDir
}
