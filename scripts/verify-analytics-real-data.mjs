import assert from 'node:assert/strict';
import { mkdir } from 'node:fs/promises';
import { chromium } from 'playwright';

const base = (process.argv.find((arg) => arg.startsWith('--base='))?.slice(7) || process.env.APP_URL || 'http://127.0.0.1:8000').replace(/\/$/, '');
const password = process.env.E2E_OFFICE_PASSWORD;
const cookie = process.env.E2E_OFFICE_COOKIE;
const colleges = [
  'College of Accountancy, Business, Economics and International Hospitality Management',
  'College of Arts and Sciences',
  'College of Criminal Justice Education',
  'College of Health Sciences',
  'College of Informatics and Computing Sciences',
  'College of Teacher Education',
  'Laboratory School',
  'Campus Wide',
];

async function loginOffice(page) {
  assert.ok(password, 'Set E2E_OFFICE_PASSWORD or E2E_OFFICE_COOKIE to authenticate as OSO.');
  await page.goto(`${base}/orgchain-office-access-a9e2f71c4b83`, { waitUntil: 'domcontentloaded' });
  await page.locator('input[name="email"]').fill('oso.office@g.batstate-u.edu.ph');
  await page.locator('input[name="password"]').fill(password);
  await page.locator('button[type="submit"]').click();
  await page.waitForURL(/office-desk/, { timeout: 20000 });
}

const browser = await chromium.launch({ headless: true });
try {
  const context = await browser.newContext({ viewport: { width: 1440, height: 900 } });
  if (cookie) {
    const separator = cookie.indexOf('=');
    assert.ok(separator > 0, 'E2E_OFFICE_COOKIE must be a name=value session cookie.');
    await context.addCookies([{ name: cookie.slice(0, separator), value: cookie.slice(separator + 1), url: base }]);
  }
  const page = await context.newPage();
  if (!cookie) await loginOffice(page);
  const response = await page.goto(`${base}/office-desk/analytics`, { waitUntil: 'domcontentloaded' });
  assert.equal(response?.status(), 200, 'Analytics request must succeed');
  assert.match(new URL(page.url()).pathname, /^\/office-desk\/analytics\/?$/, 'Expected analytics page, not a login redirect');
  await page.locator('#yearFilter').selectOption('2026');
  await page.locator('#semesterFilter').selectOption('all');
  await page.locator('#monthFilter').selectOption('all');
  await page.locator('#scopeFilter').selectOption('all');

  const number = (text) => Number(text.replace(/[^0-9.]/g, ''));
  const scopes = {};
  for (const scope of ['in_campus', 'local_off_campus']) {
    const card = page.locator(`[data-scope-card="${scope}"]`);
    await card.waitFor();
    const count = number(await card.locator('[data-scope-count]').innerText());
    const allocated = number(await card.locator('[data-scope-allocated]').innerText());
    assert.ok(count > 0, `${scope} activity count must be positive`);
    assert.ok(allocated > 0, `${scope} allocation must be positive`);
    scopes[scope] = { count, allocated };
  }
  const rows = await page.locator('#collegePerformanceTableBody tr').evaluateAll((elements) => elements.map((row) =>
    [...row.querySelectorAll('td')].map((cell) => cell.textContent.trim())
  ));
  for (const college of colleges) {
    const row = rows.find((cells) => cells[0] === college);
    assert.ok(row, `Missing college performance row: ${college}`);
    assert.ok(number(row[1]) > 0, `${college} must have activities`);
  }
  await mkdir('storage/app', { recursive: true });
  const screenshot = 'storage/app/analytics-real-data-verified.png';
  await page.screenshot({ path: screenshot, fullPage: true });
  console.log(JSON.stringify({ base, year: 2026, scopes, colleges: rows.map((row) => ({ college: row[0], activities: number(row[1]) })), screenshot }, null, 2));
} finally {
  await browser.close();
}
