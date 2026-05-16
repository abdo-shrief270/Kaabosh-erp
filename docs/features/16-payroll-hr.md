# Payroll, Attendance, Leave — Egyptian Labor Law

> Employee master, monthly payroll runs, salary components, loans, attendance, leave requests, and Egyptian labor-law helpers (Law 12/2003 overtime, end-of-service, social insurance Law 148/2019).

## Access & Auth
- **Authentication:** Bearer (Sanctum).
- **Tenant scope:** yes.
- **Permissions:**
  - `manage_employees` — employee CRUD.
  - `manage_payroll` — payroll runs, salary components, loans, leave, attendance, labor-law, social insurance.
- **Feature flag:** `payroll`.
- **Rate limits:** none.

## Employees (`permission: manage_employees`)

### `GET /v1/employees`
**Query:** `department, status (active|inactive|terminated), search, per_page`.

### `POST /v1/employees`
```json
{
  "first_name": "string (required)",
  "last_name": "string (required)",
  "national_id": "string (optional, 14 digits)",
  "email": "string (optional)",
  "phone": "string (optional)",
  "hire_date": "YYYY-MM-DD (required)",
  "job_title": "string (required)",
  "department": "string (optional)",
  "cost_center_id": "int (optional)",
  "employment_type": "full_time | part_time | contractor | intern",
  "basic_salary": "number (required)",
  "currency": "string (optional, default EGP)",
  "bank_name": "string (optional)",
  "bank_account": "string (optional)",
  "social_insurance_number": "string (optional)",
  "tax_number": "string (optional)",
  "address": "string (optional)",
  "date_of_birth": "YYYY-MM-DD (optional)",
  "gender": "male | female (optional)",
  "marital_status": "single | married | divorced | widowed (optional)",
  "dependents": "number (optional)"
}
```

### `GET|PUT|DELETE /v1/employees/{employee}`

---

## Payroll Runs (`permission: manage_payroll`)

### `GET /v1/payroll`
List payroll runs.
**Query:** `status, year, month, per_page`.

### `POST /v1/payroll`
Create a new run.
```json
{
  "period_month": 4,
  "period_year": 2026,
  "date_from": "2026-04-01",
  "date_to": "2026-04-30",
  "employee_ids": "int[] (optional, omit = all active)",
  "notes": "string (optional)"
}
```

### `GET /v1/payroll/{payrollRun}`
Run summary + totals.

### `DELETE /v1/payroll/{payrollRun}`
Delete a draft run.

### `POST /v1/payroll/{payrollRun}/calculate`
Compute gross, deductions, taxes, SI, net per employee. Writes `payroll_items`.

### `POST /v1/payroll/{payrollRun}/approve`
Lock the run and post the payroll JE.

### `POST /v1/payroll/{payrollRun}/mark-paid`
Record the bank-run payment.
```json
{
  "payment_date": "YYYY-MM-DD",
  "bank_account_id": "int (required)",
  "reference": "string (optional)"
}
```

### `GET /v1/payroll/{payrollRun}/items`
Per-employee breakdown.

### `GET /v1/payroll/{payrollRun}/items/{payrollItem}/payslip`
PDF payslip download.

---

## Salary Components

### `GET /v1/salary-components`
Master list of salary components (allowances, deductions).

### `POST /v1/salary-components`
```json
{
  "name_en": "string",
  "name_ar": "string (required)",
  "type": "earning | deduction (required)",
  "calculation": "fixed | percent_of_basic | percent_of_gross | formula",
  "amount": "number (optional, for fixed)",
  "percent": "number (optional)",
  "formula": "string (optional) — expression",
  "is_taxable": "bool (default true)",
  "is_insurable": "bool (default true) — subject to social insurance",
  "account_id": "int (optional) — GL account",
  "frequency": "monthly | quarterly | annually (default monthly)"
}
```

### `PUT|DELETE /v1/salary-components/{salaryComponent}`

### `POST /v1/employees/{employee}/salary-components`
Assign a component to an employee with an override amount.
```json
{
  "salary_component_id": "int (required)",
  "amount": "number (optional, overrides default)",
  "start_date": "YYYY-MM-DD",
  "end_date": "YYYY-MM-DD (optional)"
}
```

### `GET /v1/employees/{employee}/salary-components`
Components assigned to an employee.

---

## Loans

### `GET /v1/loans`
**Query:** `employee_id, status (active|paid|cancelled), per_page`.

