# OrgChain Slim Chain Node (helper laptop)

Other laptops do **not** need full OrgChain / Laragon / MySQL.

## Requirements
- Node.js
- ngrok account + authtoken

## Run
Double-click `scripts/start-chain-node.bat`  
or:

```powershell
.\scripts\start-chain-node.ps1
```

It will ask for:
1. **ngrok authtoken**
2. **shared node secret** (must match main `BLOCKCHAIN_NODE_SECRET`)
3. **Node ID** (`2` or `3`)

Then it starts the slim node + ngrok and prints a public URL.

## On MAIN OrgChain `.env`
```env
BLOCKCHAIN_NODE_1_URL=local
BLOCKCHAIN_NODE_2_URL=https://YOUR-NODE2.ngrok-free.dev
BLOCKCHAIN_NODE_3_URL=https://YOUR-NODE3.ngrok-free.dev
BLOCKCHAIN_NODE_SECRET=same-secret-used-on-helper-laptops
```

Laptop must stay **on**. Close the script = that node goes offline.

## Same Wi‑Fi (no ngrok)
```powershell
$env:ORGCHAIN_NODE_ID=2
$env:ORGCHAIN_NODE_PORT=8001
$env:ORGCHAIN_NODE_SECRET="your-secret"
node scripts/chain-node/server.mjs
```
Main uses `http://192.168.x.x:8001` instead of ngrok.
