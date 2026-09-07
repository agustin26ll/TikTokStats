# ADR-001: Stack and Architecture

## Context
Pilot project of the AI lab. $0 budget. Requires a backend because login
(TikTok OAuth) and conditional persistence are planned for Track B.

## Decision
- Backend: Laravel + hexagonal-lite architecture (Domain/Application/
  Infrastructure/Http) instead of plain MVC
- Strategy pattern for statistic calculators (Open/Closed)
- Repository + DTO to decouple Eloquent from the domain

## Alternatives discarded
- No backend (pure React): discarded because login + donation-conditioned
  persistence (Track B) require it from the start
- Plain Laravel MVC: discarded because each new statistic would end up
  bloating a single Controller or Model — violates SRP and OCP

