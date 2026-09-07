# ADR-003: v1 (Track A) vs v2 (Track B) Scope

## Decision
v1 = TikTok login + data input + 6 statistics + free report.
Donations, premium tiers, AI-based recommendations, and a formal data policy
are explicitly out of scope for v1.

## Reason
Avoid building auth + payments + AI before validating that the statistics
engine actually works on real data. Revisit this ADR once v1 is closed.

