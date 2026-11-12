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
    page.waitForURL(/office-desk/, { timeout: 25000 }),
    page.click('button[type="submit"]'),
  ]);
  console.log('Signed in as SSC');

  await page.goto('https://orgchain.tech/office-desk/activities/create?type=in_campus', { waitUntil: 'networkidle' });
  console.log('Opened Activity Creation form');

  // Select plan activity
  await page.selectOption('select[name="plan_activity_key"]', { index: 1 });
  console.log('Selected plan activity');

  await page.fill('input[name="location"]', 'Alfonso Joson Gymnasium');
  await page.fill('input[name="approved_budget"]', '35000');
  await page.fill('textarea[name="participants"]', '150 participants from student council and college departments.');
  await page.fill('textarea[name="safety_plan"]', 'Medical response team on standby, safety perimeter, and certified disaster risk briefing.');
  await page.fill('input[name="starts_at"]', '2026-11-28T08:00');
  await page.fill('input[name="ends_at"]', '2026-11-28T17:00');

  // Check documents_complete
  await page.check('input[name="documents_complete"]');

  // File uploads
  const inDir = path.resolve('storage/app/ssc-completed-docs');
  const filesMap = {
    'checklist_template': path.join(inDir, '0. Checklist Template.docx'),
    'programme': path.join(inDir, '2. Programme.docx'),
    'project_proposal': path.join(inDir, '3. Project Proposal.docx'),
    'budget_proposal': path.join(inDir, '4. Budget Proposal.docx'),
    'medical_clearance': path.join(inDir, '5. Medical Request Sample Letter.docx'),
    'insurance': path.join(inDir, '6. Insurance Request Sample Letter.docx'),
    'resolution': path.join(inDir, '7. Resolution of the Organization .docx'),
    'faculty_in_charge': path.join(inDir, '14. Faculty-In-Charge.docx'),
    'sample_letter': path.join(inDir, 'Sample Letter.docx'),
    'wpcf': path.join(inDir, 'Waste-Policy-Compliance-Form-2026 (1).docx'),
  };
  // Set files and dispatch change event so activity-create.js validates readiness
  for (const [key, filePath] of Object.entries(filesMap)) {
    const input = page.locator(`#activity-file-in_campus-${key}`);
    if (await input.count() > 0) {
      await input.setInputFiles(filePath);
      await input.dispatchEvent('change');
    }
  }
  console.log('Set all 10 document files and dispatched change');
  await page.waitForTimeout(1000);

  const isBtnDisabled = await page.locator('#activitySubmitButton').isDisabled();
  console.log('Is #activitySubmitButton disabled?:', isBtnDisabled);

  const remainingText = await page.locator('#activityRemainingHelp').innerText();
  console.log('Readiness status text:', remainingText);

  // Click submit button
  const submitBtn = page.locator('#activitySubmitButton');
  console.log('Clicking Submit for Review...');
  await submitBtn.click();
  await page.waitForTimeout(1000);
  await page.waitForTimeout(600);

  // Handle confirmation dialog if opened
  const confirmAccept = page.locator('.action-confirm-dialog .action-confirm-accept');
  if (await confirmAccept.isVisible()) {
    console.log('Confirm dialog visible! Clicking accept...');
    await Promise.all([
      page.waitForNavigation({ waitUntil: 'networkidle', timeout: 35000 }).catch(() => {}),
      confirmAccept.click(),
    ]);
  }

  console.log('Final URL after submit:', page.url());
  const errorAlert = await page.locator('.ap-upload-notice.is-error, .alert-danger, [role="alert"]').allInnerTexts();
  if (errorAlert.length > 0) {
    console.log('Submission alerts/errors:', errorAlert);
  }

  await browser.close();
})();
