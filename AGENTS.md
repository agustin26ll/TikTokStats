# TikTok Stats — Agent Context

## Project reality check (non-negotiable)
This is a real Laravel app, created via `laravel new` / `composer create-project
laravel/laravel`. It has artisan, bootstrap/, config/, routes/, database/.
NEVER treat this as a bare illuminate/* package. If artisan is missing,
STOP and flag it — do not attempt to work around it.

## Language rule
This file and all ADRs are in English (reloaded every session — token
efficiency). All generated code (identifiers, comments, domain vocabulary)
MUST be in Spanish. Never translate domain terms like `Publicacion`,
`calcularRetencionPromedio`, etc.

## Stack
- Frontend: React (Vite) + SheetJS
- Backend: Laravel (PHP 8.3+), REST API
- DB: SQLite (dev), MySQL/PostgreSQL free tier (prod, Track B)

## Naming conventions (do not break)
- Spanish domain vocabulary on both sides
- PHP: snake_case (DB/routes), PascalCase (classes), camelCase (methods) — PSR-12
- JS/React: camelCase (variables/functions), PascalCase (components)
- Each new statistic = its own independent use-case class — never add an `if`
  to an existing class to support a different statistic

## Mandatory SOLID principles
- SRP: one class = one statistic or one responsibility.
- OCP: new statistic → new class implementing `CalculadoraEstadistica`.
- LSP: any repository interface implementation must be swappable.
- ISP: small, specific interfaces.
- DIP: controllers depend on `Application/` interfaces, never Eloquent directly.

## Design patterns to use
- Strategy: statistics implement `CalculadoraEstadistica`.
- Repository: behind `PublicacionRepositorioInterface`.
- DTO: crosses Infrastructure → Application → Http, never a raw Eloquent model.

## Do NOT
- Mix business logic into controllers
- Scrape or call unauthorized TikTok endpoints
- Evaluate formulas from uploaded Excel files
- Add donations/premium/AI in this phase (Track B — see ADR-003)
- Make any field non-nullable without checking if real Excel data can be empty there

## Review gate
At the end of every stage, before the final commit, run a self-review
against this file and the ADRs. Output a written report only (see standard
template used in chat) — never ask open questions directly to the user
inside a coding session; findings get routed through the human's separate
planning conversation first.
