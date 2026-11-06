import { chromium } from 'playwright';
import { createServer } from 'node:http';
import { readFile } from 'node:fs/promises';
import assert from 'node:assert/strict';

const script = await readFile(new URL('../public/js/action-confirmations.js', import.meta.url));
const style = await readFile(new URL('../public/css/action-confirmations.css', import.meta.url));
const fixture = (explicit) => `<!doctype html><meta charset="utf-8"><link rel="stylesheet" href="/style.css">
<script src="/confirm.js" ${explicit ? 'data-confirm-scope="explicit"' : ''}></script>
<form id="auto" method="post" data-confirm-title="Remove from Draft?" data-confirm-accept="Yes, Remove" data-confirm-message="Remove this applicant? Their application will not be deleted.">
<input name="_method" value="DELETE"><input name="attachment" type="file"><button name="decision" value="remove" type="submit" formaction="/different-action" formmethod="post">Remove</button></form>
<form id="manual" method="post" data-confirm="manual"><input name="body" value="Private comment"><button type="submit">Send</button></form>
<form id="off" method="post" data-confirm="off"><button type="submit">No prompt</button></form>
<form id="plain" method="post"><button type="submit">Plain</button></form>
<form id="get" method="get"><button type="submit">Search</button></form>
<form id="invalid" method="post"><input name="required" required><button type="submit">Submit</button></form>
<form id="native" method="post" action="/capture" enctype="multipart/form-data"><input name="attachment" type="file"><button name="decision" value="approve" type="submit">Submit record</button></form>
<dialog id="parent"><form id="nested" method="post" data-confirm="manual"><button type="submit">Save nested</button></form></dialog>
<script>
window.results = [];
for (const id of ['auto','off','get','plain']) document.getElementById(id).addEventListener('submit', e => { e.preventDefault(); const data = new FormData(e.target, e.submitter); window.results.push({id, decision:data.get('decision'), method:data.get('_method'), file:data.get('attachment')?.name, action:e.submitter.formAction}); });
for (const id of ['manual','nested']) document.getElementById(id).addEventListener('submit', e => { e.preventDefault(); if (!window.OrgActionConfirmations.confirmSubmit(e, 'Save these changes?')) return; window.results.push({id}); });
</script>`;
const uploads = [];
const server = createServer((req, res) => {
    if (req.method === 'POST' && req.url === '/capture') {
        const chunks = [];
        req.on('data', (chunk) => chunks.push(chunk));
        req.on('end', () => { uploads.push(Buffer.concat(chunks).toString()); res.writeHead(200); res.end('Submitted'); });
        return;
    }
    if (req.url === '/confirm.js') { res.writeHead(200, { 'Content-Type': 'text/javascript' }); res.end(script); return; }
    if (req.url === '/style.css') { res.writeHead(200, { 'Content-Type': 'text/css' }); res.end(style); return; }
    res.writeHead(200, { 'Content-Type': 'text/html; charset=utf-8' });
    res.end(fixture(req.url === '/explicit'));
});
await new Promise((resolve) => server.listen(0, '127.0.0.1', resolve));
const base = `http://127.0.0.1:${server.address().port}`;
let browser;
try {
    browser = await chromium.launch({ headless: true });
    const page = await browser.newPage({ viewport: { width: 1100, height: 800 } });
    await page.goto(base);
    const dialog = page.locator('.action-confirm-dialog');
    const cancel = page.locator('.action-confirm-cancel');
    const accept = page.locator('.action-confirm-accept');
    await page.locator('#auto input[type=file]').setInputFiles({ name: 'evidence.txt', mimeType: 'text/plain', buffer: Buffer.from('evidence') });
    await page.locator('#auto button').click();
    await dialog.waitFor({ state: 'visible' });
    assert.equal(await page.locator('#actionConfirmTitle').textContent(), 'Remove from Draft?');
    assert.equal(await accept.textContent(), 'Yes, Remove');
    assert.deepEqual(await page.evaluate(() => window.results), []);
    await cancel.click();
    await dialog.waitFor({ state: 'hidden' });
    assert.deepEqual(await page.evaluate(() => window.results), []);
    await page.locator('#auto button').click();
    await dialog.waitFor({ state: 'visible' });
    await page.keyboard.press('Escape');
    await dialog.waitFor({ state: 'hidden' });
    assert.deepEqual(await page.evaluate(() => window.results), []);
    await page.locator('#auto button').click();
    await dialog.waitFor({ state: 'visible' });
    await page.evaluate(() => document.querySelector('#auto').requestSubmit(document.querySelector('#auto button')));
    await accept.click();
    await page.waitForFunction(() => window.results.length === 1);
    assert.deepEqual(await page.evaluate(() => window.results[0]), { id: 'auto', decision: 'remove', method: 'DELETE', file: 'evidence.txt', action: `${base}/different-action` });
    await page.locator('#manual button').click();
    await dialog.waitFor({ state: 'visible' });
    await cancel.click();
    await dialog.waitFor({ state: 'hidden' });
    assert.equal(await page.evaluate(() => window.results.length), 1);
    await page.locator('#manual button').click();
    await dialog.waitFor({ state: 'visible' });
    await accept.click();
    await page.waitForFunction(() => window.results.length === 2);
    assert.equal(await page.evaluate(() => window.results[1].id), 'manual');
    await page.evaluate(() => document.getElementById('parent').showModal());
    await page.locator('#nested button').click();
    await dialog.waitFor({ state: 'visible' });
    await accept.click();
    await page.waitForFunction(() => window.results.length === 3);
    assert.equal(await page.evaluate(() => document.getElementById('parent').open), true);
    await page.evaluate(() => document.getElementById('parent').close());
    await page.locator('#off button').click();
    await page.locator('#get button').click();
    assert.deepEqual(await page.evaluate(() => window.results.slice(3).map((x) => x.id)), ['off', 'get']);
    await page.locator('#invalid button').click();
    assert.equal(await dialog.isVisible(), false);
    await page.evaluate(() => { window.directAnswer = window.OrgActionConfirmations.confirm('Save these settings?'); });
    await dialog.waitFor({ state: 'visible' });
    assert.equal(await page.evaluate(() => window.OrgActionConfirmations.confirm('Second action?')), false);
    await cancel.click();
    assert.equal(await page.evaluate(() => window.directAnswer), false);
    await page.evaluate(() => { window.directAnswer = window.OrgActionConfirmations.confirm('Save these settings?'); });
    await dialog.waitFor({ state: 'visible' });
    await accept.click();
    assert.equal(await page.evaluate(() => window.directAnswer), true);
    await page.locator('#native input[type=file]').setInputFiles({ name: 'actual.txt', mimeType: 'text/plain', buffer: Buffer.from('real attachment') });
    await page.locator('#native button').click();
    await dialog.waitFor({ state: 'visible' });
    await cancel.click();
    await dialog.waitFor({ state: 'hidden' });
    assert.equal(uploads.length, 0);
    await page.locator('#native button').click();
    await dialog.waitFor({ state: 'visible' });
    await accept.click();
    await page.waitForURL('**/capture');
    assert.equal(uploads.length, 1);
    assert.match(uploads[0], /filename="actual\.txt"/);
    assert.match(uploads[0], /real attachment/);
    assert.match(uploads[0], /name="decision"\r\n\r\napprove/);
    await page.goto(`${base}/explicit`);
    await page.locator('#off button').click();
    await page.locator('#plain button').click();
    assert.equal(await dialog.count(), 0);
    await page.locator('#auto button').click();
    await dialog.waitFor({ state: 'visible' });
    await cancel.click();
    await dialog.waitFor({ state: 'hidden' });
    console.log('Confirmation smoke passed: cancel, Escape, submitter, upload, manual AJAX, nested dialog and explicit scope.');
} finally {
    await browser?.close();
    await new Promise((resolve) => server.close(resolve));
}
