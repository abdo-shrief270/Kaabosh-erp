# Muhasebi - Technology Stack

> Last Updated: March 31, 2026
> Platform: Multi-Tenant SaaS for Legal Accounting Firms (Egyptian Market)

---

## Backend Stack

| Technology | Version | Purpose |
|---|---|---|
| **PHP** | 8.4.x (latest stable) | Primary backend language |
| **Laravel** | 12.x (LTS, supported until 2027) | Backend framework — API, queue, scheduler, auth |
| **PostgreSQL** | 17.x (latest: 17.9) | Primary relational database with Row-Level Security |
| **Redis** | 8.x (latest: 8.6) | Caching, session storage, queue driver, real-time pub/sub |
| **Laravel Octane** | 2.x (Swoole/FrankenPHP) | High-performance application server |
| **Laravel Horizon** | 5.x | Queue monitoring and management dashboard |
| **Laravel Telescope** | 5.x | Debug assistant (dev/staging only) |
| **Laravel Sanctum** | 4.x | SPA + Mobile API token authentication |
| **Laravel Passport** | 12.x | OAuth 2.0 server for third-party API access |
| **Spatie Laravel Permission** | 6.x | Role-based access control (RBAC) |
| **Spatie Laravel Multitenancy** | 4.x | Multi-tenant architecture foundation |
| **Spatie Laravel Activitylog** | 4.x | Audit logging for all model events |
| **Spatie Laravel MediaLibrary** | 11.x | File/document management with conversions |
| **Laravel Excel (Maatwebsite)** | 3.1.x | Excel/CSV import and export |
| **Barryvdh Laravel DomPDF** | 3.x | PDF generation for invoices, reports, statements |
| **Laravel Scout** | 10.x + Meilisearch | Full-text search engine |
| **Meilisearch** | 1.12.x | Search engine for documents, clients, transactions |
| **Laravel Reverb** | 1.x | WebSocket server for real-time notifications |
| **Laravel Cashier (Stripe)** | 15.x | Subscription billing with Stripe |
| **PHPUnit** | 11.x | Unit and feature testing |
| **Pest PHP** | 3.x | Elegant testing framework on top of PHPUnit |
| **Laravel Pint** | 1.x | PHP code style fixer (PSR-12) |
| **PHPStan / Larastan** | 2.x / 3.x | Static analysis for type safety |

### Backend Architecture Patterns

- **Domain-Driven Design (DDD):** Organized by bounded contexts (Accounting, Tax, Billing, HR, etc.)
- **CQRS (Command Query Responsibility Segregation):** Separate read/write models for performance-critical modules
- **Event Sourcing:** For financial transaction audit trail (optional per module)
- **Repository Pattern:** Abstracted data access layer
- **Service Layer:** Business logic encapsulated in service classes
- **Action Classes:** Single-responsibility action classes for complex operations
- **DTOs (Data Transfer Objects):** Type-safe data passing between layers
- **API Resources / Transformers:** Consistent API response formatting

---

## Frontend Stack

| Technology | Version | Purpose |
|---|---|---|
| **Vue.js** | 3.5.x (latest stable) | Frontend reactive framework |
| **Nuxt** | 4.x (latest: 4.4.x) | Vue meta-framework — SSR, routing, auto-imports |
| **TypeScript** | 5.7.x | Type-safe JavaScript |
| **Pinia** | 2.3.x | State management (replaces Vuex) |
| **Nuxt UI** | 3.x | UI component library (Tailwind-based) |
| **Tailwind CSS** | 4.x | Utility-first CSS framework |
| **VueUse** | 12.x | Collection of essential Vue composition utilities |
| **Nuxt i18n** | 9.x | Internationalization (Arabic RTL + English LTR) |
| **Chart.js** | 4.x + vue-chartjs | Dashboard charts and analytics visualizations |
| **ApexCharts** | 4.x + vue3-apexcharts | Advanced interactive charts |
| **VCalendar** | 3.x | Date picker and calendar components |
| **Vitest** | 3.x | Unit testing framework |
| **Playwright** | 1.50.x | End-to-end testing |
| **ESLint** | 9.x + @nuxt/eslint | Linting and code quality |
| **Prettier** | 3.x | Code formatting |

### Frontend Architecture Patterns

