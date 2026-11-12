import { chromium } from 'playwright';
import path from 'path';

const BASE = 'https://orgchain.tech';
const PASSWORD = 'Office@2026!';
const LOGIN_PATH = '/orgchain-office-access-a9e2f71c4b83';

async function loginAs(context, roleKey) {
  const emails = {
    ssc: 'sscnasugbu@g.batstate-u.edu.ph',
    oso: 'oso.office@g.batstate-u.edu.ph',
  };
  const page = await context.newPage();
  await page.goto(BASE + LOGIN_PATH, { waitUntil: 'networkidle' });
  await page.fill('input[name="email"]', emails[roleKey]);
  await page.fill('input[name="password"]', PASSWORD);
  await Promise.all([
    page.waitForURL(/office-desk/),
    page.click('button[type="submit"]'),
  ]);
  return page;
}

(async () => {
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext({ viewport: { width: 1440, height: 900 } });
  const page = await loginAs(context, 'ssc');
  console.log('Signed in as SSC');

  // =========================================================================
  // 1. Submit AR (Accomplishment Report)
  // =========================================================================
  await page.goto(BASE + '/office-desk/accomplishment-report', { waitUntil: 'networkidle' });
  console.log('Opened Accomplishment Report');

  const arFileInput = page.locator('#arUploadFile');
  if (await arFileInput.count() > 0) {
    const arDocPath = path.resolve('storage/Template/Accomplishment and Financial Report Format for First Semester AY 2025-2026/Accomplishment Report_Template.docx');
    await arFileInput.setInputFiles(arDocPath);
    console.log('Attached AR template file');
    await page.click('#arUploadSave');
    await page.waitForNavigation({ waitUntil: 'networkidle', timeout: 25000 }).catch(() => {});
    console.log('Saved AR document');
  }

  const arSubmitBtn = page.locator('form[action*="/reports/ar/submit"] button[type="submit"]');
  if (await arSubmitBtn.isVisible() && !(await arSubmitBtn.isDisabled())) {
    console.log('Submitting AR to OSO...');
    await arSubmitBtn.click();
    await page.waitForTimeout(600);
    const confirm = page.locator('.action-confirm-dialog .action-confirm-accept');
    if (await confirm.isVisible()) {
      await Promise.all([
        page.waitForNavigation({ waitUntil: 'networkidle', timeout: 25000 }).catch(() => {}),
        confirm.click(),
      ]);
    }
    console.log('AR Submitted! Status alert:', await page.locator('.ar-alert, .alert-success, [role="alert"]').allInnerTexts());
  }

  // =========================================================================
  // 2. Upload & Submit FR (Financial Report)
  // =========================================================================
  await page.goto(BASE + '/office-desk/financial-report', { waitUntil: 'networkidle' });
  console.log('Opened Financial Report');

  const createFrBtn = page.locator('button[data-fin-open="finReportDialog"]');
  if (await createFrBtn.count() > 0) {
    await createFrBtn.click();
    await page.waitForTimeout(500);
    const frDocPath = path.resolve('storage/Template/Accomplishment and Financial Report Format for First Semester AY 2025-2026/Financial Report_New Format.xlsx');
    await page.locator('#finReportDialog input[type="file"][name="document"]').setInputFiles(frDocPath);
    console.log('Attached FR template file');
    await Promise.all([
      page.waitForNavigation({ waitUntil: 'networkidle', timeout: 25000 }),
      page.click('#finReportDialog button[type="submit"]'),
    ]);
    console.log('Saved FR report');
  }

  // Click View to satisfy preview gate
  const viewFrBtn = page.locator('button[data-fin-view]').first();
  if (await viewFrBtn.count() > 0) {
    console.log('Clicking View button to open preview modal...');
    await viewFrBtn.click();
    await page.waitForTimeout(1200);
    const closeFinBtn = page.locator('#finPreviewDialog [data-fin-close]').first();
    if (await closeFinBtn.isVisible()) await closeFinBtn.click();
    await page.waitForTimeout(600);
  }

  // Submit FR to OSO
  const frSubmitBtn = page.locator('button[data-fin-submit]').first();
  if (await frSubmitBtn.count() > 0) {
    console.log('FR Submit button disabled?:', await frSubmitBtn.isDisabled());
    if (!(await frSubmitBtn.isDisabled())) {
      console.log('Submitting FR to OSO...');
      await frSubmitBtn.click();
      await page.waitForTimeout(600);
      const confirm = page.locator('.action-confirm-dialog .action-confirm-accept');
      if (await confirm.isVisible()) {
        await Promise.all([
          page.waitForNavigation({ waitUntil: 'networkidle', timeout: 25000 }).catch(() => {}),
          confirm.click(),
        ]);
      }
      console.log('FR Submitted! Alerts:', await page.locator('.fin-alert, .alert-success, [role="alert"]').allInnerTexts());
    }
  }

  await context.close();

  // =========================================================================
  // 3. OSO Review AR & FR
  // =========================================================================
  console.log('\n>>> Signing in as OSO to review submitted reports...');
  const osoCtx = await browser.newContext({ viewport: { width: 1440, height: 900 } });
  const osoPg = await loginAs(osoCtx, 'oso');
  await osoPg.goto(BASE + '/office-desk/reports', { waitUntil: 'networkidle' });
  console.log('OSO opened AR & FR Reports Desk');

  await osoPg.screenshot({ path: 'storage/app/e2e-ssc-step4-ar-fr-completed.png', fullPage: false });
  console.log('Saved screenshot: storage/app/e2e-ssc-step4-ar-fr-completed.png');

  await osoCtx.close();
  await browser.close();
})();
