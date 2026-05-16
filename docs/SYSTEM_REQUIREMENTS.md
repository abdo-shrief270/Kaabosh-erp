# Muhasebi - System Requirements

> Last Updated: March 31, 2026
> Document Scope: Development, Staging, and Production Environment Requirements

---

## 1. Development Environment Requirements

### 1.1 Backend Development

| Requirement | Minimum | Recommended |
|---|---|---|
| **Operating System** | macOS 13+, Ubuntu 22.04+, Windows 11 (WSL2) | macOS 14+ or Ubuntu 24.04 |
| **CPU** | 4 cores | 8+ cores (Apple M-series or AMD Ryzen 7+) |
| **RAM** | 8 GB | 16 GB+ |
| **Disk Space** | 20 GB free (SSD) | 50 GB+ free (NVMe SSD) |
| **PHP** | 8.4.0+ | 8.4.x (latest patch) |
| **Composer** | 2.8.0+ | 2.8.x (latest) |
| **PostgreSQL** | 17.0+ | 17.9+ (latest patch) |
| **Redis** | 7.4+ | 8.x (latest stable) |
| **Node.js** | 22.x LTS | 22.x LTS (latest patch) |
| **Docker** | 27.0+ | 27.x (latest) |
| **Docker Compose** | 2.20+ | 2.x (latest) |
| **Git** | 2.40+ | 2.47+ |

#### Required PHP Extensions

```
php-bcmath        # Precision math for financial calculations
php-ctype         # Character type checking
php-curl          # HTTP client
php-dom           # XML/HTML parsing
php-fileinfo      # File type detection
php-gd            # Image processing
php-intl          # Internationalization (Arabic/English formatting)
php-json          # JSON handling
php-mbstring      # Multi-byte string support (Arabic text)
php-openssl       # Encryption and SSL
php-pdo           # Database abstraction
php-pdo_pgsql     # PostgreSQL driver
php-redis         # Redis extension (phpredis)
php-tokenizer     # PHP tokenizer
php-xml           # XML processing
php-zip           # ZIP archive handling
php-sodium        # Modern encryption
php-pcntl         # Process control (for Octane/Horizon)
php-posix         # POSIX functions (for queue workers)
```

### 1.2 Frontend Development

| Requirement | Minimum | Recommended |
|---|---|---|
| **Node.js** | 22.x LTS | 22.x LTS (latest patch) |
| **pnpm** | 9.0+ | 9.x (latest) |
| **Browser** | Chrome 120+ / Firefox 120+ | Chrome latest / Firefox latest |
| **Screen Resolution** | 1920x1080 | 2560x1440+ (for responsive testing) |

### 1.3 Mobile Development

| Requirement | Minimum | Recommended |
|---|---|---|
| **Flutter SDK** | 3.29.0+ | 3.29.x (latest stable) |
| **Dart SDK** | 3.7.0+ | 3.7.x (latest stable) |
| **Android Studio** | Hedgehog (2023.1.1+) | Latest stable |
| **Xcode** | 15.0+ (macOS only) | 16.x (latest stable) |
| **Android SDK** | API 24+ (target: 35) | API 35 (latest) |
| **iOS Deployment Target** | iOS 15.0+ | iOS 16.0+ |
| **Java/JDK** | 17 | 21 LTS |
| **CocoaPods** | 1.15+ (macOS) | Latest |

---

## 2. Production Environment Requirements

### 2.1 Server Infrastructure (Self-Hosted / VPS)

#### Application Server(s)

| Resource | Small (up to 50 tenants) | Medium (50-500 tenants) | Large (500+ tenants) |
|---|---|---|---|
| **CPU** | 4 vCPUs | 8 vCPUs | 16+ vCPUs (auto-scaling) |
| **RAM** | 8 GB | 16 GB | 32+ GB |
| **Disk** | 100 GB NVMe SSD | 250 GB NVMe SSD | 500 GB+ NVMe SSD |
| **Network** | 1 Gbps | 1 Gbps | 10 Gbps |
| **OS** | Ubuntu 24.04 LTS | Ubuntu 24.04 LTS | Ubuntu 24.04 LTS |
| **Instances** | 1 | 2 (load balanced) | 3+ (auto-scaling group) |

#### Database Server(s)

