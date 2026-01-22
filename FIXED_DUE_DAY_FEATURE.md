# Fixed Due Day Feature - Complete Guide

## Overview

The **Fixed Due Day** feature standardizes all monthly payment due dates across loans and savings accounts to a specific day of the month (1-28). This simplifies cash flow management and ensures consistent member payment schedules.

---

## Key Features

### ✅ Unified Due Dates
- All loan EMIs due on the same day each month
- All savings deposits due on the same day each month
- Configurable between day 1-28 (avoids month-end issues)

### ✅ Automatic Email Alerts
- **On Due Date:** Members receive email notification when payment is due TODAY
- **3-Day Advance:** Members receive reminder 3 days before due date
- **Only if Email Present:** System checks for valid email before sending

### ✅ Automatic Fine Application
- Fines are **automatically applied** after due date passes
- Based on configured Fine Rules (per day, fixed, percentage)
- Applied once per installment (no duplicates)
- Separate rules for loans and savings

### ✅ Status Updates
- Installments automatically marked as "overdue" after due date
- NPA status automatically assigned after configured days
- Member notifications created in-app

---

## Configuration

### Step 1: Enable the Feature

1. Go to **Admin > Settings**
2. Scroll to **Business Rules** section
3. Set **Fixed Due Day** (e.g., 10 = 10th of every month)
4. Check **Enable Fixed Due Day** checkbox
5. Click **Save Settings**

### Step 2: Configure Email Settings

1. Go to **Admin > Settings > Email Configuration** tab
2. Enter SMTP details:
   - Host: `smtp.gmail.com`
   - Port: `587`
   - Encryption: `TLS`
   - Username & Password
3. Test email configuration
4. Save settings

### Step 3: Set Up Fine Rules

1. Go to **Admin > Settings > Fine Rules** tab
2. Create rules for:
   - **Loan Late Payment** (e.g., ₹100/day after 3 grace days)
   - **Savings Late Deposit** (e.g., ₹50/day after 5 grace days)
3. Save fine rules

### Step 4: Set Up Cron Jobs

**Required:** Daily cron must run for automatic features to work.

```bash
# Add to crontab (Linux)
0 2 * * * php /path/to/windeep_finance/index.php cli/cron/daily

# Or Windows Task Scheduler
C:\xampp_new\php\php.exe C:\xampp_new\htdocs\windeep_finance\index.php cli/cron/daily
```

See [CRON_SETUP.md](CRON_SETUP.md) for complete instructions.

---

## How It Works

### For New Loans

When disbursing a loan with Fixed Due Day **enabled**:

```
Example: Fixed Due Day = 10
Disbursement Date: January 25, 2026

Calculation:
- First EMI Date would normally be February 25
- With Fixed Due Day enabled → First EMI Date becomes February 10
- All subsequent EMIs: March 10, April 10, May 10, etc.
```

**Shorter Months:**
- If Fixed Due Day = 30, February EMIs will be on Feb 28 (last day)
- System automatically adjusts to last day of month

### For Savings Accounts

When creating a savings account with Fixed Due Day **enabled**:

```
Example: Fixed Due Day = 5
Account Start: January 20, 2026
Monthly Amount: ₹1,000

Schedule:
- February 5 → ₹1,000 due
- March 5 → ₹1,000 due
- April 5 → ₹1,000 due
```

### Email Alert Flow

**Timeline Example (Fixed Due Day = 10):**

| Date | Action | Email Sent |
|------|--------|------------|
| **March 7** | 3 days before | ✉️ "Payment due in 3 days" |
| **March 10** | Due date | ✉️ "Payment due TODAY" |
| **March 11** | 1 day overdue | ⚠️ Fine applied automatically |
| **March 12** | 2 days overdue | Status changed to "overdue" |

**Email Content (Due Date):**
```
Subject: Payment Due Today - ₹8,500.00

Dear Rajesh,

Your EMI payment of ₹8,500.00 is due TODAY.

Loan Number: LN00123
Installment: #5
Due Date: 10 Mar 2026

Please make the payment to avoid late fees.

Thank you,
Windeep Finance
```

### Automatic Fine Application

**Daily Cron (2:00 AM) Process:**

1. **Check Overdue Installments**
   ```sql
   SELECT * FROM loan_installments
   WHERE status = 'pending'
   AND due_date < CURDATE()
   ```

2. **Check if Fine Already Applied**
   - Prevents duplicate fines for same installment on same day

3. **Calculate Fine Amount**
   - Fetches applicable fine rule
   - Calculates based on days overdue and outstanding amount
   - Example: ₹100/day after 3 grace days

4. **Apply Fine**
   ```sql
   INSERT INTO fines (
     loan_id, installment_id, member_id,
     fine_type, fine_amount, reason,
     days_overdue, status
   ) VALUES (...)
   ```

5. **Update Installment Status**
   ```sql
   UPDATE loan_installments
   SET status = 'overdue'
   WHERE due_date < CURDATE()
   ```

---

## Member Experience

### Before Due Date (3 Days)
- Receives email reminder: "Payment due in 3 days"
- In-app notification appears
- Can login and make payment online

### On Due Date
- Receives email: "Payment due TODAY"
- Urgent in-app notification
- Payment option highlighted in member portal

### After Due Date
- **Day 1 Overdue:** Fine automatically applied based on rules
- **Day 2+:** Daily fine increments (if per-day rule)
- **Day 30+:** Loan may be marked as NPA (configurable)

### If Member Has No Email
- Still receives in-app notifications
- Admin can send manual SMS/WhatsApp
- Payment reminders shown in member portal

---

## Admin Dashboard

