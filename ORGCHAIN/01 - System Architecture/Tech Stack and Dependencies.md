---
title: Tech Stack and Dependencies
tags: [techstack, dependencies, php, laravel, composer]
created: 2026-08-20
updated: 2026-10-16
status: active
---

# Tech Stack and Dependencies

The values below are declared package constraints, not a promise that every machine runs the same installed patch release. `composer.lock` and `package-lock.json` record resolved dependencies; local CLI and deployed PHP-FPM can be different runtimes.

## Application packages

| Dependency | Declared constraint | Use |
| --- | --- | --- |
| PHP | `^8.3` | Application runtime |
| `laravel/framework` | `^13.8` | Routing, guards, ORM, views and services |
| `laravel/tinker` | `^3.0` | Local console tooling |
| `kornrunner/ethereum-offline-raw-tx` | `^0.9` | Ethereum-compatible transaction construction/signing dependency |
| `phpoffice/phpspreadsheet` | `^5.10` | Spreadsheet handling and workbook exports |
| `vite` | `^8.0.0` | Frontend bundling |
| `laravel-vite-plugin` | `^3.1` | Laravel asset integration |
| `tailwindcss`, `@tailwindcss/vite` | `^4.0.0` | CSS generation |
| `playwright` | `^1.63.0` | Browser tooling |
| `docx` | `^9.7.1` | Report-generation tooling |

Source: `composer.json` and `package.json`. Use the Vite dependency's `engines` field in the lockfile when choosing Node; do not reuse the old Node 18/Vite 6/Tailwind 3 instructions.

## Runtime services

- Windows/Laragon local development uses the PHP 8.3 family in the existing setup note.
- The last recorded Ubuntu VPS deployment uses PHP 8.5-FPM, MySQL 8.4, Nginx, Cloudflare Tunnel and Docker-managed Besu/conversion services. This documentation update did not query the VPS.
- Dual-schema MySQL is the configured runtime database architecture. SQLite is used by some isolated tests; it is not evidence of full native-voting/platform compatibility.
- Private document storage, self-hosted DOCX preview and browser PDF rendering complement the isolated legacy-Word converter. There is no in-app Word authoring/editor workflow.
- Besu QBFT and the explicit file/JSONL driver are distinct modes. See [[High-Level Technical Architecture]].

Use [[Local Development Setup Laragon PHP 8.3]] for safe local preparation and [[VPS Pilot Deployment and Status]] for the recorded deployment boundaries.
