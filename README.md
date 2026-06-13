# EthioBank Receipts

Extract receipt data from Ethiopian bank transaction pages and PDFs.

## Supported Banks

- CBE (Commercial Bank of Ethiopia)
- Dashen Bank
- Awash Bank
- Bank of Abyssinia (BOA)
- Zemen Bank
- Tele (Ethio Telecom)
- M-Pesa
- CBE Birr

## Structure

- `backend/` — Laravel API (`/api/receipts/extract`, `/api/receipts/banks`)
- `frontend/` — React SPA (Vite)

## Setup

```bash
cd backend
composer setup    # install, .env, key, migrate, build
composer dev      # serve + queue + logs + vite
```

## API

```bash
# List supported banks
GET /api/receipts/banks

# Extract receipt data
POST /api/receipts/extract
{
  "bank": "cbe|dashen|awash|boa|zemen|tele|mpesa|cbe_birr|auto",
  "url": "https://...",
  "reference": "...",
  "account": "...",
  "phone": "..."
}
```
