
# ADR-004: authenticated-user-resolution

## Decision
Every part of the system resolves the current user via auth()->id() against
the local `usuarios` table — never by reading TikTok OAuth claims directly
outside the login callback.

## Reason

Centralizes provider-specific logic (TikTok claim parsing) to a single sync
point (the OAuth callback in Etapa 8). Every controller, use case, and test
depends only on a local Laravel user id, making the auth provider swappable
without touching business logic.

