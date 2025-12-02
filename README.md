# Contacts Agenda – PHP + Vue.js

Clean Contacts management API using **PHP 8.0+** and **Vue.js 3** - no frameworks, no dependencies.

## 🚀 Quick Start

### Local (PHP Built-in Server)
```bash
php -S 127.0.0.1:8181 -t public/
```
Access: http://127.0.0.1:8181

### Docker
```bash
docker compose up -d
```
Access: http://127.0.0.1:8181



## 📋 Features

- ✅ Full CRUD for contacts and phones
- ✅ Pagination and search
- ✅ Unique email constraint
- ✅ Validation on all inputs
- ✅ Vue.js 3 responsive UI
- ✅ Type-safe PHP (100%)
- ✅ Zero external dependencies
- ✅ RESTful JSON API

## 🎯 API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/contacts` | List with pagination & search |
| GET | `/api/contacts/{id}` | Get contact with phones |
| POST | `/api/contacts` | Create contact |
| PUT | `/api/contacts/{id}` | Update contact |
| DELETE | `/api/contacts/{id}` | Delete contact |
| POST | `/api/contacts/{id}/phones` | Add phone |
| DELETE | `/api/contacts/{id}/phones/{id}` | Remove phone |

## 📂 Project Structure

```
src/
├── Domain/           # Contact, Phone models
├── Repository/       # Data access layer with interfaces
├── Http/
│   ├── Controller/   # API endpoint handlers
│   ├── Router.php    # URL routing engine
│   ├── Request.php   # HTTP input abstraction
│   └── JsonResponse.php # HTTP response helpers
├── Infrastructure/   # Database connection
├── Validation/       # Input validators
├── Autoloader.php    # PSR-4 class loader
└── Config.php        # Configuration

public/
└── index.php         # Entry point (API routes + Vue.js UI)

tests/
└── run.php           # Test suite with colored output
```

## 💾 Database Schema

**Contacts:** id, name, email (UNIQUE), address, created_at, updated_at  
**Phones:** id, contact_id, number, label (optional)

## ✨ Best Practices Applied

- **Type Safety:** `declare(strict_types=1)`, full type hints
- **Architecture:** Repository + Controller patterns
- **Validation:** Email, phone, length checks
- **Security:** Prepared statements, input validation
- **Code Quality:** PSR-12, SOLID principles, DRY
- **Immutability:** Readonly domain models
- **Responses:** Semantic HTTP helpers (ok, created, notFound, etc)

## 📝 Example Usage

### Create Contact
```bash
curl -X POST http://127.0.0.1:8181/api/contacts \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Wagner Andrade",
    "email": "wsawebmaster@yahoo.com.br",
    "address": "395 Wanda Mesquita Rezende St",
    "phones": [{"number": "11982470496", "label": "Cel Phone"}]
  }'
```

### List Contacts
```bash
curl "http://127.0.0.1:8181/api/contacts?page=1&per_page=10&search=wagner"
```

### Update Contact
```bash
curl -X PUT http://127.0.0.1:8181/api/contacts/1 \
  -H "Content-Type: application/json" \
  -d '{"name": "Wagner Andrade"}'
```

## 🧪 Testing

### Local
```bash
php tests/run.php
```

### Docker Container
```bash
# Access container bash
docker compose exec -it app bash

# Inside the container, run tests
php tests/run.php

# Exit container
exit
```

### Swagger UI
Access: `http://127.0.0.1:8181/swagger/`

## 🔧 Environment Variables

Optional MySQL configuration (defaults to SQLite if not set):

```
DB_HOST=127.0.0.1        # Default: 127.0.0.1
DB_NAME=contacts         # Default: contacts
DB_USER=root             # Default: root
DB_PASS=                 # Default: (empty)
```

SQLite database file: `data/contacts.sqlite` (auto-created on first run)

