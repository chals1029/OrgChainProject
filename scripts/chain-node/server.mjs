/**
 * OrgChain slim blockchain validator node (no full Laragon app required).
 *
 * Compatible with VoteChain remote APIs:
 *   POST /voting-system/api/blockchain/node-receive
 *   GET  /voting-system/api/blockchain/node-verify-block
 *   GET  /voting-system/api/blockchain/node-status
 *   GET  /health
 *
 * Usage:
 *   node scripts/chain-node/server.mjs
 *   ORGCHAIN_NODE_ID=2 ORGCHAIN_NODE_SECRET=... ORGCHAIN_NODE_PORT=8001 node scripts/chain-node/server.mjs
 */
import http from 'http';
import fs from 'fs';
import path from 'path';
import crypto from 'crypto';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ROOT = path.resolve(__dirname, '../..');

const PORT = Number(process.env.ORGCHAIN_NODE_PORT || 8001);
const NODE_ID = Number(process.env.ORGCHAIN_NODE_ID || 2);
const SECRET =
  process.env.ORGCHAIN_NODE_SECRET ||
  process.env.BLOCKCHAIN_NODE_SECRET ||
  'orgchain-node-auth-secret-2026';
const LEDGER_ROOT =
  process.env.ORGCHAIN_NODE_LEDGER ||
  path.join(ROOT, 'storage', 'app', 'chain-node', `node-${NODE_ID}`);

function ensureDir(dir) {
  fs.mkdirSync(dir, { recursive: true });
}

function sha256Json(obj) {
  return crypto.createHash('sha256').update(JSON.stringify(obj)).digest('hex');
}

function voteExpectedHash(block) {
  return sha256Json({
    election_id: Number(block.election_id ?? 1),
    reference_code: String(block.reference_code ?? ''),
    voter_commitment: String(block.voter_commitment ?? ''),
    ballot_root: String(block.ballot_root ?? ''),
    created_at: String(block.created_at ?? ''),
    previous_hash: String(block.previous_hash ?? ''),
  });
}

function budgetExpectedHash(block) {
  return sha256Json({
    type: 'budget_utilization',
    index: Number(block.index ?? 0),
    previous_hash: String(block.previous_hash ?? ''),
    activity_title: String(block.activity_title ?? ''),
    item_name: String(block.item_name ?? ''),
    supplier: String(block.supplier ?? ''),
    organization_name: String(block.organization_name ?? ''),
    receipt_reference: String(block.receipt_reference ?? ''),
    quantity: Number(block.quantity ?? 1),
    unit_cost: Number(block.unit_cost ?? 0),
    total: Number(block.total ?? 0),
    expense_date: String(block.expense_date ?? ''),
    sealed_at: String(block.sealed_at ?? ''),
  });
}

function verifyBlock(block) {
  const received = String(block.block_hash ?? '');
  if (!received) return { ok: false, error: 'Missing block_hash' };

  if (block.type === 'budget_utilization') {
    const expected = budgetExpectedHash(block);
    if (expected !== received) {
      return { ok: false, error: 'Budget hash mismatch', expected, received };
    }
    return { ok: true, kind: 'budget' };
  }

  // Default: VoteChain integrity check
  const expected = voteExpectedHash(block);
  if (expected !== received) {
    return { ok: false, error: 'Vote hash mismatch', expected, received };
  }
  return { ok: true, kind: 'vote' };
}

function ledgerPath(electionId) {
  ensureDir(LEDGER_ROOT);
  if (electionId != null) {
    return path.join(LEDGER_ROOT, `election-${electionId}.jsonl`);
  }
  return path.join(LEDGER_ROOT, 'budget.jsonl');
}

function appendBlock(block, targetNode) {
  const electionId = block.type === 'budget_utilization' ? null : Number(block.election_id ?? 1);
  const file = ledgerPath(electionId);
  fs.appendFileSync(file, JSON.stringify(block) + '\n', 'utf8');
  return {
    status: 'ok',
    node: targetNode || NODE_ID,
    block_hash: block.block_hash,
    path: path.relative(ROOT, file),
  };
}

function readBlocks(electionId) {
  const file = ledgerPath(electionId);
  if (!fs.existsSync(file)) return [];
  return fs
    .readFileSync(file, 'utf8')
    .split(/\r?\n/)
    .filter(Boolean)
    .map((line) => {
      try {
        return JSON.parse(line);
      } catch {
        return null;
      }
    })
    .filter(Boolean);
}

function getToken(req) {
  const h = req.headers['x-node-token'];
  if (h) return String(h);
  const auth = req.headers.authorization || '';
  const m = /Bearer\s+(.*)$/i.exec(auth);
  return m ? m[1].trim() : '';
}

function tokensMatch(a, b) {
  const aa = Buffer.from(String(a));
  const bb = Buffer.from(String(b));
  if (aa.length !== bb.length) return false;
  return crypto.timingSafeEqual(aa, bb);
}

