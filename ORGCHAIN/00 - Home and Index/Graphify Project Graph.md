---
title: Graphify Project Graph
tags: [graphify, architecture]
status: active
---

# Graphify Project Graph

Open [[Graphify Generated/graph.canvas|the generated project canvas]] or use Obsidian's Graph view to browse the generated notes. Graphify's [interactive browser view](file:///C:/Users/charl/.graphify/orgchain/graphify-out/graph.html) and [analysis report](file:///C:/Users/charl/.graphify/orgchain/graphify-out/GRAPH_REPORT.md) are stored outside the repository on this computer.

The graph is a local snapshot of code and configuration extracted with Graphify. Inferred connections may be incorrect; verify against the source.

The scheduled refresh indexes code and configuration only; existing vault notes remain in Obsidian's own graph. `Graphify Generated`, ignored, private, and hidden files are excluded.

## Refresh the graph

The Windows scheduled task `OrgChain Graphify Refresh` runs `scripts/refresh-graphify.ps1` every five minutes while logged in. The computer must be awake.

For troubleshooting, run the same refresh manually from the project root in PowerShell:

```powershell
& ".\scripts\refresh-graphify.ps1"
```

The refresh log is at `%USERPROFILE%\.graphify\orgchain\refresh.log`.

The generated vault folder is ignored by Git. No Git hooks or cloud services are configured.
