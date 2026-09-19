@echo off
cd /d "%~dp0.."
echo.
echo OrgChain Chain Node — helper laptop
echo Only needs Node.js + ngrok token
echo.
powershell -ExecutionPolicy Bypass -File ".\scripts\start-chain-node.ps1" %*
pause
