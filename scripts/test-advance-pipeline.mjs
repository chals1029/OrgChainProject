import { chromium } from 'playwright';

const BASE = 'https://orgchain.tech';
const PASSWORD = 'Office@2026!';
const LOGIN_PATH = '/orgchain-office-access-a9e2f71c4b83';

const DESKS = [
  { role: 'oso', email: 'oso.office@g.batstate-u.edu.ph' },
  { role: 'sdo', email: 'sdo.office@g.batstate-u.edu.ph' },
  { role: 'ovcaa', email: 'ovcaa.office@g.batstate-u.edu.ph' },
  { role: 'oc', email: 'oc.office@g.batstate-u.edu.ph' },
];

(async () => {
  const browser = await chromium.launch({ headless: true });

  for (const desk of DESKS) {
    console.log(`\n>>> Advancing at ${desk.role.toUpperCase()} desk (${desk.email})...`);
    const context = await browser.newContext({ viewport: { width: 1440, height: 900 } });
    const page = await context.newPage();

    // Login
    await page.goto(`${BASE}${LOGIN_PATH}`, { waitUntil: 'networkidle' });
    await page.fill('input[name="email"]', desk.email);
    await page.fill('input[name="password"]', PASSWORD);
    await Promise.all([
      page.waitForURL(/office-desk/, { timeout: 25000 }),
      page.click('button[type="submit"]'),
    ]);
    console.log(`[${desk.role.toUpperCase()}] Signed in`);

    // Navigate to activity 2985
    await page.goto(`${BASE}/office-desk/activities?activity=2985`, { waitUntil: 'networkidle' });
    console.log(`[${desk.role.toUpperCase()}] Opened activity 2985 details`);

    // Find advance form
    const advanceForm = page.locator('form[action*="/activities/2985/advance"]');
    if (await advanceForm.count() > 0) {
      // Check documents_reviewed checkbox if present
      const docCheck = advanceForm.locator('input[type="checkbox"][name="documents_reviewed"]');
      if (await docCheck.count() > 0 && !(await docCheck.isChecked())) {
        await docCheck.check();
        console.log(`[${desk.role.toUpperCase()}] Checked documents_reviewed checkbox`);
      }

      // Check plan_of_activities_verified if present
      const planCheck = advanceForm.locator('input[type="checkbox"][name="plan_of_activities_verified"]');
      if (await planCheck.count() > 0 && !(await planCheck.isChecked())) {
        await planCheck.check();
      }

      // Handle confirmation dialog
      const submitBtn = advanceForm.locator('button[type="submit"]');
      console.log(`[${desk.role.toUpperCase()}] Clicking advance / endorse button...`);
      await submitBtn.click();
      await page.waitForTimeout(600);

      // Handle custom confirmation dialog
      const confirmAccept = page.locator('.action-confirm-dialog .action-confirm-accept');
      if (await confirmAccept.isVisible()) {
        console.log(`[${desk.role.toUpperCase()}] Confirm dialog visible! Clicking accept...`);
        await Promise.all([
          page.waitForNavigation({ waitUntil: 'networkidle', timeout: 35000 }).catch(() => {}),
          confirmAccept.click(),
        ]);
      }
      console.log(`[${desk.role.toUpperCase()}] Successfully endorsed/approved!`);
    } else {
      console.log(`[${desk.role.toUpperCase()}] No advance form found on this page`);
    }

    await context.close();
  }

  await browser.close();
})();