- **Composition API:** All components use Vue 3 Composition API with `<script setup>`
- **Composables:** Shared logic extracted into reusable composables
- **Auto-imports:** Nuxt auto-imports for Vue APIs, composables, and components
- **File-based Routing:** Nuxt pages directory for automatic route generation
- **Middleware:** Auth guards, tenant context, role checks via Nuxt middleware
- **Layouts:** Multiple layouts (auth, admin, client, super-admin)
- **Server Routes:** Nuxt server routes for BFF (Backend for Frontend) pattern

---

## Mobile Stack

| Technology | Version | Purpose |
|---|---|---|
| **Flutter** | 3.29.x (latest stable) | Cross-platform mobile framework (iOS + Android) |
| **Dart** | 3.7.x | Programming language for Flutter |
| **flutter_bloc** | 9.x | State management (BLoC pattern) |
| **dio** | 5.x | HTTP client for API calls |
| **go_router** | 15.x | Declarative routing |
| **flutter_secure_storage** | 9.x | Encrypted local storage for tokens |
| **hive** | 4.x | Lightweight local database for offline data |
| **firebase_messaging** | 15.x | Push notifications (FCM) |
| **local_auth** | 2.x | Biometric authentication |
| **flutter_localizations** | SDK | Arabic/English localization |
| **intl** | 0.19.x | Internationalization and formatting |
| **freezed** | 2.x | Immutable data classes and unions |
| **json_serializable** | 6.x | JSON serialization code generation |
| **mocktail** | 1.x | Testing mocks |
| **patrol** | 3.x | Integration testing |

---

## DevOps & Infrastructure

| Technology | Version | Purpose |
|---|---|---|
| **Docker** | 27.x | Containerization |
| **Docker Compose** | 2.x | Local development orchestration |
| **Kubernetes (K8s)** | 1.31.x | Production container orchestration (optional) |
| **Nginx** | 1.27.x | Reverse proxy, SSL termination, static file serving |
| **GitHub Actions** | N/A | CI/CD pipelines |
| **Terraform** | 1.10.x | Infrastructure as Code |
| **AWS / Hetzner Cloud** | N/A | Cloud hosting (primary: Hetzner for cost, AWS for scale) |
| **Cloudflare** | N/A | CDN, DDoS protection, DNS, SSL |
| **MinIO** | Latest | S3-compatible object storage (self-hosted) |
| **AWS S3** | N/A | Cloud object storage (production) |

---

## Monitoring & Observability

| Technology | Version | Purpose |
|---|---|---|
| **Sentry** | Latest | Error tracking and performance monitoring |
| **Grafana** | 11.x | Metrics visualization and dashboards |
| **Prometheus** | 2.x | Metrics collection |
| **Loki** | 3.x | Log aggregation |
| **Uptime Kuma** | 2.x | Self-hosted uptime monitoring |
| **Laravel Pulse** | 1.x | Application-level performance monitoring |

---

## Third-Party Integrations

| Service | Purpose |
|---|---|
| **Paymob** | Egyptian payment gateway (cards, wallets, Fawry) |
| **Stripe** | International payment processing |
| **SendGrid / Mailgun** | Transactional email delivery |
| **Twilio** | SMS and WhatsApp messaging |
| **Google Cloud Translation** | Auto-translate API for i18n |
| **ETA (Egyptian Tax Authority) API** | E-invoice and e-receipt submission |
| **Central Bank of Egypt (CBE)** | Daily exchange rate feeds |
| **Google Vision / AWS Textract** | OCR for document processing |

---

## Development Tools

| Tool | Purpose |
|---|---|
| **VS Code / PhpStorm** | IDE |
| **TablePlus / pgAdmin** | Database management |
| **Postman / Hoppscotch** | API testing |
| **Figma** | UI/UX design |
| **Linear / GitHub Issues** | Project management |
| **Storybook** | Component documentation (optional) |

---

## Package Managers & Build Tools

| Tool | Version | Purpose |
|---|---|---|
| **Composer** | 2.8.x | PHP dependency management |
| **pnpm** | 9.x | Node.js package manager (faster, disk-efficient) |
| **Vite** | 6.x | Frontend build tool (bundled with Nuxt) |
| **Turborepo** | 2.x | Monorepo build system (if using monorepo) |
