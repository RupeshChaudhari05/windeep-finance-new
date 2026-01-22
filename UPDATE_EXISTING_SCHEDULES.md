# Updating Existing Schedules to Fixed Due Day

## Problem

When you enable the **Fixed Due Day** setting, it only applies to **NEW** loans and savings created after enabling. **Existing schedules** keep their original due dates.

Example:
- You have 50 savings accounts with due dates on **1st of month**
- You enable Fixed Due Day = **10**
- Old accounts still show due date as **1st**
- Only NEW accounts created after this will use **10th**

---

## Solution

### Option 1: Use the Update Button (Recommended)

1. Go to **Admin > Settings**
2. Scroll to **Fixed Due Day** section
3. Ensure **Enable Fixed Due Day** is checked
4. Click the **"Update All Existing Schedules"** button
5. Confirm the action

**What it does:**
- Updates ALL future savings schedules to use new due day
- Updates ALL future loan installments to use new due day
- Only affects **unpaid/pending** schedules
- Does NOT change already paid or overdue items
- Does NOT change due dates that have already passed

**Example:**
```
Before:
- Savings #1: Feb 1, Mar 1, Apr 1
- Savings #2: Feb 1, Mar 1, Apr 1
- Loan #1: Feb 5, Mar 5, Apr 5

After (Fixed Due Day = 10):
- Savings #1: Feb 10, Mar 10, Apr 10
- Savings #2: Feb 10, Mar 10, Apr 10
- Loan #1: Feb 10, Mar 10, Apr 10
```

---

### Option 2: Run SQL Manually

If you prefer to run SQL directly:

```sql
-- Update future savings schedules
UPDATE savings_schedule
SET due_date = CONCAT(
    YEAR(due_month), '-',
    LPAD(MONTH(due_month), 2, '0'), '-',
    LPAD(LEAST(10, DAY(LAST_DAY(due_month))), 2, '0')
)
WHERE status = 'pending'
AND due_date > CURDATE();

-- Update future loan installments
UPDATE loan_installments
SET due_date = CONCAT(
    YEAR(due_date), '-',
    LPAD(MONTH(due_date), 2, '0'), '-',
    LPAD(LEAST(10, DAY(LAST_DAY(due_date))), 2, '0')
)
WHERE status = 'pending'
AND due_date > CURDATE();
```

**Replace `10` with your configured Fixed Due Day.**

---

## What Gets Updated

| Item | Updated? | Reason |
|------|----------|--------|
| Future pending savings | ✅ Yes | Can be changed |
| Future pending loan EMIs | ✅ Yes | Can be changed |
| Already paid items | ❌ No | Historical record |
| Overdue items | ❌ No | Already late, don't change |
| Past due dates | ❌ No | Historical accuracy |
| Today's due date | ❌ No | Too late to change |

---

## Important Notes

### Before Updating

1. **Take a database backup**
   ```bash
   # Admin > System > Create Backup
   ```

2. **Inform members** about the due date change
   - Send email/SMS notification
   - Explain the new unified due date
   - Give them 7 days notice

3. **Check your cash flow**
   - Moving from 1st to 10th means 9 days delay
   - Ensure you can handle the temporary gap

### After Updating

1. **Verify the changes**
   - Go to Admin > Savings > Pending
   - Check a few schedules manually
   - Verify due dates are correct

2. **Monitor collection**
   - First month after change, watch closely
   - Some members might miss the change
   - Send extra reminders

3. **Update member communications**
   - Loan agreements should mention the unified due date
   - Savings passbooks should reflect new schedule

---

## Rollback

If you need to revert changes:

```sql
-- This is complex because we don't store original due dates
-- Best practice: Restore from backup taken before update

-- Alternative: Manually set back to 1st
UPDATE savings_schedule
SET due_date = CONCAT(YEAR(due_month), '-', LPAD(MONTH(due_month), 2, '0'), '-01')
WHERE status = 'pending'
AND due_date > CURDATE();
```

---

## Best Practices

### Gradual Rollout

Instead of updating everything at once:

1. **Month 1:** Enable fixed due day, but don't update existing
2. **Month 2:** Update only savings schedules
3. **Month 3:** Update loan installments
4. **Month 4:** Full migration complete

### Communication Template

**Email to Members:**

```
Subject: Important: New Payment Due Date

Dear Member,

We're standardizing all payment due dates to the 10th of every month for better service.

Your NEW due dates:
- Old: 1st of every month
- New: 10th of every month

This change applies from [Month, Year] onwards.

Benefits:
✓ Easier to remember
✓ Better aligned with salary dates
✓ Automatic email reminders

Thank you,
Windeep Finance Team
```

---

## Troubleshooting

**Q: Button is disabled**
- A: Enable "Fixed Due Day" checkbox first

**Q: Update failed with error**
- A: Check application/logs/ for error details
- Ensure database user has UPDATE permission

**Q: Some schedules didn't update**
- A: Only FUTURE pending schedules are updated
- Already paid or overdue items are not changed

**Q: Due dates are wrong**
- A: Check Fixed Due Day value (1-28)
- For February, max is 28

---

## Summary

1. **Enable Fixed Due Day** in Settings
2. Click **"Update All Existing Schedules"** button
3. **Verify** changes in Savings > Pending
4. **Inform members** about new due dates
5. **Monitor collection** in first month

✅ All future schedules will now use your configured Fixed Due Day!
