# Accounting Module — Reference

> **📋 Planned — nothing built.** This is the agreed scope for the Accounts module (owner,
> 2026-10-28). It describes *what* the module will do and *why*; where things will live and what they
> are called is [`ARCHITECTURE.md`](../ARCHITECTURE.md)'s job. When the first slice is built, this
> file becomes its reference and the planned parts shrink as they ship.
>
> **🧪 POC overview:** `/accounting` (`accounting.dashboard`, permission `accounting.dashboard.view`) shows sample data
> under a Preview banner. Its page body and `components/accounting/sample-data.ts` are throwaway; the route,
> permission and role stay ([`ARCHITECTURE.md` §5](../ARCHITECTURE.md#5-module-registry)).

---

## 1. Overview

Accounting keeps the **books of each company**: a double-entry general ledger, the vouchers that
feed it, and the statements read from it. It also owns **sales invoices**. It does **not** own the
customer list: the ERP's one customer list is **Projects → Clients**, kept per company (owner,
2026-11-02, moving it from here — Projects needs a client for every project before Accounting
exists). Invoices will point at those clients ([`projects.md` §4](projects.md#clients--built)).
Nor does it own the **supplier list**: the ERP's one supplier list is **Projects → Suppliers**, kept
per company and classified by group-wide supplier categories (owner, 2026-11-02). Phase 2's bills
will point at those suppliers and add their billing fields — currency, payment terms, payable account
([`projects.md` §4](projects.md#suppliers--built)).

| | |
| --- | --- |
| Menu label | Accounts |
| Namespace | `Accounting` |
| Route file | `routes/accounting.php` |
| Name / permission prefix | `accounting.` |
| URL prefix | `accounting/` |
| Pages | `pages/accounting/*` |

The code uses **Accounting**, not *Accounts*, because "account" already means a sign-in account
(Admin → Users, the Settings module) and, inside this module, a ledger account. The menu can still
say *Accounts* (owner, 2026-10-28).

**Every record belongs to a company**, and each company has its own chart of accounts, fiscal years
and ledger ([ARCHITECTURE §9.10](../ARCHITECTURE.md#910-multi-company)).

## 2. Scope

**In scope (phase 1)**

- The chart of accounts: groups and ledger accounts, by type (asset, liability, equity, income,
  expense).
- Fiscal years, defaulting to **July–June** (Bangladesh), with opening balances and year closing.
- Currencies: base currency **BDT**, plus foreign-currency vouchers (for example USD receipts from foreign customers) at a
  recorded exchange rate.
- Vouchers: **payment, receipt, journal, contra**, with approval.
- Ledgers, the trial balance, profit and loss, and the balance sheet.
- **Minimal receivables (AR)**: sales invoices to Projects' clients, and receipts against invoices.
- Draft vouchers that arrive from other modules ([§5](#5-workflows-and-approvals)).

**Later**

- Payables (bills and payments to Projects' suppliers), bank reconciliation, VAT and TDS (tax deducted at source)
  under Bangladesh rules, cost centres, budgets and cheque printing.

**Out of scope**

- Payroll calculation belongs to HRM, which only posts its result here. Project hours and billing
  requests belong to Projects.

## 3. Phases

Build order across the ERP is **Company (Admin) → Projects → Accounting → HRM** (owner, 2026-10-28).

| Phase | Delivers |
| --- | --- |
| **1 — ledger** | Chart of accounts, fiscal years, currencies and rates, vouchers with approval, ledgers, trial balance, P&L, balance sheet. |
| **1b — receivables** | Sales invoices (manual, or from Projects' billing requests), receipts against invoices, customer ledger and ageing. |
| **2 — payables and banking** | Bills and payments (to Projects' suppliers, already built), bank reconciliation. |
| **3 — tax and analysis** | VAT/TDS, cost centres, budgets. |

## 4. Main entities

- **Account group**: a node in the chart-of-accounts tree with a type (asset, liability, equity,
  income, expense).
- **Ledger account**: a postable leaf under a group. Some are flagged for special use (cash, bank,
  receivable control).
- **Fiscal year**: a dated period per company. It is open or closed, and a closed year accepts no
  postings.
- **Currency and exchange rate**: BDT is the base. A rate is recorded per currency per date.
- **Voucher**: a type (payment, receipt, journal, contra), a date, a number, a narration, a
  currency and rate, and a status. It has **voucher lines** (an account and a debit or credit),
  which must balance. When it came from another module it carries a **source link** (a billing
  request, a payroll run).
- **Client billing details**: the billing currency, credit terms and receivable account an invoice
  needs, added to Projects' clients when receivables are built (whether as columns on `clients` or a
  table of Accounting's own is open, [§8](#8-open-decisions)).
- **Sales invoice**: a client, a date, a currency, and lines. It may come from a Projects billing
  request. Approving it posts a voucher.
- **Receipt allocation**: matches a receipt voucher to one or more invoices.

## 5. Workflows and approvals

Approvals follow the ERP-wide pattern ([ARCHITECTURE §9.11](../ARCHITECTURE.md#911-approvals-planned)):
a status of draft → submitted → approved or rejected, an `.approve` permission, and a service blocker
that stops anyone approving their own voucher.

- **Vouchers.** A voucher is entered as a draft and submitted. **Only an approved voucher reaches the
  ledger.** A posted voucher is never edited; it is corrected by a reversing voucher.
- **Vouchers from other modules** ([ARCHITECTURE §9.12](../ARCHITECTURE.md#912-posting-to-the-ledger-planned)).
  Payroll (HRM) and, later, project costs (Projects) create **draft vouchers** linked to their
  source record. An accountant reviews and approves them, so Accounting keeps control of the books.
  Once posted, the source record is locked.
- **Billing requests.** Projects' *ready to invoice* requests appear in a queue. An accountant turns
  one into a sales invoice, and the request becomes *invoiced*.
- **Closing a year** carries balances forward and closes the year to postings.

## 6. Draft permission catalogue

*Draft — not seeded.* Names follow `{module}.{resource}.{action}` ([ARCHITECTURE
§6.2](../ARCHITECTURE.md#62-routes-and-permissions)).

| Permission | Allows |
| --- | --- |
| `accounting.accounts.{view,create,update,delete}` | Manage the chart of accounts |
| `accounting.fiscal-years.{view,create,update,close}` | Manage and close fiscal years |
| `accounting.currencies.{view,manage}` | Currencies and exchange rates |
| `accounting.vouchers.{view,create,update,delete}` | Enter vouchers (drafts only) |
| `accounting.vouchers.approve` | Approve vouchers, which posts them to the ledger |
| `accounting.invoices.{view,create,update,delete,approve}` | Sales invoices |
| `accounting.reports.view` | Ledgers, trial balance, P&L, balance sheet |

## 7. Links to other modules

| Module | Accounting uses | Accounting provides |
| --- | --- | --- |
| Admin | Companies, user↔company access | — |
| Projects | Billing requests, **clients** (the one customer list), **suppliers** (the one supplier list) | Currencies, invoices |
| HRM | Payroll draft vouchers (HRM phase 2) | Ledger accounts that payroll posts to |

## 8. Open decisions

- Voucher and invoice numbering: the format, and whether it is per company, per type or per year.
- The exchange-rate source: typed in daily, or imported (Bangladesh Bank rates)?
- How are exchange gains and losses on foreign receipts posted?
- Where a client's billing details live: columns Accounting adds to `clients`, or a table of its own
  keyed by client.
- Is there a standard chart of accounts template seeded for each new company?
- Do vouchers need more than one approval level (for example, above an amount)? This would mean
  revisiting the "no generic engine" decision.
