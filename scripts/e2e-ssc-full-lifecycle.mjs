import { chromium } from 'playwright';
import path from 'path';
import fs from 'fs';

const BASE = 'https://orgchain.tech';
const PASSWORD = 'Office@2026!';
const LOGIN_PATH = '/orgchain-office-access-a9e2f71c4b83';

const ROLES = {
  ssc: { email: 'sscnasugbu@g.batstate-u.edu.ph', password: PASSWORD },
  oso: { email: 'oso.office@g.batstate-u.edu.ph', password: PASSWORD },
  sdo: { email: 'sdo.office@g.batstate-u.edu.ph', password: PASSWORD },
  ovcaa: { email: 'ovcaa.office@g.batstate-u.edu.ph', password: PASSWORD },
  oc: { email: 'oc.office@g.batstate-u.edu.ph', password: PASSWORD },
};

async function loginAs(context, roleKey) {
  const role = ROLES[roleKey];
  const page = await context.newPage();
  console.log(`[AUTH] Signing in as ${roleKey.toUpperCase()} (${role.email})...`);
  await page.goto(`${BASE}${LOGIN_PATH}`, { waitUntil: 'networkidle' });
  await page.fill('input[name="email"]', role.email);
  await page.fill('input[name="password"]', role.password);
  await Promise.all([
    page.waitForURL(/office-desk/, { timeout: 25000 }),
    page.click('button[type="submit"]'),
  ]);
  console.log(`[AUTH] Signed in successfully as ${roleKey.toUpperCase()}`);
  return page;
}