| Resource | Small | Medium | Large |
|---|---|---|---|
| **CPU** | 4 vCPUs | 8 vCPUs | 16+ vCPUs |
| **RAM** | 16 GB | 32 GB | 64+ GB |
| **Disk** | 200 GB NVMe SSD | 500 GB NVMe SSD | 1+ TB NVMe SSD (RAID) |
| **PostgreSQL** | Single instance | Primary + 1 replica | Primary + 2 replicas + standby |
| **Backups** | Daily automated | Daily + WAL archiving | Continuous WAL + PITR |
| **Connection Pooler** | PgBouncer (optional) | PgBouncer (required) | PgBouncer (required) |

#### Cache / Queue Server

| Resource | Small | Medium | Large |
|---|---|---|---|
| **CPU** | 2 vCPUs | 4 vCPUs | 8 vCPUs |
| **RAM** | 4 GB | 8 GB | 16+ GB |
| **Redis** | Single instance | Sentinel (3 nodes) | Redis Cluster (6+ nodes) |
| **Disk** | 50 GB SSD | 100 GB SSD | 200 GB SSD |

#### Search Server

| Resource | Small | Medium | Large |
|---|---|---|---|
| **Meilisearch** | Embedded on app server | Dedicated 4 vCPU / 8 GB | Dedicated 8+ vCPU / 16 GB |
| **Disk** | Shared | 100 GB SSD | 250 GB SSD |

### 2.2 Cloud Hosting Options

#### Option A: Hetzner Cloud (Cost-Optimized, Egypt-Friendly)

```
Recommended for startups and small-to-medium deployments:

App Server:     CPX41 (8 vCPU, 16 GB RAM, 240 GB) — ~€28/mo
DB Server:      CPX41 (8 vCPU, 16 GB RAM, 240 GB) — ~€28/mo
Cache Server:   CPX21 (3 vCPU, 4 GB RAM, 80 GB)   — ~€9/mo
Object Storage: 1 TB                                — ~€5/mo
Load Balancer:  LB11                                — ~€6/mo
Backups:        20% of server cost                  — ~€13/mo

Estimated Monthly: ~€89/mo (~$97/mo)
```

#### Option B: AWS (Enterprise-Grade, Full Managed Services)

```
Recommended for large/enterprise deployments:

EC2:            t3.xlarge (4 vCPU, 16 GB) x2        — ~$250/mo
RDS PostgreSQL: db.r6g.large (2 vCPU, 16 GB)        — ~$200/mo
ElastiCache:    cache.r6g.large                      — ~$130/mo
S3:             Standard, 1 TB                       — ~$25/mo
CloudFront:     500 GB transfer                      — ~$45/mo
ALB:            Application Load Balancer             — ~$25/mo
Route 53:       Hosted zone + queries                 — ~$5/mo

Estimated Monthly: ~$680/mo
```

#### Option C: Hybrid (Recommended Balance)

```
Hetzner for compute + database (primary)
Cloudflare for CDN, DDoS, DNS, SSL
AWS S3 for object storage and backups
SendGrid for transactional email

Estimated Monthly: ~$120-180/mo (for 100 tenants)
```

### 2.3 SSL / TLS Requirements

