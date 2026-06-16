# tp-finance — Design (read-layer, pure-PHP, ecosystem-integrated)

**สถานะ:** design phase (ยังไม่ build) · อิง `SYSTEM_CONTEXT.md` (spec) + audit ecosystem
**Decision:** สร้างเป็น **module/แอป pure-PHP ใน ecosystem** (อ่านข้อมูลการเงินจาก tp-erp/tp-crm) — **ไม่ใช่ standalone คนละ stack** (ปฏิเสธ Laravel/Next ที่ spec แนะ เพื่อ consistency + กัน finance surface/calculator ที่ 3)

---

## 1. หลักการสถาปัตยกรรม
- **Stack:** PHP 8.1+ pure PHP + Composer, Apache (เหมือนทุกแอป) · ผูก `tpasset/tp-common`
- **Auth/SSO:** `TpCommon\Session\SharedSession` (cookie `tp_session`) + `SsoGuard` — staff ภายใน
- **DB:** ใช้ `tp_crm` ร่วม (read-only สำหรับตัวเลขการเงิน) · owns เฉพาะตาราง config ของตัวเอง (`finance_*` prefix)
- **Integration (API-first):** อ่านผ่าน `TpCommon\Http\ErpClient` / `CrmClient` (HTTP) เป็นหลัก; fallback direct read บน shared DB ได้ตาม `DATA_INTEGRATION_MODE`
- **Standards:** `ApiResponse` envelope, `ApiAuth` (ถ้าเปิด API), UI ตาม `UI_RULES`/`UI_UX_STANDARD` (mobile-first, Kanit — ตรงกับ spec อยู่แล้ว)
- **Deploy:** git-pull pattern (เหมือน ecosystem) + secret deploy

## 2. กฎเหล็ก: single profit source (กันปัญหา "calculator ที่ 3")
ตัวเลขกำไร/ขาดทุน **ต้องมาจาก formula เดียว** — ปัจจุบันคือ `tp-crm CustomerCaseProfitService::buildCaseProfitLoss`:
```
revenue = deals(actual_sale_price ที่ received) + projects(revenue) + ledger(income)
cost    = deal total_costs + debt_closure(paid) + renovation + boq + acquisition + ledger(expense)
gross   = revenue − cost ; margin% = gross/revenue*100
```
→ **แผน:** extract formula นี้เป็น helper กลาง (`TpCommon\Ledger\DealPnl` หรือคล้าย) ให้ **ทั้ง CustomerCaseProfitService และ tp-finance calculator ใช้ร่วม** (mirror บทเรียน WorkdayCalculator) · สำหรับ real deal → tp-finance อ่านผ่าน CRM API; สำหรับ what-if calc → ใช้ formula เดียวกันแบบ stateless

## 3. Data-ownership: owns vs reads
| ข้อมูล | tp-finance | source |
|---|---|---|
| โครงสร้างบัญชี 4 (INCOME/PROJECT/EXPENSE/HOLDING) + คำอธิบาย | ✅ **owns** (`finance_accounts` config — ใหม่, เล็ก; ไม่มีของเดิม) | — |
| case-study / usage-guide content | ✅ owns (static/config) | — |
| what-if scratch calc (ยังไม่ใช่ดีลจริง) | ✅ owns (ไม่ persist เป็น financial record) | — |
| ตัวเลข P&L ดีล/โปรเจกต์จริง | ❌ **read-only** | tp-crm `CustomerCaseProfitService` / deals / case_pl |
| income statement / cash flow / project P&L / balance sheet | ❌ read-only | tp-erp `/api/v1/reports/*` (มีแล้ว: project-pl, income-statement, cash-flow, balance-sheet, trial-balance) |
| KPI / analytics | ❌ read-only | tp-erp `/api/v1/analytics/kpis` |

> tp-finance **ห้ามเขียน** ตัวเลขการเงินจริง (ไม่ใช่ owner) — กัน data divergence

## 4. Page plan (7 หน้า ตาม spec → data source)
| หน้า | เนื้อหา | source |
|---|---|---|
| 1 Dashboard | สรุป 4 บัญชี + quick cards (รับเงิน/เงินกู้/ค่าใช้จ่าย/กำไร) | owns(บัญชี) + read erp income-statement/cash-flow |
| 2 Account Structure | อธิบาย 4 บัญชี | owns |
| 3 Account Usage Guide | เงินประเภทไหนเข้าบัญชีไหน | owns |
| 4 Case Studies (A-D) | flow เงิน 4 เคส | owns (content) |
| 5 Deal Calculator | กรอกตัวเลข → gross/net/margin% + คำแนะนำ | **shared P&L formula** (กฎข้อ 2) |
| 6 Project Tracker | รายการเข้า-ออกต่อโปรเจกต์ | read erp project-pl (ไม่ duplicate ledger) |
| 7 Profit-Loss Summary | สรุปผู้บริหาร แยกโปรเจกต์/ประเภทดีล | read erp income-statement + crm case_pl |

## 5. Build plan (phased, low-risk ก่อน)
1. **P1 scaffold:** pure PHP + tp-common + SharedSession SSO + nav + หน้า explanatory (2/3/4) = static/owns — ไม่แตะ integration, ความเสี่ยงต่ำสุด
2. **P2 Deal Calculator:** extract canonical P&L → shared helper, ใช้ใน calculator (what-if) + reconcile กับ CustomerCaseProfitService
3. **P3 Dashboard + P&L summary:** read-only ผ่าน ErpClient/CrmClient
4. **P4 Project Tracker:** read erp project-pl

## 6. สิ่งที่ต้องตัดสิน/เตรียมก่อน build
- ยืนยัน decision: module-in-ecosystem (ตาม doc นี้) vs standalone
- init tp-finance เป็น git repo + deploy (git-pull pattern) + secret
- ออกแบบตาราง `finance_accounts` (config 4 บัญชี) — owned, prefix `finance_`
- (P2) ตกลงที่ extract `DealPnl` helper ลง tp-common (กระทบ tp-crm CustomerCaseProfitService — safe-change-order: tp-common ก่อน)
