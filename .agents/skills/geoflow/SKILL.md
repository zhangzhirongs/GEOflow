---
name: geoflow
description: Develop or operate GEOFlow across Laravel backend/admin/API/CLI, the default site, themes, leads, and GeoFlow Agent channel sites. Use for code changes, running-system operations, frontend or template edits, channel capability sync, legacy PHP migration, or retired yao-geoflow-cli, yao-geoflow-design, and yao-geoflow-template IDs. Discover real routes first. Excludes unrelated work, database shortcuts, invented routes, auth bypass, secret exposure, raw copying, and unapproved live or destructive actions.
---

# GEOFlow

## Capability Description

GEOFlow is a Laravel-based GEO content engineering and multi-site distribution platform. This skill routes work across AI model configuration, materials, prompts, knowledge bases and RAG, content tasks, article review and publication, analytics, leads, themes, homepage modules, enterprise knowledge, distribution channels, target-site packages, queues, and legacy migration.

When exposing GEOFlow through an MCP server, preserve these capability boundaries. Prefer read-only discovery tools first, use CLI/API v1 for catalog, task, job, material, and article operations, and use authenticated admin routes for capabilities not exposed by API v1. Every mutation must identify the target, preserve authorization and idempotency, and read the resource back afterward. High-risk tools such as deletion, secret rotation, system update, rollback, lead export, channel deletion, and theme publication require explicit target and confirmation fields.

MCP results should return a structured envelope containing `success`, `operation`, `resource`, final `state`, `verification`, and redacted `warnings` or `error` data. Separate local persistence success, queued-job success, and remote-channel success; do not report one as proof of the others.

## Route

1. Run `scripts/discover_geoflow_workspace.py <workspace>` for source work or `scripts/geoflow_preflight.sh "<workspace>" [config] [checks]` before runtime mutations. Inspect current CLI help and routes when available.
2. Load one route:

- `development`: [development-workflow.md](references/development-workflow.md) and [system-capability-discovery.md](references/system-capability-discovery.md)
- `operations`: [operation-boundary.md](references/operation-boundary.md), [command-map.md](references/command-map.md), and [geoflow-current-capability-map.md](references/geoflow-current-capability-map.md)
- `public_frontend`: [frontend-resource-index.md](references/frontend-resource-index.md) and [geoflow-frontend-map.md](references/geoflow-frontend-map.md)
- `channel_frontend`: [frontend-resource-index.md](references/frontend-resource-index.md) and [channel-frontend-contract.md](references/channel-frontend-contract.md)
- `legacy_migration`: [legacy-template-migration.md](references/legacy-template-migration.md) and [legacy-skill-id-migration.md](references/legacy-skill-id-migration.md)

3. Mixed work follows: discover, implement, test or preview, approved finalize.

## Mode Boundaries

Each phase has one mode. `development` edits code and tests; `operations` uses supported runtime interfaces; `public_frontend` prepares themes or payloads; `channel_frontend` handles target-package contracts. Switch modes when crossing these boundaries. Keep authenticated form creation, sync, activation, and publication as confirmed operations phases.

## Guardrails

- Follow repository rules and focused tests.
- Run bundled helpers on macOS, Linux, or WSL with Python 3.10+ and Bash where required; runtime preflight also needs `curl`, and live channel reports need PHP CLI plus a working project `artisan`. If a dependency is unavailable, stay in read-only discovery, report the missing verification layer, and do not claim live success.
- Preserve authentication, CSRF, idempotency, permissions, readback, contracts, and secret redaction.
- Separate preview, import, sync, activation, publication, updates, rollback, and destructive actions. Require the exact target and explicit approval for high-risk steps.
- Report the selected mode, evidence, touched files/routes/resources, verification, final state, and remaining risks.
