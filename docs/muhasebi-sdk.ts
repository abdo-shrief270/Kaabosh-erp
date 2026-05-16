/**
 * Muhasebi API TypeScript SDK
 * Auto-generated from OpenAPI specification.
 * Do not edit manually — regenerate with: php artisan api:typescript-sdk
 *
 * Usage:
 *   const api = new MuhasebiApi('https://api.muhasebi.com/api/v1', 'your-token');
 *   const invoices = await api.invoices.list({ page: 1 });
 */

export interface ApiConfig {
  baseUrl: string;
  token?: string;
  tenantId?: string;
  locale?: 'ar' | 'en';
}

export interface PaginatedResponse<T> {
  data: T[];
  meta: { current_page: number; last_page: number; total: number };
}

async function request<T>(
  config: ApiConfig,
  method: string,
  path: string,
  body?: any,
  params?: Record<string, string>,
): Promise<T> {
  const url = new URL(config.baseUrl + path);
  if (params) {
    Object.entries(params).forEach(([k, v]) => { if (v) url.searchParams.set(k, v); });
  }

  const headers: Record<string, string> = {
    'Accept': 'application/json',
    'Content-Type': 'application/json',
    'Accept-Language': config.locale || 'ar',
  };
  if (config.token) headers['Authorization'] = `Bearer ${config.token}`;
  if (config.tenantId) headers['X-Tenant'] = config.tenantId;

  const res = await fetch(url.toString(), {
    method,
    headers,
    body: body ? JSON.stringify(body) : undefined,
  });

  if (!res.ok) {
    const error = await res.json().catch(() => ({ message: res.statusText }));
    throw new ApiError(res.status, error.message || res.statusText, error.errors);
  }

  return res.json();
}

export class ApiError extends Error {
  constructor(
    public status: number,
    message: string,
    public errors?: Record<string, string[]>,
  ) {
    super(message);
    this.name = 'ApiError';
  }
}

export class MuhasebiApi {
  private config: ApiConfig;

  constructor(baseUrl: string, token?: string, tenantId?: string, locale?: 'ar' | 'en') {
    this.config = { baseUrl, token, tenantId, locale };
  }

  setToken(token: string) { this.config.token = token; }
  setTenant(tenantId: string) { this.config.tenantId = tenantId; }

