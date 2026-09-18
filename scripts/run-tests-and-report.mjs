/**
 * Run backend + UI smoke tests and write a detailed DOCX report.
 *
 * Usage:
 *   node scripts/run-tests-and-report.mjs
 *   node scripts/run-tests-and-report.mjs --skip-ui
 *   node scripts/run-tests-and-report.mjs --skip-backend
 */
import { execSync, spawnSync } from 'child_process';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import {
  AlignmentType,
  BorderStyle,
  Document,
  Footer,
  Header,
  HeadingLevel,
  LevelFormat,
  Packer,
  PageNumber,
  Paragraph,
  ShadingType,
  Table,
  TableCell,
  TableRow,
  TextRun,
  WidthType,
} from 'docx';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ROOT = path.resolve(__dirname, '..');
const OUT_DIR = path.join(ROOT, 'storage', 'app', 'test-reports');
const args = new Set(process.argv.slice(2));
const skipUi = args.has('--skip-ui');
const skipBackend = args.has('--skip-backend');
const skipAuth = args.has('--skip-auth');
const skipPageSmoke = args.has('--skip-pages');

const border = { style: BorderStyle.SINGLE, size: 8, color: 'D4C4C8' };
const borders = { top: border, bottom: border, left: border, right: border };
const headerShading = { type: ShadingType.CLEAR, fill: '8B1828' };
const altShading = { type: ShadingType.CLEAR, fill: 'FDF6F7' };
const TABLE_W = 9360;

function ensureDir(dir) {
  fs.mkdirSync(dir, { recursive: true });
}

function stamp() {
  const d = new Date();
  const p = (n) => String(n).padStart(2, '0');
  return `${d.getFullYear()}${p(d.getMonth() + 1)}${p(d.getDate())}-${p(d.getHours())}${p(d.getMinutes())}`;
}

function run(cmd, opts = {}) {
  const started = Date.now();
  const result = spawnSync(cmd, {
    cwd: ROOT,
    shell: true,
    encoding: 'utf8',
    env: { ...process.env, ...opts.env },
    maxBuffer: 20 * 1024 * 1024,
  });
  return {
    command: cmd,
    exitCode: result.status ?? 1,
    stdout: result.stdout || '',
    stderr: result.stderr || '',
    durationMs: Date.now() - started,
  };
}

function parseJUnit(xmlPath) {
  if (!fs.existsSync(xmlPath)) {
    return { suites: [], totals: { tests: 0, failures: 0, errors: 0, skipped: 0, time: 0 }, cases: [] };
  }
  const xml = fs.readFileSync(xmlPath, 'utf8');
  const suites = [];
  const cases = [];
  const suiteRe = /<testsuite\b([^>]*)>([\s\S]*?)<\/testsuite>/g;
  let m;
  while ((m = suiteRe.exec(xml))) {
    const attrs = m[1];
    const body = m[2];
    const name = /name="([^"]*)"/.exec(attrs)?.[1] || 'suite';
    const tests = Number(/tests="([^"]*)"/.exec(attrs)?.[1] || 0);
    const failures = Number(/failures="([^"]*)"/.exec(attrs)?.[1] || 0);
    const errors = Number(/errors="([^"]*)"/.exec(attrs)?.[1] || 0);
    const skipped = Number(/skipped="([^"]*)"/.exec(attrs)?.[1] || 0);
    const time = Number(/time="([^"]*)"/.exec(attrs)?.[1] || 0);
    if (name === 'Unit' || name === 'Feature' || tests > 0) {
      suites.push({ name, tests, failures, errors, skipped, time });
    }
    const caseRe = /<testcase\b([^>]*)\/>|<testcase\b([^>]*)>([\s\S]*?)<\/testcase>/g;
    let c;
    while ((c = caseRe.exec(body))) {
      const a = c[1] || c[2] || '';
      const inner = c[3] || '';
      const className = /classname="([^"]*)"/.exec(a)?.[1] || '';
      const testName = /name="([^"]*)"/.exec(a)?.[1] || '';
      const t = Number(/time="([^"]*)"/.exec(a)?.[1] || 0);
      let status = 'passed';
      let detail = '';
      if (/<failure\b/.test(inner)) {
        status = 'failed';
        detail = (/<failure\b[^>]*>([\s\S]*?)<\/failure>/.exec(inner)?.[1] || '').trim().slice(0, 500);
      } else if (/<error\b/.test(inner)) {
        status = 'error';
        detail = (/<error\b[^>]*>([\s\S]*?)<\/error>/.exec(inner)?.[1] || '').trim().slice(0, 500);
      } else if (/<skipped\b/.test(inner)) {
        status = 'skipped';
      }
      cases.push({ suite: name, className, testName, time: t, status, detail });
    }
  }

  const totals = cases.reduce(
    (acc, row) => {
      acc.tests += 1;
      if (row.status === 'failed') acc.failures += 1;
      if (row.status === 'error') acc.errors += 1;
      if (row.status === 'skipped') acc.skipped += 1;
      acc.time += row.time;
      return acc;
    },
    { tests: 0, failures: 0, errors: 0, skipped: 0, time: 0 }
  );

  return { suites, totals, cases };
}

