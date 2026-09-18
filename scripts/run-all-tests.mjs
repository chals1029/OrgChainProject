/**
 * One-shot OrgChain test runner.
 *
 * Runs:
 *   1) Backend PHPUnit (Feature + Unit, incl. Auth/OTP)
 *   2) Auth/OTP Playwright journey (office + student)
 *   3) Console page smoke (all roles)
 *   4) Detailed DOCX report under storage/app/test-reports/
 *
 * Usage:
 *   npm run test:all
 *   node scripts/run-all-tests.mjs
 *   node scripts/run-all-tests.mjs --open          # open LATEST.docx when done
 *   node scripts/run-all-tests.mjs --skip-ui       # backend + DOCX only
 *   node scripts/run-all-tests.mjs --skip-pages    # backend + auth only
 *   node scripts/run-all-tests.mjs --base http://127.0.0.1:8000
 */
import { spawnSync } from 'child_process';
import fs from 'fs';
import http from 'http';
import https from 'https';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ROOT = path.resolve(__dirname, '..');
const args = process.argv.slice(2);
const openDocx = args.includes('--open');
const baseArg = args.find((a) => a.startsWith('--base='))?.slice(7)
  || (args.includes('--base') ? args[args.indexOf('--base') + 1] : null);
const BASE = baseArg || process.env.ORGCHAIN_BASE_URL || 'http://127.0.0.1:8000';

const passThrough = args.filter(
  (a, i) =>
    a !== '--open' &&
    a !== '--base' &&
    !a.startsWith('--base=') &&
    !(args[i - 1] === '--base' && !a.startsWith('-'))
);

function banner(title) {
  const line = '='.repeat(56);
  console.log(`\n${line}\n  ${title}\n${line}`);
}

function check(cmd, label) {
  const r = spawnSync(cmd, { cwd: ROOT, shell: true, encoding: 'utf8' });
  const ok = (r.status ?? 1) === 0;
  console.log(`  [${ok ? 'OK' : 'FAIL'}] ${label}`);
  if (!ok && r.stderr) console.log(`         ${r.stderr.trim().split('\n')[0]}`);
  return ok;
}

function probeUrl(url, timeoutMs = 4000) {
  return new Promise((resolve) => {
    let done = false;
    const finish = (ok, detail) => {
      if (done) return;
      done = true;
      resolve({ ok, detail });
    };
    try {
      const lib = url.startsWith('https') ? https : http;
      const req = lib.get(url, { timeout: timeoutMs }, (res) => {
        res.resume();
        finish(true, `HTTP ${res.statusCode}`);
      });
      req.on('timeout', () => {
        req.destroy();
        finish(false, 'timeout');
      });
      req.on('error', (e) => finish(false, e.message));
    } catch (e) {
      finish(false, e.message);
    }
  });
}

function readEnvFlag(key) {
  const envPath = path.join(ROOT, '.env');
  if (!fs.existsSync(envPath)) return null;
  const line = fs
    .readFileSync(envPath, 'utf8')
    .split(/\r?\n/)
    .find((l) => l.startsWith(`${key}=`));
  if (!line) return null;
  return line.slice(key.length + 1).trim().replace(/^["']|["']$/g, '');
}

async function preflight() {
  banner('OrgChain · Run All Tests — preflight');
  console.log(`  Base URL: ${BASE}`);
  console.log(`  Root:     ${ROOT}`);

  const phpOk = check('php -v', 'PHP available');
  const nodeOk = check('node -v', 'Node available');
  if (!phpOk || !nodeOk) {
    console.error('\nPreflight failed. Install PHP/Node (Laragon) and retry.');
    process.exit(2);
  }

  const skipUi = passThrough.includes('--skip-ui');
  if (!skipUi) {
    const probe = await probeUrl(BASE);
    console.log(`  [${probe.ok ? 'OK' : 'WARN'}] App reachable (${probe.detail})`);
    if (!probe.ok) {
      console.warn(`\n  Start the app first, then re-run:`);
      console.warn(`    php artisan serve --port=8000`);
      console.warn(`  Auth/OTP + page smoke need a live server at ${BASE}\n`);
      process.exit(2);
    }

    const expose = readEnvFlag('EXPOSE_TEST_OTP');
    if (expose !== 'true' && expose !== '1') {
      console.warn(
        '  [WARN] EXPOSE_TEST_OTP is not true — student OTP UI verify may fail.\n' +
          '         Set EXPOSE_TEST_OTP=true in .env and restart php artisan serve.'
      );
    } else {
      console.log('  [OK] EXPOSE_TEST_OTP enabled (OTP UI verify can read debug_code)');
    }
  }

  return true;
}

function openLatestReport() {
  const latest = path.join(ROOT, 'storage', 'app', 'test-reports', 'OrgChain-Test-Report-LATEST.docx');
  const stamped = fs
    .readdirSync(path.join(ROOT, 'storage', 'app', 'test-reports'))
    .filter((f) => /^OrgChain-Test-Report-\d{8}-\d{4}\.docx$/.test(f))
    .sort()
    .reverse()[0];
  const target = fs.existsSync(latest)
    ? latest
    : stamped
      ? path.join(ROOT, 'storage', 'app', 'test-reports', stamped)
      : null;
  if (!target) return;
  if (process.platform === 'win32') {
    spawnSync('cmd', ['/c', 'start', '', target], { cwd: ROOT, shell: false });
  } else if (process.platform === 'darwin') {
    spawnSync('open', [target], { cwd: ROOT });
  } else {
    spawnSync('xdg-open', [target], { cwd: ROOT });
  }
  console.log(`Opened: ${target}`);
}

async function main() {
  await preflight();

  banner('Running full suite (backend · auth/OTP · pages · DOCX)');
  const reportScript = path.join(ROOT, 'scripts', 'run-tests-and-report.mjs');
  const env = { ...process.env, ORGCHAIN_BASE_URL: BASE };
  const result = spawnSync(process.execPath, [reportScript, ...passThrough], {
    cwd: ROOT,
    env,
    stdio: 'inherit',
  });

  const code = result.status ?? 1;
  banner(code === 0 ? 'ALL TESTS PASSED' : 'TESTS FINISHED WITH FAILURES');
  console.log(`  DOCX folder: storage/app/test-reports/`);
  console.log(`  LATEST:      storage/app/test-reports/OrgChain-Test-Report-LATEST.docx`);
  console.log(`  Exit code:   ${code}`);

  if (openDocx) {
    try {
      openLatestReport();
    } catch (e) {
      console.warn(`Could not open DOCX: ${e.message}`);
    }
  }

  process.exit(code);
}

main().catch((e) => {
  console.error(e);
  process.exit(2);
});
