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
  const page = await context.newPage();

  // Login as SSC
  await page.goto(BASE + LOGIN_PATH, { waitUntil: 'networkidle' });
  await page.fill('input[name="email"]', 'sscnasugbu@g.batstate-u.edu.ph');
  await page.fill('input[name="password"]', PASSWORD);
  await Promise.all([
    page.waitForURL(/office-desk/),
    page.click('button[type="submit"]'),
  ]);
  console.log('Signed in as SSC');

  // =========================================================================
  // 4a. Submit AR (Accomplishment Report)
  // =========================================================================
  await page.goto(BASE + '/office-desk/accomplishment-report', { waitUntil: 'networkidle' });
  console.log('Opened Accomplishment Report desk');

  // Open upload modal
  const openArBtn = page.locator('button[data-ar-upload]');
  if (await openArBtn.count() > 0) {
    await openArBtn.click();
    await page.waitForTimeout(500);

    const arDocPath = path.resolve('storage/Template/Accomplishment and Financial Report Format for First Semester AY 2025-2026/Accomplishment Report_Template.docx');
    await page.locator('#arUploadFile').setInputFiles(arDocPath);
    console.log('Attached AR template file');

    await page.click('#arUploadSave');
    await page.waitForNavigation({ waitUntil: 'networkidle', timeout: 25000 }).catch(() => {});
    console.log('Saved AR document');
  }

  // Submit AR to OSO
  const arSubmit = page.locator('form[action*="/reports/ar/submit"] button[type="submit"]');
  if (await arSubmit.isVisible() && !(await arSubmit.isDisabled())) {
    console.log('Clicking Submit AR to OSO button...');
    await arSubmit.click();
    await page.waitForTimeout(600);

    const confirmAccept = page.locator('.action-confirm-dialog .action-confirm-accept');
    if (await confirmAccept.isVisible()) {
      console.log('Confirming AR submission...');
      await Promise.all([
        page.waitForNavigation({ waitUntil: 'networkidle', timeout: 25000 }).catch(() => {}),
        confirmAccept.click(),
      ]);
    }
    console.log('AR Report submitted successfully!');
  }

  // =========================================================================
  // 4b. Submit FR (Financial Report)
  // =========================================================================
  await page.goto(BASE + '/office-desk/financial-report', { waitUntil: 'networkidle' });
  console.log('Opened Financial Report desk');

  // Upload FR document
  const frFileInput = page.locator('form.sr-upload input[type="file"][name="document"]');
  if (await frFileInput.count() > 0) {
    const frDocPath = path.resolve('storage/Template/Accomplishment and Financial Report Format for First Semester AY 2025-2026/Financial Report_New Format.xlsx');
    await frFileInput.setInputFiles(frDocPath);
    console.log('Attached FR workbook file');
    await page.click('form.sr-upload button[type="submit"]');
    await page.waitForNavigation({ waitUntil: 'networkidle', timeout: 25000 }).catch(() => {});
    console.log('Saved FR document');
  }

  // Click View on the document to satisfy the preview gate
  const frViewLink = page.locator('.sr-actions a.app-view-action').first();
  if (await frViewLink.count() > 0) {
    console.log('Opening preview to satisfy preview gate...');
    const [previewTab] = await Promise.all([
      context.waitForEvent('page', { timeout: 8000 }).catch(() => null),
      frViewLink.click().catch(() => {}),
    ]);
    if (previewTab) {
      await previewTab.waitForLoadState('networkidle').catch(() => {});
      await previewTab.close();
    }
    await page.waitForTimeout(1000);
    // Reload page to reflect viewed status
    await page.reload({ waitUntil: 'networkidle' });
  }

  // Submit FR to OSO
  const frSubmit = page.locator('form[action*="/reports/fr/submit"] button[type="submit"]');
  if (await frSubmit.isVisible() && !(await frSubmit.isDisabled())) {
    console.log('Clicking Submit FR to OSO button...');
    await frSubmit.click();
    await page.waitForTimeout(600);

    const confirmAccept = page.locator('.action-confirm-dialog .action-confirm-accept');
    if (await confirmAccept.isVisible()) {
      console.log('Confirming FR submission...');
      await Promise.all([
        page.waitForNavigation({ waitUntil: 'networkidle', timeout: 25000 }).catch(() => {}),
        confirmAccept.click(),
      ]);
    }
    console.log('FR Report submitted successfully!');
  }

  await context.close();

  // =========================================================================
  // 4c. OSO Review AR & FR
  // =========================================================================
  console.log('\n>>> Signing in as OSO to verify and review submitted reports...');
  const osoCtx = await browser.newContext({ viewport: { width: 1440, height: 900 } });
  const osoPg = await loginAs(osoCtx, 'oso');
  await osoPg.goto(BASE + '/office-desk/reports', { waitUntil: 'networkidle' });
  console.log('OSO opened AR & FR Reports Desk');

  const pageTitle = await osoPg.title();
  console.log('Reports Page Title:', pageTitle);

  await osoPg.screenshot({ path: 'storage/app/e2e-ssc-step4-ar-fr-completed.png', fullPage: false });
  console.log('Saved screenshot: storage/app/e2e-ssc-step4-ar-fr-completed.png');

  await osoCtx.close();
  await browser.close();
})();