function loadUiReport() {
  const p = path.join(ROOT, 'storage', 'app', 'console-smoke-report.json');
  if (!fs.existsSync(p)) return null;
  return JSON.parse(fs.readFileSync(p, 'utf8'));
}

/** Strip ANSI colors + XML-illegal control chars so Word can open the DOCX. */
function safeText(value) {
  return String(value ?? '')
    .replace(/\u001b\[[0-9;?]*[ -/]*[@-~]/g, '') // CSI sequences
    .replace(/\u001b[@-Z\\-_]/g, '') // other ESC sequences
    .replace(/[\u0000-\u0008\u000B\u000C\u000E-\u001F]/g, '')
    .replace(/\r\n/g, '\n')
    .replace(/\r/g, '\n');
}

function p(text, opts = {}) {
  return new Paragraph({
    spacing: { after: opts.after ?? 120 },
    ...opts,
    children: [
      new TextRun({
        text: safeText(text),
        font: 'Arial',
        size: opts.size ?? 22,
        bold: opts.bold,
        color: opts.color,
        italics: opts.italics,
      }),
    ],
  });
}

function h1(text) {
  return new Paragraph({
    heading: HeadingLevel.HEADING_1,
    spacing: { before: 280, after: 160 },
    children: [new TextRun({ text: safeText(text), font: 'Arial', bold: true, size: 32, color: '1A1618' })],
  });
}

function h2(text) {
  return new Paragraph({
    heading: HeadingLevel.HEADING_2,
    spacing: { before: 220, after: 120 },
    children: [new TextRun({ text: safeText(text), font: 'Arial', bold: true, size: 26, color: '8B1828' })],
  });
}

function cell(text, width, opts = {}) {
  return new TableCell({
    borders,
    width: { size: width, type: WidthType.DXA },
    shading: opts.header ? headerShading : opts.alt ? altShading : undefined,
    children: [
      new Paragraph({
        children: [
          new TextRun({
            text: safeText(text),
            font: 'Arial',
            size: opts.header ? 18 : 18,
            bold: opts.header || opts.bold,
            color: opts.header ? 'FFFFFF' : opts.color || '1A1618',
          }),
        ],
      }),
    ],
  });
}

function table(headers, rows, widths) {
  const w = widths || headers.map(() => Math.floor(TABLE_W / headers.length));
  return new Table({
    width: { size: TABLE_W, type: WidthType.DXA },
    columnWidths: w,
    rows: [
      new TableRow({
        children: headers.map((h, i) => cell(h, w[i], { header: true })),
      }),
      ...rows.map(
        (row, ri) =>
          new TableRow({
            children: row.map((val, i) =>
              cell(val, w[i], {
                alt: ri % 2 === 1,
                color: String(val).toLowerCase().includes('fail') || String(val).toLowerCase().includes('error')
                  ? 'B91C1C'
                  : String(val).toLowerCase() === 'passed' || String(val).toLowerCase() === 'ok'
                    ? '15803D'
                    : undefined,
                bold: i === 0,
              })
            ),
          })
      ),
    ],
  });
}

async function buildDocx(payload) {
  const { generatedAt, backend, ui, auth, overall } = payload;
  const children = [];

  children.push(
    new Paragraph({
      alignment: AlignmentType.CENTER,
      spacing: { after: 80 },
      children: [new TextRun({ text: 'OrgChain', font: 'Arial', bold: true, size: 40, color: '8B1828' })],
    })
  );
  children.push(
    new Paragraph({
      alignment: AlignmentType.CENTER,
      spacing: { after: 80 },
      children: [new TextRun({ text: 'Automated Testing Report', font: 'Arial', bold: true, size: 36, color: '1A1618' })],
    })
  );
  children.push(
    new Paragraph({
      alignment: AlignmentType.CENTER,
      spacing: { after: 240 },
      children: [
        new TextRun({
          text: `Generated ${generatedAt} · Backend (PHPUnit) + UI (Playwright smoke)`,
          font: 'Arial',
          size: 20,
          color: '7A7074',
          italics: true,
        }),
      ],
    })
  );

  children.push(h1('1. Executive Summary'));
  children.push(
    table(
      ['Layer', 'Result', 'Details', 'Duration'],
      [
        [
          'Overall',
          overall.ok ? 'PASSED' : 'FAILED',
          overall.summary,
          `${((backend?.durationMs || 0) + (ui?.durationMs || 0) + (auth?.durationMs || 0)) / 1000}s`,
        ],
        [
          'Backend',
          backend?.skipped ? 'SKIPPED' : backend?.ok ? 'PASSED' : 'FAILED',
          backend?.skipped
            ? 'Not run (--skip-backend)'
            : `${backend?.totals?.tests || 0} tests · ${backend?.totals?.failures || 0} fail · ${backend?.totals?.errors || 0} err · ${backend?.totals?.skipped || 0} skip`,
          backend?.skipped ? '—' : `${((backend?.durationMs || 0) / 1000).toFixed(1)}s`,
        ],
        [
          'Auth / OTP UI',
          auth?.skipped ? 'SKIPPED' : auth?.ok ? 'PASSED' : 'FAILED',
          auth?.skipped
            ? 'Not run (--skip-auth)'
            : `${auth?.stepsOk ?? 0}/${auth?.stepsTotal ?? 0} steps · ${auth?.issueCount ?? 0} issues`,
          auth?.skipped ? '—' : `${((auth?.durationMs || 0) / 1000).toFixed(1)}s`,
        ],
        [
          'UI Page Smoke',
          ui?.skipped ? 'SKIPPED' : ui?.ok ? 'PASSED' : 'FAILED',
          ui?.skipped
            ? 'Not run (--skip-ui / --skip-pages)'
            : `${ui?.issueCount ?? 0} console/HTTP issues across ${ui?.pageCount ?? 0} pages`,
          ui?.skipped ? '—' : `${((ui?.durationMs || 0) / 1000).toFixed(1)}s`,
        ],
      ],
      [1800, 1400, 4360, 1800]
    )
  );

  children.push(h1('2. Backend (PHPUnit) Details'));
  if (backend?.skipped) {
    children.push(p('Backend suite was skipped for this run.'));
  } else {
    children.push(p(`Command: ${backend.command}`));
    children.push(p(`Exit code: ${backend.exitCode}`));
    if (backend.suites?.length) {
      children.push(h2('2.1 Suites'));
      children.push(
        table(
          ['Suite', 'Tests', 'Failures', 'Errors', 'Skipped', 'Time (s)'],
          backend.suites.map((s) => [
            s.name,
            s.tests,
            s.failures,
            s.errors,
            s.skipped,
            Number(s.time || 0).toFixed(2),
          ]),
          [2400, 1200, 1400, 1200, 1400, 1760]
        )
      );
    }
    children.push(h2('2.2 Test Cases'));
    if (backend.cases?.length) {
      children.push(
        table(
          ['Status', 'Class', 'Test', 'Time (s)'],
          backend.cases.map((c) => [
            c.status.toUpperCase(),
            (c.className || '').split('\\').pop() || c.suite,
            c.testName,
            Number(c.time || 0).toFixed(3),
          ]),
          [1400, 2800, 3960, 1200]
        )
      );
      const failed = backend.cases.filter((c) => c.status === 'failed' || c.status === 'error');
      if (failed.length) {
        children.push(h2('2.3 Failure Details'));
        for (const f of failed) {
          children.push(p(`${f.className} :: ${f.testName}`, { bold: true, color: 'B91C1C' }));
          children.push(p(f.detail || 'No failure message captured.', { size: 18, color: '3F3538' }));
        }
      }
    } else {
      children.push(p('No JUnit testcases parsed. See raw log appendix.'));
    }
  }

  children.push(h1('3. Auth & OTP (Office + Student)'));
  if (auth?.skipped) {
    children.push(p('Auth/OTP UI journey was skipped for this run.'));
  } else if (!auth?.report) {
    children.push(p('Auth/OTP smoke did not produce a report.', { color: 'B91C1C', bold: true }));
    if (auth?.errorHint) children.push(p(auth.errorHint, { size: 18 }));
  } else {
    children.push(p(`Result: ${auth.ok ? 'PASSED' : 'FAILED'} · issues=${auth.issueCount}`));
    children.push(
      table(
        ['Step', 'Status', 'Detail'],
        (auth.report.steps || []).map((s) => [
          s.name,
          s.skipped ? 'SKIPPED' : s.ok ? 'OK' : 'FAIL',
          s.detail || s.http || '—',
        ]),
        [3600, 1600, 4160]
      )
    );
    if (auth.report.findings?.length) {
      children.push(h2('3.1 Auth findings'));
      for (const f of auth.report.findings) {
        children.push(
          new Paragraph({
            numbering: { reference: 'bullets', level: 0 },
            children: [
              new TextRun({
                text: safeText(`${f.kind}: ${String(f.text || '').slice(0, 220)}`),
                font: 'Arial',
                size: 18,
                color: '3F3538',
              }),
            ],
          })
        );
      }
    }
    children.push(
      p(
        'Full OTP verify in the browser needs EXPOSE_TEST_OTP=true in .env (local only) and a server restart. Backend PHPUnit covers send/verify/lockout without that flag.',
        { size: 18, italics: true, color: '7A7074' }
      )
    );
  }

  children.push(h1('4. UI Page Smoke (Playwright) Details'));
  if (ui?.skipped) {
    children.push(p('UI page smoke was skipped for this run.'));
  } else if (!ui?.report) {
    children.push(p('UI smoke did not produce a fresh report JSON.', { color: 'B91C1C', bold: true }));
    if (ui?.errorHint) {
      children.push(p(ui.errorHint, { size: 18, color: '3F3538' }));
    }
  } else {
    children.push(p(`Base URL: ${ui.report.base || 'http://127.0.0.1:8000'}`));
    children.push(p(`Issues found: ${ui.issueCount}`));
    children.push(h2('4.1 Roles & Pages'));
    const pageRows = [];
    for (const [role, pages] of Object.entries(ui.report.roles || {})) {
      for (const [pagePath, findings] of Object.entries(pages || {})) {
        const real = Array.isArray(findings) ? findings : [];
        pageRows.push([
          role,
          pagePath,
          real.length ? 'ISSUES' : 'OK',
          real.length ? real.map((f) => f.kind).join(', ') : '—',
        ]);
      }
    }
    children.push(table(['Role', 'Path', 'Status', 'Finding kinds'], pageRows, [1400, 3600, 1400, 2960]));

    if (ui.report.summary?.length) {
      children.push(h2('4.2 Issue Breakdown'));
      for (const row of ui.report.summary) {
        children.push(p(`[${row.role}] ${row.path}`, { bold: true }));
        for (const f of row.findings || []) {
          children.push(
            new Paragraph({
              numbering: { reference: 'bullets', level: 0 },
              spacing: { after: 60 },
              children: [
                new TextRun({
                  text: safeText(`${f.kind}: ${String(f.text || '').slice(0, 220)}`),
                  font: 'Arial',
                  size: 18,
                  color: '3F3538',
                }),
              ],
            })
          );
        }
      }
    } else {
      children.push(p('No console, page, or HTTP document errors on crawled pages.', { color: '15803D' }));
    }
  }

  children.push(h1('5. Coverage Notes'));
  children.push(
    new Paragraph({
      numbering: { reference: 'bullets', level: 0 },
      children: [new TextRun({ text: 'Backend: PHPUnit — BudgetChain, Renewal access, Office auth, Student OTP send/verify/lockout, portal guard.', font: 'Arial', size: 20 })],
    })
  );
  children.push(
    new Paragraph({
      numbering: { reference: 'bullets', level: 0 },
      children: [new TextRun({ text: 'Auth UI: Playwright — office bad/good login + student OTP JSON send/verify (needs EXPOSE_TEST_OTP for verify).', font: 'Arial', size: 20 })],
    })
  );
  children.push(
    new Paragraph({
      numbering: { reference: 'bullets', level: 0 },
      children: [new TextRun({ text: 'Page smoke: Playwright — public/SO/OSO/SDO/OVCAA/Student pages + light safe clicks.', font: 'Arial', size: 20 })],
    })
  );
  children.push(
    new Paragraph({
      numbering: { reference: 'bullets', level: 0 },
      children: [new TextRun({ text: 'Not covered: live Google OAuth round-trip, real SMTP delivery, password reset (N/A).', font: 'Arial', size: 20 })],
    })
  );

  children.push(h1('6. How to Re-run'));
  children.push(p('npm run test:report'));
  children.push(p('npm run test:report:backend'));
  children.push(p('node scripts/auth-otp-smoke.mjs'));
  children.push(p('php artisan test --filter=AuthAndOtpTest'));
  children.push(p('For full browser OTP verify: set EXPOSE_TEST_OTP=true in .env, restart serve, then re-run auth smoke.'));

  if (backend && !backend.skipped) {
    children.push(h1('Appendix A — Backend Raw Output (trimmed)'));
    const raw = `${backend.stdout}\n${backend.stderr}`.trim().slice(0, 6000);
    children.push(p(raw || '(empty)', { size: 16, color: '554D50' }));
  }

  const doc = new Document({
    styles: {
      default: { document: { run: { font: 'Arial', size: 22 } } },
      paragraphStyles: [
        {
          id: 'Heading1',
          name: 'Heading 1',
          basedOn: 'Normal',
          next: 'Normal',
          quickFormat: true,
          run: { size: 32, bold: true, font: 'Arial', color: '1A1618' },
          paragraph: { spacing: { before: 280, after: 160 }, outlineLevel: 0 },
        },
        {
          id: 'Heading2',
          name: 'Heading 2',
          basedOn: 'Normal',
          next: 'Normal',
          quickFormat: true,
          run: { size: 26, bold: true, font: 'Arial', color: '8B1828' },
          paragraph: { spacing: { before: 220, after: 120 }, outlineLevel: 1 },
        },
      ],
    },
    numbering: {
      config: [
        {
          reference: 'bullets',
          levels: [
            {
              level: 0,
              format: LevelFormat.BULLET,
              text: '•',
              alignment: AlignmentType.LEFT,
              style: { paragraph: { indent: { left: 720, hanging: 360 } } },
            },
          ],
        },
      ],
    },
    sections: [
      {
        properties: {
          page: {
            size: { width: 12240, height: 15840 },
            margin: { top: 1080, right: 1080, bottom: 1080, left: 1080 },
          },
        },
        headers: {
          default: new Header({
            children: [
              new Paragraph({
                children: [
                  new TextRun({ text: 'OrgChain · Automated Testing Report', font: 'Arial', size: 16, color: '7A7074' }),
                ],
              }),
            ],
          }),
        },
        footers: {
          default: new Footer({
            children: [
              new Paragraph({
                alignment: AlignmentType.CENTER,
                children: [
                  new TextRun({ text: 'Page ', font: 'Arial', size: 16, color: '7A7074' }),
                  new TextRun({ children: [PageNumber.CURRENT], font: 'Arial', size: 16, color: '7A7074' }),
                  new TextRun({ text: ' of ', font: 'Arial', size: 16, color: '7A7074' }),
                  new TextRun({ children: [PageNumber.TOTAL_PAGES], font: 'Arial', size: 16, color: '7A7074' }),
                ],
              }),
            ],
          }),
        },
        children,
      },
    ],
  });

  return Packer.toBuffer(doc);
}

async function main() {
  ensureDir(OUT_DIR);
  const id = stamp();
  const junitPath = path.join(OUT_DIR, `phpunit-${id}.xml`);
  const metaPath = path.join(OUT_DIR, `run-${id}.json`);

  let backend = { skipped: true };
  if (!skipBackend) {
    console.log('Running backend PHPUnit...');
    const cmd = `php artisan test --log-junit="${junitPath}"`;
    const result = run(cmd);
    const parsed = parseJUnit(junitPath);
    backend = {
      skipped: false,
      command: cmd,
      exitCode: result.exitCode,
      durationMs: result.durationMs,
      stdout: result.stdout,
      stderr: result.stderr,
      ...parsed,
      ok: result.exitCode === 0 && parsed.totals.failures === 0 && parsed.totals.errors === 0,
    };
    console.log(`  Backend exit=${result.exitCode} tests=${parsed.totals.tests}`);
  }

  let ui = { skipped: true };
  if (!skipUi && !skipPageSmoke) {
    console.log('Running UI Playwright page smoke...');
    const startedAt = Date.now();
    const result = run('node scripts/console-smoke.mjs');
    const reportPath = path.join(ROOT, 'storage', 'app', 'console-smoke-report.json');
    let report = null;
    if (fs.existsSync(reportPath)) {
      const mtime = fs.statSync(reportPath).mtimeMs;
      if (mtime >= startedAt - 2000) {
        report = JSON.parse(fs.readFileSync(reportPath, 'utf8'));
      }
    }
    const pageCount = report
      ? Object.values(report.roles || {}).reduce((n, pages) => n + Object.keys(pages || {}).length, 0)
      : 0;
    const issueCount = report?.issueCount ?? (result.exitCode === 0 ? 0 : 1);
    ui = {
      skipped: false,
      exitCode: result.exitCode,
      durationMs: result.durationMs,
      stdout: result.stdout,
      stderr: result.stderr,
      report,
      issueCount,
      pageCount,
      ok: result.exitCode === 0 && !!report && issueCount === 0,
      errorHint: result.exitCode !== 0 ? (result.stderr || result.stdout || 'UI smoke failed').slice(0, 400) : '',
    };
    console.log(`  UI exit=${result.exitCode} issues=${ui.issueCount} pages=${pageCount}`);
  }

  let auth = { skipped: true };
  if (!skipUi && !skipAuth) {
    console.log('Running Auth/OTP Playwright journey...');
    const startedAt = Date.now();
    const result = run('node scripts/auth-otp-smoke.mjs');
    const reportPath = path.join(ROOT, 'storage', 'app', 'auth-otp-smoke-report.json');
    let report = null;
    if (fs.existsSync(reportPath) && fs.statSync(reportPath).mtimeMs >= startedAt - 2000) {
      report = JSON.parse(fs.readFileSync(reportPath, 'utf8'));
    }
    const steps = report?.steps || [];
    auth = {
      skipped: false,
      exitCode: result.exitCode,
      durationMs: result.durationMs,
      stdout: result.stdout,
      stderr: result.stderr,
      report,
      stepsTotal: steps.length,
      stepsOk: steps.filter((s) => s.ok).length,
      issueCount: report?.issueCount ?? (result.exitCode === 0 ? 0 : 1),
      ok: result.exitCode === 0 && !!report && (report.ok === true),
      errorHint: result.exitCode !== 0 ? (result.stderr || result.stdout || 'Auth smoke failed').slice(0, 400) : '',
    };
    console.log(`  Auth exit=${result.exitCode} steps=${auth.stepsOk}/${auth.stepsTotal}`);
  }

  const overall = {
    ok: (backend.skipped || backend.ok) && (ui.skipped || ui.ok) && (auth.skipped || auth.ok),
    summary: [
      backend.skipped ? 'backend skipped' : backend.ok ? 'backend pass' : 'backend fail',
      auth.skipped ? 'auth skipped' : auth.ok ? 'auth pass' : 'auth fail',
      ui.skipped ? 'pages skipped' : ui.ok ? 'pages pass' : 'pages fail',
    ].join(' · '),
  };

  const payload = {
    generatedAt: new Date().toISOString(),
    backend,
    ui,
    auth,
    overall,
  };
  fs.writeFileSync(metaPath, JSON.stringify(payload, null, 2));

  console.log('Building DOCX...');
  const buffer = await buildDocx(payload);
  const docxPath = path.join(OUT_DIR, `OrgChain-Test-Report-${id}.docx`);
  fs.writeFileSync(docxPath, buffer);

  // Also write a stable "latest" copy (ignore lock if Word has the file open)
  const latest = path.join(OUT_DIR, 'OrgChain-Test-Report-LATEST.docx');
  try {
    fs.copyFileSync(docxPath, latest);
  } catch (e) {
    if (e && e.code === 'EBUSY') {
      console.warn('LATEST.docx is locked (close Word and re-run to refresh). Timestamped report is ready.');
    } else {
      throw e;
    }
  }

  console.log(`\nDOCX: ${docxPath}`);
  console.log(`LATEST: ${latest}`);
  console.log(`Meta: ${metaPath}`);
  process.exit(overall.ok ? 0 : 1);
}

main().catch((e) => {
  console.error(e);
  process.exit(2);
});