### View Upcoming Due Dates

Go to **Admin > Dashboard > Upcoming Payments** to see:
- All payments due today
- All payments due in next 7 days
- Overdue payments with fine amounts

### Manual Email Sending

If cron is not running, admin can manually:
1. Go to **Admin > System > Cron Jobs**
2. Click **Run Daily Cron** button
3. Emails and fines will be processed immediately

### Reports

**Due Date Report:**
- Go to **Reports > Due Date Analysis**
- Shows collection efficiency by due date
- Helps identify best due day for your organization

---

## Fine Rule Examples

### Example 1: Simple Fixed Fine
```
Type: Loan Late Payment
Fine Type: Fixed
Amount: ₹100
Grace Days: 0
```
**Result:** ₹100 fine applied on day 1 after due date

### Example 2: Per Day Fine
```
Type: Loan Late Payment
Fine Type: Per Day
Amount: ₹50 per day
Grace Days: 3
Max Fine: ₹500
```
**Result:**
- Days 1-3: No fine (grace period)
- Day 4: ₹50
- Day 5: ₹100
- Day 6: ₹150
- Day 10+: ₹500 (max cap reached)

### Example 3: Percentage Fine
```
Type: Loan Late Payment
Fine Type: Percentage
Amount: 1% of outstanding
Grace Days: 5
```
**Result:**
- Outstanding: ₹10,000
- Days 1-5: No fine
- Day 6: ₹100 (1% of ₹10,000)

---

## Testing

### Test Before Going Live

1. **Set Fixed Due Day to Tomorrow**
   - Settings > Fixed Due Day = (tomorrow's date)
   - Enable feature

2. **Create Test Loan**
   - Disburse small loan to test member
   - Verify first EMI date = tomorrow

3. **Wait Until Tomorrow**
   - Check if email is received
   - Verify notification appears in portal

4. **Check Day After (Overdue)**
   - Verify fine is applied automatically
   - Check status changed to "overdue"

5. **Make Payment**
   - Record payment for test loan
   - Verify fine is cleared
   - Status changes to "paid"

### Manual Trigger (Testing)

```bash
# Run daily cron manually for testing
php index.php cli/cron/daily

# Check logs
tail -f application/logs/cron_YYYY-MM-DD.log
```

---

## Troubleshooting

### Emails Not Being Sent?

**Check:**
1. Email configuration in Settings > Email
2. Test email works (Send Test Email button)
3. Daily cron is running (check System > Cron Logs)
4. Member has valid email address
5. Check spam folder

**Fix:**
```bash
# Verify email helper
php -r "require 'application/helpers/email_helper.php'; var_dump(function_exists('send_email'));"

# Manually run cron
php index.php cli/cron/daily
```

### Fines Not Being Applied?

**Check:**
1. Fine rules are configured (Settings > Fine Rules)
2. Auto-apply fines is enabled (Settings > Business Rules)
3. Daily cron is running
4. Check application/logs/cron_YYYY-MM-DD.log

**Manual Fix:**
```bash
# Apply fines manually
php index.php cli/cron/apply_overdue_fines
```

### Wrong Due Dates?

**Check:**
1. Fixed Due Day setting (1-28)
2. "Enable Fixed Due Day" checkbox is checked
3. For existing loans: Due dates won't change (only new loans)

**Fix for Existing Loans:**
- Go to loan detail page
- Click "Adjust Schedule" button (if available)
- Or create new loan with correct dates

---

## Best Practices

### Choosing the Right Due Day

| Due Day | Pros | Cons |
|---------|------|------|
| **1-5** | Start of month, easy to remember | Might clash with rent payments |
| **6-10** | ✅ **Recommended** - After salary receipt | Good balance |
| **11-15** | Mid-month stability | May conflict with other EMIs |
| **16-20** | Good for bi-weekly salary | Less common |
| **21-28** | End of month | Risk of clashing with many obligations |

**Recommendation:** Use day **5** or **10** for optimal collection rates.

### Email Best Practices

1. **Verify Member Emails:** Import clean email list
2. **Test Before Launch:** Send test emails to yourself
3. **Monitor Bounce Rate:** Check System > Email Logs
4. **Add Backup Contact:** Collect phone numbers for SMS

### Fine Rule Best Practices

1. **Use Grace Period:** Allow 2-3 days grace before fines
2. **Cap Maximum Fine:** Prevent excessive penalties
3. **Start Low:** ₹50-100/day for loans, ₹25-50 for savings
4. **Communicate Clearly:** Show fine rules in member agreement

---

## FAQ

**Q: Can I change the Fixed Due Day after setting it?**
A: Yes, but it only affects NEW loans/savings. Existing schedules remain unchanged.

**Q: What if member doesn't have an email?**
A: They still get in-app notifications. You can manually send SMS/WhatsApp.

**Q: Can I disable auto-fines for specific members?**
A: Currently no. You can waive fines manually after they're applied.

**Q: What happens on months with fewer than 28 days?**
A: System automatically uses the last day of the month (Feb 28/29).

**Q: Can I have different due days for loans vs savings?**
A: Currently no - one global due day. Feature request for future version.

**Q: How do I test without spamming members?**
A: Use a test member account with your own email address.

---

## Summary

✅ **Enable Feature:** Settings > Fixed Due Day (1-28)  
✅ **Set Up Email:** Configure SMTP in Email Settings  
✅ **Create Fine Rules:** Define penalty structure  
✅ **Run Cron Daily:** Required for automation  
✅ **Test Thoroughly:** Use test accounts first  

**Result:** Streamlined payment collection with automatic emails and fine management! 🎉
