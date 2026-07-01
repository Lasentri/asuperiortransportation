@echo off
:: wakeup-claude.bat - Double-click to launch your full Claude dev environment
:: Or right-click → "Run as administrator" if PuTTY session needs elevation

powershell.exe -ExecutionPolicy Bypass -File "%~dp0wakeup-claude.ps1"
pause