### `POST /v1/loans`
```json
{
  "employee_id": "int (required)",
  "amount": "number (required)",
  "interest_rate": "number (optional, default 0)",
  "start_date": "YYYY-MM-DD (required)",
  "installments": "number (required) — count of periodic deductions",
  "frequency": "monthly (default)",
  "notes": "string (optional)"
}
```

### `POST /v1/loans/{loan}/installment`
Record a specific installment payment.
```json
{ "amount": "number", "payment_date": "YYYY-MM-DD" }
```

### `POST /v1/loans/{loan}/cancel`
```json
{ "reason": "string" }
```

---

## Leave

### `GET /v1/leave-types`
List configured leave types (annual, sick, casual, unpaid, maternity, hajj, etc.).

### `POST /v1/leave-types`
```json
{
  "name_ar": "string (required)",
  "name_en": "string (optional)",
  "days_per_year": 21,
  "paid": true,
  "requires_approval": true,
  "accrual_method": "annual | monthly | none"
}
```

### `GET /v1/leave-requests`
**Query:** `employee_id, status (pending|approved|rejected|cancelled), from_date, to_date`.

### `POST /v1/leave-requests`
```json
{
  "employee_id": "int (required)",
  "leave_type_id": "int (required)",
  "from_date": "YYYY-MM-DD (required)",
  "to_date": "YYYY-MM-DD (required)",
  "days": "number (required)",
  "reason": "string (optional)",
  "attachments": ["document_id (optional)"]
}
```

### `POST /v1/leave-requests/{leaveRequest}/approve`
### `POST /v1/leave-requests/{leaveRequest}/reject`
```json
{ "reason": "string (required)" }
```
### `POST /v1/leave-requests/{leaveRequest}/cancel`

### `GET /v1/employees/{employee}/leave-balance`
```json
{ "data": { "annual": { "entitled": 21, "used": 5, "pending": 2, "available": 14 }, "sick": { ... } } }
```

---

## Attendance

### `GET /v1/attendance`
Per-employee per-day records.
**Query:** `employee_id, from_date, to_date, department, per_page`.

### `POST /v1/attendance`
```json
{
  "employee_id": "int (required)",
  "date": "YYYY-MM-DD (required)",
  "check_in": "HH:MM (optional)",
  "check_out": "HH:MM (optional)",
  "status": "present | absent | leave | holiday",
  "overtime_hours": "number (optional)",
  "notes": "string (optional)"
}
```

### `POST /v1/attendance/bulk`
Upload many at once (CSV import or JSON array).

### `GET /v1/employees/{employee}/attendance-summary`
**Query:** `from_date, to_date`. Returns totals, late days, overtime hours.

---

## Labor Law Helpers (Egyptian Law 12/2003)

### `POST /v1/labor-law/overtime`
Compute overtime pay per Article 85.
```json
{
  "employee_id": "int",
  "hours": 5,
  "wage_rate": "number (optional)",
  "overtime_type": "regular | holiday | night"
}
```

### `POST /v1/labor-law/end-of-service`
End-of-service gratuity (Articles 122/123) given termination date.
```json
{ "employee_id": "int", "termination_date": "YYYY-MM-DD", "reason": "string" }
```

### `GET /v1/labor-law/leave-entitlement/{employee}`
Statutory leave entitlement based on years of service.

### `POST /v1/labor-law/validate-wage`
Check a proposed wage against the national minimum wage.
```json
{ "amount": 6000, "date": "2026-04-18" }
```

### `POST /v1/labor-law/social-insurance`
Preview social insurance computation for given wage.
```json
{ "employee_id": "int", "gross_wage": 15000 }
```

---

## Social Insurance (Law 148/2019)

### `POST /v1/social-insurance/calculate`
Compute employer + employee SI for a period.
```json
{ "period_month": 4, "period_year": 2026, "employee_ids": "int[] (optional)" }
```

### `GET /v1/social-insurance/monthly-report`
Monthly form ready for submission.
**Query:** `period_month, period_year, format (json|pdf)`.

### `POST /v1/social-insurance/register`
Register an employee with the SI authority.
```json
{ "employee_id": "int" }
```

### `GET /v1/social-insurance/rates`
Current Egyptian SI brackets and rates.

## Notes
- The payroll engine is Egypt-specific: progressive income tax brackets, minimum wage, social insurance ceilings/floors per Law 148/2019.
- Leave balances accrue monthly by default; statutory minimum is 21 days/year after 10 years of service.
- End-of-service: ½ month per year for first 5 years, 1 month per year thereafter (configurable override).
- Payslips are bilingual (Arabic + English) and include official SI reference numbers.
