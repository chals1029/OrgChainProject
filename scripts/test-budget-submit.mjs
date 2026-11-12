import { chromium } from 'playwright';
import path from 'path';

(async () => {
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext({ viewport: { width: 1440, height: 900 } });
  const page = await context.newPage();

  page.on('console', msg => console.log('BROWSER CONSOLE:', msg.text()));

  // Login as SSC
  await page.goto('https://orgchain.tech/orgchain-office-access-a9e2f71c4b83', { waitUntil: 'networkidle' });
  await page.fill('input[name="email"]', 'sscnasugbu@g.batstate-u.edu.ph');
  await page.fill('input[name="password"]', 'Office@2026!');
  await Promise.all([
    page.waitForURL(/office-desk/),
    page.click('button[type="submit"]'),
  ]);
  console.log('Signed in as SSC');

  await page.goto('https://orgchain.tech/office-desk/budget-utilization', { waitUntil: 'networkidle' });

  const actSelect = page.locator('#receiptActivityId');
  const count = await actSelect.locator('option').count();
  console.log('Approved activity options count:', count);
  const options = await actSelect.locator('option').allInnerTexts();
  console.log('Option titles:', options);

  await actSelect.selectOption({ index: 1 });
  console.log('Selected activity in dropdown');

  // Attach receipt
  const receiptPath = path.resolve('public/images/bsuarasof.jpg');
  await page.locator('#soReceiptInput').setInputFiles(receiptPath);
  await page.locator('#soReceiptInput').dispatchEvent('change');
  console.log('Attached receipt and dispatched change');
  await page.waitForTimeout(600);

  // Fill in expense details
  await page.fill('input[name="expenses[0][item_name]"]', 'Delegate Conference Kits & Lanyards');
  await page.fill('input[name="expenses[0][supplier]"]', 'Batangas Print Hub');
  await page.fill('input[name="expenses[0][receipt_reference]"]', `OR-SSC-2026-001`);
  await page.fill('input[name="expenses[0][quantity]"]', '100');
  await page.fill('input[name="expenses[0][unit_cost]"]', '150');
  await page.fill('input[name="expenses[0][expense_date]"]', '2026-11-28');

  console.log('Clicking submit button...');
  console.log('Clicking submit button on #soExpenseForm...');
  const submitBtn = page.locator('#soExpenseForm button[type="submit"]');
  await submitBtn.click();
  await page.waitForTimeout(1000);

  const confirmAccept = page.locator('.action-confirm-dialog .action-confirm-accept');
  console.log('Confirm dialog visible?:', await confirmAccept.isVisible());
  if (await confirmAccept.isVisible()) {
    console.log('Clicking confirm accept...');
    await Promise.all([
      page.waitForNavigation({ waitUntil: 'networkidle', timeout: 35000 }).catch(() => {}),
      confirmAccept.click(),
    ]);
  }

  console.log('Final URL after submit:', page.url());
  const alerts = await page.locator('.so-alert, [role="alert"]').allInnerTexts();
  console.log('Alerts:', alerts);

  await browser.close();
})();
