@echo off
setlocal
cd /d "%~dp0.."

echo.
echo  OrgChain — Run All Tests
echo  (backend + auth/OTP + page smoke + DOCX report)
echo.

where node >nul 2>&1
if errorlevel 1 (
  echo ERROR: Node.js not found. Open Laragon / install Node, then retry.
  exit /b 2
)

where php >nul 2>&1
if errorlevel 1 (
  echo ERROR: PHP not found. Open Laragon terminal, then retry.
  exit /b 2
)

REM Pass-through flags: --open  --skip-ui  --skip-pages  --base http://127.0.0.1:8000
node scripts\run-all-tests.mjs %*
set EXITCODE=%ERRORLEVEL%

echo.
if %EXITCODE%==0 (
  echo DONE — report: storage\app\test-reports\OrgChain-Test-Report-LATEST.docx
) else (
  echo FAILED — see console + storage\app\test-reports\
)

exit /b %EXITCODE%
