# Physiomobile ExpenseFlow (MVP Foundation)

This repository contains **Phase 1 foundation scaffolding** for the Physiomobile Expense Management & Receipt Capture System.

## Included in this phase

- Domain models and enums for claimable/non-claimable workflows.
- Database migration stubs for all MVP tables.
- Service-layer scaffolding for:
  - OpenAI receipt extraction
  - Expense record creation/status transitions
  - Duplicate receipt checks
- Queue job stub for asynchronous receipt extraction.
- Red/white mobile-first Blade layout starter.
- Route starter for dashboard and expense flow.
- Seeders for departments and expense categories.
- Config skeleton for AI extraction settings.

## Next implementation steps

1. Install Laravel dependencies in a network-enabled environment.
2. Wire migration classes to Laravel base classes and run migrations.
3. Add authentication scaffolding and Spatie permissions setup.
4. Implement controllers/form requests/policies.
5. Connect OpenAI Responses API and secure storage.
6. Build status workflows, approvals, exports, and audit logs.

## Environment variables expected

```env
OPENAI_API_KEY=
OPENAI_RECEIPT_MODEL=gpt-4.1-mini
AI_RECEIPT_EXTRACTION_ENABLED=true
AI_DAILY_SCAN_LIMIT=50
APP_NAME="Physiomobile ExpenseFlow"
APP_THEME_PRIMARY="#D71920"
```