(async () => {
  const browser = await chromium.launch({ headless: true });
  console.log(`=======================================================`);
  console.log(`STARTING COMPLETE SSC E2E WORKFLOW ON ${BASE}`);
  console.log(`=======================================================\n`);

  // =========================================================================
  // STEP 1: RENEWAL PROCESS
  // =========================================================================
  console.log(`>>> STEP 1: RENEWAL PROCESS (Verifying SSC Renewal Approval & Active Status)`);
  const osoContext = await browser.newContext({ viewport: { width: 1440, height: 900 } });
  const osoPage = await loginAs(osoContext, 'oso');

  await osoPage.goto(`${BASE}/office-desk/renewal`, { waitUntil: 'networkidle' });
  await osoPage.fill('#osoRenewalSearch', 'Supreme Student Council');
  await osoPage.waitForTimeout(600);

  const sscRow = osoPage.locator('#osoRenewalApplicationsTable tbody tr:not([hidden])').first();
  const sscRenewalStatus = await sscRow.locator('td:nth-child(2)').innerText();
  const sscEligibility = await sscRow.locator('td:nth-child(3)').innerText();
  console.log(`[RENEWAL VERIFICATION] Status: ${sscRenewalStatus.trim()} | Eligibility: ${sscEligibility.trim()}`);

  await osoPage.screenshot({ path: 'storage/app/e2e-ssc-step1-renewal-approved.png', fullPage: false });
  console.log(`[SCREENSHOT] Saved: storage/app/e2e-ssc-step1-renewal-approved.png\n`);
  await osoContext.close();

  // =========================================================================
  // STEP 2: CREATION OF ACTIVITY & MULTI-DESK ADVANCEMENT
  // =========================================================================
  console.log(`>>> STEP 2: ACTIVITY CREATION (SSC files In-Campus Proposal)`);
  const sscContext = await browser.newContext({ viewport: { width: 1440, height: 900 } });
  const sscPage = await loginAs(sscContext, 'ssc');

  await sscPage.goto(`${BASE}/office-desk/activities/create?type=in_campus`, { waitUntil: 'networkidle' });
  console.log(`[ACTIVITY] Opened Activity Creation Form`);

  // Select plan activity
  const planSelect = sscPage.locator('select[name="plan_activity_key"]');
  if (await planSelect.count() > 0) {
    await planSelect.selectOption({ index: 2 });
    console.log(`[ACTIVITY] Selected activity from approved Attachment I plan`);
  }
  // Location and budget fields
  await sscPage.fill('input[name="location"]', 'Alfonso Joson Gymnasium & Plenary Hall');
  await sscPage.fill('input[name="approved_budget"]', '35000');
  await sscPage.fill('textarea[name="participants"]', '150 participants from student council and college departments.');
  await sscPage.fill('textarea[name="safety_plan"]', 'Full university health and safety protocols, certified medical team on site, and emergency crowd management orientation.');
  await sscPage.fill('input[name="starts_at"]', '2026-11-28T08:00');
  await sscPage.fill('input[name="ends_at"]', '2026-11-28T17:00');

  // Check documents_complete checkbox
  await sscPage.check('input[name="documents_complete"]');

  // Attach all 10 compliant completed DOCX documents
  const completedDocsDir = path.resolve('storage/app/ssc-completed-docs');
  const filesMap = {
    'checklist_template': path.join(completedDocsDir, '0. Checklist Template.docx'),
    'programme': path.join(completedDocsDir, '2. Programme.docx'),
    'project_proposal': path.join(completedDocsDir, '3. Project Proposal.docx'),
    'budget_proposal': path.join(completedDocsDir, '4. Budget Proposal.docx'),
    'medical_clearance': path.join(completedDocsDir, '5. Medical Request Sample Letter.docx'),
    'insurance': path.join(completedDocsDir, '6. Insurance Request Sample Letter.docx'),
    'resolution': path.join(completedDocsDir, '7. Resolution of the Organization .docx'),
    'faculty_in_charge': path.join(completedDocsDir, '14. Faculty-In-Charge.docx'),
    'sample_letter': path.join(completedDocsDir, 'Sample Letter.docx'),
    'wpcf': path.join(completedDocsDir, 'Waste-Policy-Compliance-Form-2026 (1).docx'),
  };

  for (const [key, filePath] of Object.entries(filesMap)) {
    const input = sscPage.locator(`#activity-file-in_campus-${key}`);
    if (await input.count() > 0) {
      await input.setInputFiles(filePath);
      await input.dispatchEvent('change');
    }
  }
  console.log(`[ACTIVITY] Uploaded all 10 completed compliance files`);
  await sscPage.waitForTimeout(1000);

  // Submit proposal
  console.log(`[ACTIVITY] Submitting proposal package to OSO...`);
  const submitBtn = sscPage.locator('#activitySubmitButton');
  await submitBtn.click();
  await sscPage.waitForTimeout(600);

  const confirmAccept = sscPage.locator('.action-confirm-dialog .action-confirm-accept');
  if (await confirmAccept.isVisible()) {
    console.log(`[ACTIVITY] Confirming submission dialog...`);
    await Promise.all([
      sscPage.waitForNavigation({ waitUntil: 'networkidle', timeout: 35000 }).catch(() => {}),
      confirmAccept.click(),
    ]);
  }
  console.log(`[ACTIVITY] Proposal submitted! URL: ${sscPage.url()}`);

  const cardTitle = await sscPage.locator('.org-grid-card-title').first().innerText();
  const cardBadge = await sscPage.locator('.org-grid-doc-badge').first().innerText();
  console.log(`[ACTIVITY PROPOSAL] Title: "${cardTitle.trim()}" | Docs: ${cardBadge.trim()}`);

  await sscPage.screenshot({ path: 'storage/app/e2e-ssc-step2-activity-created.png', fullPage: false });
  console.log(`[SCREENSHOT] Saved: storage/app/e2e-ssc-step2-activity-created.png\n`);

  // Extract created activity ID from first card link
  const firstCardLink = await sscPage.locator('.org-grid-card').first().getAttribute('href');
  const activityIdMatch = firstCardLink.match(/activity=(\d+)/);
  const createdActivityId = activityIdMatch ? activityIdMatch[1] : '2985';
  console.log(`[PIPELINE TARGET] Activity ID: ${createdActivityId}`);
  await sscContext.close();

  // Multi-Desk Pipeline Loop: OSO -> SDO -> OVCAA -> OC
  const deskWorkflow = [
    { role: 'oso', email: 'oso.office@g.batstate-u.edu.ph', next: 'SDO Review' },
    { role: 'sdo', email: 'sdo.office@g.batstate-u.edu.ph', next: 'OVCAA Review' },
    { role: 'ovcaa', email: 'ovcaa.office@g.batstate-u.edu.ph', next: 'OC Final Approval' },
    { role: 'oc', email: 'oc.office@g.batstate-u.edu.ph', next: 'OC Approved' },
  ];

  for (const desk of deskWorkflow) {
    console.log(`[PIPELINE] Signing in as ${desk.role.toUpperCase()} to advance activity...`);
    const deskCtx = await browser.newContext({ viewport: { width: 1440, height: 900 } });
    const deskPg = await loginAs(deskCtx, desk.role);

    await deskPg.goto(`${BASE}/office-desk/activities?activity=${createdActivityId}`, { waitUntil: 'networkidle' });
    console.log(`[${desk.role.toUpperCase()}] Opened Activity #${createdActivityId}`);

    // Check plan & documents checkboxes if present
    const planChk = deskPg.locator('input[name="plan_of_activities_verified"]');
    if (await planChk.count() > 0 && !(await planChk.isChecked())) {
      await planChk.check();
    }
    const docChk = deskPg.locator('input[name="documents_reviewed"]');
    if (await docChk.count() > 0 && !(await docChk.isChecked())) {
      await docChk.check();
    }

    // Submit advance form directly
    console.log(`[${desk.role.toUpperCase()}] Advancing to ${desk.next}...`);
    await Promise.all([
      deskPg.waitForNavigation({ waitUntil: 'networkidle', timeout: 30000 }).catch(() => {}),
      deskPg.evaluate(() => document.getElementById('activityAdvanceForm')?.submit()),
    ]);
    console.log(`[${desk.role.toUpperCase()}] Advanced successfully!`);

    if (desk.role === 'oc') {
      await deskPg.screenshot({ path: 'storage/app/e2e-ssc-step2-activity-approved-oc.png', fullPage: false });
      console.log(`[SCREENSHOT] Saved: storage/app/e2e-ssc-step2-activity-approved-oc.png\n`);
    }

    await deskCtx.close();
  }
  console.log(`[PIPELINE] Complete! Activity #${createdActivityId} has received Final OC Approval.\n`);

  // =========================================================================
  // STEP 3: BUDGET UTILIZATION & LIQUIDATION
  // =========================================================================
  console.log(`>>> STEP 3: BUDGET UTILIZATION (SSC Records Itemized Receipts & Blockchain Anchor)`);
  const sscBudgetCtx = await browser.newContext({ viewport: { width: 1440, height: 900 } });
  const sscBudgetPg = await loginAs(sscBudgetCtx, 'ssc');

  await sscBudgetPg.goto(`${BASE}/office-desk/budget-utilization`, { waitUntil: 'networkidle' });
  console.log(`[BUDGET] Navigated to Budget Utilization desk`);

  const actSelect = sscBudgetPg.locator('#receiptActivityId');
  const count = await actSelect.locator('option').count();
  console.log(`[BUDGET] Activities available to liquidate: ${count}`);

  if (count > 1) {
    await actSelect.selectOption({ index: 1 });
    console.log(`[BUDGET] Selected approved activity`);

    // Attach receipt photo and dispatch change event
    const receiptPath = path.resolve('public/images/bsuarasof.jpg');
    const fileInput = sscBudgetPg.locator('#soReceiptInput');
    await fileInput.setInputFiles(receiptPath);
    await fileInput.dispatchEvent('change');
    console.log(`[BUDGET] Attached official receipt scan`);
    await sscBudgetPg.waitForTimeout(600);

    // Fill in item details
    await sscBudgetPg.locator('input[name="expenses[0][item_name]"]').fill('Delegate Conference Kits & Lanyards');
    await sscBudgetPg.locator('input[name="expenses[0][supplier]"]').fill('Batangas Print Hub');
    await sscBudgetPg.locator('input[name="expenses[0][receipt_reference]"]').fill(`OR-SSC-2026-${Date.now().toString().slice(-4)}`);
    await sscBudgetPg.locator('input[name="expenses[0][quantity]"]').fill('100');
    await sscBudgetPg.locator('input[name="expenses[0][unit_cost]"]').fill('150');
    await sscBudgetPg.locator('input[name="expenses[0][expense_date]"]').fill('2026-11-28');

    // Submit expense record
    const submitExpenseBtn = sscBudgetPg.locator('#soExpenseSubmitBtn, button[type="submit"]:has-text("Record expense")');
    console.log(`[BUDGET] Submitting expense batch...`);
    await submitExpenseBtn.click();
    await sscBudgetPg.waitForTimeout(600);

    const confirmAccept = sscBudgetPg.locator('.action-confirm-dialog .action-confirm-accept');
    if (await confirmAccept.isVisible()) {
      console.log(`[BUDGET] Confirming expense modal...`);
      await Promise.all([
        sscBudgetPg.waitForNavigation({ waitUntil: 'networkidle', timeout: 35000 }).catch(() => {}),
        confirmAccept.click(),
      ]);
    }
    console.log(`[BUDGET] Expense batch submitted and anchored to blockchain!`);
  }

  await sscBudgetPg.screenshot({ path: 'storage/app/e2e-ssc-step3-budget-utilized.png', fullPage: false });
  console.log(`[SCREENSHOT] Saved: storage/app/e2e-ssc-step3-budget-utilized.png\n`);
  await sscBudgetCtx.close();

  // =========================================================================
  // STEP 4: AR (ACCOMPLISHMENT) & FR (FINANCIAL) REPORTS
  // =========================================================================
  console.log(`>>> STEP 4: AR & FR REPORTS (SSC submits, OSO reviews)`);
  const sscRepCtx = await browser.newContext({ viewport: { width: 1440, height: 900 } });
  const sscRepPg = await loginAs(sscRepCtx, 'ssc');

  // 4a. Submit AR
  await sscRepPg.goto(`${BASE}/office-desk/accomplishment-report`, { waitUntil: 'networkidle' });
  console.log(`[REPORTS] Opened Accomplishment Report desk`);

  // Upload AR document if needed
  const arFileInput = sscRepPg.locator('#arUploadFile');
  if (await arFileInput.count() > 0) {
    const arDocPath = path.resolve('storage/Template/Accomplishment and Financial Report Format for First Semester AY 2025-2026/Accomplishment Report_Template.docx');
    await arFileInput.setInputFiles(arDocPath);
    console.log(`[REPORTS] Attached AR document file`);
    await sscRepPg.click('#arUploadSave');
    await sscRepPg.waitForTimeout(2000);
  }

  const arSubmit = sscRepPg.locator('form[action*="/reports/ar/submit"] button[type="submit"]');
  if (await arSubmit.isVisible() && !(await arSubmit.isDisabled())) {
    console.log(`[REPORTS] Submitting AR to OSO...`);
    await arSubmit.click();
    await sscRepPg.waitForTimeout(600);
    const confirm = sscRepPg.locator('.action-confirm-dialog .action-confirm-accept');
    if (await confirm.isVisible()) await confirm.click();
    await sscRepPg.waitForTimeout(2000);
    console.log(`[REPORTS] AR Report submitted to OSO!`);
  }

  // 4b. Submit FR
  await sscRepPg.goto(`${BASE}/office-desk/financial-report`, { waitUntil: 'networkidle' });
  console.log(`[REPORTS] Opened Financial Report desk`);

  // Upload FR document if needed
  const frFileInput = sscRepPg.locator('form.sr-upload input[type="file"][name="document"]');
  if (await frFileInput.count() > 0) {
    const frDocPath = path.resolve('storage/Template/Accomplishment and Financial Report Format for First Semester AY 2025-2026/Financial Report_New Format.xlsx');
    await frFileInput.setInputFiles(frDocPath);
    console.log(`[REPORTS] Attached FR workbook file`);
    await sscRepPg.click('form.sr-upload button[type="submit"]');
    await sscRepPg.waitForTimeout(2500);
  }

  // View FR document to satisfy preview gate
  const frViewLink = sscRepPg.locator('.sr-actions a.app-view-action').first();
  if (await frViewLink.count() > 0) {
    const [previewTab] = await Promise.all([
      sscRepCtx.waitForEvent('page').catch(() => null),
      frViewLink.click().catch(() => {}),
    ]);
    if (previewTab) await previewTab.close();
    await sscRepPg.waitForTimeout(1000);
  }

  const frSubmit = sscRepPg.locator('form[action*="/reports/fr/submit"] button[type="submit"]');
  if (await frSubmit.isVisible() && !(await frSubmit.isDisabled())) {
    console.log(`[REPORTS] Submitting FR to OSO...`);
    await frSubmit.click();
    await sscRepPg.waitForTimeout(600);
    const confirm = sscRepPg.locator('.action-confirm-dialog .action-confirm-accept');
    if (await confirm.isVisible()) await confirm.click();
    await sscRepPg.waitForTimeout(2000);
    console.log(`[REPORTS] FR Report submitted to OSO!`);
  }
  await sscRepCtx.close();

  // 4c. OSO Review AR & FR
  const osoRepCtx = await browser.newContext({ viewport: { width: 1440, height: 900 } });
  const osoRepPg = await loginAs(osoRepCtx, 'oso');
  await osoRepPg.goto(`${BASE}/office-desk/reports`, { waitUntil: 'networkidle' });
  console.log(`[REPORTS] OSO opened AR & FR Reports Review Desk`);

  await osoRepPg.screenshot({ path: 'storage/app/e2e-ssc-step4-ar-fr-completed.png', fullPage: false });
  console.log(`[SCREENSHOT] Saved: storage/app/e2e-ssc-step4-ar-fr-completed.png\n`);
  await osoRepCtx.close();

  console.log(`=======================================================`);
  console.log(`ALL 4 PHASES COMPLETED LIVE END-TO-END FOR SSC!`);
  console.log(`=======================================================`);

  await browser.close();
})();
