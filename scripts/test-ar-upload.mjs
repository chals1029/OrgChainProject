import { chromium } from 'playwright';
import path from 'path';

(async () => {
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext({ viewport: { width: 1440, height: 900 } });
  const page = await context.newPage();

  // Login as SSC
  await page.goto('https://orgchain.tech/orgchain-office-access-a9e2f71c4b83', { waitUntil: 'networkidle' });
  await page.fill('input[name="email"]', 'sscnasugbu@g.batstate-u.edu.ph');
  await page.fill('input[name="password"]', 'Office@2026!');
  await Promise.all([
    page.waitForURL(/office-desk/),
    page.click('button[type="submit"]'),
  ]);
  console.log('Signed in as SSC');

  // Go to Accomplishment Report
  await page.goto('https://orgchain.tech/office-desk/accomplishment-report', { waitUntil: 'networkidle' });
  console.log('Opened Accomplishment Report');

  // Inspect #arUploadForm inputs
  const arInputs = await page.locator('#arUploadForm input, #arUploadForm select').evaluateAll(els => els.map(e => ({ name: e.name, value: e.value, type: e.type })));
  console.log('arUploadForm inputs:', arInputs);

  // Upload file
  const docPath = path.resolve('storage/Template/Accomplishment and Financial Report Format for First Semester AY 2025-2026/Accomplishment Report_Template.docx');
  await page.locator('#arUploadFile').setInputFiles(docPath);
  console.log('Set file on #arUploadFile');

  // Submit #arUploadForm
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'networkidle' }),
    page.evaluate(() => document.getElementById('arUploadForm').submit()),
  ]);
  console.log('Submitted arUploadForm! Landed on:', page.url());

  const alertMsg = await page.locator('.ar-alert, .alert-success, [role="alert"]').allInnerTexts();
  console.log('Alert messages on AR desk:', alertMsg);

  await browser.close();
})();
