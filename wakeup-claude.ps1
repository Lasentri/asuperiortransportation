# ============================================================
#  wakeup-claude.ps1
#  Run this (or say "wakeup claude") to restore your full
#  Superior Transportation dev environment.
# ============================================================

Write-Host "Waking up Claude environment..." -ForegroundColor Cyan

# --- Paths ---
$puttyExe   = "G:\Program Files\putty.exe"
$winscpExe  = "G:\Program Files\WinSCP\WinSCP.exe"
$claudeExe  = "$env:LOCALAPPDATA\Claude\Claude.exe"   # adjust if needed
$repoPath   = "C:\Users\ceric\Desktop\All Things Superior\superiortransportation"

# --- 1. PuTTY → lasentri.com ---
Write-Host "  [1/4] Opening PuTTY (lasentri.com)..." -ForegroundColor Green
if (Test-Path $puttyExe) {
    Start-Process $puttyExe -ArgumentList "-load `"lasentri.com`""
} else {
    Write-Warning "PuTTY not found at $puttyExe"
}
Start-Sleep -Seconds 1

# --- 2. WinSCP → root@farm2gut.com ---
Write-Host "  [2/4] Opening WinSCP (root@farm2gut.com)..." -ForegroundColor Green
if (Test-Path $winscpExe) {
    Start-Process $winscpExe -ArgumentList `
        "sftp://root@farm2gut.com/var/www/asuperiortransportation.com/wp-content/"
} else {
    Write-Warning "WinSCP not found at $winscpExe"
}
Start-Sleep -Seconds 1

# --- 3. Open repo folder in File Explorer ---
Write-Host "  [3/4] Opening superiortransportation folder..." -ForegroundColor Green
Start-Process explorer.exe -ArgumentList "`"$repoPath`""
Start-Sleep -Seconds 1

# --- 4. Claude desktop app ---
Write-Host "  [4/4] Opening Claude..." -ForegroundColor Green
if (Test-Path $claudeExe) {
    Start-Process $claudeExe
} else {
    # Fallback: try Start Menu launch
    Start-Process "shell:AppsFolder\AnthropicPBC.Claude_*!Claude" -ErrorAction SilentlyContinue
    if ($LASTEXITCODE -ne 0) {
        Write-Warning "Claude not found at $claudeExe — open it manually."
    }
}

Write-Host ""
Write-Host "Environment launched! Say 'wakeup claude' in Claude to continue where you left off." -ForegroundColor Cyan