function sendJson(res, code, body) {
  const payload = JSON.stringify(body, null, 2);
  res.writeHead(code, {
    'Content-Type': 'application/json',
    'Access-Control-Allow-Origin': '*',
    'Access-Control-Allow-Headers': 'Content-Type, X-Node-Token, Authorization, ngrok-skip-browser-warning',
  });
  res.end(payload);
}

function readBody(req) {
  return new Promise((resolve, reject) => {
    const chunks = [];
    req.on('data', (c) => chunks.push(c));
    req.on('end', () => resolve(Buffer.concat(chunks).toString('utf8')));
    req.on('error', reject);
  });
}

function route(urlPath) {
  return urlPath.replace(/\?.*$/, '');
}

ensureDir(LEDGER_ROOT);

const server = http.createServer(async (req, res) => {
  const url = new URL(req.url || '/', `http://${req.headers.host || 'localhost'}`);
  const p = route(url.pathname);

  if (req.method === 'OPTIONS') {
    sendJson(res, 200, { ok: true });
    return;
  }

  if (req.method === 'GET' && (p === '/' || p === '/health')) {
    sendJson(res, 200, {
      ok: true,
      service: 'OrgChain Chain Node',
      node_id: NODE_ID,
      ledger: LEDGER_ROOT,
      endpoints: [
        'POST /voting-system/api/blockchain/node-receive',
        'GET /voting-system/api/blockchain/node-verify-block',
        'GET /voting-system/api/blockchain/node-status',
      ],
    });
    return;
  }

  // Auth for mutating / sensitive node APIs
  const needsAuth =
    p.includes('/api/blockchain/node-receive') ||
    p.includes('/api/blockchain/node-verify-block') ||
    p.includes('/api/blockchain/node-status');

  if (needsAuth) {
    const token = getToken(req);
    if (SECRET && !tokensMatch(SECRET, token)) {
      sendJson(res, 403, { ok: false, error: 'Unauthorized node. Invalid or missing node security token.' });
      return;
    }
  }

  if (req.method === 'POST' && p === '/voting-system/api/blockchain/node-receive') {
    const raw = await readBody(req);
    let payload;
    try {
      payload = JSON.parse(raw || '{}');
    } catch {
      sendJson(res, 400, { ok: false, error: 'Invalid JSON body.' });
      return;
    }

    if (!payload?.block || typeof payload.block !== 'object') {
      sendJson(res, 400, { ok: false, error: 'Invalid payload format. Expected JSON containing block object.' });
      return;
    }

    const check = verifyBlock(payload.block);
    if (!check.ok) {
      sendJson(res, 400, {
        ok: false,
        error: 'Cryptographic integrity failure: ' + check.error,
        expected: check.expected,
        received: check.received,
      });
      return;
    }

    const targetNode = Number(payload.target_node ?? NODE_ID);
    const result = appendBlock(payload.block, targetNode);
    sendJson(res, 200, {
      ok: true,
      status: 'ok',
      node: result.node,
      block_hash: result.block_hash,
      reference_code: payload.block.reference_code,
      message: 'Block successfully verified and appended to node ledger.',
      kind: check.kind,
      path: result.path,
    });
    return;
  }

  if (req.method === 'GET' && p === '/voting-system/api/blockchain/node-verify-block') {
    const electionId = Number(url.searchParams.get('election_id') || 1);
    const reference = String(url.searchParams.get('reference') || '');
    const blockHash = String(url.searchParams.get('block_hash') || '');
    if (!reference || !blockHash) {
      sendJson(res, 400, { ok: false, error: 'Missing required parameters: reference and block_hash are required.' });
      return;
    }

    const blocks = readBlocks(electionId);
    const found = blocks.find(
      (b) => b.block_hash === blockHash && String(b.reference_code || '') === reference
    );
    sendJson(res, 200, {
      api_version: '1.0',
      timestamp: new Date().toISOString(),
      result: {
        node: NODE_ID,
        status: found ? 'ok' : 'mismatch',
        message: found ? 'Block present and verified on node.' : 'Block not found on node ledger.',
        found: !!found,
      },
    });
    return;
  }

  if (req.method === 'GET' && p === '/voting-system/api/blockchain/node-status') {
    const electionId = Number(url.searchParams.get('election_id') || 1);
    const blocks = readBlocks(electionId);
    const last = blocks[blocks.length - 1] || null;
    sendJson(res, 200, {
      api_version: '1.0',
      timestamp: new Date().toISOString(),
      node_info: {
        node: NODE_ID,
        election_id: electionId,
        blocks: blocks.length,
        latest_hash: last?.block_hash || null,
        ledger_dir: LEDGER_ROOT,
        online: true,
      },
    });
    return;
  }

  sendJson(res, 404, { ok: false, error: 'Not found', path: p });
});

server.listen(PORT, '0.0.0.0', () => {
  console.log('');
  console.log('OrgChain slim chain node running');
  console.log(`  node_id : ${NODE_ID}`);
  console.log(`  port    : ${PORT}`);
  console.log(`  ledger  : ${LEDGER_ROOT}`);
  console.log(`  health  : http://127.0.0.1:${PORT}/health`);
  console.log('');
});
