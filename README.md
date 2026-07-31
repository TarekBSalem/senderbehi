# MailForge — Marketing Email Tool

> **Simple, powerful email campaigns for businesses**

---

## Vision

Become the go-to email marketing tool for small businesses and marketers who want to send beautiful campaigns without the complexity of enterprise platforms.

## Mission

Provide an intuitive platform to create, send, and track email campaigns using pre-built templates — starting with Gmail/SMTP and scaling to professional email services.

---

## Document Index

### Phase A — Business & Strategy

| # | Document | Description | Status |
|---|----------|-------------|--------|
| 01 | [Vision Document](01-business-model/01-vision-document.md) | Vision, mission, value proposition | ✅ Complete |
| 02 | [Business Requirements](01-business-model/02-business-requirements.md) | Revenue model, success metrics | ✅ Complete |
| 03 | [Competitor Analysis](01-business-model/03-competitor-analysis.md) | Market landscape, competitors | ✅ Complete |

### Phase B — Features & Product

| # | Document | Description | Status |
|---|----------|-------------|--------|
| 04 | [Product Requirements](03-feature-specs/04-product-requirements.md) | PRD, feature scope by phase | ✅ Complete |
| 05 | [User Stories](02-user-journeys/05-user-stories.md) | Stories for user roles | ✅ Complete |
| 06 | [User Flows](02-user-journeys/06-user-flows.md) | Complete user journey maps | ✅ Complete |

### Phase C — Technical Architecture

| # | Document | Description | Status |
|---|----------|-------------|--------|
| 07 | [Backend Architecture](07-backend-architecture/07-backend-architecture.md) | Laravel structure, patterns | ✅ Complete |
| 08 | [Database Design](05-database-schema/08-database-design.md) | Complete schema | ✅ Complete |
| 09 | [API Design](06-api-specs/09-api-design.md) | REST API endpoints | ✅ Complete |

### Phase D — UX & Design

| # | Document | Description | Status |
|---|----------|-------------|--------|
| 10 | [Wireframes](04-ux-wireframes/10-wireframes.md) | Screen wireframes | ✅ Complete |
| 11 | [Screen Inventory](04-ux-wireframes/11-screen-inventory.md) | Complete screen list | ✅ Complete |

### Phase E — Operations

| # | Document | Description | Status |
|---|----------|-------------|--------|
| 12 | [Deployment Guide](08-deployment/12-deployment.md) | Setup, config, deployment | ✅ Complete |
| 13 | [Testing Strategy](09-testing/13-testing.md) | Test plan | ✅ Complete |
| 14 | [Development Roadmap](10-development-roadmap/14-roadmap.md) | Phase 1-4 plan | ✅ Complete |

---

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | Laravel 13, PHP 8.3+ |
| Frontend | React 19, TypeScript, Inertia 2 |
| UI Components | shadcn/ui, Radix UI |
| Styling | Tailwind CSS v4 |
| Database | SQLite (dev) → MySQL (prod) |
| Queue | Database driver |
| Email | Laravel Mail + SMTP |
| Build Tool | Vite |
| Hosting | Local → Vercel/Railway |

---

## Project Info

| Field | Value |
|-------|-------|
| Project Name | MailForge |
| Version | 0.1.0 |
| Last Updated | 2026-07-13 |
| Author | Tarek Ben Salem |
| Status | Specification Phase |
| Total Documents | 14 |
| Completion | 14/14 ✅ |

---

## Quick Start

```bash
# Create Laravel project with React starter kit
laravel new mailforge --react

# Navigate to project
cd mailforge

# Configure Gmail SMTP in .env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls

# Run migrations
php artisan migrate

# Seed templates
php artisan db:seed --class=TemplateSeeder

# Install Node dependencies
npm install

# Start Vite dev server (Herd serves Laravel automatically)
npm run dev
```

> **Note:** Laravel Herd automatically serves your app at `http://mailforge.test`

---

## License

Proprietary — All rights reserved.
