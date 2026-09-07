# ADR-002: Naming Convention

## Decision
- PHP/Laravel: snake_case (DB, routes) + PascalCase (classes) + camelCase
  (methods) — PSR-12 standard
- JS/React: camelCase (variables/functions) + PascalCase (components)
- Business domain vocabulary in Spanish on both sides
- AGENTS.md and ADRs in English (reloaded every session — token efficiency);
  all generated code in Spanish

## Reason
Avoids mixing two naming languages within the same domain, follows each
ecosystem's standard instead of inventing one, and keeps agent context cheap
without touching domain code.