| Requirement | Details |
|---|---|
| **SSL Certificate** | Wildcard SSL for *.muhasebi.com (Let's Encrypt or Cloudflare) |
| **TLS Version** | TLS 1.2 minimum, TLS 1.3 preferred |
| **HSTS** | Enabled with max-age=31536000, includeSubDomains |
| **Custom Domains** | Per-tenant custom domain with automated SSL provisioning (Caddy / Cloudflare for SaaS) |

---

## 3. Network Requirements

### 3.1 Ports & Firewall Rules

| Port | Protocol | Service | Access |
|---|---|---|---|
| 80 | TCP | HTTP (redirect to HTTPS) | Public |
| 443 | TCP | HTTPS (Nginx/Caddy) | Public |
| 5432 | TCP | PostgreSQL | Internal only |
| 6379 | TCP | Redis | Internal only |
| 7700 | TCP | Meilisearch | Internal only |
| 8080 | TCP | Laravel Octane | Internal (behind reverse proxy) |
| 6001 | TCP | Laravel Reverb (WebSocket) | Internal (behind reverse proxy) |
| 22 | TCP | SSH | Restricted IPs only |
| 9090 | TCP | Prometheus | Internal monitoring |
| 3000 | TCP | Grafana | Internal monitoring |

### 3.2 DNS Configuration

```
muhasebi.com              → A record → Load balancer IP
*.muhasebi.com            → CNAME → muhasebi.com (wildcard for tenant subdomains)
api.muhasebi.com          → A record → API server
app.muhasebi.com          → A record → Frontend app
portal.muhasebi.com       → A record → Client portal
status.muhasebi.com       → CNAME → Uptime Kuma instance
mail.muhasebi.com         → MX/CNAME → Email provider
```

### 3.3 Bandwidth & Latency

| Metric | Target |
|---|---|
| **Bandwidth** | 100 Mbps minimum for small, 1 Gbps for production |
| **Latency (Egypt)** | < 50ms to application server |
| **Latency (MENA)** | < 100ms via Cloudflare CDN |
| **CDN** | Cloudflare with Middle East PoPs for static asset delivery |

---

## 4. Software Dependencies (Production)

### 4.1 Server Software

```bash
# Operating System
Ubuntu 24.04 LTS (Server)

# Web Server / Reverse Proxy
Nginx 1.27.x  OR  Caddy 2.9.x (recommended for auto-SSL)

# PHP Runtime
PHP 8.4.x with FPM  OR  Laravel Octane with FrankenPHP/Swoole

# Process Manager
Supervisor 4.x (for queue workers, Horizon, Reverb)

# Container Runtime (if using Docker in production)
Docker 27.x + Docker Compose 2.x
  OR
Kubernetes 1.31.x (for large-scale)
```

### 4.2 Database Configuration

```ini
# PostgreSQL 17 - Key Configuration Parameters
max_connections = 200                  # Adjust based on tenant count
shared_buffers = 4GB                   # 25% of total RAM
effective_cache_size = 12GB            # 75% of total RAM
work_mem = 64MB                        # Per-operation memory
maintenance_work_mem = 1GB             # For VACUUM, CREATE INDEX
wal_level = replica                    # For replication and PITR
max_wal_size = 2GB                     # WAL segment retention
checkpoint_completion_target = 0.9     # Spread checkpoint writes
random_page_cost = 1.1                 # For SSD storage
effective_io_concurrency = 200         # For SSD storage
default_statistics_target = 200        # Better query planning
row_security = on                      # Enable Row-Level Security
log_min_duration_statement = 200       # Log slow queries (200ms+)
```

### 4.3 Redis Configuration

```ini
# Redis 8.x - Key Configuration
maxmemory 2gb                          # Adjust based on available RAM
maxmemory-policy allkeys-lru           # Eviction policy
appendonly yes                         # AOF persistence
appendfsync everysec                   # Fsync every second
```

---

## 5. Security Requirements

### 5.1 Server Hardening

- SSH key-only authentication (no password login)
- Fail2Ban configured for SSH and application login
- UFW firewall with deny-all default, explicit allow rules
- Automatic security updates enabled (unattended-upgrades)
- Non-root user for all application processes
- SELinux or AppArmor profiles for application processes
- Regular security patching schedule (monthly at minimum)

### 5.2 Application Security

- All secrets in environment variables (never in code)
- Secret management via HashiCorp Vault or AWS Secrets Manager
- OWASP Top 10 mitigations implemented
- Content Security Policy (CSP) headers
- X-Frame-Options, X-Content-Type-Options headers
- Rate limiting on all authentication endpoints
- CAPTCHA on public-facing forms (hCaptcha / Turnstile)
- SQL injection prevention via parameterized queries (Eloquent)
- XSS prevention via output encoding (Vue auto-escaping)
- CSRF protection on all state-changing requests

### 5.3 Compliance Requirements

| Standard | Requirement |
|---|---|
| **Egyptian Data Protection Law 151/2020** | Data localization option, consent management, DPO |
| **GDPR** | For EU-based clients, right to access/erasure, DPA |
| **PCI DSS** | No card data stored locally (tokenization via Paymob/Stripe) |
| **Egyptian E-Signature Law 15/2004** | Compliant digital signatures |
| **ETA E-Invoice Regulations** | HSM/USB token digital signing, real-time submission |

---

## 6. Backup & Disaster Recovery

### 6.1 Backup Strategy

| Component | Frequency | Retention | Method |
|---|---|---|---|
| **Database** | Continuous (WAL) + Daily full | 30 days (daily), 12 months (monthly) | pg_basebackup + WAL archiving to S3 |
| **File Storage** | Daily incremental | 30 days | Restic / rclone to S3 |
| **Redis** | Hourly RDB snapshot | 7 days | RDB dump to S3 |
| **Application Config** | On change (Git) | Unlimited | Git repository |
| **Full System** | Weekly snapshot | 4 weeks | Server snapshot (Hetzner/AWS) |

### 6.2 Recovery Objectives

| Metric | Target |
|---|---|
| **RPO (Recovery Point Objective)** | < 1 hour (< 5 minutes with WAL shipping) |
| **RTO (Recovery Time Objective)** | < 4 hours (< 1 hour with hot standby) |
| **Annual DR Test** | At least 1 full DR drill per year |

---

## 7. Performance Targets

| Metric | Target |
|---|---|
| **Page Load Time** | < 2 seconds (first contentful paint) |
| **API Response Time (p95)** | < 300ms |
| **API Response Time (p99)** | < 1 second |
| **Database Query Time (p95)** | < 100ms |
| **Uptime SLA** | 99.9% (8.76 hours downtime/year max) |
| **Concurrent Users** | 1,000+ per application instance |
| **E-Invoice Processing** | < 5 seconds per invoice (ETA submission) |
| **Report Generation** | < 10 seconds for standard reports |
| **Search Response** | < 200ms for full-text search |
| **WebSocket Latency** | < 100ms for real-time notifications |

---

## 8. Browser & Device Support

### 8.1 Web Application

| Browser | Minimum Version |
|---|---|
| Google Chrome | 120+ |
| Mozilla Firefox | 120+ |
| Safari | 17+ |
| Microsoft Edge | 120+ |
| Samsung Internet | 24+ |

### 8.2 Mobile Application

| Platform | Minimum Version |
|---|---|
| iOS | 15.0+ |
| Android | API 24 (Android 7.0+) |

### 8.3 Responsive Breakpoints

| Breakpoint | Width | Target |
|---|---|---|
| Mobile | 320px - 639px | Smartphones |
| Tablet | 640px - 1023px | Tablets, small laptops |
| Desktop | 1024px - 1279px | Standard laptops |
| Large Desktop | 1280px+ | External monitors, wide screens |

---

## 9. Scalability Architecture

```
                    ┌──────────────┐
                    │  Cloudflare  │
                    │  CDN + WAF   │
                    └──────┬───────┘
                           │
                    ┌──────┴───────┐
                    │ Load Balancer│
                    │  (Nginx/ALB) │
                    └──────┬───────┘
                           │
              ┌────────────┼────────────┐
              │            │            │
        ┌─────┴─────┐ ┌───┴─────┐ ┌───┴─────┐
        │  App #1   │ │ App #2  │ │ App #N  │
        │  (Octane) │ │ (Octane)│ │ (Octane)│
        └─────┬─────┘ └───┬─────┘ └───┬─────┘
              │            │            │
              └────────────┼────────────┘
                           │
         ┌─────────────────┼─────────────────┐
         │                 │                 │
   ┌─────┴─────┐   ┌──────┴──────┐   ┌─────┴─────┐
   │ PostgreSQL │   │    Redis    │   │ Meilisearch│
   │  Primary   │   │  (Cluster)  │   │  (Search)  │
   │  + Replica │   │             │   │            │
   └───────────┘   └─────────────┘   └────────────┘
         │
   ┌─────┴─────┐
   │   S3 /    │
   │   MinIO   │
   │ (Storage) │
   └───────────┘
```

---

## 10. Monitoring & Alerting

### Alert Thresholds

| Metric | Warning | Critical |
|---|---|---|
| CPU Usage | > 70% for 5 min | > 90% for 2 min |
| Memory Usage | > 80% | > 95% |
| Disk Usage | > 75% | > 90% |
| DB Connections | > 80% of max | > 95% of max |
| API Error Rate | > 1% | > 5% |
| Response Time (p95) | > 500ms | > 2s |
| Queue Depth | > 1000 jobs | > 5000 jobs |
| Failed Jobs | > 10/hour | > 50/hour |

### Notification Channels

- Slack / Discord for team alerts
- PagerDuty / OpsGenie for on-call escalation
- Email for non-urgent notifications
- SMS for critical infrastructure alerts
