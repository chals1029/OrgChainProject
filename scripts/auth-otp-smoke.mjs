/**
 * Auth + OTP UI journey (Playwright).
 * Student OTP uses EXPOSE_TEST_OTP debug_code from JSON send endpoint.
 *
 * Usage:
 *   EXPOSE_TEST_OTP=true (in .env of the running app) then:
 *   node scripts/auth-otp-smoke.mjs
 */
import { chromium } from 'playwright';
import fs from 'fs';

const BASE = process.env.APP_URL || 'http://127.0.0.1:8000';
const OFFICE_PATH = '/orgchain-office-access-a9e2f71c4b83';
const OFFICE = {
  email: 'so.office@g.batstate-u.edu.ph',
  password: 'Office@2026!',
};
const STUDENT_SR = process.env.E2E_SR_CODE || '21-00001';

const findings = [];

function note(kind, text) {
  findings.push({ kind, text });
  console.log(`  - ${kind}: ${text}`);
}

async function main() {
  console.log(`Auth/OTP UI smoke @ ${BASE}`);
  const browser = await chromium.launch({ headless: true });
  const report = { base: BASE, startedAt: new Date().toISOString(), steps: [], findings };

  // --- Office auth ---
  {
    const ctx = await browser.newContext();
    const page = await ctx.newPage();
    page.on('pageerror', (e) => note('pageerror', e.message));
    page.on('console', (m) => {
      if (m.type() === 'error') note('console.error', m.text());
    });

    console.log('[Office] bad password');
    await page.goto(`${BASE}${OFFICE_PATH}`, { waitUntil: 'domcontentloaded', timeout: 30000 });
    await page.fill('input[name="email"]', OFFICE.email);
    await page.fill('input[name="password"]', 'WrongPassword!!!');
    await page.click('button[type="submit"]');
    await page.waitForTimeout(800);
    const badOk = page.url().includes(OFFICE_PATH.replace(/^\//, '')) || (await page.locator('.error, .invalid-feedback, .text-danger, [role="alert"]').count()) > 0 || (await page.content()).includes('credentials');
    report.steps.push({ name: 'office_bad_password', ok: badOk });
    if (!badOk) note('assert', 'Office bad password did not stay on login / show error');

    console.log('[Office] good password');
    await page.fill('input[name="email"]', OFFICE.email);
    await page.fill('input[name="password"]', OFFICE.password);
    await Promise.all([
      page.waitForURL(/office-desk/, { timeout: 20000 }),
      page.click('button[type="submit"]'),
    ]);
    const goodOk = page.url().includes('/office-desk');
    report.steps.push({ name: 'office_login_ok', ok: goodOk });
    if (!goodOk) note('assert', 'Office login failed');

    await page.goto(`${BASE}/office-desk/renewal`, { waitUntil: 'domcontentloaded' });
    report.steps.push({ name: 'office_renewal_after_login', ok: page.url().includes('/renewal') });
    await ctx.close();
  }

  // --- Student OTP ---
  {
    const ctx = await browser.newContext();
    const page = await ctx.newPage();
    page.on('pageerror', (e) => note('pageerror', e.message));

    console.log('[Student] request OTP via JSON');
    await page.goto(`${BASE}/`, { waitUntil: 'domcontentloaded', timeout: 30000 });

    const csrf = await page.locator('meta[name="csrf-token"]').getAttribute('content');
    const send = await page.request.post(`${BASE}/student/login/code`, {
      headers: {
        'X-CSRF-TOKEN': csrf || '',
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
      form: { sr_code: STUDENT_SR },
    });
    const sendJson = await send.json().catch(() => ({}));
    const code = sendJson.debug_code;
    report.steps.push({
      name: 'student_otp_send',
      ok: send.ok() && !!sendJson.ok,
      detail: sendJson.email || send.status(),
    });
    if (!send.ok() || !sendJson.ok) {
      note('assert', `OTP send failed: ${send.status()} ${JSON.stringify(sendJson)}`);
    }
    if (!code) {
      note(
        'assert',
        'No debug_code in OTP response. Set EXPOSE_TEST_OTP=true in .env and restart php artisan serve for full OTP UI verify.'
      );
      report.steps.push({ name: 'student_otp_verify', ok: false, skipped: true });
    } else {
      console.log('[Student] verify OTP');
      // Refresh CSRF then verify in the same browser cookie jar
      await page.goto(`${BASE}/`, { waitUntil: 'domcontentloaded' });
      const csrf2 = await page.locator('meta[name="csrf-token"]').getAttribute('content');
      const verify = await page.request.post(`${BASE}/student/login/verify`, {
        headers: {
          'X-CSRF-TOKEN': csrf2 || '',
          Accept: 'text/html',
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        form: {
          _token: csrf2 || '',
          sr_code: STUDENT_SR,
          code,
        },
        maxRedirects: 5,
      });
      await page.goto(`${BASE}/portal`, { waitUntil: 'domcontentloaded', timeout: 20000 }).catch(() => null);
      const portalOk = page.url().includes('/portal') && !page.url().includes('login');
      const bodyHasPortal = portalOk || (await page.content()).includes('Budget Utilization') || (await page.content()).includes('Hello');
      report.steps.push({
        name: 'student_otp_verify',
        ok: portalOk || bodyHasPortal,
        http: verify.status(),
        detail: page.url(),
      });
      if (!(portalOk || bodyHasPortal)) {
        note('assert', `Student OTP verify did not reach /portal (url=${page.url()}, http=${verify.status()})`);
      } else {
        await page.goto(`${BASE}/portal/community`, { waitUntil: 'domcontentloaded' });
        report.steps.push({ name: 'student_community', ok: page.url().includes('/community') });
      }
    }
    await ctx.close();
  }

  await browser.close();

  const failed = report.steps.filter((s) => s.ok === false && !s.skipped);
  report.ok = failed.length === 0 && findings.filter((f) => f.kind !== 'assert' || true).length >= 0;
  report.ok = failed.length === 0;
  report.issueCount = failed.length + findings.filter((f) => f.kind === 'pageerror' || f.kind === 'console.error').length;

  fs.mkdirSync('storage/app', { recursive: true });
  fs.writeFileSync('storage/app/auth-otp-smoke-report.json', JSON.stringify(report, null, 2));
  console.log(`\nAuth/OTP steps: ${report.steps.length}, ok=${report.ok}, issues=${report.issueCount}`);
  console.log('Report: storage/app/auth-otp-smoke-report.json');
  process.exit(report.ok ? 0 : 1);
}

main().catch((e) => {
  console.error(e);
  process.exit(2);
});
