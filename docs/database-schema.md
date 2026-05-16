# Muhasebi Database Schema

> Auto-generated on 2026-04-05

## Table of Contents

- [accounts](#accounts)
- [activity_log](#activity_log)
- [api_request_logs](#api_request_logs)
- [api_usage_meters](#api_usage_meters)
- [blog_categories](#blog_categories)
- [blog_post_tag](#blog_post_tag)
- [blog_posts](#blog_posts)
- [blog_tags](#blog_tags)
- [cache](#cache)
- [cache_locks](#cache_locks)
- [clients](#clients)
- [cms_pages](#cms_pages)
- [contact_submissions](#contact_submissions)
- [currencies](#currencies)
- [documents](#documents)
- [email_templates](#email_templates)
- [employees](#employees)
- [eta_documents](#eta_documents)
- [eta_item_codes](#eta_item_codes)
- [eta_settings](#eta_settings)
- [eta_submissions](#eta_submissions)
- [exchange_rates](#exchange_rates)
- [failed_jobs](#failed_jobs)
- [faqs](#faqs)
- [feature_flags](#feature_flags)
- [fiscal_periods](#fiscal_periods)
- [fiscal_years](#fiscal_years)
- [import_jobs](#import_jobs)
- [integration_settings](#integration_settings)
- [investor_tenant_shares](#investor_tenant_shares)
- [investors](#investors)
- [invoice_lines](#invoice_lines)
- [invoice_settings](#invoice_settings)
- [invoices](#invoices)
- [job_batches](#job_batches)
- [jobs](#jobs)
- [journal_entries](#journal_entries)
- [journal_entry_lines](#journal_entry_lines)
- [landing_settings](#landing_settings)
- [media](#media)
- [messages](#messages)
- [model_has_permissions](#model_has_permissions)
- [model_has_roles](#model_has_roles)
- [notification_preferences](#notification_preferences)
- [notifications](#notifications)
- [onboarding_steps](#onboarding_steps)
- [password_reset_tokens](#password_reset_tokens)
- [payments](#payments)
- [payroll_items](#payroll_items)
- [payroll_runs](#payroll_runs)
- [permissions](#permissions)
- [personal_access_tokens](#personal_access_tokens)
- [plans](#plans)
- [platform_settings](#platform_settings)
- [profit_distributions](#profit_distributions)
- [recurring_invoices](#recurring_invoices)
- [role_has_permissions](#role_has_permissions)
- [roles](#roles)
- [sessions](#sessions)
- [slug_redirects](#slug_redirects)
- [storage_quotas](#storage_quotas)
- [subscription_payments](#subscription_payments)
- [subscriptions](#subscriptions)
- [tenants](#tenants)
- [testimonials](#testimonials)
- [timers](#timers)
- [timesheet_entries](#timesheet_entries)
- [usage_records](#usage_records)
- [users](#users)
- [webhook_deliveries](#webhook_deliveries)
- [webhook_endpoints](#webhook_endpoints)

---

## accounts

| Column | Type | Nullable | Default |
|--------|------|----------|---------|
| `id` | int8 | No | nextval('accounts_id_seq'::regclass) |
| `tenant_id` | int8 | No | - |
| `parent_id` | int8 | Yes | - |
| `code` | varchar | No | - |
| `name_ar` | varchar | No | - |
| `name_en` | varchar | Yes | - |
| `type` | varchar | No | - |
| `normal_balance` | varchar | No | - |
| `is_active` | bool | No | true |
| `is_group` | bool | No | false |
| `level` | int2 | No | '1'::smallint |
| `description` | text | Yes | - |
| `currency` | varchar | No | 'EGP'::character varying |
| `created_at` | timestamp | Yes | - |
| `updated_at` | timestamp | Yes | - |
| `deleted_at` | timestamp | Yes | - |

**Indexes:**
- `accounts_is_active_index` (INDEX) on (is_active)
- `accounts_parent_id_index` (INDEX) on (parent_id)
- `accounts_pkey` (UNIQUE) on (id)
- `accounts_tenant_id_code_unique` (UNIQUE) on (tenant_id, code)
- `accounts_tenant_id_index` (INDEX) on (tenant_id)
- `accounts_type_index` (INDEX) on (type)

**Foreign Keys:**
- `accounts_parent_id_foreign`: (parent_id) → accounts(id)
- `accounts_tenant_id_foreign`: (tenant_id) → tenants(id)

---

## activity_log

| Column | Type | Nullable | Default |
|--------|------|----------|---------|
| `id` | int8 | No | nextval('activity_log_id_seq'::regclass) |
| `log_name` | varchar | Yes | - |
| `description` | text | No | - |
| `subject_type` | varchar | Yes | - |
| `subject_id` | int8 | Yes | - |
| `event` | varchar | Yes | - |
| `causer_type` | varchar | Yes | - |
| `causer_id` | int8 | Yes | - |
| `attribute_changes` | json | Yes | - |
| `properties` | json | Yes | - |
| `created_at` | timestamp | Yes | - |
| `updated_at` | timestamp | Yes | - |

**Indexes:**
- `activity_log_log_name_index` (INDEX) on (log_name)
- `activity_log_pkey` (UNIQUE) on (id)
- `causer` (INDEX) on (causer_type, causer_id)
- `subject` (INDEX) on (subject_type, subject_id)

---

## api_request_logs

| Column | Type | Nullable | Default |
|--------|------|----------|---------|
| `id` | int8 | No | nextval('api_request_logs_id_seq'::regclass) |
| `request_id` | varchar | No | - |
| `method` | varchar | No | - |
| `path` | varchar | No | - |
| `status_code` | int2 | No | - |
| `duration_ms` | int4 | No | - |
| `ip` | varchar | Yes | - |
| `user_agent` | varchar | Yes | - |
| `user_id` | int8 | Yes | - |
| `tenant_id` | varchar | Yes | - |
| `request_size` | int4 | No | 0 |
| `response_size` | int4 | No | 0 |
| `request_headers` | json | Yes | - |
| `request_body` | json | Yes | - |
| `error_message` | text | Yes | - |
| `created_at` | timestamp | No | CURRENT_TIMESTAMP |

**Indexes:**
- `api_request_logs_created_at_index` (INDEX) on (created_at)
- `api_request_logs_duration_ms_index` (INDEX) on (duration_ms)
- `api_request_logs_pkey` (UNIQUE) on (id)
- `api_request_logs_request_id_index` (INDEX) on (request_id)
- `api_request_logs_status_code_created_at_index` (INDEX) on (status_code, created_at)
- `api_request_logs_tenant_id_index` (INDEX) on (tenant_id)
- `api_request_logs_user_id_index` (INDEX) on (user_id)

---

## api_usage_meters

| Column | Type | Nullable | Default |
|--------|------|----------|---------|
| `id` | int8 | No | nextval('api_usage_meters_id_seq'::regclass) |
| `tenant_id` | int8 | No | - |
| `date` | date | No | - |
| `api_calls` | int8 | No | '0'::bigint |
| `invoices_created` | int8 | No | '0'::bigint |
| `journal_entries_created` | int8 | No | '0'::bigint |
| `documents_uploaded` | int8 | No | '0'::bigint |
| `eta_submissions` | int8 | No | '0'::bigint |
| `emails_sent` | int8 | No | '0'::bigint |
| `storage_bytes` | int8 | No | '0'::bigint |
| `created_at` | timestamp | Yes | - |
| `updated_at` | timestamp | Yes | - |

**Indexes:**
- `api_usage_meters_pkey` (UNIQUE) on (id)
- `api_usage_meters_tenant_id_date_index` (INDEX) on (tenant_id, date)
- `api_usage_meters_tenant_id_date_unique` (UNIQUE) on (tenant_id, date)

**Foreign Keys:**
- `api_usage_meters_tenant_id_foreign`: (tenant_id) → tenants(id)

---

## blog_categories

| Column | Type | Nullable | Default |
|--------|------|----------|---------|
| `id` | int8 | No | nextval('blog_categories_id_seq'::regclass) |
| `slug` | varchar | No | - |
| `name_ar` | varchar | No | - |
| `name_en` | varchar | No | - |
| `description_ar` | varchar | Yes | - |
| `description_en` | varchar | Yes | - |
| `sort_order` | int4 | No | 0 |
| `created_at` | timestamp | Yes | - |
| `updated_at` | timestamp | Yes | - |

**Indexes:**
- `blog_categories_pkey` (UNIQUE) on (id)
- `blog_categories_slug_unique` (UNIQUE) on (slug)

---

## blog_post_tag

| Column | Type | Nullable | Default |
|--------|------|----------|---------|
| `post_id` | int8 | No | - |
| `tag_id` | int8 | No | - |

**Indexes:**
- `blog_post_tag_pkey` (UNIQUE) on (post_id, tag_id)

**Foreign Keys:**
- `blog_post_tag_post_id_foreign`: (post_id) → blog_posts(id)
- `blog_post_tag_tag_id_foreign`: (tag_id) → blog_tags(id)

---

## blog_posts

| Column | Type | Nullable | Default |
|--------|------|----------|---------|
| `id` | int8 | No | nextval('blog_posts_id_seq'::regclass) |
| `slug` | varchar | No | - |
| `category_id` | int8 | Yes | - |
| `title_ar` | varchar | No | - |
| `title_en` | varchar | No | - |
| `excerpt_ar` | text | Yes | - |
| `excerpt_en` | text | Yes | - |
| `content_ar` | text | No | - |
| `content_en` | text | No | - |
| `cover_image` | varchar | Yes | - |
| `meta_description_ar` | varchar | Yes | - |
| `meta_description_en` | varchar | Yes | - |
| `author_name` | varchar | Yes | - |
| `is_published` | bool | No | false |
| `is_featured` | bool | No | false |
| `published_at` | timestamp | Yes | - |
| `reading_time` | int4 | No | 3 |
| `views_count` | int8 | No | '0'::bigint |
| `created_at` | timestamp | Yes | - |
| `updated_at` | timestamp | Yes | - |

**Indexes:**
- `blog_posts_category_idx` (INDEX) on (category_id, is_published, published_at)
- `blog_posts_featured_idx` (INDEX) on (is_published, is_featured, published_at)
- `blog_posts_is_featured_index` (INDEX) on (is_featured)
- `blog_posts_is_published_index` (INDEX) on (is_published)
- `blog_posts_pkey` (UNIQUE) on (id)
- `blog_posts_published_at_index` (INDEX) on (published_at)
- `blog_posts_published_idx` (INDEX) on (is_published, published_at)
- `blog_posts_slug_unique` (UNIQUE) on (slug)

**Foreign Keys:**
- `blog_posts_category_id_foreign`: (category_id) → blog_categories(id)

---

## blog_tags

| Column | Type | Nullable | Default |
|--------|------|----------|---------|
| `id` | int8 | No | nextval('blog_tags_id_seq'::regclass) |
| `slug` | varchar | No | - |
| `name_ar` | varchar | No | - |
| `name_en` | varchar | No | - |
| `created_at` | timestamp | Yes | - |
| `updated_at` | timestamp | Yes | - |

**Indexes:**
- `blog_tags_pkey` (UNIQUE) on (id)
- `blog_tags_slug_unique` (UNIQUE) on (slug)

---

## cache

| Column | Type | Nullable | Default |
|--------|------|----------|---------|
| `key` | varchar | No | - |
| `value` | text | No | - |
| `expiration` | int8 | No | - |

**Indexes:**
- `cache_expiration_index` (INDEX) on (expiration)
- `cache_pkey` (UNIQUE) on (key)

---

## cache_locks

| Column | Type | Nullable | Default |
|--------|------|----------|---------|
| `key` | varchar | No | - |
| `owner` | varchar | No | - |
| `expiration` | int8 | No | - |

**Indexes:**
- `cache_locks_expiration_index` (INDEX) on (expiration)
- `cache_locks_pkey` (UNIQUE) on (key)

---

## clients

| Column | Type | Nullable | Default |
|--------|------|----------|---------|
| `id` | int8 | No | nextval('clients_id_seq'::regclass) |
| `tenant_id` | int8 | No | - |
| `name` | varchar | No | - |
| `trade_name` | varchar | Yes | - |
| `tax_id` | varchar | Yes | - |
| `commercial_register` | varchar | Yes | - |
| `activity_type` | varchar | Yes | - |
| `address` | text | Yes | - |
| `city` | varchar | Yes | - |
| `phone` | varchar | Yes | - |
| `email` | varchar | Yes | - |
| `contact_person` | varchar | Yes | - |
| `contact_phone` | varchar | Yes | - |
| `notes` | text | Yes | - |
| `is_active` | bool | No | true |
| `created_at` | timestamp | Yes | - |
| `updated_at` | timestamp | Yes | - |
| `deleted_at` | timestamp | Yes | - |

**Indexes:**
- `clients_commercial_register_index` (INDEX) on (commercial_register)
- `clients_is_active_index` (INDEX) on (is_active)
- `clients_pkey` (UNIQUE) on (id)
- `clients_tax_id_index` (INDEX) on (tax_id)
- `clients_tenant_id_index` (INDEX) on (tenant_id)
- `clients_tenant_id_tax_id_unique` (UNIQUE) on (tenant_id, tax_id)

**Foreign Keys:**
- `clients_tenant_id_foreign`: (tenant_id) → tenants(id)

---

## cms_pages

| Column | Type | Nullable | Default |
|--------|------|----------|---------|
| `id` | int8 | No | nextval('cms_pages_id_seq'::regclass) |
| `slug` | varchar | No | - |
| `title_ar` | varchar | No | - |
| `title_en` | varchar | No | - |
| `content_ar` | text | Yes | - |
| `content_en` | text | Yes | - |
| `meta_description_ar` | varchar | Yes | - |
| `meta_description_en` | varchar | Yes | - |
| `is_published` | bool | No | false |
| `created_at` | timestamp | Yes | - |
| `updated_at` | timestamp | Yes | - |

**Indexes:**
- `cms_pages_pkey` (UNIQUE) on (id)
- `cms_pages_published_slug_idx` (INDEX) on (is_published, slug)
- `cms_pages_slug_unique` (UNIQUE) on (slug)

---

## contact_submissions

| Column | Type | Nullable | Default |
|--------|------|----------|---------|
| `id` | int8 | No | nextval('contact_submissions_id_seq'::regclass) |
| `name` | varchar | No | - |
| `email` | varchar | No | - |
| `phone` | varchar | Yes | - |
| `company` | varchar | Yes | - |
| `subject` | varchar | No | - |
| `message` | text | No | - |
| `is_read` | bool | No | false |
| `admin_notes` | text | Yes | - |
| `created_at` | timestamp | Yes | - |
| `updated_at` | timestamp | Yes | - |
| `status` | varchar | No | 'new'::character varying |
| `assigned_to` | varchar | Yes | - |

**Indexes:**
- `contact_submissions_created_at_index` (INDEX) on (created_at)
- `contact_submissions_is_read_index` (INDEX) on (is_read)
- `contact_submissions_pkey` (UNIQUE) on (id)
- `contacts_status_date_idx` (INDEX) on (status, created_at)

---

## currencies

| Column | Type | Nullable | Default |
|--------|------|----------|---------|
| `id` | int8 | No | nextval('currencies_id_seq'::regclass) |
| `code` | varchar | No | - |
| `name_ar` | varchar | No | - |
| `name_en` | varchar | No | - |
| `symbol` | varchar | No | - |
| `decimal_places` | int2 | No | '2'::smallint |
| `is_active` | bool | No | true |
| `created_at` | timestamp | Yes | - |
| `updated_at` | timestamp | Yes | - |

**Indexes:**
- `currencies_code_unique` (UNIQUE) on (code)
- `currencies_pkey` (UNIQUE) on (id)

---

## documents

| Column | Type | Nullable | Default |
|--------|------|----------|---------|
| `id` | int8 | No | nextval('documents_id_seq'::regclass) |
| `tenant_id` | int8 | No | - |
| `client_id` | int8 | Yes | - |
| `name` | varchar | No | - |
| `disk` | varchar | No | 'local'::character varying |
| `path` | varchar | No | - |
| `mime_type` | varchar | No | - |
| `size_bytes` | int8 | No | - |
| `hash` | varchar | No | - |
| `category` | varchar | No | 'other'::character varying |
| `storage_tier` | varchar | No | 'hot'::character varying |
| `description` | text | Yes | - |
| `metadata` | json | Yes | - |
| `uploaded_by` | int8 | Yes | - |
| `is_archived` | bool | No | false |
| `archived_at` | timestamp | Yes | - |
| `created_at` | timestamp | Yes | - |
| `updated_at` | timestamp | Yes | - |
| `deleted_at` | timestamp | Yes | - |

**Indexes:**
- `documents_category_index` (INDEX) on (category)
- `documents_client_id_index` (INDEX) on (client_id)
- `documents_hash_index` (INDEX) on (hash)
- `documents_is_archived_index` (INDEX) on (is_archived)
- `documents_pkey` (UNIQUE) on (id)
- `documents_tenant_id_index` (INDEX) on (tenant_id)
- `documents_uploaded_by_index` (INDEX) on (uploaded_by)

**Foreign Keys:**
- `documents_client_id_foreign`: (client_id) → clients(id)
- `documents_tenant_id_foreign`: (tenant_id) → tenants(id)
- `documents_uploaded_by_foreign`: (uploaded_by) → users(id)

---

## email_templates

| Column | Type | Nullable | Default |
|--------|------|----------|---------|
| `id` | int8 | No | nextval('email_templates_id_seq'::regclass) |
| `key` | varchar | No | - |
| `name` | varchar | No | - |
| `subject_ar` | varchar | No | - |
| `subject_en` | varchar | No | - |
| `body_ar` | text | No | - |
| `body_en` | text | No | - |
| `variables` | json | Yes | - |
| `is_active` | bool | No | true |
| `created_at` | timestamp | Yes | - |
| `updated_at` | timestamp | Yes | - |

**Indexes:**
- `email_templates_key_unique` (UNIQUE) on (key)
- `email_templates_pkey` (UNIQUE) on (id)

---

## employees

| Column | Type | Nullable | Default |
|--------|------|----------|---------|
| `id` | int8 | No | nextval('employees_id_seq'::regclass) |
| `tenant_id` | int8 | No | - |
| `user_id` | int8 | No | - |
| `hire_date` | date | No | - |
| `department` | varchar | Yes | - |
| `job_title` | varchar | Yes | - |
| `base_salary` | numeric | No | - |
| `social_insurance_number` | varchar | Yes | - |
| `bank_account` | varchar | Yes | - |
| `is_insured` | bool | No | false |
| `created_at` | timestamp | Yes | - |
| `updated_at` | timestamp | Yes | - |
| `deleted_at` | timestamp | Yes | - |

**Indexes:**
- `employees_pkey` (UNIQUE) on (id)
- `employees_tenant_id_index` (INDEX) on (tenant_id)
- `employees_tenant_id_user_id_unique` (UNIQUE) on (tenant_id, user_id)

**Foreign Keys:**
- `employees_tenant_id_foreign`: (tenant_id) → tenants(id)
- `employees_user_id_foreign`: (user_id) → users(id)

---

## eta_documents

| Column | Type | Nullable | Default |
|--------|------|----------|---------|
| `id` | int8 | No | nextval('eta_documents_id_seq'::regclass) |
| `tenant_id` | int8 | No | - |
| `invoice_id` | int8 | No | - |
| `eta_submission_id` | int8 | Yes | - |
| `document_type` | varchar | No | - |
| `internal_id` | varchar | Yes | - |
| `eta_uuid` | varchar | Yes | - |
| `eta_long_id` | varchar | Yes | - |
| `status` | varchar | No | 'prepared'::character varying |
| `signed_data` | text | Yes | - |
| `document_data` | json | Yes | - |
| `eta_response` | json | Yes | - |
| `errors` | json | Yes | - |
| `qr_code_data` | text | Yes | - |
| `submitted_at` | timestamp | Yes | - |
| `cancelled_at` | timestamp | Yes | - |
| `cancelled_by` | int8 | Yes | - |
| `created_at` | timestamp | Yes | - |
| `updated_at` | timestamp | Yes | - |

**Indexes:**
- `eta_documents_eta_uuid_unique` (UNIQUE) on (eta_uuid)
- `eta_documents_pkey` (UNIQUE) on (id)
- `eta_documents_status_index` (INDEX) on (status)
- `eta_documents_tenant_id_index` (INDEX) on (tenant_id)
- `eta_documents_tenant_id_invoice_id_unique` (UNIQUE) on (tenant_id, invoice_id)

**Foreign Keys:**
- `eta_documents_cancelled_by_foreign`: (cancelled_by) → users(id)
- `eta_documents_eta_submission_id_foreign`: (eta_submission_id) → eta_submissions(id)
- `eta_documents_invoice_id_foreign`: (invoice_id) → invoices(id)
- `eta_documents_tenant_id_foreign`: (tenant_id) → tenants(id)

---

## eta_item_codes

| Column | Type | Nullable | Default |
|--------|------|----------|---------|
| `id` | int8 | No | nextval('eta_item_codes_id_seq'::regclass) |
| `tenant_id` | int8 | No | - |
| `code_type` | varchar | No | - |
| `item_code` | varchar | No | - |
| `description` | varchar | No | - |
| `description_ar` | varchar | Yes | - |
| `unit_type` | varchar | No | 'EA'::character varying |
| `default_tax_type` | varchar | No | 'T1'::character varying |
| `default_tax_subtype` | varchar | No | 'V009'::character varying |
| `is_active` | bool | No | true |
| `created_at` | timestamp | Yes | - |
| `updated_at` | timestamp | Yes | - |

**Indexes:**
- `eta_item_codes_pkey` (UNIQUE) on (id)
- `eta_item_codes_tenant_id_index` (INDEX) on (tenant_id)
- `eta_item_codes_tenant_id_item_code_unique` (UNIQUE) on (tenant_id, item_code)

**Foreign Keys:**
- `eta_item_codes_tenant_id_foreign`: (tenant_id) → tenants(id)

---

## eta_settings

| Column | Type | Nullable | Default |
|--------|------|----------|---------|
| `id` | int8 | No | nextval('eta_settings_id_seq'::regclass) |
| `tenant_id` | int8 | No | - |
| `is_enabled` | bool | No | false |
| `environment` | varchar | No | 'preprod'::character varying |
| `client_id` | varchar | Yes | - |
| `client_secret` | text | Yes | - |
| `branch_id` | varchar | No | '0'::character varying |
| `branch_address_country` | varchar | No | 'EG'::character varying |
| `branch_address_governate` | varchar | Yes | - |
| `branch_address_region_city` | varchar | Yes | - |
| `branch_address_street` | varchar | Yes | - |
| `branch_address_building_number` | varchar | Yes | - |
| `activity_code` | varchar | Yes | - |
| `company_trade_name` | varchar | Yes | - |
| `access_token` | text | Yes | - |
| `token_expires_at` | timestamp | Yes | - |
| `created_at` | timestamp | Yes | - |
| `updated_at` | timestamp | Yes | - |

**Indexes:**
- `eta_settings_pkey` (UNIQUE) on (id)
- `eta_settings_tenant_id_unique` (UNIQUE) on (tenant_id)

**Foreign Keys:**
- `eta_settings_tenant_id_foreign`: (tenant_id) → tenants(id)

---

## eta_submissions

| Column | Type | Nullable | Default |
|--------|------|----------|---------|
| `id` | int8 | No | nextval('eta_submissions_id_seq'::regclass) |
| `tenant_id` | int8 | No | - |
| `submission_uuid` | varchar | Yes | - |
| `status` | varchar | No | 'pending'::character varying |
| `document_count` | int2 | No | '0'::smallint |
| `accepted_count` | int2 | No | '0'::smallint |
| `rejected_count` | int2 | No | '0'::smallint |
| `response_data` | json | Yes | - |
| `submitted_at` | timestamp | Yes | - |
| `submitted_by` | int8 | Yes | - |
| `created_at` | timestamp | Yes | - |
| `updated_at` | timestamp | Yes | - |

**Indexes:**
- `eta_submissions_pkey` (UNIQUE) on (id)
- `eta_submissions_status_index` (INDEX) on (status)
- `eta_submissions_submission_uuid_unique` (UNIQUE) on (submission_uuid)
- `eta_submissions_tenant_id_index` (INDEX) on (tenant_id)

**Foreign Keys:**
- `eta_submissions_submitted_by_foreign`: (submitted_by) → users(id)
- `eta_submissions_tenant_id_foreign`: (tenant_id) → tenants(id)

---

## exchange_rates

| Column | Type | Nullable | Default |
|--------|------|----------|---------|
| `id` | int8 | No | nextval('exchange_rates_id_seq'::regclass) |
| `base_currency` | varchar | No | 'EGP'::character varying |
| `target_currency` | varchar | No | - |
| `rate` | numeric | No | - |
| `effective_date` | date | No | - |
| `source` | varchar | No | 'manual'::character varying |
| `created_at` | timestamp | Yes | - |
| `updated_at` | timestamp | Yes | - |

**Indexes:**
- `exchange_rates_base_currency_target_currency_effective_date_ind` (INDEX) on (base_currency, target_currency, effective_date)
- `exchange_rates_base_currency_target_currency_effective_date_uni` (UNIQUE) on (base_currency, target_currency, effective_date)
- `exchange_rates_pkey` (UNIQUE) on (id)

---

## failed_jobs

| Column | Type | Nullable | Default |
|--------|------|----------|---------|
| `id` | int8 | No | nextval('failed_jobs_id_seq'::regclass) |
| `uuid` | varchar | No | - |
| `connection` | text | No | - |
| `queue` | text | No | - |
| `payload` | text | No | - |
| `exception` | text | No | - |
| `failed_at` | timestamp | No | CURRENT_TIMESTAMP |

**Indexes:**
- `failed_jobs_pkey` (UNIQUE) on (id)
- `failed_jobs_uuid_unique` (UNIQUE) on (uuid)

---

## faqs

| Column | Type | Nullable | Default |
|--------|------|----------|---------|
| `id` | int8 | No | nextval('faqs_id_seq'::regclass) |
| `question_ar` | varchar | No | - |
| `question_en` | varchar | No | - |
| `answer_ar` | text | No | - |
| `answer_en` | text | No | - |
| `is_active` | bool | No | true |
| `sort_order` | int4 | No | 0 |
| `created_at` | timestamp | Yes | - |
| `updated_at` | timestamp | Yes | - |

**Indexes:**
- `faqs_active_sort_idx` (INDEX) on (is_active, sort_order)
- `faqs_pkey` (UNIQUE) on (id)

---

## feature_flags

| Column | Type | Nullable | Default |
|--------|------|----------|---------|
| `id` | int8 | No | nextval('feature_flags_id_seq'::regclass) |
| `key` | varchar | No | - |
| `name` | varchar | No | - |
| `description` | varchar | Yes | - |
| `is_enabled_globally` | bool | No | false |
| `enabled_for_plans` | json | Yes | - |
| `enabled_for_tenants` | json | Yes | - |
| `disabled_for_tenants` | json | Yes | - |
| `rollout_percentage` | varchar | Yes | - |
| `created_at` | timestamp | Yes | - |
| `updated_at` | timestamp | Yes | - |

**Indexes:**
- `feature_flags_key_unique` (UNIQUE) on (key)
- `feature_flags_pkey` (UNIQUE) on (id)

---

## fiscal_periods

| Column | Type | Nullable | Default |
|--------|------|----------|---------|
| `id` | int8 | No | nextval('fiscal_periods_id_seq'::regclass) |
| `tenant_id` | int8 | No | - |
| `fiscal_year_id` | int8 | No | - |
| `name` | varchar | No | - |
| `period_number` | int2 | No | - |
| `start_date` | date | No | - |
| `end_date` | date | No | - |
| `is_closed` | bool | No | false |
| `closed_at` | timestamp | Yes | - |
| `closed_by` | int8 | Yes | - |
| `created_at` | timestamp | Yes | - |
| `updated_at` | timestamp | Yes | - |

**Indexes:**
- `fiscal_periods_fiscal_year_id_index` (INDEX) on (fiscal_year_id)
- `fiscal_periods_fiscal_year_id_period_number_unique` (UNIQUE) on (fiscal_year_id, period_number)
- `fiscal_periods_pkey` (UNIQUE) on (id)
- `fiscal_periods_tenant_id_index` (INDEX) on (tenant_id)

**Foreign Keys:**
- `fiscal_periods_closed_by_foreign`: (closed_by) → users(id)
- `fiscal_periods_fiscal_year_id_foreign`: (fiscal_year_id) → fiscal_years(id)
- `fiscal_periods_tenant_id_foreign`: (tenant_id) → tenants(id)

---

## fiscal_years

| Column | Type | Nullable | Default |
|--------|------|----------|---------|
| `id` | int8 | No | nextval('fiscal_years_id_seq'::regclass) |
| `tenant_id` | int8 | No | - |
| `name` | varchar | No | - |
| `start_date` | date | No | - |
| `end_date` | date | No | - |
| `is_closed` | bool | No | false |
| `closed_at` | timestamp | Yes | - |
| `closed_by` | int8 | Yes | - |
| `created_at` | timestamp | Yes | - |
| `updated_at` | timestamp | Yes | - |

**Indexes:**
- `fiscal_years_pkey` (UNIQUE) on (id)
- `fiscal_years_tenant_id_index` (INDEX) on (tenant_id)
- `fiscal_years_tenant_id_name_unique` (UNIQUE) on (tenant_id, name)

**Foreign Keys:**
- `fiscal_years_closed_by_foreign`: (closed_by) → users(id)
- `fiscal_years_tenant_id_foreign`: (tenant_id) → tenants(id)

---

## import_jobs

| Column | Type | Nullable | Default |
|--------|------|----------|---------|
| `id` | int8 | No | nextval('import_jobs_id_seq'::regclass) |
| `tenant_id` | int8 | No | - |
| `user_id` | int8 | No | - |
| `type` | varchar | No | - |
| `file_path` | varchar | No | - |
| `original_filename` | varchar | No | - |
| `status` | varchar | No | 'pending'::character varying |
| `total_rows` | int4 | No | 0 |
| `processed_rows` | int4 | No | 0 |
| `success_count` | int4 | No | 0 |
| `error_count` | int4 | No | 0 |
| `errors` | json | Yes | - |
| `options` | json | Yes | - |
| `started_at` | timestamp | Yes | - |
| `completed_at` | timestamp | Yes | - |
| `created_at` | timestamp | Yes | - |
| `updated_at` | timestamp | Yes | - |

**Indexes:**
- `import_jobs_pkey` (UNIQUE) on (id)
- `import_jobs_tenant_id_status_index` (INDEX) on (tenant_id, status)

**Foreign Keys:**
- `import_jobs_tenant_id_foreign`: (tenant_id) → tenants(id)
- `import_jobs_user_id_foreign`: (user_id) → users(id)

---

## integration_settings

| Column | Type | Nullable | Default |
|--------|------|----------|---------|
| `id` | int8 | No | nextval('integration_settings_id_seq'::regclass) |
| `provider` | varchar | No | - |
| `display_name` | varchar | No | - |
| `is_enabled` | bool | No | false |
| `credentials` | json | Yes | - |
| `config` | json | Yes | - |
| `last_verified_at` | timestamp | Yes | - |
| `created_at` | timestamp | Yes | - |
| `updated_at` | timestamp | Yes | - |

**Indexes:**
- `integration_settings_pkey` (UNIQUE) on (id)
- `integration_settings_provider_unique` (UNIQUE) on (provider)

---

## investor_tenant_shares

| Column | Type | Nullable | Default |
|--------|------|----------|---------|
| `id` | int8 | No | nextval('investor_tenant_shares_id_seq'::regclass) |
| `investor_id` | int8 | No | - |
| `tenant_id` | int8 | No | - |
| `ownership_percentage` | numeric | No | - |
| `created_at` | timestamp | Yes | - |
| `updated_at` | timestamp | Yes | - |

**Indexes:**
- `investor_tenant_shares_investor_id_tenant_id_unique` (UNIQUE) on (investor_id, tenant_id)
- `investor_tenant_shares_pkey` (UNIQUE) on (id)
- `investor_tenant_shares_tenant_id_index` (INDEX) on (tenant_id)

**Foreign Keys:**
- `investor_tenant_shares_investor_id_foreign`: (investor_id) → investors(id)
- `investor_tenant_shares_tenant_id_foreign`: (tenant_id) → tenants(id)

---

## investors

| Column | Type | Nullable | Default |
|--------|------|----------|---------|
| `id` | int8 | No | nextval('investors_id_seq'::regclass) |
| `name` | varchar | No | - |
| `email` | varchar | Yes | - |
| `phone` | varchar | Yes | - |
| `join_date` | date | No | - |
| `is_active` | bool | No | true |
| `notes` | text | Yes | - |
| `created_at` | timestamp | Yes | - |
| `updated_at` | timestamp | Yes | - |
| `deleted_at` | timestamp | Yes | - |

**Indexes:**
- `investors_email_unique` (UNIQUE) on (email)
- `investors_pkey` (UNIQUE) on (id)

---

## invoice_lines

| Column | Type | Nullable | Default |
|--------|------|----------|---------|
| `id` | int8 | No | nextval('invoice_lines_id_seq'::regclass) |
| `invoice_id` | int8 | No | - |
| `description` | varchar | No | - |
| `quantity` | numeric | No | '1'::numeric |
| `unit_price` | numeric | No | - |
| `discount_percent` | numeric | No | '0'::numeric |
| `vat_rate` | numeric | No | '14'::numeric |
| `line_total` | numeric | No | '0'::numeric |
| `vat_amount` | numeric | No | '0'::numeric |
| `total` | numeric | No | '0'::numeric |
| `sort_order` | int2 | No | '0'::smallint |
| `account_id` | int8 | Yes | - |
| `created_at` | timestamp | Yes | - |
| `updated_at` | timestamp | Yes | - |

**Indexes:**
- `invoice_lines_invoice_id_index` (INDEX) on (invoice_id)
- `invoice_lines_pkey` (UNIQUE) on (id)

**Foreign Keys:**
- `invoice_lines_account_id_foreign`: (account_id) → accounts(id)
- `invoice_lines_invoice_id_foreign`: (invoice_id) → invoices(id)

---

## invoice_settings

| Column | Type | Nullable | Default |
|--------|------|----------|---------|
| `id` | int8 | No | nextval('invoice_settings_id_seq'::regclass) |
| `tenant_id` | int8 | No | - |
| `invoice_prefix` | varchar | No | 'INV'::character varying |
| `credit_note_prefix` | varchar | No | 'CN'::character varying |
| `debit_note_prefix` | varchar | No | 'DN'::character varying |
| `next_invoice_number` | int4 | No | 1 |
| `next_credit_note_number` | int4 | No | 1 |
| `next_debit_note_number` | int4 | No | 1 |
| `default_due_days` | int2 | No | '30'::smallint |
| `default_vat_rate` | numeric | No | '14'::numeric |
| `default_payment_terms` | text | Yes | - |
| `default_notes` | text | Yes | - |
| `ar_account_id` | int8 | Yes | - |
| `revenue_account_id` | int8 | Yes | - |
| `vat_account_id` | int8 | Yes | - |
| `created_at` | timestamp | Yes | - |
| `updated_at` | timestamp | Yes | - |
| `pdf_template` | varchar | No | 'modern'::character varying |
| `pdf_show_logo` | bool | No | true |
| `pdf_show_vat_breakdown` | bool | No | true |
| `pdf_show_payment_terms` | bool | No | true |
| `pdf_footer_text` | varchar | Yes | - |
| `pdf_header_text` | varchar | Yes | - |
| `pdf_accent_color` | varchar | No | '#2c3e50'::character varying |

**Indexes:**
- `invoice_settings_pkey` (UNIQUE) on (id)
- `invoice_settings_tenant_id_unique` (UNIQUE) on (tenant_id)

**Foreign Keys:**
- `invoice_settings_ar_account_id_foreign`: (ar_account_id) → accounts(id)
- `invoice_settings_revenue_account_id_foreign`: (revenue_account_id) → accounts(id)
- `invoice_settings_tenant_id_foreign`: (tenant_id) → tenants(id)
- `invoice_settings_vat_account_id_foreign`: (vat_account_id) → accounts(id)

---

## invoices

| Column | Type | Nullable | Default |
|--------|------|----------|---------|
| `id` | int8 | No | nextval('invoices_id_seq'::regclass) |
| `tenant_id` | int8 | No | - |
| `client_id` | int8 | No | - |
| `type` | varchar | No | 'invoice'::character varying |
| `invoice_number` | varchar | No | - |
| `date` | date | No | - |
| `due_date` | date | No | - |
| `status` | varchar | No | 'draft'::character varying |
| `subtotal` | numeric | No | '0'::numeric |
| `discount_amount` | numeric | No | '0'::numeric |
| `vat_amount` | numeric | No | '0'::numeric |
| `total` | numeric | No | '0'::numeric |
| `amount_paid` | numeric | No | '0'::numeric |
| `currency` | varchar | No | 'EGP'::character varying |
| `notes` | text | Yes | - |
| `terms` | text | Yes | - |
| `sent_at` | timestamp | Yes | - |
| `cancelled_at` | timestamp | Yes | - |
| `cancelled_by` | int8 | Yes | - |
| `original_invoice_id` | int8 | Yes | - |
| `journal_entry_id` | int8 | Yes | - |
| `created_by` | int8 | Yes | - |
| `created_at` | timestamp | Yes | - |
| `updated_at` | timestamp | Yes | - |
| `deleted_at` | timestamp | Yes | - |

**Indexes:**
- `invoices_client_id_index` (INDEX) on (client_id)
- `invoices_date_index` (INDEX) on (date)
- `invoices_due_date_index` (INDEX) on (due_date)
- `invoices_pkey` (UNIQUE) on (id)
- `invoices_status_index` (INDEX) on (status)
- `invoices_tenant_id_index` (INDEX) on (tenant_id)
- `invoices_tenant_id_invoice_number_unique` (UNIQUE) on (tenant_id, invoice_number)
- `invoices_type_index` (INDEX) on (type)

**Foreign Keys:**
- `invoices_cancelled_by_foreign`: (cancelled_by) → users(id)
- `invoices_client_id_foreign`: (client_id) → clients(id)
- `invoices_created_by_foreign`: (created_by) → users(id)
- `invoices_journal_entry_id_foreign`: (journal_entry_id) → journal_entries(id)
- `invoices_original_invoice_id_foreign`: (original_invoice_id) → invoices(id)
- `invoices_tenant_id_foreign`: (tenant_id) → tenants(id)

---

## job_batches

| Column | Type | Nullable | Default |
|--------|------|----------|---------|
| `id` | varchar | No | - |
| `name` | varchar | No | - |
| `total_jobs` | int4 | No | - |
| `pending_jobs` | int4 | No | - |
| `failed_jobs` | int4 | No | - |
| `failed_job_ids` | text | No | - |
| `options` | text | Yes | - |
| `cancelled_at` | int4 | Yes | - |
| `created_at` | int4 | No | - |
| `finished_at` | int4 | Yes | - |

**Indexes:**
- `job_batches_pkey` (UNIQUE) on (id)

---

## jobs

| Column | Type | Nullable | Default |
|--------|------|----------|---------|
| `id` | int8 | No | nextval('jobs_id_seq'::regclass) |
| `queue` | varchar | No | - |
| `payload` | text | No | - |
| `attempts` | int2 | No | - |
| `reserved_at` | int4 | Yes | - |
| `available_at` | int4 | No | - |
| `created_at` | int4 | No | - |

**Indexes:**
- `jobs_pkey` (UNIQUE) on (id)
- `jobs_queue_index` (INDEX) on (queue)

---

## journal_entries

| Column | Type | Nullable | Default |
|--------|------|----------|---------|
| `id` | int8 | No | nextval('journal_entries_id_seq'::regclass) |
| `tenant_id` | int8 | No | - |
| `fiscal_period_id` | int8 | Yes | - |
| `entry_number` | varchar | No | - |
| `date` | date | No | - |
| `description` | text | No | - |
| `reference` | varchar | Yes | - |
| `status` | varchar | No | 'draft'::character varying |
| `posted_at` | timestamp | Yes | - |
| `posted_by` | int8 | Yes | - |
| `reversed_at` | timestamp | Yes | - |
| `reversed_by` | int8 | Yes | - |
| `reversal_of_id` | int8 | Yes | - |
| `created_by` | int8 | Yes | - |
| `total_debit` | numeric | No | '0'::numeric |
| `total_credit` | numeric | No | '0'::numeric |
| `created_at` | timestamp | Yes | - |
| `updated_at` | timestamp | Yes | - |
| `deleted_at` | timestamp | Yes | - |

**Indexes:**
- `journal_entries_date_index` (INDEX) on (date)
- `journal_entries_entry_number_index` (INDEX) on (entry_number)
- `journal_entries_pkey` (UNIQUE) on (id)
- `journal_entries_status_index` (INDEX) on (status)
- `journal_entries_tenant_id_entry_number_unique` (UNIQUE) on (tenant_id, entry_number)
- `journal_entries_tenant_id_index` (INDEX) on (tenant_id)

**Foreign Keys:**
- `journal_entries_created_by_foreign`: (created_by) → users(id)
- `journal_entries_fiscal_period_id_foreign`: (fiscal_period_id) → fiscal_periods(id)
- `journal_entries_posted_by_foreign`: (posted_by) → users(id)
- `journal_entries_reversal_of_id_foreign`: (reversal_of_id) → journal_entries(id)
- `journal_entries_reversed_by_foreign`: (reversed_by) → users(id)
- `journal_entries_tenant_id_foreign`: (tenant_id) → tenants(id)

---

## journal_entry_lines

| Column | Type | Nullable | Default |
|--------|------|----------|---------|
| `id` | int8 | No | nextval('journal_entry_lines_id_seq'::regclass) |
| `journal_entry_id` | int8 | No | - |
| `account_id` | int8 | No | - |
| `debit` | numeric | No | '0'::numeric |
| `credit` | numeric | No | '0'::numeric |
| `description` | varchar | Yes | - |
| `cost_center` | varchar | Yes | - |
| `created_at` | timestamp | Yes | - |
| `updated_at` | timestamp | Yes | - |

**Indexes:**
- `journal_entry_lines_account_id_index` (INDEX) on (account_id)
- `journal_entry_lines_journal_entry_id_index` (INDEX) on (journal_entry_id)
- `journal_entry_lines_pkey` (UNIQUE) on (id)

**Foreign Keys:**
- `journal_entry_lines_account_id_foreign`: (account_id) → accounts(id)
- `journal_entry_lines_journal_entry_id_foreign`: (journal_entry_id) → journal_entries(id)

---

## landing_settings

| Column | Type | Nullable | Default |
|--------|------|----------|---------|
| `id` | int8 | No | nextval('landing_settings_id_seq'::regclass) |
| `section` | varchar | No | - |
| `data` | json | No | - |
| `created_at` | timestamp | Yes | - |
| `updated_at` | timestamp | Yes | - |

**Indexes:**
- `landing_settings_pkey` (UNIQUE) on (id)
- `landing_settings_section_unique` (UNIQUE) on (section)

---

## media

| Column | Type | Nullable | Default |
|--------|------|----------|---------|
| `id` | int8 | No | nextval('media_id_seq'::regclass) |
| `model_type` | varchar | No | - |
| `model_id` | int8 | No | - |
| `uuid` | uuid | Yes | - |
| `collection_name` | varchar | No | - |
| `name` | varchar | No | - |
| `file_name` | varchar | No | - |
| `mime_type` | varchar | Yes | - |
| `disk` | varchar | No | - |
| `conversions_disk` | varchar | Yes | - |
| `size` | int8 | No | - |
| `manipulations` | json | No | - |
| `custom_properties` | json | No | - |
| `generated_conversions` | json | No | - |
| `responsive_images` | json | No | - |
| `order_column` | int4 | Yes | - |
| `created_at` | timestamp | Yes | - |
| `updated_at` | timestamp | Yes | - |

**Indexes:**
- `media_model_type_model_id_index` (INDEX) on (model_type, model_id)
- `media_order_column_index` (INDEX) on (order_column)
- `media_pkey` (UNIQUE) on (id)
- `media_uuid_unique` (UNIQUE) on (uuid)

---

## messages

| Column | Type | Nullable | Default |
|--------|------|----------|---------|
| `id` | int8 | No | nextval('messages_id_seq'::regclass) |
| `tenant_id` | int8 | No | - |
| `client_id` | int8 | No | - |
| `user_id` | int8 | No | - |
| `direction` | varchar | No | - |
| `subject` | varchar | No | - |
| `body` | text | No | - |
| `read_at` | timestamp | Yes | - |
| `created_at` | timestamp | Yes | - |
| `updated_at` | timestamp | Yes | - |
| `deleted_at` | timestamp | Yes | - |

**Indexes:**
- `messages_client_id_index` (INDEX) on (client_id)
- `messages_direction_index` (INDEX) on (direction)
- `messages_pkey` (UNIQUE) on (id)
- `messages_tenant_id_index` (INDEX) on (tenant_id)
- `messages_user_id_index` (INDEX) on (user_id)

**Foreign Keys:**
- `messages_client_id_foreign`: (client_id) → clients(id)
- `messages_tenant_id_foreign`: (tenant_id) → tenants(id)
- `messages_user_id_foreign`: (user_id) → users(id)

---

## model_has_permissions

| Column | Type | Nullable | Default |
|--------|------|----------|---------|
| `permission_id` | int8 | No | - |
| `model_type` | varchar | No | - |
| `model_id` | int8 | No | - |

**Indexes:**
- `model_has_permissions_model_id_model_type_index` (INDEX) on (model_id, model_type)
- `model_has_permissions_pkey` (UNIQUE) on (permission_id, model_id, model_type)

**Foreign Keys:**
- `model_has_permissions_permission_id_foreign`: (permission_id) → permissions(id)

---

## model_has_roles

| Column | Type | Nullable | Default |
|--------|------|----------|---------|
| `role_id` | int8 | No | - |
| `model_type` | varchar | No | - |
| `model_id` | int8 | No | - |

**Indexes:**
- `model_has_roles_model_id_model_type_index` (INDEX) on (model_id, model_type)
- `model_has_roles_pkey` (UNIQUE) on (role_id, model_id, model_type)

**Foreign Keys:**
- `model_has_roles_role_id_foreign`: (role_id) → roles(id)

---

## notification_preferences

| Column | Type | Nullable | Default |
|--------|------|----------|---------|
| `id` | int8 | No | nextval('notification_preferences_id_seq'::regclass) |
| `user_id` | int8 | No | - |
| `channel` | varchar | No | - |
| `type` | varchar | No | - |
| `enabled` | bool | No | true |
| `created_at` | timestamp | Yes | - |
| `updated_at` | timestamp | Yes | - |

**Indexes:**
- `notification_preferences_pkey` (UNIQUE) on (id)
- `notification_preferences_user_id_channel_type_unique` (UNIQUE) on (user_id, channel, type)
- `notification_preferences_user_id_index` (INDEX) on (user_id)

**Foreign Keys:**
- `notification_preferences_user_id_foreign`: (user_id) → users(id)

---

## notifications

| Column | Type | Nullable | Default |
|--------|------|----------|---------|
| `id` | uuid | No | - |
| `tenant_id` | int8 | No | - |
| `user_id` | int8 | No | - |
| `type` | varchar | No | - |
| `channel` | varchar | No | 'in_app'::character varying |
| `title_ar` | varchar | No | - |
| `title_en` | varchar | Yes | - |
| `body_ar` | text | Yes | - |
| `body_en` | text | Yes | - |
| `action_url` | varchar | Yes | - |
| `data` | json | Yes | - |
| `read_at` | timestamp | Yes | - |
| `emailed_at` | timestamp | Yes | - |
| `created_at` | timestamp | Yes | - |
| `updated_at` | timestamp | Yes | - |

**Indexes:**
- `notifications_pkey` (UNIQUE) on (id)
- `notifications_read_at_index` (INDEX) on (read_at)
- `notifications_tenant_id_index` (INDEX) on (tenant_id)
- `notifications_type_index` (INDEX) on (type)
- `notifications_user_id_index` (INDEX) on (user_id)

**Foreign Keys:**
- `notifications_tenant_id_foreign`: (tenant_id) → tenants(id)
- `notifications_user_id_foreign`: (user_id) → users(id)

---

## onboarding_steps

| Column | Type | Nullable | Default |
|--------|------|----------|---------|
| `id` | int8 | No | nextval('onboarding_steps_id_seq'::regclass) |
| `tenant_id` | int8 | No | - |
| `company_details_completed` | bool | No | false |
| `coa_template_selected` | bool | No | false |
| `coa_template_name` | varchar | Yes | - |
| `first_client_added` | bool | No | false |
| `first_invoice_created` | bool | No | false |
| `team_invited` | bool | No | false |
| `sample_data_loaded` | bool | No | false |
| `wizard_completed` | bool | No | false |
| `wizard_completed_at` | timestamp | Yes | - |
| `wizard_skipped` | bool | No | false |
| `current_step` | int2 | No | '1'::smallint |
| `created_at` | timestamp | Yes | - |
| `updated_at` | timestamp | Yes | - |

**Indexes:**
- `onboarding_steps_pkey` (UNIQUE) on (id)
- `onboarding_steps_tenant_id_unique` (UNIQUE) on (tenant_id)

**Foreign Keys:**
- `onboarding_steps_tenant_id_foreign`: (tenant_id) → tenants(id)

---

## password_reset_tokens

| Column | Type | Nullable | Default |
|--------|------|----------|---------|
| `email` | varchar | No | - |
| `token` | varchar | No | - |
| `created_at` | timestamp | Yes | - |

**Indexes:**
- `password_reset_tokens_pkey` (UNIQUE) on (email)

---

## payments

| Column | Type | Nullable | Default |
|--------|------|----------|---------|
| `id` | int8 | No | nextval('payments_id_seq'::regclass) |
| `tenant_id` | int8 | No | - |
| `invoice_id` | int8 | No | - |
| `amount` | numeric | No | - |
| `date` | date | No | - |
| `method` | varchar | No | - |
| `reference` | varchar | Yes | - |
| `notes` | text | Yes | - |
| `journal_entry_id` | int8 | Yes | - |
| `created_by` | int8 | Yes | - |
| `created_at` | timestamp | Yes | - |
| `updated_at` | timestamp | Yes | - |
| `deleted_at` | timestamp | Yes | - |

**Indexes:**
- `payments_date_index` (INDEX) on (date)
- `payments_invoice_id_index` (INDEX) on (invoice_id)
- `payments_method_index` (INDEX) on (method)
- `payments_pkey` (UNIQUE) on (id)
- `payments_tenant_id_index` (INDEX) on (tenant_id)

**Foreign Keys:**
- `payments_created_by_foreign`: (created_by) → users(id)
- `payments_invoice_id_foreign`: (invoice_id) → invoices(id)
- `payments_journal_entry_id_foreign`: (journal_entry_id) → journal_entries(id)
- `payments_tenant_id_foreign`: (tenant_id) → tenants(id)

---

## payroll_items

| Column | Type | Nullable | Default |
|--------|------|----------|---------|
| `id` | int8 | No | nextval('payroll_items_id_seq'::regclass) |
| `payroll_run_id` | int8 | No | - |
| `employee_id` | int8 | No | - |
| `base_salary` | numeric | No | - |
| `allowances` | numeric | No | '0'::numeric |
| `overtime_hours` | numeric | No | '0'::numeric |
| `overtime_amount` | numeric | No | '0'::numeric |
| `gross_salary` | numeric | No | - |
| `social_insurance_employee` | numeric | No | '0'::numeric |
| `social_insurance_employer` | numeric | No | '0'::numeric |
| `taxable_income` | numeric | No | '0'::numeric |
| `income_tax` | numeric | No | '0'::numeric |
| `other_deductions` | numeric | No | '0'::numeric |
| `net_salary` | numeric | No | - |
| `notes` | text | Yes | - |
| `created_at` | timestamp | Yes | - |
| `updated_at` | timestamp | Yes | - |

**Indexes:**
- `payroll_items_employee_id_index` (INDEX) on (employee_id)
- `payroll_items_payroll_run_id_employee_id_unique` (UNIQUE) on (payroll_run_id, employee_id)
- `payroll_items_pkey` (UNIQUE) on (id)

**Foreign Keys:**
- `payroll_items_employee_id_foreign`: (employee_id) → employees(id)
- `payroll_items_payroll_run_id_foreign`: (payroll_run_id) → payroll_runs(id)

---

## payroll_runs

| Column | Type | Nullable | Default |
|--------|------|----------|---------|
| `id` | int8 | No | nextval('payroll_runs_id_seq'::regclass) |
| `tenant_id` | int8 | No | - |
| `month` | int2 | No | - |
| `year` | int2 | No | - |
| `status` | varchar | No | 'draft'::character varying |
| `total_gross` | numeric | No | '0'::numeric |
| `total_deductions` | numeric | No | '0'::numeric |
| `total_net` | numeric | No | '0'::numeric |
| `total_social_insurance` | numeric | No | '0'::numeric |
| `total_tax` | numeric | No | '0'::numeric |
| `run_by` | int8 | Yes | - |
| `approved_by` | int8 | Yes | - |
| `approved_at` | timestamp | Yes | - |
| `created_at` | timestamp | Yes | - |
| `updated_at` | timestamp | Yes | - |
| `deleted_at` | timestamp | Yes | - |

**Indexes:**
- `payroll_runs_pkey` (UNIQUE) on (id)
- `payroll_runs_status_index` (INDEX) on (status)
- `payroll_runs_tenant_id_index` (INDEX) on (tenant_id)
- `payroll_runs_tenant_id_month_year_unique` (UNIQUE) on (tenant_id, month, year)

**Foreign Keys:**
- `payroll_runs_approved_by_foreign`: (approved_by) → users(id)
- `payroll_runs_run_by_foreign`: (run_by) → users(id)
- `payroll_runs_tenant_id_foreign`: (tenant_id) → tenants(id)

---

## permissions

| Column | Type | Nullable | Default |
|--------|------|----------|---------|
| `id` | int8 | No | nextval('permissions_id_seq'::regclass) |
| `name` | varchar | No | - |
| `guard_name` | varchar | No | - |
| `created_at` | timestamp | Yes | - |
| `updated_at` | timestamp | Yes | - |

**Indexes:**
- `permissions_name_guard_name_unique` (UNIQUE) on (name, guard_name)
- `permissions_pkey` (UNIQUE) on (id)

---

## personal_access_tokens

| Column | Type | Nullable | Default |
|--------|------|----------|---------|
| `id` | int8 | No | nextval('personal_access_tokens_id_seq'::regclass) |
| `tokenable_type` | varchar | No | - |
| `tokenable_id` | int8 | No | - |
| `name` | text | No | - |
| `token` | varchar | No | - |
| `abilities` | text | Yes | - |
| `last_used_at` | timestamp | Yes | - |
| `expires_at` | timestamp | Yes | - |
| `created_at` | timestamp | Yes | - |
| `updated_at` | timestamp | Yes | - |

**Indexes:**
- `personal_access_tokens_expires_at_index` (INDEX) on (expires_at)
- `personal_access_tokens_pkey` (UNIQUE) on (id)
- `personal_access_tokens_token_unique` (UNIQUE) on (token)
- `personal_access_tokens_tokenable_type_tokenable_id_index` (INDEX) on (tokenable_type, tokenable_id)

---

## plans

| Column | Type | Nullable | Default |
|--------|------|----------|---------|
| `id` | int8 | No | nextval('plans_id_seq'::regclass) |
| `name_en` | varchar | No | - |
| `name_ar` | varchar | No | - |
| `slug` | varchar | No | - |
| `description_en` | text | Yes | - |
| `description_ar` | text | Yes | - |
| `price_monthly` | numeric | No | '0'::numeric |
| `price_annual` | numeric | No | '0'::numeric |
| `currency` | varchar | No | 'EGP'::character varying |
| `trial_days` | int2 | No | '14'::smallint |
| `limits` | json | No | - |
| `features` | json | No | - |
| `is_active` | bool | No | true |
| `sort_order` | int2 | No | '0'::smallint |
| `created_at` | timestamp | Yes | - |
| `updated_at` | timestamp | Yes | - |
| `deleted_at` | timestamp | Yes | - |

**Indexes:**
- `plans_is_active_index` (INDEX) on (is_active)
- `plans_pkey` (UNIQUE) on (id)
- `plans_slug_index` (INDEX) on (slug)
- `plans_slug_unique` (UNIQUE) on (slug)

---

## platform_settings

| Column | Type | Nullable | Default |
|--------|------|----------|---------|
| `id` | int8 | No | nextval('platform_settings_id_seq'::regclass) |
| `key` | varchar | No | - |
| `value` | text | Yes | - |
| `created_at` | timestamp | Yes | - |
| `updated_at` | timestamp | Yes | - |

**Indexes:**
- `platform_settings_key_unique` (UNIQUE) on (key)
- `platform_settings_pkey` (UNIQUE) on (id)

---

## profit_distributions

| Column | Type | Nullable | Default |
|--------|------|----------|---------|
| `id` | int8 | No | nextval('profit_distributions_id_seq'::regclass) |
| `investor_id` | int8 | No | - |
| `tenant_id` | int8 | No | - |
| `month` | int2 | No | - |
| `year` | int2 | No | - |
| `tenant_revenue` | numeric | No | '0'::numeric |
| `tenant_expenses` | numeric | No | '0'::numeric |
| `net_profit` | numeric | No | '0'::numeric |
| `ownership_percentage` | numeric | No | - |
| `investor_share` | numeric | No | '0'::numeric |
| `status` | varchar | No | 'draft'::character varying |
| `paid_at` | timestamp | Yes | - |
| `notes` | text | Yes | - |
| `created_at` | timestamp | Yes | - |
| `updated_at` | timestamp | Yes | - |

**Indexes:**
- `profit_distributions_investor_id_index` (INDEX) on (investor_id)
- `profit_distributions_investor_id_tenant_id_month_year_unique` (UNIQUE) on (investor_id, tenant_id, month, year)
- `profit_distributions_pkey` (UNIQUE) on (id)
- `profit_distributions_status_index` (INDEX) on (status)
- `profit_distributions_tenant_id_index` (INDEX) on (tenant_id)

**Foreign Keys:**
- `profit_distributions_investor_id_foreign`: (investor_id) → investors(id)
- `profit_distributions_tenant_id_foreign`: (tenant_id) → tenants(id)

---

## recurring_invoices

| Column | Type | Nullable | Default |
|--------|------|----------|---------|
| `id` | int8 | No | nextval('recurring_invoices_id_seq'::regclass) |
| `tenant_id` | int8 | No | - |
| `client_id` | int8 | No | - |
| `created_by` | int8 | Yes | - |
| `frequency` | varchar | No | - |
| `day_of_month` | int2 | Yes | - |
| `day_of_week` | int2 | Yes | - |
| `start_date` | date | No | - |
| `end_date` | date | Yes | - |
| `next_run_date` | date | No | - |
| `last_run_date` | date | Yes | - |
| `line_items` | json | No | - |
| `currency` | varchar | No | 'EGP'::character varying |
| `notes` | text | Yes | - |
| `terms` | text | Yes | - |
| `due_days` | int2 | No | '30'::smallint |
| `is_active` | bool | No | true |
| `auto_send` | bool | No | false |
| `invoices_generated` | int4 | No | 0 |
| `max_occurrences` | int4 | Yes | - |
| `created_at` | timestamp | Yes | - |
| `updated_at` | timestamp | Yes | - |
| `deleted_at` | timestamp | Yes | - |

**Indexes:**
- `recurring_invoices_pkey` (UNIQUE) on (id)
- `recurring_invoices_tenant_id_is_active_next_run_date_index` (INDEX) on (tenant_id, is_active, next_run_date)

**Foreign Keys:**
- `recurring_invoices_client_id_foreign`: (client_id) → clients(id)
- `recurring_invoices_created_by_foreign`: (created_by) → users(id)
- `recurring_invoices_tenant_id_foreign`: (tenant_id) → tenants(id)

---

## role_has_permissions

| Column | Type | Nullable | Default |
|--------|------|----------|---------|
| `permission_id` | int8 | No | - |
| `role_id` | int8 | No | - |

**Indexes:**
- `role_has_permissions_pkey` (UNIQUE) on (permission_id, role_id)

**Foreign Keys:**
- `role_has_permissions_permission_id_foreign`: (permission_id) → permissions(id)
- `role_has_permissions_role_id_foreign`: (role_id) → roles(id)

---

## roles

| Column | Type | Nullable | Default |
|--------|------|----------|---------|
| `id` | int8 | No | nextval('roles_id_seq'::regclass) |
| `name` | varchar | No | - |
| `guard_name` | varchar | No | - |
| `created_at` | timestamp | Yes | - |
| `updated_at` | timestamp | Yes | - |

**Indexes:**
- `roles_name_guard_name_unique` (UNIQUE) on (name, guard_name)
- `roles_pkey` (UNIQUE) on (id)

---

## sessions

| Column | Type | Nullable | Default |
|--------|------|----------|---------|
| `id` | varchar | No | - |
| `user_id` | int8 | Yes | - |
| `ip_address` | varchar | Yes | - |
| `user_agent` | text | Yes | - |
| `payload` | text | No | - |
| `last_activity` | int4 | No | - |

**Indexes:**
- `sessions_last_activity_index` (INDEX) on (last_activity)
- `sessions_pkey` (UNIQUE) on (id)
- `sessions_user_id_index` (INDEX) on (user_id)

---

## slug_redirects

| Column | Type | Nullable | Default |
|--------|------|----------|---------|
| `id` | int8 | No | nextval('slug_redirects_id_seq'::regclass) |
| `old_slug` | varchar | No | - |
| `new_slug` | varchar | No | - |
| `type` | varchar | No | - |
| `created_at` | timestamp | Yes | - |
| `updated_at` | timestamp | Yes | - |

**Indexes:**
- `slug_redirects_old_slug_index` (INDEX) on (old_slug)
- `slug_redirects_old_slug_type_unique` (UNIQUE) on (old_slug, type)
- `slug_redirects_pkey` (UNIQUE) on (id)

---

## storage_quotas

| Column | Type | Nullable | Default |
|--------|------|----------|---------|
| `id` | int8 | No | nextval('storage_quotas_id_seq'::regclass) |
| `tenant_id` | int8 | No | - |
| `max_bytes` | int8 | No | '1073741824'::bigint |
| `used_bytes` | int8 | No | '0'::bigint |
| `max_files` | int4 | No | 5000 |
| `used_files` | int4 | No | 0 |
| `created_at` | timestamp | Yes | - |
| `updated_at` | timestamp | Yes | - |

**Indexes:**
- `storage_quotas_pkey` (UNIQUE) on (id)
- `storage_quotas_tenant_id_unique` (UNIQUE) on (tenant_id)

**Foreign Keys:**
- `storage_quotas_tenant_id_foreign`: (tenant_id) → tenants(id)

---

## subscription_payments

| Column | Type | Nullable | Default |
|--------|------|----------|---------|
| `id` | int8 | No | nextval('subscription_payments_id_seq'::regclass) |
| `tenant_id` | int8 | No | - |
| `subscription_id` | int8 | No | - |
| `amount` | numeric | No | - |
| `currency` | varchar | No | 'EGP'::character varying |
| `status` | varchar | No | 'pending'::character varying |
| `gateway` | varchar | No | - |
| `gateway_transaction_id` | varchar | Yes | - |
| `gateway_order_id` | varchar | Yes | - |
| `payment_method_type` | varchar | Yes | - |
| `billing_period_start` | date | Yes | - |
| `billing_period_end` | date | Yes | - |
| `paid_at` | timestamp | Yes | - |
| `failed_at` | timestamp | Yes | - |
| `failure_reason` | text | Yes | - |
| `refunded_at` | timestamp | Yes | - |
| `receipt_url` | varchar | Yes | - |
| `metadata` | json | Yes | - |
| `created_at` | timestamp | Yes | - |
| `updated_at` | timestamp | Yes | - |

**Indexes:**
- `subscription_payments_gateway_order_id_index` (INDEX) on (gateway_order_id)
- `subscription_payments_gateway_transaction_id_index` (INDEX) on (gateway_transaction_id)
- `subscription_payments_pkey` (UNIQUE) on (id)
- `subscription_payments_status_index` (INDEX) on (status)
- `subscription_payments_subscription_id_index` (INDEX) on (subscription_id)
- `subscription_payments_tenant_id_index` (INDEX) on (tenant_id)

**Foreign Keys:**
- `subscription_payments_subscription_id_foreign`: (subscription_id) → subscriptions(id)
- `subscription_payments_tenant_id_foreign`: (tenant_id) → tenants(id)

---

## subscriptions

| Column | Type | Nullable | Default |
|--------|------|----------|---------|
| `id` | int8 | No | nextval('subscriptions_id_seq'::regclass) |
| `tenant_id` | int8 | No | - |
| `plan_id` | int8 | No | - |
| `status` | varchar | No | 'trial'::character varying |
| `billing_cycle` | varchar | No | 'monthly'::character varying |
| `price` | numeric | No | - |
| `currency` | varchar | No | 'EGP'::character varying |
| `trial_ends_at` | timestamp | Yes | - |
| `current_period_start` | date | Yes | - |
| `current_period_end` | date | Yes | - |
| `cancelled_at` | timestamp | Yes | - |
| `cancellation_reason` | text | Yes | - |
| `expires_at` | timestamp | Yes | - |
| `gateway` | varchar | Yes | - |
| `gateway_subscription_id` | varchar | Yes | - |
| `metadata` | json | Yes | - |
| `created_at` | timestamp | Yes | - |
| `updated_at` | timestamp | Yes | - |

**Indexes:**
- `subscriptions_pkey` (UNIQUE) on (id)
- `subscriptions_plan_id_index` (INDEX) on (plan_id)
- `subscriptions_status_index` (INDEX) on (status)

**Foreign Keys:**
- `subscriptions_plan_id_foreign`: (plan_id) → plans(id)
- `subscriptions_tenant_id_foreign`: (tenant_id) → tenants(id)

---

## tenants

| Column | Type | Nullable | Default |
|--------|------|----------|---------|
| `id` | int8 | No | nextval('tenants_id_seq'::regclass) |
| `name` | varchar | No | - |
| `slug` | varchar | No | - |
| `domain` | varchar | Yes | - |
| `email` | varchar | Yes | - |
| `phone` | varchar | Yes | - |
| `tax_id` | varchar | Yes | - |
| `commercial_register` | varchar | Yes | - |
| `address` | text | Yes | - |
| `city` | varchar | Yes | - |
| `status` | varchar | No | 'trial'::character varying |
| `settings` | jsonb | No | '{}'::jsonb |
| `trial_ends_at` | timestamp | Yes | - |
| `logo_path` | varchar | Yes | - |
| `created_at` | timestamp | Yes | - |
| `updated_at` | timestamp | Yes | - |
| `deleted_at` | timestamp | Yes | - |
| `tagline` | varchar | Yes | - |
| `description` | text | Yes | - |
| `primary_color` | varchar | Yes | '#2c3e50'::character varying |
| `secondary_color` | varchar | Yes | '#3498db'::character varying |
| `hero_image_path` | varchar | Yes | - |
| `is_landing_page_active` | bool | No | false |

**Indexes:**
- `tenants_domain_unique` (UNIQUE) on (domain)
- `tenants_pkey` (UNIQUE) on (id)
- `tenants_slug_unique` (UNIQUE) on (slug)
- `tenants_status_index` (INDEX) on (status)
- `tenants_trial_ends_at_index` (INDEX) on (trial_ends_at)

---

## testimonials

| Column | Type | Nullable | Default |
|--------|------|----------|---------|
| `id` | int8 | No | nextval('testimonials_id_seq'::regclass) |
| `name_ar` | varchar | No | - |
| `name_en` | varchar | No | - |
| `role_ar` | varchar | No | - |
| `role_en` | varchar | No | - |
| `quote_ar` | text | No | - |
| `quote_en` | text | No | - |
| `rating` | int2 | No | '5'::smallint |
| `is_active` | bool | No | true |
| `sort_order` | int4 | No | 0 |
| `created_at` | timestamp | Yes | - |
| `updated_at` | timestamp | Yes | - |

**Indexes:**
- `testimonials_active_sort_idx` (INDEX) on (is_active, sort_order)
- `testimonials_pkey` (UNIQUE) on (id)

---

## timers

| Column | Type | Nullable | Default |
|--------|------|----------|---------|
| `id` | int8 | No | nextval('timers_id_seq'::regclass) |
| `tenant_id` | int8 | No | - |
| `user_id` | int8 | No | - |
| `client_id` | int8 | Yes | - |
| `task_description` | varchar | No | - |
| `started_at` | timestamp | No | - |
| `stopped_at` | timestamp | Yes | - |
| `is_running` | bool | No | true |
| `created_at` | timestamp | Yes | - |
| `updated_at` | timestamp | Yes | - |

**Indexes:**
- `timers_is_running_index` (INDEX) on (is_running)
- `timers_pkey` (UNIQUE) on (id)
- `timers_tenant_id_index` (INDEX) on (tenant_id)
- `timers_user_id_index` (INDEX) on (user_id)

**Foreign Keys:**
- `timers_client_id_foreign`: (client_id) → clients(id)
- `timers_tenant_id_foreign`: (tenant_id) → tenants(id)
- `timers_user_id_foreign`: (user_id) → users(id)

---

## timesheet_entries

| Column | Type | Nullable | Default |
|--------|------|----------|---------|
| `id` | int8 | No | nextval('timesheet_entries_id_seq'::regclass) |
| `tenant_id` | int8 | No | - |
| `user_id` | int8 | No | - |
| `client_id` | int8 | Yes | - |
| `date` | date | No | - |
| `task_description` | varchar | No | - |
| `hours` | numeric | No | - |
| `is_billable` | bool | No | true |
| `status` | varchar | No | 'draft'::character varying |
| `approved_by` | int8 | Yes | - |
| `approved_at` | timestamp | Yes | - |
| `hourly_rate` | numeric | Yes | - |
| `notes` | text | Yes | - |
| `invoice_id` | int8 | Yes | - |
| `created_at` | timestamp | Yes | - |
| `updated_at` | timestamp | Yes | - |
| `deleted_at` | timestamp | Yes | - |

**Indexes:**
- `timesheet_entries_client_id_index` (INDEX) on (client_id)
- `timesheet_entries_date_index` (INDEX) on (date)
- `timesheet_entries_pkey` (UNIQUE) on (id)
- `timesheet_entries_status_index` (INDEX) on (status)
- `timesheet_entries_tenant_id_index` (INDEX) on (tenant_id)
- `timesheet_entries_user_id_index` (INDEX) on (user_id)

**Foreign Keys:**
- `timesheet_entries_approved_by_foreign`: (approved_by) → users(id)
- `timesheet_entries_client_id_foreign`: (client_id) → clients(id)
- `timesheet_entries_invoice_id_foreign`: (invoice_id) → invoices(id)
- `timesheet_entries_tenant_id_foreign`: (tenant_id) → tenants(id)
- `timesheet_entries_user_id_foreign`: (user_id) → users(id)

---

## usage_records

| Column | Type | Nullable | Default |
|--------|------|----------|---------|
| `id` | int8 | No | nextval('usage_records_id_seq'::regclass) |
| `tenant_id` | int8 | No | - |
| `recorded_at` | date | No | - |
| `users_count` | int4 | No | 0 |
| `clients_count` | int4 | No | 0 |
| `invoices_count` | int4 | No | 0 |
| `storage_bytes` | int8 | No | '0'::bigint |
| `metadata` | json | Yes | - |
| `created_at` | timestamp | Yes | - |
| `updated_at` | timestamp | Yes | - |

**Indexes:**
- `usage_records_pkey` (UNIQUE) on (id)
- `usage_records_recorded_at_index` (INDEX) on (recorded_at)
- `usage_records_tenant_id_index` (INDEX) on (tenant_id)
- `usage_records_tenant_id_recorded_at_unique` (UNIQUE) on (tenant_id, recorded_at)

**Foreign Keys:**
- `usage_records_tenant_id_foreign`: (tenant_id) → tenants(id)

---

## users

| Column | Type | Nullable | Default |
|--------|------|----------|---------|
| `id` | int8 | No | nextval('users_id_seq'::regclass) |
| `tenant_id` | int8 | Yes | - |
| `name` | varchar | No | - |
| `email` | varchar | No | - |
| `email_verified_at` | timestamp | Yes | - |
| `password` | varchar | No | - |
| `phone` | varchar | Yes | - |
| `role` | varchar | No | 'client'::character varying |
| `locale` | varchar | No | 'ar'::character varying |
| `is_active` | bool | No | true |
| `last_login_at` | timestamp | Yes | - |
| `remember_token` | varchar | Yes | - |
| `created_at` | timestamp | Yes | - |
| `updated_at` | timestamp | Yes | - |
| `deleted_at` | timestamp | Yes | - |
| `client_id` | int8 | Yes | - |
| `timezone` | varchar | Yes | - |
| `two_factor_secret` | text | Yes | - |
| `two_factor_recovery_codes` | text | Yes | - |
| `two_factor_enabled` | bool | No | false |
| `password_changed_at` | timestamp | Yes | - |

**Indexes:**
- `users_client_id_index` (INDEX) on (client_id)
- `users_email_unique` (UNIQUE) on (email)
- `users_is_active_index` (INDEX) on (is_active)
- `users_pkey` (UNIQUE) on (id)
- `users_role_index` (INDEX) on (role)
- `users_tenant_id_email_index` (INDEX) on (tenant_id, email)
- `users_tenant_id_index` (INDEX) on (tenant_id)

**Foreign Keys:**
- `users_client_id_foreign`: (client_id) → clients(id)
- `users_tenant_id_foreign`: (tenant_id) → tenants(id)

---

## webhook_deliveries

| Column | Type | Nullable | Default |
|--------|------|----------|---------|
| `id` | int8 | No | nextval('webhook_deliveries_id_seq'::regclass) |
| `endpoint_id` | int8 | No | - |
| `event` | varchar | No | - |
| `payload` | json | No | - |
| `status_code` | int2 | Yes | - |
| `response_body` | text | Yes | - |
| `duration_ms` | int4 | Yes | - |
| `attempt` | int2 | No | '1'::smallint |
| `status` | varchar | No | 'pending'::character varying |
| `error_message` | text | Yes | - |
| `next_retry_at` | timestamp | Yes | - |
| `created_at` | timestamp | Yes | - |
| `updated_at` | timestamp | Yes | - |

**Indexes:**
- `webhook_deliveries_endpoint_id_status_index` (INDEX) on (endpoint_id, status)
- `webhook_deliveries_pkey` (UNIQUE) on (id)
- `webhook_deliveries_status_next_retry_at_index` (INDEX) on (status, next_retry_at)

**Foreign Keys:**
- `webhook_deliveries_endpoint_id_foreign`: (endpoint_id) → webhook_endpoints(id)

---

## webhook_endpoints

| Column | Type | Nullable | Default |
|--------|------|----------|---------|
| `id` | int8 | No | nextval('webhook_endpoints_id_seq'::regclass) |
| `tenant_id` | int8 | No | - |
| `url` | varchar | No | - |
| `secret` | varchar | No | - |
| `events` | json | No | - |
| `description` | varchar | Yes | - |
| `is_active` | bool | No | true |
| `last_triggered_at` | timestamp | Yes | - |
| `failure_count` | int4 | No | 0 |
| `created_at` | timestamp | Yes | - |
| `updated_at` | timestamp | Yes | - |

**Indexes:**
- `webhook_endpoints_pkey` (UNIQUE) on (id)
- `webhook_endpoints_tenant_id_is_active_index` (INDEX) on (tenant_id, is_active)

**Foreign Keys:**
- `webhook_endpoints_tenant_id_foreign`: (tenant_id) → tenants(id)

---

## Summary

- **Total tables:** 71
- **Database:** pgsql
- **Generated by:** `php artisan docs:schema`