  /** 2fa */
  2fa = {
    /** Disable */
    disable: (body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//2fa/disable`, body, params),

    /** Enable */
    enable: (body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//2fa/enable`, body, params),

    /** Status */
    status: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//2fa/status`, params),

    /** Verify */
    verify: (body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//2fa/verify`, body, params),

  };

  /** accounts */
  accounts = {
    /** Index */
    index: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//accounts`, params),

    /** Store */
    store: (body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//accounts`, body, params),

    /** Tree */
    tree: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//accounts/tree`, params),

    /** Show */
    show: (account: string | number, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//accounts/${account}`, params),

    /** Update */
    update: (account: string | number, body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'PUT', `//accounts/${account}`, body, params),

    /** Update */
    update: (account: string | number, body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'PATCH', `//accounts/${account}`, body, params),

    /** Destroy */
    destroy: (account: string | number, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'DELETE', `//accounts/${account}`, params),

  };

  /** adminActivityLog */
  adminActivityLog = {
    /** Index */
    index: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//admin/activity-log`, params),

  };

  /** adminApiLogs */
  adminApiLogs = {
    /** Index */
    index: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//admin/api-logs`, params),

    /** Stats */
    stats: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//admin/api-logs/stats`, params),

  };

  /** adminAuditLogs */
  adminAuditLogs = {
    /** Index */
    index: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//admin/audit-logs`, params),

    /** Stats */
    stats: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//admin/audit-logs/stats`, params),

  };

  /** adminBatch */
  adminBatch = {
    /** Delete */
    delete: (body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//admin/batch/blog/delete`, body, params),

    /** Toggle Publish */
    togglePublish: (body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//admin/batch/blog/toggle-publish`, body, params),

    /** Delete */
    delete: (body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//admin/batch/contacts/delete`, body, params),

    /** Mark Read */
    markRead: (body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//admin/batch/contacts/mark-read`, body, params),

    /** Update Status */
    updateStatus: (body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//admin/batch/contacts/update-status`, body, params),

    /** Update Status */
    updateStatus: (body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//admin/batch/tenants/update-status`, body, params),

    /** Delete */
    delete: (body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//admin/batch/users/delete`, body, params),

    /** Toggle Active */
    toggleActive: (body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//admin/batch/users/toggle-active`, body, params),

  };

  /** adminBlog */
  adminBlog = {
    /** Index */
    index: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//admin/blog/categories`, params),

    /** Store */
    store: (body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//admin/blog/categories`, body, params),

    /** Update */
    update: (category: string | number, body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'PUT', `//admin/blog/categories/${category}`, body, params),

    /** Destroy */
    destroy: (category: string | number, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'DELETE', `//admin/blog/categories/${category}`, params),

    /** Index */
    index: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//admin/blog/posts`, params),

    /** Store */
    store: (body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//admin/blog/posts`, body, params),

    /** Show */
    show: (post: string | number, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//admin/blog/posts/${post}`, params),

    /** Update */
    update: (post: string | number, body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'PUT', `//admin/blog/posts/${post}`, body, params),

    /** Destroy */
    destroy: (post: string | number, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'DELETE', `//admin/blog/posts/${post}`, params),

    /** Blog Cover */
    blogCover: (post: string | number, body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//admin/blog/posts/${post}/cover`, body, params),

    /** Index */
    index: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//admin/blog/tags`, params),

    /** Store */
    store: (body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//admin/blog/tags`, body, params),

    /** Destroy */
    destroy: (tag: string | number, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'DELETE', `//admin/blog/tags/${tag}`, params),

  };

  /** adminCms */
  adminCms = {
    /** Analytics */
    analytics: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//admin/cms/analytics`, params),

  };

  /** adminContacts */
  adminContacts = {
    /** Index */
    index: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//admin/contacts`, params),

    /** Show */
    show: (contactSubmission: string | number, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//admin/contacts/${contactSubmission}`, params),

    /** Update */
    update: (contactSubmission: string | number, body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'PUT', `//admin/contacts/${contactSubmission}`, body, params),

    /** Destroy */
    destroy: (contactSubmission: string | number, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'DELETE', `//admin/contacts/${contactSubmission}`, params),

  };

  /** adminCurrencies */
  adminCurrencies = {
    /** Store */
    store: (body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//admin/currencies`, body, params),

    /** Set Rate */
    setRate: (body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//admin/currencies/set-rate`, body, params),

  };

  /** adminDashboard */
  adminDashboard = {
    /** Dashboard */
    dashboard: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//admin/dashboard`, params),

  };

  /** adminDistributions */
  adminDistributions = {
    /** Index */
    index: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//admin/distributions`, params),

    /** Calculate */
    calculate: (body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//admin/distributions/calculate`, body, params),

    /** Show */
    show: (distribution: string | number, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//admin/distributions/${distribution}`, params),

    /** Destroy */
    destroy: (distribution: string | number, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'DELETE', `//admin/distributions/${distribution}`, params),

    /** Approve */
    approve: (distribution: string | number, body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//admin/distributions/${distribution}/approve`, body, params),

    /** Mark Paid */
    markPaid: (distribution: string | number, body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//admin/distributions/${distribution}/mark-paid`, body, params),

  };

  /** adminEmailTemplates */
  adminEmailTemplates = {
    /** Index */
    index: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//admin/email-templates`, params),

    /** Store */
    store: (body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//admin/email-templates`, body, params),

    /** Show */
    show: (emailTemplate: string | number, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//admin/email-templates/${emailTemplate}`, params),

    /** Update */
    update: (emailTemplate: string | number, body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'PUT', `//admin/email-templates/${emailTemplate}`, body, params),

    /** Preview */
    preview: (emailTemplate: string | number, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//admin/email-templates/${emailTemplate}/preview`, params),

  };

  /** adminFaqs */
  adminFaqs = {
    /** Index */
    index: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//admin/faqs`, params),

    /** Store */
    store: (body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//admin/faqs`, body, params),

    /** Update */
    update: (faq: string | number, body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'PUT', `//admin/faqs/${faq}`, body, params),

    /** Destroy */
    destroy: (faq: string | number, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'DELETE', `//admin/faqs/${faq}`, params),

  };

  /** adminFeatureFlags */
  adminFeatureFlags = {
    /** Index */
    index: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//admin/feature-flags`, params),

    /** Store */
    store: (body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//admin/feature-flags`, body, params),

    /** Check */
    check: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//admin/feature-flags/check`, params),

    /** Update */
    update: (featureFlag: string | number, body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'PUT', `//admin/feature-flags/${featureFlag}`, body, params),

    /** Destroy */
    destroy: (featureFlag: string | number, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'DELETE', `//admin/feature-flags/${featureFlag}`, params),

  };

  /** adminIntegrations */
  adminIntegrations = {
    /** Index */
    index: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//admin/integrations`, params),

    /** Upsert */
    upsert: (body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//admin/integrations`, body, params),

    /** Show */
    show: (integrationSetting: string | number, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//admin/integrations/${integrationSetting}`, params),

    /** Toggle */
    toggle: (integrationSetting: string | number, body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//admin/integrations/${integrationSetting}/toggle`, body, params),

    /** Verify */
    verify: (integrationSetting: string | number, body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//admin/integrations/${integrationSetting}/verify`, body, params),

  };

  /** adminInvestors */
  adminInvestors = {
    /** Index */
    index: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//admin/investors`, params),

    /** Store */
    store: (body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//admin/investors`, body, params),

    /** Show */
    show: (investor: string | number, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//admin/investors/${investor}`, params),

    /** Update */
    update: (investor: string | number, body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'PUT', `//admin/investors/${investor}`, body, params),

    /** Update */
    update: (investor: string | number, body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'PATCH', `//admin/investors/${investor}`, body, params),

    /** Destroy */
    destroy: (investor: string | number, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'DELETE', `//admin/investors/${investor}`, params),

    /** Payslip */
    payslip: (investor: string | number, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//admin/investors/${investor}/payslip`, params),

    /** Shares */
    shares: (investor: string | number, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//admin/investors/${investor}/shares`, params),

    /** Set Share */
    setShare: (investor: string | number, body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//admin/investors/${investor}/shares`, body, params),

    /** Remove Share */
    removeShare: (investor: string | number, tenant: string | number, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'DELETE', `//admin/investors/${investor}/shares/${tenant}`, params),

  };

  /** adminLanding */
  adminLanding = {
    /** Landing */
    landing: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//admin/landing`, params),

    /** Update */
    update: (body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'PUT', `//admin/landing`, body, params),

  };

  /** adminMedia */
  adminMedia = {
    /** Destroy */
    destroy: (mediaId: string | number, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'DELETE', `//admin/media/${mediaId}`, params),

  };

  /** adminMetrics */
  adminMetrics = {
    /** Index */
    index: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//admin/metrics`, params),

    /** Reset Circuit */
    resetCircuit: (body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//admin/metrics/reset-circuit-breaker`, body, params),

  };

  /** adminPages */
  adminPages = {
    /** Index */
    index: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//admin/pages`, params),

    /** Store */
    store: (body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//admin/pages`, body, params),

    /** Show */
    show: (page: string | number, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//admin/pages/${page}`, params),

    /** Update */
    update: (page: string | number, body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'PUT', `//admin/pages/${page}`, body, params),

    /** Destroy */
    destroy: (page: string | number, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'DELETE', `//admin/pages/${page}`, params),

  };

  /** adminPermissions */
  adminPermissions = {
    /** Index */
    index: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//admin/permissions`, params),

  };

  /** adminPlans */
  adminPlans = {
    /** Store */
    store: (body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//admin/plans`, body, params),

    /** Update */
    update: (plan: string | number, body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'PUT', `//admin/plans/${plan}`, body, params),

    /** Update */
    update: (plan: string | number, body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'PATCH', `//admin/plans/${plan}`, body, params),

    /** Destroy */
    destroy: (plan: string | number, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'DELETE', `//admin/plans/${plan}`, params),

  };

  /** adminRevenue */
  adminRevenue = {
    /** By Plan */
    byPlan: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//admin/revenue/by-plan`, params),

    /** Monthly */
    monthly: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//admin/revenue/monthly`, params),

  };

  /** adminRoles */
  adminRoles = {
    /** Index */
    index: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//admin/roles`, params),

    /** Store */
    store: (body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//admin/roles`, body, params),

    /** Show */
    show: (role: string | number, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//admin/roles/${role}`, params),

    /** Update */
    update: (role: string | number, body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'PUT', `//admin/roles/${role}`, body, params),

    /** Destroy */
    destroy: (role: string | number, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'DELETE', `//admin/roles/${role}`, params),

  };

  /** adminSettings */
  adminSettings = {
    /** Index */
    index: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//admin/settings`, params),

    /** Update */
    update: (body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'PUT', `//admin/settings`, body, params),

  };

  /** adminSubscriptions */
  adminSubscriptions = {
    /** Index */
    index: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//admin/subscriptions`, params),

    /** Assign */
    assign: (body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//admin/subscriptions/assign`, body, params),

    /** Refund */
    refund: (payment: string | number, body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//admin/subscriptions/payments/${payment}/refund`, body, params),

    /** Show */
    show: (subscription: string | number, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//admin/subscriptions/${subscription}`, params),

    /** Update */
    update: (subscription: string | number, body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'PUT', `//admin/subscriptions/${subscription}`, body, params),

  };

  /** adminTenants */
  adminTenants = {
    /** Index */
    index: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//admin/tenants`, params),

    /** Activity */
    activity: (tenantId: string | number, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//admin/tenants/${tenantId}/activity`, params),

    /** Show */
    show: (tenant: string | number, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//admin/tenants/${tenant}`, params),

    /** Update */
    update: (tenant: string | number, body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'PUT', `//admin/tenants/${tenant}`, body, params),

    /** Activate */
    activate: (tenant: string | number, body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//admin/tenants/${tenant}/activate`, body, params),

    /** Cancel */
    cancel: (tenant: string | number, body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//admin/tenants/${tenant}/cancel`, body, params),

    /** Export */
    export: (tenant: string | number, body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//admin/tenants/${tenant}/export`, body, params),

    /** Impersonate */
    impersonate: (tenant: string | number, body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//admin/tenants/${tenant}/impersonate`, body, params),

    /** Suspend */
    suspend: (tenant: string | number, body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//admin/tenants/${tenant}/suspend`, body, params),

  };

  /** adminTestimonials */
  adminTestimonials = {
    /** Index */
    index: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//admin/testimonials`, params),

    /** Store */
    store: (body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//admin/testimonials`, body, params),

    /** Update */
    update: (testimonial: string | number, body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'PUT', `//admin/testimonials/${testimonial}`, body, params),

    /** Destroy */
    destroy: (testimonial: string | number, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'DELETE', `//admin/testimonials/${testimonial}`, params),

  };

  /** adminUpload */
  adminUpload = {
    /** Editor Image */
    editorImage: (body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//admin/upload/image`, body, params),

  };

  /** adminUsage */
  adminUsage = {
    /** Platform */
    platform: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//admin/usage/platform`, params),

    /** Tenant */
    tenant: (tenantId: string | number, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//admin/usage/tenants/${tenantId}`, params),

  };

  /** adminUsers */
  adminUsers = {
    /** Index */
    index: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//admin/users`, params),

    /** Create Super Admin */
    createSuperAdmin: (body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//admin/users/create-super-admin`, body, params),

    /** Toggle Active */
    toggleActive: (user: string | number, body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'PATCH', `//admin/users/${user}/toggle-active`, body, params),

  };

  /** blog */
  blog = {
    /** Index */
    index: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//blog`, params),

    /** Categories */
    categories: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//blog/categories`, params),

    /** Featured */
    featured: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//blog/featured`, params),

    /** Rss */
    rss: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//blog/rss`, params),

    /** Search */
    search: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//blog/search`, params),

    /** Tags */
    tags: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//blog/tags`, params),

    /** Show */
    show: (slug: string | number, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//blog/${slug}`, params),

  };

  /** changePassword */
  changePassword = {
    /** Change Password */
    changePassword: (body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//change-password`, body, params),

  };

  /** clients */
  clients = {
    /** Index */
    index: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//clients`, params),

    /** Store */
    store: (body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//clients`, body, params),

    /** Show */
    show: (client: string | number, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//clients/${client}`, params),

    /** Update */
    update: (client: string | number, body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'PUT', `//clients/${client}`, body, params),

    /** Update */
    update: (client: string | number, body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'PATCH', `//clients/${client}`, body, params),

    /** Destroy */
    destroy: (client: string | number, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'DELETE', `//clients/${client}`, params),

    /** Invite Portal */
    invitePortal: (client: string | number, body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//clients/${client}/invite-portal`, body, params),

    /** Messages */
    messages: (client: string | number, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//clients/${client}/messages`, params),

    /** Send Message */
    sendMessage: (client: string | number, body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//clients/${client}/messages`, body, params),

    /** Restore */
    restore: (client: string | number, body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//clients/${client}/restore`, body, params),

    /** Toggle Active */
    toggleActive: (client: string | number, body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'PATCH', `//clients/${client}/toggle-active`, body, params),

  };

  /** contact */
  contact = {
    /** Submit */
    submit: (body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//contact`, body, params),

  };

  /** currencies */
  currencies = {
    /** Index */
    index: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//currencies`, params),

    /** Convert */
    convert: (body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//currencies/convert`, body, params),

    /** Rate History */
    rateHistory: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//currencies/rate-history`, params),

  };

  /** dashboard */
  dashboard = {
    /** Dashboard */
    dashboard: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//dashboard`, params),

  };

  /** docs */
  docs = {
    /** Ui */
    ui: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//docs`, params),

    /** Spec */
    spec: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//docs/spec`, params),

  };

  /** documents */
  documents = {
    /** Index */
    index: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//documents`, params),

    /** Store */
    store: (body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//documents`, body, params),

    /** Bulk */
    bulk: (body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//documents/bulk`, body, params),

    /** Quota */
    quota: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//documents/quota`, params),

    /** Show */
    show: (document: string | number, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//documents/${document}`, params),

    /** Update */
    update: (document: string | number, body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'PUT', `//documents/${document}`, body, params),

    /** Update */
    update: (document: string | number, body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'PATCH', `//documents/${document}`, body, params),

    /** Destroy */
    destroy: (document: string | number, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'DELETE', `//documents/${document}`, params),

    /** Archive */
    archive: (document: string | number, body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//documents/${document}/archive`, body, params),

    /** Download */
    download: (document: string | number, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//documents/${document}/download`, params),

    /** Unarchive */
    unarchive: (document: string | number, body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//documents/${document}/unarchive`, body, params),

  };

  /** employees */
  employees = {
    /** Index */
    index: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//employees`, params),

    /** Store */
    store: (body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//employees`, body, params),

    /** Show */
    show: (employee: string | number, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//employees/${employee}`, params),

    /** Update */
    update: (employee: string | number, body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'PUT', `//employees/${employee}`, body, params),

    /** Destroy */
    destroy: (employee: string | number, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'DELETE', `//employees/${employee}`, params),

  };

  /** eta */
  eta = {
    /** Index */
    index: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//eta/documents`, params),

    /** Show */
    show: (invoice: string | number, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//eta/documents/${invoice}`, params),

    /** Cancel */
    cancel: (invoice: string | number, body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//eta/documents/${invoice}/cancel`, body, params),

    /** Check Status */
    checkStatus: (invoice: string | number, body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//eta/documents/${invoice}/check-status`, body, params),

    /** Prepare */
    prepare: (invoice: string | number, body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//eta/documents/${invoice}/prepare`, body, params),

    /** Submit */
    submit: (invoice: string | number, body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//eta/documents/${invoice}/submit`, body, params),

    /** Index */
    index: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//eta/item-codes`, params),

    /** Store */
    store: (body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//eta/item-codes`, body, params),

    /** Show */
    show: (item_code: string | number, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//eta/item-codes/${item_code}`, params),

    /** Update */
    update: (item_code: string | number, body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'PUT', `//eta/item-codes/${item_code}`, body, params),

    /** Update */
    update: (item_code: string | number, body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'PATCH', `//eta/item-codes/${item_code}`, body, params),

    /** Destroy */
    destroy: (item_code: string | number, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'DELETE', `//eta/item-codes/${item_code}`, params),

    /** Reconcile */
    reconcile: (body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//eta/reconcile`, body, params),

    /** Show */
    show: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//eta/settings`, params),

    /** Update */
    update: (body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'PUT', `//eta/settings`, body, params),

  };

  /** export */
  export = {
    /** Clients */
    clients: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//export/clients`, params),

    /** Invoices */
    invoices: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//export/invoices`, params),

    /** Journal Entries */
    journalEntries: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//export/journal-entries`, params),

  };

  /** fiscalPeriods */
  fiscalPeriods = {
    /** Close */
    close: (fiscalPeriod: string | number, body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//fiscal-periods/${fiscalPeriod}/close`, body, params),

    /** Reopen */
    reopen: (fiscalPeriod: string | number, body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//fiscal-periods/${fiscalPeriod}/reopen`, body, params),

  };

  /** fiscalYears */
  fiscalYears = {
    /** Index */
    index: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//fiscal-years`, params),

    /** Store */
    store: (body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//fiscal-years`, body, params),

    /** Show */
    show: (fiscal_year: string | number, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//fiscal-years/${fiscal_year}`, params),

  };

  /** health */
  health = {
    /** Health */
    health: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//health`, params),

  };

  /** import */
  import = {
    /** Index */
    index: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//import`, params),

    /** Store */
    store: (body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//import`, body, params),

    /** Accounts */
    accounts: (body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//import/accounts`, body, params),

    /** Clients */
    clients: (body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//import/clients`, body, params),

    /** Opening Balances */
    openingBalances: (body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//import/opening-balances`, body, params),

    /** Template */
    template: (type: string | number, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//import/template/${type}`, params),

    /** Show */
    show: (importJob: string | number, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//import/${importJob}`, params),

  };

  /** invoiceSettings */
  invoiceSettings = {
    /** Show */
    show: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//invoice-settings`, params),

    /** Update */
    update: (body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'PUT', `//invoice-settings`, body, params),

  };

  /** invoices */
  invoices = {
    /** Index */
    index: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//invoices`, params),

    /** Store */
    store: (body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//invoices`, body, params),

    /** Show */
    show: (invoice: string | number, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//invoices/${invoice}`, params),

    /** Update */
    update: (invoice: string | number, body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'PUT', `//invoices/${invoice}`, body, params),

    /** Update */
    update: (invoice: string | number, body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'PATCH', `//invoices/${invoice}`, body, params),

    /** Destroy */
    destroy: (invoice: string | number, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'DELETE', `//invoices/${invoice}`, params),

    /** Cancel */
    cancel: (invoice: string | number, body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//invoices/${invoice}/cancel`, body, params),

    /** Credit Note */
    creditNote: (invoice: string | number, body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//invoices/${invoice}/credit-note`, body, params),

    /** Pdf */
    pdf: (invoice: string | number, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//invoices/${invoice}/pdf`, params),

    /** Post To Gl */
    postToGl: (invoice: string | number, body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//invoices/${invoice}/post-to-gl`, body, params),

    /** Send */
    send: (invoice: string | number, body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//invoices/${invoice}/send`, body, params),

  };

  /** journalEntries */
  journalEntries = {
    /** Index */
    index: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//journal-entries`, params),

    /** Store */
    store: (body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//journal-entries`, body, params),

    /** Post */
    post: (journalEntry: string | number, body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//journal-entries/${journalEntry}/post`, body, params),

    /** Reverse */
    reverse: (journalEntry: string | number, body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//journal-entries/${journalEntry}/reverse`, body, params),

    /** Show */
    show: (journal_entry: string | number, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//journal-entries/${journal_entry}`, params),

    /** Update */
    update: (journal_entry: string | number, body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'PUT', `//journal-entries/${journal_entry}`, body, params),

    /** Update */
    update: (journal_entry: string | number, body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'PATCH', `//journal-entries/${journal_entry}`, body, params),

    /** Destroy */
    destroy: (journal_entry: string | number, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'DELETE', `//journal-entries/${journal_entry}`, params),

  };

  /** landing */
  landing = {
    /** Index */
    index: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//landing`, params),

  };

  /** landingPageSettings */
  landingPageSettings = {
    /** Show */
    show: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//landing-page-settings`, params),

    /** Update */
    update: (body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'PUT', `//landing-page-settings`, body, params),

  };

  /** login */
  login = {
    /** Login */
    login: (body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//login`, body, params),

  };

  /** logout */
  logout = {
    /** Logout */
    logout: (body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//logout`, body, params),

  };

  /** me */
  me = {
    /** Me */
    me: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//me`, params),

  };

  /** messaging */
  messaging = {
    /** Conversations */
    conversations: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//messaging/conversations`, params),

    /** Messages */
    messages: (conversationId: string | number, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//messaging/conversations/${conversationId}`, params),

    /** Reply */
    reply: (conversationId: string | number, body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//messaging/conversations/${conversationId}/reply`, body, params),

    /** Sms */
    sms: (body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//messaging/sms`, body, params),

    /** Templates */
    templates: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//messaging/templates`, params),

    /** Whatsapp */
    whatsapp: (body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//messaging/whatsapp`, body, params),

  };

  /** notificationPreferences */
  notificationPreferences = {
    /** Index */
    index: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//notification-preferences`, params),

    /** Update */
    update: (body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'PUT', `//notification-preferences`, body, params),

  };

  /** notifications */
  notifications = {
    /** Index */
    index: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//notifications`, params),

    /** Read All */
    readAll: (body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//notifications/read-all`, body, params),

    /** Unread Count */
    unreadCount: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//notifications/unread-count`, params),

    /** Destroy */
    destroy: (notification: string | number, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'DELETE', `//notifications/${notification}`, params),

    /** Read */
    read: (notification: string | number, body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//notifications/${notification}/read`, body, params),

  };

  /** onboarding */
  onboarding = {
    /** Complete Step */
    completeStep: (body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//onboarding/complete-step`, body, params),

    /** Invite Team Member */
    inviteTeamMember: (body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//onboarding/invite-team-member`, body, params),

    /** Load Sample Data */
    loadSampleData: (body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//onboarding/load-sample-data`, body, params),

    /** Progress */
    progress: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//onboarding/progress`, params),

    /** Setup Coa */
    setupCoa: (body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//onboarding/setup-coa`, body, params),

    /** Setup Fiscal Year */
    setupFiscalYear: (body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//onboarding/setup-fiscal-year`, body, params),

    /** Skip */
    skip: (body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//onboarding/skip`, body, params),

  };

  /** pages */
  pages = {
    /** Show */
    show: (slug: string | number, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//pages/${slug}`, params),

  };

  /** payments */
  payments = {
    /** Index */
    index: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//payments`, params),

    /** Store */
    store: (body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//payments`, body, params),

    /** Destroy */
    destroy: (payment: string | number, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'DELETE', `//payments/${payment}`, params),

  };

  /** payroll */
  payroll = {
    /** Index */
    index: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//payroll`, params),

    /** Store */
    store: (body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//payroll`, body, params),

    /** Show */
    show: (payrollRun: string | number, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//payroll/${payrollRun}`, params),

    /** Destroy */
    destroy: (payrollRun: string | number, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'DELETE', `//payroll/${payrollRun}`, params),

    /** Approve */
    approve: (payrollRun: string | number, body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//payroll/${payrollRun}/approve`, body, params),

    /** Calculate */
    calculate: (payrollRun: string | number, body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//payroll/${payrollRun}/calculate`, body, params),

    /** Items */
    items: (payrollRun: string | number, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//payroll/${payrollRun}/items`, params),

    /** Payslip */
    payslip: (payrollRun: string | number, payrollItem: string | number, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//payroll/${payrollRun}/items/${payrollItem}/payslip`, params),

    /** Mark Paid */
    markPaid: (payrollRun: string | number, body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//payroll/${payrollRun}/mark-paid`, body, params),

  };

  /** plans */
  plans = {
    /** Index */
    index: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//plans`, params),

    /** Show */
    show: (plan: string | number, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//plans/${plan}`, params),

  };

  /** portalDashboard */
  portalDashboard = {
    /** Dashboard */
    dashboard: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//portal/dashboard`, params),

  };

  /** portalDocuments */
  portalDocuments = {
    /** Index */
    index: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//portal/documents`, params),

    /** Store */
    store: (body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//portal/documents`, body, params),

    /** Download */
    download: (document: string | number, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//portal/documents/${document}/download`, params),

  };

  /** portalInvoices */
  portalInvoices = {
    /** Index */
    index: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//portal/invoices`, params),

    /** Show */
    show: (invoice: string | number, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//portal/invoices/${invoice}`, params),

    /** Pay */
    pay: (invoice: string | number, body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//portal/invoices/${invoice}/pay`, body, params),

    /** Pdf */
    pdf: (invoice: string | number, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//portal/invoices/${invoice}/pdf`, params),

  };

  /** portalMessages */
  portalMessages = {
    /** Index */
    index: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//portal/messages`, params),

    /** Store */
    store: (body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//portal/messages`, body, params),

    /** Show */
    show: (message: string | number, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//portal/messages/${message}`, params),

    /** Read */
    read: (message: string | number, body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//portal/messages/${message}/read`, body, params),

  };

  /** portalNotifications */
  portalNotifications = {
    /** Index */
    index: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//portal/notifications`, params),

    /** Read All */
    readAll: (body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//portal/notifications/read-all`, body, params),

    /** Read */
    read: (notification: string | number, body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//portal/notifications/${notification}/read`, body, params),

  };

  /** portalProfile */
  portalProfile = {
    /** Profile */
    profile: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//portal/profile`, params),

  };

  /** profile */
  profile = {
    /** Update Profile */
    updateProfile: (body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'PUT', `//profile`, body, params),

  };

  /** recurringInvoices */
  recurringInvoices = {
    /** Index */
    index: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//recurring-invoices`, params),

    /** Store */
    store: (body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//recurring-invoices`, body, params),

    /** Show */
    show: (recurring_invoice: string | number, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//recurring-invoices/${recurring_invoice}`, params),

    /** Update */
    update: (recurring_invoice: string | number, body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'PUT', `//recurring-invoices/${recurring_invoice}`, body, params),

    /** Update */
    update: (recurring_invoice: string | number, body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'PATCH', `//recurring-invoices/${recurring_invoice}`, body, params),

    /** Destroy */
    destroy: (recurring_invoice: string | number, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'DELETE', `//recurring-invoices/${recurring_invoice}`, params),

  };

  /** register */
  register = {
    /** Register */
    register: (body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//register`, body, params),

  };

  /** reports */
  reports = {
    /** Account Ledger */
    accountLedger: (account: string | number, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//reports/accounts/${account}/ledger`, params),

    /** Aging */
    aging: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//reports/aging`, params),

    /** Balance Sheet */
    balanceSheet: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//reports/balance-sheet`, params),

    /** Balance Sheet Pdf */
    balanceSheetPdf: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//reports/balance-sheet/pdf`, params),

    /** Cash Flow */
    cashFlow: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//reports/cash-flow`, params),

    /** Cash Flow Pdf */
    cashFlowPdf: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//reports/cash-flow/pdf`, params),

    /** Client Statement */
    clientStatement: (client: string | number, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//reports/clients/${client}/statement`, params),

    /** Comparative Balance Sheet */
    comparativeBalanceSheet: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//reports/comparative/balance-sheet`, params),

    /** Comparative Income Statement */
    comparativeIncomeStatement: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//reports/comparative/income-statement`, params),

    /** Income Statement */
    incomeStatement: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//reports/income-statement`, params),

    /** Income Statement Pdf */
    incomeStatementPdf: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//reports/income-statement/pdf`, params),

    /** Trial Balance */
    trialBalance: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//reports/trial-balance`, params),

    /** Trial Balance Pdf */
    trialBalancePdf: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//reports/trial-balance/pdf`, params),

  };

  /** subscription */
  subscription = {
    /** Show */
    show: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//subscription`, params),

    /** Cancel */
    cancel: (body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//subscription/cancel`, body, params),

    /** Change Plan */
    changePlan: (body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//subscription/change-plan`, body, params),

    /** Payments */
    payments: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//subscription/payments`, params),

    /** Renew */
    renew: (body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//subscription/renew`, body, params),

    /** Subscribe */
    subscribe: (body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//subscription/subscribe`, body, params),

    /** Usage */
    usage: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//subscription/usage`, params),

    /** Usage History */
    usageHistory: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//subscription/usage-history`, params),

  };

  /** team */
  team = {
    /** Index */
    index: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//team`, params),

    /** Invite */
    invite: (body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//team/invite`, body, params),

    /** Update */
    update: (user: string | number, body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'PUT', `//team/${user}`, body, params),

    /** Destroy */
    destroy: (user: string | number, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'DELETE', `//team/${user}`, params),

    /** Assign Role */
    assignRole: (user: string | number, body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'PUT', `//team/${user}/role`, body, params),

    /** Toggle Active */
    toggleActive: (user: string | number, body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'PATCH', `//team/${user}/toggle-active`, body, params),

  };

  /** timeBilling */
  timeBilling = {
    /** Generate */
    generate: (body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//time-billing/generate`, body, params),

    /** Preview */
    preview: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//time-billing/preview`, params),

  };

  /** timers */
  timers = {
    /** Current */
    current: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//timers/current`, params),

    /** Start */
    start: (body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//timers/start`, body, params),

    /** Discard */
    discard: (timer: string | number, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'DELETE', `//timers/${timer}`, params),

    /** Stop */
    stop: (timer: string | number, body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//timers/${timer}/stop`, body, params),

  };

  /** timesheets */
  timesheets = {
    /** Index */
    index: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//timesheets`, params),

    /** Store */
    store: (body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//timesheets`, body, params),

    /** Bulk Approve */
    bulkApprove: (body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//timesheets/bulk-approve`, body, params),

    /** Bulk Submit */
    bulkSubmit: (body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//timesheets/bulk-submit`, body, params),

    /** Summary */
    summary: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//timesheets/summary`, params),

    /** Show */
    show: (timesheet: string | number, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//timesheets/${timesheet}`, params),

    /** Update */
    update: (timesheet: string | number, body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'PUT', `//timesheets/${timesheet}`, body, params),

    /** Update */
    update: (timesheet: string | number, body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'PATCH', `//timesheets/${timesheet}`, body, params),

    /** Destroy */
    destroy: (timesheet: string | number, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'DELETE', `//timesheets/${timesheet}`, params),

    /** Approve */
    approve: (timesheet: string | number, body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//timesheets/${timesheet}/approve`, body, params),

    /** Reject */
    reject: (timesheet: string | number, body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//timesheets/${timesheet}/reject`, body, params),

    /** Submit */
    submit: (timesheet: string | number, body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//timesheets/${timesheet}/submit`, body, params),

  };

  /** webhooks */
  webhooks = {
    /** Index */
    index: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//webhooks`, params),

    /** Store */
    store: (body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//webhooks`, body, params),

    /** Beon Chat */
    beonChat: (body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//webhooks/beon-chat`, body, params),

    /** Events */
    events: (params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//webhooks/events`, params),

    /** Fawry */
    fawry: (body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//webhooks/fawry`, body, params),

    /** Paymob */
    paymob: (body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'POST', `//webhooks/paymob`, body, params),

    /** Update */
    update: (webhookEndpoint: string | number, body?: any, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'PUT', `//webhooks/${webhookEndpoint}`, body, params),

    /** Destroy */
    destroy: (webhookEndpoint: string | number, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'DELETE', `//webhooks/${webhookEndpoint}`, params),

    /** Deliveries */
    deliveries: (webhookEndpoint: string | number, params?: Record<string, string>): Promise<any> =>
      request(this.config, 'GET', `//webhooks/${webhookEndpoint}/deliveries`, params),

  };

}
