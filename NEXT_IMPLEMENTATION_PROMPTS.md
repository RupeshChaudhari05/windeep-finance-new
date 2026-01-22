# 🚀 NEXT IMPLEMENTATION PROMPTS

**For:** Windeep Finance System  
**Based on:** Comprehensive Audit Report (87% Complete)  
**Target:** 100% Feature Completion  
**Priority:** High → Low

---

## 📋 HOW TO USE THIS DOCUMENT

Each prompt below is **ready to use** with an AI coding assistant. Simply:
1. Copy the entire prompt section
2. Paste into your AI assistant (GitHub Copilot, ChatGPT, Claude, etc.)
3. Follow the implementation steps
4. Test thoroughly
5. Move to next prompt

**Estimated Total Time:** 116 hours (14.5 days)  
**Priority Order:** Phase 1 → Phase 2 → Phase 3

---

## 🔴 PHASE 1: CRITICAL PRODUCTION SETUP (12 hours)

### PROMPT 1.1: Schedule Interest Calculation Cron Job

**Priority:** CRITICAL 🔴  
**Time:** 2 hours  
**Difficulty:** Medium

```
I need to set up automated interest calculation for savings accounts in my CodeIgniter 3 application.

CONTEXT:
- Project: Windeep Finance (NBFC Management System)
- Framework: CodeIgniter 3
- Database: MySQL
- Current Status: Interest calculation logic exists in Savings_model but not scheduled

REQUIREMENTS:
1. Create a CLI controller for interest calculation job
2. Calculate monthly interest for all active savings accounts
3. Post interest to savings_schedule table
4. Update current_balance in savings_accounts table
5. Create ledger entries (Debit: Interest Expense, Credit: Savings Account)
6. Send email notification to members
7. Log all calculations to audit table

DATABASE STRUCTURE:
- savings_accounts: id, member_id, scheme_id, current_balance, interest_rate, status
- savings_schemes: id, name, interest_rate, frequency
- savings_schedule: id, account_id, due_date, amount, interest, status
- ledger: id, date, account, debit, credit, description

EXISTING CODE:
- Savings_model has calculate_interest() method (not auto-triggered)
- File: application/models/Savings_model.php

DELIVERABLES:
1. Create application/controllers/cli/Interest_calculator.php with index() method
2. Add interest posting logic with proper validations
3. Create cron-ready command: php index.php cli/interest_calculator
4. Add error handling and logging
5. Test with sample data
6. Provide crontab entry for monthly execution (1st of every month at 2 AM)

EXAMPLE CRONTAB:
0 2 1 * * cd /path/to/project && php index.php cli/interest_calculator >> /var/log/interest_calc.log 2>&1

Please implement this with proper error handling, transaction support, and audit logging.
```

---

### PROMPT 1.2: Enable Automatic Schedule Extension

**Priority:** CRITICAL 🔴  
**Time:** 2 hours  
**Difficulty:** Medium

```
I need to create an automated job to extend savings schedules before they expire.

CONTEXT:
- Project: Windeep Finance
- Issue: Savings schedules only generate 12 months ahead, need auto-extension
- Current: Manual extension works, need automation

REQUIREMENTS:
1. Create CLI controller for schedule extension
2. Find all savings accounts with schedules ending within 3 months
3. Generate next 12 months of schedules
4. Use scheme's due_day for date calculation
5. Handle month-end edge cases (day 31 → day 28/29 for February)
6. Send notification to members about extended schedule
7. Log all operations

DATABASE TABLES:
- savings_accounts: id, scheme_id, opening_date, status
- savings_schemes: id, deposit_amount, due_day (1-31)
- savings_schedule: id, account_id, due_date, amount, status

EXISTING CODE:
- Savings_model->generate_schedule($account_id, $start_date, $months) exists
- File: application/models/Savings_model.php

IMPLEMENTATION:
1. Create application/controllers/cli/Schedule_extender.php
2. Query accounts needing extension (max_due_date < NOW() + 3 months)
3. Call generate_schedule() for each account
4. Validate no duplicate entries
5. Send batch notifications
6. Generate summary report

CRON SCHEDULE:
Run weekly (every Sunday at 3 AM):
0 3 * * 0 cd /path/to/project && php index.php cli/schedule_extender >> /var/log/schedule_ext.log 2>&1

EDGE CASES:
- Account closed/matured: Skip
- Schedule already extended: Skip
- Invalid due_day (e.g., 31 for February): Clamp to month end
- Database errors: Rollback and log

Please implement with transaction support and detailed logging.
```

---

### PROMPT 1.3: Add Form Validation to Remaining Forms

**Priority:** HIGH 🔴  
**Time:** 4 hours  
**Difficulty:** Easy

```
I need to add comprehensive form validation to 3 forms that currently lack proper validation.

CONTEXT:
- Project: Windeep Finance (CodeIgniter 3)
- Security Issue: Some forms allow invalid data submission
- Framework: Using CodeIgniter Form Validation Library

FORMS TO FIX:

1. SAVINGS WITHDRAWAL FORM
   Location: application/views/admin/savings/withdraw.php
   Controller: application/controllers/admin/Savings.php->process_withdrawal()
   
   Validations Needed:
   - account_id: required|integer|exists in savings_accounts
   - amount: required|decimal|greater_than[0]|less_than_equal_to[current_balance]
   - withdrawal_date: required|valid_date|not_future_date
   - reason: required|min_length[10]|max_length[500]
   - lock_in_check: verify account not in lock-in period
   
   Error Messages:
   - Custom messages for each validation rule
   - Show current balance in error
   - Highlight lock-in expiry date

2. FINE WAIVER REQUEST FORM
   Location: application/views/admin/fines/waiver.php
   Controller: application/controllers/admin/Fines.php->process_waiver()
   
   Validations Needed:
   - fine_id: required|integer|exists in fines table
   - waiver_type: required|in_list[full,partial]
   - waiver_amount: required_if[waiver_type,partial]|decimal|less_than_equal_to[fine_amount]
   - reason: required|min_length[20]|max_length[1000]
   - supporting_document: optional|valid_file|max_size[2MB]|allowed_types[pdf,jpg,png]
   
   Business Rules:
   - Cannot request waiver for already paid fine
   - Cannot request waiver twice for same fine
   - Waiver amount must be > 0 and <= outstanding fine

3. BANK TRANSACTION MAPPING FORM
   Location: application/views/admin/bank/map_transaction.php
   Controller: application/controllers/admin/Bank.php->save_mapping()
   
   Validations Needed:
   - transaction_id: required|integer|exists in bank_transactions
   - mapping_type: required|in_list[loan_payment,savings_deposit,fine_payment]
   - member_id: required|integer|exists in members table
   - account_id: required|integer|validate_account_ownership
   - amount: required|decimal|greater_than[0]|less_than_equal_to[transaction_amount]
   - remarks: optional|max_length[500]
   
   Business Rules:
   - Transaction cannot be already fully mapped
   - Sum of mapped amounts cannot exceed transaction amount
   - Account must belong to selected member
   - Account must be active

IMPLEMENTATION REQUIREMENTS:
1. Add validation rules in controller methods
2. Add custom validation callbacks where needed
3. Display errors using CodeIgniter flash messages
4. Add client-side validation (jQuery Validation) for better UX
5. Sanitize all inputs (XSS protection)
6. Add CSRF token validation
7. Log validation failures to audit table

EXAMPLE VALIDATION CODE:
$this->form_validation->set_rules('amount', 'Amount', 'required|decimal|greater_than[0]', [
    'required' => 'Please enter withdrawal amount',
    'greater_than' => 'Amount must be greater than zero'
]);

DELIVERABLES:
1. Updated controller methods with validation rules
2. Custom validation callbacks if needed
3. Client-side jQuery validation scripts
4. Error message display in views
5. Test cases for each validation rule

Please implement with comprehensive validation and user-friendly error messages.
```

---

### PROMPT 1.4: Critical Workflow Testing

**Priority:** HIGH 🔴  
**Time:** 4 hours  
**Difficulty:** Medium

```
I need to create comprehensive test cases and execute critical workflow testing for production readiness.

CONTEXT:
- Project: Windeep Finance
- Status: 87% complete, preparing for production
- Need: Validate all critical user journeys work end-to-end

CRITICAL WORKFLOWS TO TEST:

1. LOAN LIFECYCLE WORKFLOW
   Steps:
   a. Member applies for loan (with documents)
   b. Admin reviews application
   c. Admin approves loan (with modifications if needed)
   d. System generates installment schedule
   e. Admin disburses loan
   f. Member makes payment
   g. System allocates: Fine → Interest → Principal (RBI order)
   h. Payment reflects in member portal
   i. Admin generates loan statement
   
   Test Data:
   - Member: Test Member (member_code: MEM001)
   - Product: Personal Loan (12 months, 12% interest)
   - Amount: ₹50,000
   - Payment: ₹5,000 (partial)
   
   Validations:
   - Schedule generated correctly
   - Interest calculated properly
   - Payment allocation follows RBI order
   - Balance updates correctly
   - Ledger entries created
   - Notifications sent

2. BANK IMPORT & MAPPING WORKFLOW
   Steps:
   a. Upload CSV bank statement
   b. System parses transactions
   c. Auto-match by UTR/Reference
   d. Manually map unmatched transactions
   e. Apply payments to accounts
   f. Generate reconciliation report
   
   Test Data:
   - File: assets/sample_imports/bank_statement_for_import.csv
   - Transactions: 10 entries (5 auto-match, 5 manual)
   
   Validations:
   - CSV parsing correct
   - Auto-matching works
   - Manual mapping UI functional
   - Split payments work
   - Duplicate prevention works
   - Ledger posting correct

3. SAVINGS COLLECTION WORKFLOW
   Steps:
   a. Member opens savings account
   b. System generates monthly schedule
   c. Member makes deposits (regular)
   d. System updates schedule and balance
   e. Interest calculated monthly
   f. Member requests withdrawal
   g. Admin approves withdrawal
   h. Balance updated correctly
   
   Test Data:
   - Scheme: Monthly Saver (2% interest, 12 months)
   - Monthly Deposit: ₹2,000
   - Deposits: 6 months
   - Withdrawal: ₹5,000
   
   Validations:
   - Schedule generated with correct due_day
   - Deposits recorded correctly
   - Interest calculation accurate
   - Withdrawal validation works
   - Balance matches expected

DELIVERABLES:
1. Create application/tests/WorkflowTest.php with PHPUnit
2. Test each workflow with assertions
3. Document test results in TEST_RESULTS.md
4. Create test data SQL script
5. List of issues found (if any)
6. Fix any critical bugs discovered

EXAMPLE TEST STRUCTURE:
```php
class LoanWorkflowTest extends TestCase {
    public function test_loan_application_to_disbursement() {
        // Apply
        $application_id = $this->apply_for_loan();
        $this->assertNotEmpty($application_id);
        
        // Approve
        $loan_id = $this->approve_loan($application_id);
        $this->assertNotEmpty($loan_id);
        
        // Check schedule
        $schedule = $this->get_loan_schedule($loan_id);
        $this->assertEquals(12, count($schedule));
        
        // Disburse
        $result = $this->disburse_loan($loan_id);
        $this->assertTrue($result);
        
        // Check ledger
        $ledger_entries = $this->get_ledger_entries($loan_id);
        $this->assertGreaterThan(0, count($ledger_entries));
    }
}
```

Please create comprehensive tests and document all results.
```

---

## 🟡 PHASE 2: FEATURE COMPLETION (24 hours)

### PROMPT 2.1: Complete Financial Reports

**Priority:** MEDIUM 🟡  
**Time:** 8 hours  
**Difficulty:** Hard

```
I need to fix and complete the financial reports module (P&L, Balance Sheet, Trial Balance).

CONTEXT:
- Project: Windeep Finance NBFC System
- Issue: Basic reports exist but calculations are incomplete
- Requirement: Production-ready financial statements

REPORTS TO FIX:

1. PROFIT & LOSS STATEMENT
   Location: application/controllers/admin/Reports.php->profit_loss()
   View: application/views/admin/reports/profit_loss.php
   
   Income Section:
   - Interest Income from Loans (from installments table)
   - Interest Income from Savings (from savings_schedule)
   - Fine Income (from fines table)
   - Processing Fee Income (from loans table)
   - Other Income (miscellaneous)
   
   Expense Section:
   - Interest Paid on Deposits (savings interest)
   - Operating Expenses (from expenses table - may need to create)
   - Staff Salaries (from expenses table)
   - Administrative Expenses
   - Depreciation
   - Bad Debts Written Off
   
   Calculations:
   - Gross Profit = Total Income - Direct Costs
   - Operating Profit = Gross Profit - Operating Expenses
   - Net Profit = Operating Profit - Tax
   - Profit Margin % = (Net Profit / Total Income) × 100
   
   Features:
   - Date range filter
   - Month-wise comparison
   - Export to PDF/Excel
   - Visual charts (Chart.js)

2. BALANCE SHEET
   Location: application/controllers/admin/Reports.php->balance_sheet()
   View: application/views/admin/reports/balance_sheet.php
   
   Assets:
   - Current Assets:
     * Cash in Hand (from ledger)
     * Bank Balance (from bank_accounts)
     * Loans Receivable (outstanding loan principal)
     * Interest Receivable (unpaid interest)
     * Other Receivables
   - Fixed Assets:
     * Office Equipment
     * Furniture & Fixtures
     * Vehicles
     * Less: Accumulated Depreciation
   
   Liabilities:
   - Current Liabilities:
     * Savings Deposits (total balance)
     * Fixed Deposits
     * Interest Payable (accrued interest)
     * Other Payables
   - Long-term Liabilities:
     * Bank Loans
     * Other Borrowings
   
   Equity:
   - Share Capital
   - Reserves & Surplus
   - Retained Earnings
   - Current Year Profit/Loss
   
   Validations:
   - Assets = Liabilities + Equity (must balance)
   - Show difference if not balanced
   - As on date reporting
   - Comparative (current vs previous year)

3. TRIAL BALANCE
   Location: application/controllers/admin/Reports.php->trial_balance()
   View: application/views/admin/reports/trial_balance.php
   
   Structure:
   - Account Code | Account Name | Debit | Credit
   - Group by account type
   - Opening Balance
   - Current Period Transactions
   - Closing Balance
   
   Features:
   - Date range filter
   - Account grouping
   - Drill-down to ledger
   - Export to Excel
   - Validation: Total Debits = Total Credits

DATABASE SCHEMA NEEDED:
```sql
-- If expenses table doesn't exist
CREATE TABLE IF NOT EXISTS expenses (
    id INT PRIMARY KEY AUTO_INCREMENT,
    date DATE NOT NULL,
    category VARCHAR(100),
    description TEXT,
    amount DECIMAL(10,2),
    payment_mode VARCHAR(50),
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Add chart of accounts if not exists
CREATE TABLE IF NOT EXISTS accounts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(20) UNIQUE,
    name VARCHAR(200),
    type ENUM('Asset','Liability','Equity','Income','Expense'),
    parent_id INT,
    is_active TINYINT DEFAULT 1
);
```

IMPLEMENTATION:
1. Create/fix controller methods with proper SQL queries
2. Build view templates with professional formatting
3. Add PDF export (using TCPDF or mPDF)
4. Add Excel export (using PhpSpreadsheet)
5. Add chart visualizations
6. Implement caching for performance
7. Add validation and error handling

DELIVERABLES:
1. Working P&L report with accurate calculations
2. Working Balance Sheet that balances
3. Working Trial Balance with drill-down
4. PDF and Excel export functionality
5. Test with real data and verify accuracy
6. Documentation on report formulas

Please implement professional financial reports with audit-level accuracy.
```

---

### PROMPT 2.2: Bulk Member Import UI

**Priority:** MEDIUM 🟡  
**Time:** 6 hours  
**Difficulty:** Medium

```
I need to create a bulk member import feature with CSV upload, validation, and preview.

CONTEXT:
- Project: Windeep Finance
- Current: Members added manually one by one
- Need: Bulk import from CSV for initial data migration

REQUIREMENTS:

1. CSV TEMPLATE
   Columns:
   - member_code (optional, auto-generate if empty)
   - first_name (required)
   - last_name (required)
   - email (required, unique)
   - phone (required, unique)
   - aadhaar (optional)
   - pan (optional)
   - date_of_birth (YYYY-MM-DD)
   - gender (Male/Female/Other)
   - address (required)
   - city (required)
   - state (required)
   - pincode (required)
   - occupation (optional)
   - monthly_income (optional)
   - joining_date (YYYY-MM-DD)
   
   Features:
   - Download template button
   - Sample data in template
   - Instructions in first row (skip during import)

2. UPLOAD & VALIDATION
   File: application/controllers/admin/Members.php->bulk_import()
   View: application/views/admin/members/bulk_import.php
   
   Validations:
   - File type: CSV only
   - File size: Max 5MB
   - Row limit: Max 1000 members per file
   - Required fields check
   - Email format validation
   - Phone format validation (10 digits)
   - Duplicate email check (database + within CSV)
   - Duplicate phone check (database + within CSV)
   - Valid date format check
   - Age validation (18-100 years)
   
   Error Handling:
   - Show row number for errors
   - Allow download of error report
   - Highlight invalid rows
   - Continue with valid rows option

3. PREVIEW & CONFIRM
   After validation:
   - Show preview table with all data
   - Summary: X valid, Y invalid, Z duplicates
   - Color code: Green (valid), Red (invalid), Yellow (duplicate)
   - Allow edit before import
   - Confirm button to proceed
   
4. IMPORT PROCESS
   - Transaction support (all or nothing)
   - Progress bar (AJAX updates)
   - Auto-generate member codes
   - Create user accounts (if email provided)
   - Send welcome email (optional checkbox)
   - Log all imports to audit table
   - Generate import report (PDF)

5. UI DESIGN
   Step 1: Download Template / Upload File
   Step 2: Validation & Error Report
   Step 3: Preview & Edit
   Step 4: Import Progress
   Step 5: Success Summary

IMPLEMENTATION FILES:
1. application/controllers/admin/Members.php
   - bulk_import() - Show upload page
   - process_import() - Handle CSV upload
   - validate_import() - Validate data
   - confirm_import() - Final import
   
2. application/views/admin/members/bulk_import.php
   - File upload form
   - Progress indicator
   - Error display
   - Success message

3. application/models/Member_model.php
   - bulk_create() method
   - validate_bulk_data() method

EXAMPLE CSV FORMAT:
```csv
member_code,first_name,last_name,email,phone,date_of_birth,gender,address,city,state,pincode
,John,Doe,john@example.com,9876543210,1990-01-15,Male,123 Main St,Mumbai,Maharashtra,400001
MEM001,Jane,Smith,jane@example.com,9876543211,1992-05-20,Female,456 Park Ave,Pune,Maharashtra,411001
```

DELIVERABLES:
1. CSV template file (with sample data)
2. Upload and validation controller
3. Preview interface
4. Import processor with transaction support
5. Error report generation
6. Success/failure logging
7. Test with 100+ sample members

Please implement with comprehensive validation and user-friendly error messages.
```

---

### PROMPT 2.3: System Log Viewer

**Priority:** MEDIUM 🟡  
**Time:** 4 hours  
**Difficulty:** Easy

```
I need to create a system log viewer in the admin panel for debugging and monitoring.

CONTEXT:
- Project: Windeep Finance (CodeIgniter 3)
- Issue: Logs stored in application/logs/ but not accessible via UI
- Need: Admin interface to view, search, filter, and download logs

REQUIREMENTS:

1. LOG VIEWER PAGE
   Location: application/controllers/admin/System.php->logs()
   View: application/views/admin/system/logs.php
   Route: admin/system/logs
   
   Features:
   - List all log files (sorted by date, newest first)
   - Show file size and last modified time
   - Click to view log content
   - Real-time log tail (last 100 lines)
   - Download log file
   - Delete old logs (with confirmation)

2. LOG CONTENT VIEWER
   Display:
   - Syntax highlighting for log levels (ERROR, DEBUG, INFO)
   - Line numbers
   - Timestamp formatting
   - Search within log (AJAX)
   - Filter by log level
   - Pagination (100 lines per page)
   - Copy to clipboard button

3. LOG SEARCH & FILTER
   Filters:
   - Log level: ERROR, WARNING, INFO, DEBUG, ALL
   - Date range
   - Search keyword (in message)
   - IP address
   - User ID
   
   Search Features:
   - Case-insensitive
   - Regex support (optional)
   - Multi-file search
   - Export results to CSV

4. LOG MANAGEMENT
   Actions:
   - Download single log
   - Download all logs (ZIP)
   - Delete logs older than X days
   - Archive logs (move to /logs/archive/)
   - Clear all logs (with confirmation)
   
   Auto-cleanup:
   - Schedule: Delete logs older than 90 days
   - Keep last 30 days always
   - Archive before delete

5. LOG DASHBOARD
   Statistics:
   - Total errors (last 24 hours)
   - Warning count
   - Most frequent errors
   - Error trend chart (Chart.js)
   - Recent critical errors (top 10)

IMPLEMENTATION:

File: application/controllers/admin/System.php
```php
public function logs() {
    $this->check_permission('view_logs'); // Admin only
    
    $log_path = APPPATH . 'logs/';
    $data['log_files'] = $this->get_log_files($log_path);
    $data['disk_usage'] = $this->get_disk_usage($log_path);
    
    $this->load->view('admin/system/logs', $data);
}

private function get_log_files($path) {
    $files = glob($path . '*.php');
    $result = [];
    
    foreach($files as $file) {
        $result[] = [
            'name' => basename($file),
            'size' => filesize($file),
            'modified' => filemtime($file),
            'path' => $file
        ];
    }
    
    // Sort by modified time, newest first
    usort($result, function($a, $b) {
        return $b['modified'] - $a['modified'];
    });
    
    return $result;
}

public function view_log($filename) {
    $log_path = APPPATH . 'logs/' . $filename;
    
    if(!file_exists($log_path)) {
        show_404();
    }
    
    $content = file_get_contents($log_path);
    $lines = explode("\n", $content);
    
    // Pagination
    $page = $this->input->get('page') ?? 1;
    $per_page = 100;
    $total = count($lines);
    $offset = ($page - 1) * $per_page;
    
    $data['lines'] = array_slice($lines, $offset, $per_page);
    $data['filename'] = $filename;
    $data['pagination'] = $this->create_pagination($total, $per_page);
    
    $this->load->view('admin/system/view_log', $data);
}

public function search_logs() {
    $keyword = $this->input->post('keyword');
    $level = $this->input->post('level');
    
    // Search logic
    $results = $this->search_in_logs($keyword, $level);
    
    echo json_encode($results);
}

public function download_log($filename) {
    $log_path = APPPATH . 'logs/' . $filename;
    
    if(!file_exists($log_path)) {
        show_404();
    }
    
    force_download($filename, file_get_contents($log_path));
}

public function delete_logs() {
    $days = $this->input->post('days') ?? 90;
    $deleted = $this->cleanup_old_logs($days);
    
    $this->session->set_flashdata('success', "Deleted {$deleted} old log files");
    redirect('admin/system/logs');
}
```

View: application/views/admin/system/logs.php
```html
<div class="card">
    <div class="card-header">
        <h3>System Logs</h3>
        <div class="card-tools">
            <button class="btn btn-danger" onclick="clearLogs()">Clear All</button>
            <button class="btn btn-warning" onclick="archiveLogs()">Archive Old</button>
            <button class="btn btn-primary" onclick="downloadAll()">Download All</button>
        </div>
    </div>
    <div class="card-body">
        <!-- Search & Filter -->
        <div class="row mb-3">
            <div class="col-md-4">
                <input type="text" id="search" class="form-control" placeholder="Search logs...">
            </div>
            <div class="col-md-3">
                <select id="level" class="form-control">
                    <option value="">All Levels</option>
                    <option value="ERROR">ERROR</option>
                    <option value="WARNING">WARNING</option>
                    <option value="INFO">INFO</option>
                    <option value="DEBUG">DEBUG</option>
                </select>
            </div>
            <div class="col-md-3">
                <input type="date" id="date" class="form-control">
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary btn-block" onclick="searchLogs()">Search</button>
            </div>
        </div>
        
        <!-- Log Files Table -->
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>File Name</th>
                    <th>Size</th>
                    <th>Last Modified</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($log_files as $log): ?>
                <tr>
                    <td><?= $log['name'] ?></td>
                    <td><?= format_bytes($log['size']) ?></td>
                    <td><?= date('Y-m-d H:i:s', $log['modified']) ?></td>
                    <td>
                        <a href="<?= site_url('admin/system/view_log/'.$log['name']) ?>" class="btn btn-sm btn-info">View</a>
                        <a href="<?= site_url('admin/system/download_log/'.$log['name']) ?>" class="btn btn-sm btn-primary">Download</a>
                        <button onclick="deleteLog('<?= $log['name'] ?>')" class="btn btn-sm btn-danger">Delete</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
```

DELIVERABLES:
1. System controller with log viewer methods
2. Log listing page with filters
3. Log content viewer with syntax highlighting
4. Search functionality (AJAX)
5. Download and delete functions
6. Auto-cleanup scheduler
7. Permission checks (admin only)

Please implement a professional log viewer with search and management features.
```

---

### PROMPT 2.4: Backup Management UI

**Priority:** MEDIUM 🟡  
**Time:** 6 hours  
**Difficulty:** Medium

```
I need to create a backup and restore management system in the admin panel.

CONTEXT:
- Project: Windeep Finance
- Database: MySQL
- Need: Automated database backups with restore capability

REQUIREMENTS:

1. BACKUP PAGE
   Location: application/controllers/admin/System.php->backup()
   View: application/views/admin/system/backup.php
   
   Features:
   - Manual backup button
   - Scheduled backup configuration
   - Backup history table
   - Download backup files
   - Restore from backup
   - Delete old backups

2. BACKUP PROCESS
   Options:
   - Full database backup
   - Tables selection (custom backup)
   - Include/exclude data
   - Compression (GZIP)
   - Encryption (optional)
   
   Backup File Format:
   - Filename: backup_YYYYMMDD_HHMMSS.sql.gz
   - Location: /application/backups/ (outside public)
   - Metadata: JSON file with backup info
   
   Backup Content:
   - Database structure (CREATE TABLE)
   - All data (INSERT statements)
   - Views, procedures, triggers
   - Foreign key constraints
   - Indexes

3. SCHEDULED BACKUPS
   Configuration:
   - Frequency: Daily/Weekly/Monthly
   - Time: Specific hour (default 2 AM)
   - Retention: Keep last X backups (default 30)
   - Auto-delete: Delete backups older than X days
   - Email notification: Send backup report
   
   Implementation:
   - Create CLI controller for cron job
   - Store schedule in settings table
   - Log all backup operations

4. RESTORE PROCESS
   Steps:
   - Upload backup file OR select from history
   - Validate backup file
   - Show preview (tables, size, date)
   - Confirmation with warning
   - Stop all operations (maintenance mode)
   - Drop existing tables (optional)
   - Execute SQL file
   - Verify restoration
   - Send notification
   
   Safety:
   - Create auto-backup before restore
   - Transaction support
   - Rollback on error
   - Verification checksum

5. BACKUP DASHBOARD
   Metrics:
   - Total backups
   - Total storage used
   - Last backup time
   - Next scheduled backup
   - Backup success rate (chart)
   - Storage trend (chart)

IMPLEMENTATION:

Controller: application/controllers/admin/System.php
```php
public function backup() {
    $this->check_permission('manage_backup');
    
    $data['backups'] = $this->get_backup_history();
    $data['schedule'] = $this->get_backup_schedule();
    $data['disk_usage'] = $this->get_backup_disk_usage();
    
    $this->load->view('admin/system/backup', $data);
}

public function create_backup() {
    // Load database utility
    $this->load->dbutil();
    
    // Backup configuration
    $prefs = [
        'format' => 'zip',
        'filename' => 'backup_' . date('Ymd_His') . '.sql'
    ];
    
    // Create backup
    $backup = $this->dbutil->backup($prefs);
    
    // Save to file
    $backup_path = APPPATH . 'backups/';
    if(!is_dir($backup_path)) {
        mkdir($backup_path, 0755, true);
    }
    
    $filename = $prefs['filename'] . '.zip';
    $filepath = $backup_path . $filename;
    
    write_file($filepath, $backup);
    
    // Save metadata
    $metadata = [
        'filename' => $filename,
        'size' => filesize($filepath),
        'created_at' => date('Y-m-d H:i:s'),
        'created_by' => $this->session->userdata('admin_id'),
        'type' => 'manual'
    ];
    
    $this->db->insert('backups', $metadata);
    
    // Log activity
    $this->log_activity('backup_created', $filename);
    
    // Send notification
    $this->send_backup_notification($metadata);
    
    $this->session->set_flashdata('success', 'Backup created successfully');
    redirect('admin/system/backup');
}

public function restore_backup($backup_id) {
    $backup = $this->db->get_where('backups', ['id' => $backup_id])->row();
    
    if(!$backup) {
        show_404();
    }
    
    $filepath = APPPATH . 'backups/' . $backup->filename;
    
    if(!file_exists($filepath)) {
        $this->session->set_flashdata('error', 'Backup file not found');
        redirect('admin/system/backup');
    }
    
    // Create safety backup before restore
    $this->create_backup();
    
    // Load database forge
    $this->load->dbforge();
    
    // Extract and execute SQL
    try {
        $zip = new ZipArchive;
        $zip->open($filepath);
        $sql = $zip->getFromIndex(0);
        $zip->close();
        
        // Execute SQL statements
        $statements = explode(';', $sql);
        
        $this->db->trans_start();
        
        foreach($statements as $statement) {
            if(trim($statement)) {
                $this->db->query($statement);
            }
        }
        
        $this->db->trans_complete();
        
        if($this->db->trans_status() === FALSE) {
            throw new Exception('Database restore failed');
        }
        
        // Log activity
        $this->log_activity('backup_restored', $backup->filename);
        
        $this->session->set_flashdata('success', 'Database restored successfully');
        
    } catch(Exception $e) {
        $this->session->set_flashdata('error', 'Restore failed: ' . $e->getMessage());
    }
    
    redirect('admin/system/backup');
}

public function download_backup($backup_id) {
    $backup = $this->db->get_where('backups', ['id' => $backup_id])->row();
    
    if(!$backup) {
        show_404();
    }
    
    $filepath = APPPATH . 'backups/' . $backup->filename;
    
    if(!file_exists($filepath)) {
        show_404();
    }
    
    force_download($backup->filename, file_get_contents($filepath));
}

public function delete_backup($backup_id) {
    $backup = $this->db->get_where('backups', ['id' => $backup_id])->row();
    
    if(!$backup) {
        show_404();
    }
    
    // Delete file
    $filepath = APPPATH . 'backups/' . $backup->filename;
    if(file_exists($filepath)) {
        unlink($filepath);
    }
    
    // Delete record
    $this->db->delete('backups', ['id' => $backup_id]);
    
    // Log activity
    $this->log_activity('backup_deleted', $backup->filename);
    
    $this->session->set_flashdata('success', 'Backup deleted successfully');
    redirect('admin/system/backup');
}

public function configure_schedule() {
    $frequency = $this->input->post('frequency'); // daily, weekly, monthly
    $time = $this->input->post('time'); // HH:MM
    $retention = $this->input->post('retention'); // days
    
    $config = [
        'backup_frequency' => $frequency,
        'backup_time' => $time,
        'backup_retention' => $retention
    ];
    
    foreach($config as $key => $value) {
        $this->db->replace('settings', [
            'key' => $key,
            'value' => $value
        ]);
    }
    
    $this->session->set_flashdata('success', 'Backup schedule updated');
    redirect('admin/system/backup');
}
```

CLI Controller: application/controllers/cli/Backup.php
```php
<?php
class Backup extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
        
        // Verify CLI execution
        if(!$this->input->is_cli_request()) {
            show_error('This script can only be accessed via CLI');
        }
    }
    
    public function index() {
        echo "Starting scheduled backup...\n";
        
        $this->load->dbutil();
        
        // Create backup
        $prefs = [
            'format' => 'zip',
            'filename' => 'backup_' . date('Ymd_His') . '.sql'
        ];
        
        $backup = $this->dbutil->backup($prefs);
        
        // Save file
        $backup_path = APPPATH . 'backups/';
        $filename = $prefs['filename'] . '.zip';
        $filepath = $backup_path . $filename;
        
        write_file($filepath, $backup);
        
        // Save metadata
        $metadata = [
            'filename' => $filename,
            'size' => filesize($filepath),
            'created_at' => date('Y-m-d H:i:s'),
            'type' => 'scheduled'
        ];
        
        $this->db->insert('backups', $metadata);
        
        echo "Backup created: {$filename}\n";
        
        // Cleanup old backups
        $this->cleanup_old_backups();
        
        echo "Backup completed successfully!\n";
    }
    
    private function cleanup_old_backups() {
        $retention = $this->db->get_where('settings', ['key' => 'backup_retention'])->row();
        $days = $retention ? $retention->value : 30;
        
        $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        $old_backups = $this->db->where('created_at <', $cutoff_date)
                                 ->where('type', 'scheduled')
                                 ->get('backups')->result();
        
        foreach($old_backups as $backup) {
            // Delete file
            $filepath = APPPATH . 'backups/' . $backup->filename;
            if(file_exists($filepath)) {
                unlink($filepath);
            }
            
            // Delete record
            $this->db->delete('backups', ['id' => $backup->id]);
            
            echo "Deleted old backup: {$backup->filename}\n";
        }
    }
}
```

DATABASE TABLE:
```sql
CREATE TABLE IF NOT EXISTS backups (
    id INT PRIMARY KEY AUTO_INCREMENT,
    filename VARCHAR(255) NOT NULL,
    size BIGINT,
    type ENUM('manual','scheduled') DEFAULT 'manual',
    created_at DATETIME NOT NULL,
    created_by INT,
    notes TEXT,
    INDEX idx_created_at (created_at)
);
```

CRONTAB ENTRY:
```bash
# Daily backup at 2 AM
0 2 * * * cd /path/to/project && php index.php cli/backup >> /var/log/backup.log 2>&1
```

DELIVERABLES:
1. Backup controller with create/restore methods
2. Backup UI with history and actions
3. Scheduled backup CLI controller
4. Cron job configuration
5. Auto-cleanup of old backups
6. Email notifications
7. Test backup and restore process

Please implement a robust backup system with safety measures and proper error handling.
```

---

## 🟢 PHASE 3: ADVANCED FEATURES (80 hours)

### PROMPT 3.1: Multi-Level Loan Approvals

**Priority:** LOW 🟢  
**Time:** 12 hours  
**Difficulty:** Hard

```
I need to implement a multi-level approval workflow for loan applications.

CONTEXT:
- Project: Windeep Finance
- Current: Single-level approval (admin approves/rejects)
- Need: Configurable multi-step approval hierarchy

REQUIREMENTS:

1. APPROVAL HIERARCHY
   Levels:
   - Level 1: Branch Manager (< ₹50,000)
   - Level 2: Regional Manager (₹50,000 - ₹2,00,000)
   - Level 3: CFO (₹2,00,000 - ₹5,00,000)
   - Level 4: CEO (> ₹5,00,000)
   
   Rules:
   - Approval must go through all required levels
   - Each level can approve, reject, or request modifications
   - Rejection at any level cancels entire application
   - Modification request sends back to applicant
   - Timeout: Auto-escalate if pending > 7 days

2. WORKFLOW ENGINE
   States:
   - pending: Awaiting initial review
   - level1_pending: Awaiting Branch Manager
   - level1_approved: Branch Manager approved
   - level2_pending: Awaiting Regional Manager
   - level2_approved: Regional Manager approved
   - level3_pending: Awaiting CFO
   - level3_approved: CFO approved
   - level4_pending: Awaiting CEO
   - approved: Fully approved, ready for disbursement
   - rejected: Rejected at any level
   - modification_requested: Needs applicant changes
   
   Transitions:
   - Each level can move to next level or reject
   - Skip levels if amount below threshold
   - Parallel approvals for committee-based decisions

3. DATABASE SCHEMA
```sql
CREATE TABLE loan_approvals (
    id INT PRIMARY KEY AUTO_INCREMENT,
    application_id INT NOT NULL,
    level INT NOT NULL,
    approver_id INT NOT NULL,
    status ENUM('pending','approved','rejected','modification') NOT NULL,
    comments TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES loan_applications(id),
    FOREIGN KEY (approver_id) REFERENCES admins(id),
    INDEX idx_application (application_id),
    INDEX idx_status (status)
);

CREATE TABLE approval_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    level INT NOT NULL,
    role VARCHAR(50) NOT NULL,
    min_amount DECIMAL(10,2),
    max_amount DECIMAL(10,2),
    timeout_days INT DEFAULT 7,
    is_active TINYINT DEFAULT 1
);

-- Insert default approval hierarchy
INSERT INTO approval_settings (level, role, min_amount, max_amount, timeout_days) VALUES
(1, 'Branch Manager', 0, 50000, 7),
(2, 'Regional Manager', 50001, 200000, 7),
(3, 'CFO', 200001, 500000, 7),
(4, 'CEO', 500001, 999999999, 7);
```

4. IMPLEMENTATION

Controller: application/controllers/admin/Loans.php
```php
public function approve_application($id) {
    $application = $this->Loan_model->get_application($id);
    
    if(!$application) {
        show_404();
    }
    
    // Get current approval level
    $current_level = $this->get_current_approval_level($id);
    
    // Check if user has permission for this level
    if(!$this->can_approve_at_level($current_level)) {
        $this->session->set_flashdata('error', 'You do not have permission to approve at this level');
        redirect('admin/loans/view/' . $id);
    }
    
    // Process approval
    $data = [
        'application_id' => $id,
        'level' => $current_level,
        'approver_id' => $this->session->userdata('admin_id'),
        'status' => 'approved',
        'comments' => $this->input->post('comments')
    ];
    
    $this->db->insert('loan_approvals', $data);
    
    // Check if more approvals needed
    $next_level = $this->get_next_approval_level($application->amount, $current_level);
    
    if($next_level) {
        // Move to next level
        $this->db->update('loan_applications', [
            'status' => "level{$next_level}_pending"
        ], ['id' => $id]);
        
        // Notify next approver
        $this->notify_next_approver($id, $next_level);
        
        $message = "Application approved and sent to Level {$next_level}";
        
    } else {
        // Final approval
        $this->db->update('loan_applications', [
            'status' => 'approved'
        ], ['id' => $id]);
        
        // Create loan record
        $loan_id = $this->create_loan_from_application($id);
        
        // Notify applicant
        $this->notify_applicant_approval($id);
        
        $message = "Application fully approved. Loan #{$loan_id} created.";
    }
    
    $this->session->set_flashdata('success', $message);
    redirect('admin/loans/view/' . $id);
}

private function get_current_approval_level($application_id) {
    $last_approval = $this->db->where('application_id', $application_id)
                               ->where('status', 'approved')
                               ->order_by('level', 'DESC')
                               ->get('loan_approvals')
                               ->row();
    
    return $last_approval ? $last_approval->level + 1 : 1;
}

private function get_next_approval_level($amount, $current_level) {
    $next_level = $this->db->where('level >', $current_level)
                           ->where('min_amount <=', $amount)
                           ->where('max_amount >=', $amount)
                           ->where('is_active', 1)
                           ->order_by('level', 'ASC')
                           ->get('approval_settings')
                           ->row();
    
    return $next_level ? $next_level->level : null;
}

private function can_approve_at_level($level) {
    $user_role = $this->session->userdata('role');
    
    $setting = $this->db->where('level', $level)
                        ->get('approval_settings')
                        ->row();
    
    return $setting && $setting->role === $user_role;
}

private function notify_next_approver($application_id, $level) {
    // Get users with required role
    $setting = $this->db->where('level', $level)->get('approval_settings')->row();
    
    $approvers = $this->db->where('role', $setting->role)
                          ->where('is_active', 1)
                          ->get('admins')
                          ->result();
    
    // Send email/notification
    foreach($approvers as $approver) {
        $this->email_service->send_approval_notification($approver->email, $application_id, $level);
        $this->create_notification($approver->id, "New loan application pending approval", $application_id);
    }
}

public function reject_application($id) {
    $level = $this->get_current_approval_level($id);
    
    // Record rejection
    $this->db->insert('loan_approvals', [
        'application_id' => $id,
        'level' => $level,
        'approver_id' => $this->session->userdata('admin_id'),
        'status' => 'rejected',
        'comments' => $this->input->post('rejection_reason')
    ]);
    
    // Update application status
    $this->db->update('loan_applications', [
        'status' => 'rejected'
    ], ['id' => $id]);
    
    // Notify applicant
    $this->notify_applicant_rejection($id);
    
    $this->session->set_flashdata('success', 'Application rejected');
    redirect('admin/loans/pending');
}

public function request_modification($id) {
    $level = $this->get_current_approval_level($id);
    
    // Record modification request
    $this->db->insert('loan_approvals', [
        'application_id' => $id,
        'level' => $level,
        'approver_id' => $this->session->userdata('admin_id'),
        'status' => 'modification',
        'comments' => $this->input->post('modification_notes')
    ]);
    
    // Update application status
    $this->db->update('loan_applications', [
        'status' => 'modification_requested'
    ], ['id' => $id]);
    
    // Notify applicant
    $this->notify_applicant_modification($id);
    
    $this->session->set_flashdata('success', 'Modification requested');
    redirect('admin/loans/view/' . $id);
}
```

5. APPROVAL TIMELINE VIEW
   Display:
   - Vertical timeline showing all approval steps
   - Each level with status (pending/approved/rejected)
   - Approver name and timestamp
   - Comments from each approver
   - Current pending level highlighted
   - Estimated completion time

6. AUTO-ESCALATION (Cron Job)
   File: application/controllers/cli/Loan_escalation.php
   
   Logic:
   - Find applications pending > timeout_days at any level
   - Send reminder to current approver
   - If still pending after 2 reminders, escalate to next level
   - Log all escalations

DELIVERABLES:
1. Database tables for approval workflow
2. Multi-level approval controller methods
3. Approval timeline view
4. Settings page for approval hierarchy configuration
5. Email notifications for each level
6. Auto-escalation cron job
7. Test with different loan amounts

Please implement a flexible approval workflow with proper notifications and audit trail.
```

---

### PROMPT 3.2: Loan Restructuring Module

**Priority:** LOW 🟢  
**Time:** 16 hours  
**Difficulty:** Very Hard

```
I need to implement loan restructuring functionality for existing loans.

CONTEXT:
- Project: Windeep Finance
- Need: Allow modifying existing loan terms due to member hardship or business decisions
- Examples: Extend tenor, reduce EMI, change interest rate

REQUIREMENTS:

1. RESTRUCTURING TYPES
   Types:
   - Tenure Extension: Increase loan period, reduce EMI
   - Interest Rate Change: Modify interest rate
   - EMI Holiday: Skip payments for X months
   - Principal Moratorium: Pay only interest for X months
   - Full Restructure: Change all terms

2. BUSINESS RULES
   Eligibility:
   - Loan must be active (not closed/matured)
   - Minimum 3 payments made
   - No default > 90 days (unless special approval)
   - Maximum 1 restructure per loan
   - Member must request (not auto)
   
   Impact Calculation:
   - Recalculate interest on outstanding principal
   - Generate new installment schedule
   - Update loan maturity date
   - Adjust ledger entries
   - Track restructuring charges (if any)

3. DATABASE SCHEMA
```sql
CREATE TABLE loan_restructures (
    id INT PRIMARY KEY AUTO_INCREMENT,
    loan_id INT NOT NULL,
    requested_by INT NOT NULL,
    requested_date DATE NOT NULL,
    restructure_type ENUM('tenure_extension','rate_change','emi_holiday','principal_moratorium','full_restructure') NOT NULL,
    
    -- Original Terms
    original_amount DECIMAL(12,2),
    original_interest_rate DECIMAL(5,2),
    original_tenor INT,
    original_emi DECIMAL(10,2),
    original_maturity_date DATE,
    outstanding_principal DECIMAL(12,2),
    
    -- New Terms
    new_interest_rate DECIMAL(5,2),
    new_tenor INT,
    new_emi DECIMAL(10,2),
    new_maturity_date DATE,
    moratorium_months INT DEFAULT 0,
    
    -- Charges
    restructuring_fee DECIMAL(10,2) DEFAULT 0,
    legal_charges DECIMAL(10,2) DEFAULT 0,
    
    -- Approval
    status ENUM('pending','approved','rejected') DEFAULT 'pending',
    approved_by INT,
    approved_date DATE,
    rejection_reason TEXT,
    
    -- Documentation
    reason_for_restructure TEXT NOT NULL,
    supporting_documents VARCHAR(500),
    terms_and_conditions TEXT,
    member_acceptance TINYINT DEFAULT 0,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (loan_id) REFERENCES loans(id),
    FOREIGN KEY (requested_by) REFERENCES members(id),
    FOREIGN KEY (approved_by) REFERENCES admins(id),
    INDEX idx_loan (loan_id),
    INDEX idx_status (status)
);

-- Track old installments (for reference)
CREATE TABLE loan_restructure_old_schedule (
    id INT PRIMARY KEY AUTO_INCREMENT,
    restructure_id INT NOT NULL,
    installment_id INT NOT NULL,
    installment_number INT,
    due_date DATE,
    principal DECIMAL(10,2),
    interest DECIMAL(10,2),
    emi DECIMAL(10,2),
    paid_amount DECIMAL(10,2),
    status VARCHAR(50),
    FOREIGN KEY (restructure_id) REFERENCES loan_restructures(id),
    FOREIGN KEY (installment_id) REFERENCES installments(id)
);
```

4. IMPLEMENTATION

Controller: application/controllers/admin/Loans.php
```php
public function restructure($loan_id) {
    $loan = $this->Loan_model->get_loan($loan_id);
    
    if(!$loan) {
        show_404();
    }
    
    // Check eligibility
    $eligibility = $this->check_restructure_eligibility($loan_id);
    
    if(!$eligibility['eligible']) {
        $this->session->set_flashdata('error', $eligibility['reason']);
        redirect('admin/loans/view/' . $loan_id);
    }
    
    $data['loan'] = $loan;
    $data['outstanding'] = $this->calculate_outstanding($loan_id);
    $data['paid_installments'] = $this->count_paid_installments($loan_id);
    $data['restructure_types'] = $this->get_restructure_types();
    
    $this->load->view('admin/loans/restructure', $data);
}

private function check_restructure_eligibility($loan_id) {
    $loan = $this->Loan_model->get_loan($loan_id);
    
    // Check if already restructured
    $existing = $this->db->where('loan_id', $loan_id)
                         ->where('status', 'approved')
                         ->get('loan_restructures')
                         ->num_rows();
    
    if($existing > 0) {
        return [
            'eligible' => false,
            'reason' => 'Loan already restructured once'
        ];
    }
    
    // Check loan status
    if(!in_array($loan->status, ['active', 'overdue'])) {
        return [
            'eligible' => false,
            'reason' => 'Loan must be active or overdue'
        ];
    }
    
    // Check minimum payments
    $paid_count = $this->count_paid_installments($loan_id);
    if($paid_count < 3) {
        return [
            'eligible' => false,
            'reason' => 'Minimum 3 installments must be paid'
        ];
    }
    
    // Check NPA status
    $overdue_days = $this->calculate_overdue_days($loan_id);
    if($overdue_days > 90) {
        return [
            'eligible' => false,
            'reason' => 'Loan is NPA (90+ days overdue). Special approval required.'
        ];
    }
    
    return ['eligible' => true];
}

public function calculate_restructure() {
    $loan_id = $this->input->post('loan_id');
    $type = $this->input->post('restructure_type');
    
    $loan = $this->Loan_model->get_loan($loan_id);
    $outstanding = $this->calculate_outstanding($loan_id);
    
    $result = [];
    
    switch($type) {
        case 'tenure_extension':
            $new_tenor = $this->input->post('new_tenor');
            $result = $this->calculate_tenure_extension($loan, $outstanding, $new_tenor);
            break;
            
        case 'rate_change':
            $new_rate = $this->input->post('new_interest_rate');
            $result = $this->calculate_rate_change($loan, $outstanding, $new_rate);
            break;
            
        case 'emi_holiday':
            $holiday_months = $this->input->post('holiday_months');
            $result = $this->calculate_emi_holiday($loan, $outstanding, $holiday_months);
            break;
            
        case 'principal_moratorium':
            $moratorium_months = $this->input->post('moratorium_months');
            $result = $this->calculate_principal_moratorium($loan, $outstanding, $moratorium_months);
            break;
    }
    
    // Add restructuring charges
    $result['restructuring_fee'] = $this->calculate_restructuring_fee($loan);
    $result['legal_charges'] = 500; // Flat charge
    
    echo json_encode($result);
}

private function calculate_tenure_extension($loan, $outstanding, $new_tenor) {
    $remaining_tenor = $loan->tenor - $this->count_paid_installments($loan->id);
    
    if($new_tenor <= $remaining_tenor) {
        return ['error' => 'New tenor must be greater than remaining tenor'];
    }
    
    // Calculate new EMI
    $monthly_rate = $loan->interest_rate / 12 / 100;
    
    if($loan->interest_type == 'reducing') {
        $new_emi = ($outstanding * $monthly_rate * pow(1 + $monthly_rate, $new_tenor)) / 
                   (pow(1 + $monthly_rate, $new_tenor) - 1);
    } else {
        // Flat interest
        $total_interest = ($outstanding * $loan->interest_rate * $new_tenor) / (12 * 100);
        $new_emi = ($outstanding + $total_interest) / $new_tenor;
    }
    
    $old_emi = $loan->emi_amount;
    $saving_per_month = $old_emi - $new_emi;
    $total_new_payment = $new_emi * $new_tenor;
    $additional_interest = $total_new_payment - $outstanding;
    
    $new_maturity = date('Y-m-d', strtotime("+{$new_tenor} months"));
    
    return [
        'new_emi' => round($new_emi, 2),
        'old_emi' => round($old_emi, 2),
        'saving_per_month' => round($saving_per_month, 2),
        'new_tenor' => $new_tenor,
        'new_maturity_date' => $new_maturity,
        'total_payment' => round($total_new_payment, 2),
        'additional_interest' => round($additional_interest, 2)
    ];
}

private function calculate_rate_change($loan, $outstanding, $new_rate) {
    $remaining_tenor = $loan->tenor - $this->count_paid_installments($loan->id);
    
    $monthly_rate = $new_rate / 12 / 100;
    
    if($loan->interest_type == 'reducing') {
        $new_emi = ($outstanding * $monthly_rate * pow(1 + $monthly_rate, $remaining_tenor)) / 
                   (pow(1 + $monthly_rate, $remaining_tenor) - 1);
    } else {
        $total_interest = ($outstanding * $new_rate * $remaining_tenor) / (12 * 100);
        $new_emi = ($outstanding + $total_interest) / $remaining_tenor;
    }
    
    $old_emi = $loan->emi_amount;
    $difference = $new_emi - $old_emi;
    $total_impact = $difference * $remaining_tenor;
    
    return [
        'new_emi' => round($new_emi, 2),
        'old_emi' => round($old_emi, 2),
        'new_interest_rate' => $new_rate,
        'old_interest_rate' => $loan->interest_rate,
        'emi_difference' => round($difference, 2),
        'total_impact' => round($total_impact, 2),
        'remaining_tenor' => $remaining_tenor
    ];
}

public function save_restructure() {
    $this->form_validation->set_rules('loan_id', 'Loan', 'required|integer');
    $this->form_validation->set_rules('restructure_type', 'Restructure Type', 'required');
    $this->form_validation->set_rules('reason', 'Reason', 'required|min_length[50]');
    
    if($this->form_validation->run() == FALSE) {
        $this->session->set_flashdata('error', validation_errors());
        redirect($this->input->post('redirect'));
        return;
    }
    
    $loan_id = $this->input->post('loan_id');
    $loan = $this->Loan_model->get_loan($loan_id);
    $outstanding = $this->calculate_outstanding($loan_id);
    
    // Prepare restructure data
    $data = [
        'loan_id' => $loan_id,
        'requested_by' => $loan->member_id,
        'requested_date' => date('Y-m-d'),
        'restructure_type' => $this->input->post('restructure_type'),
        
        // Original terms
        'original_amount' => $loan->amount,
        'original_interest_rate' => $loan->interest_rate,
        'original_tenor' => $loan->tenor,
        'original_emi' => $loan->emi_amount,
        'original_maturity_date' => $loan->maturity_date,
        'outstanding_principal' => $outstanding,
        
        // New terms (from calculation)
        'new_interest_rate' => $this->input->post('new_interest_rate') ?? $loan->interest_rate,
        'new_tenor' => $this->input->post('new_tenor') ?? $loan->tenor,
        'new_emi' => $this->input->post('new_emi'),
        'new_maturity_date' => $this->input->post('new_maturity_date'),
        'moratorium_months' => $this->input->post('moratorium_months') ?? 0,
        
        // Charges
        'restructuring_fee' => $this->input->post('restructuring_fee') ?? 0,
        'legal_charges' => $this->input->post('legal_charges') ?? 0,
        
        // Documentation
        'reason_for_restructure' => $this->input->post('reason'),
        'status' => 'pending'
    ];
    
    // Upload supporting documents
    if($_FILES['documents']['name']) {
        $upload_result = $this->upload_restructure_documents($loan_id);
        $data['supporting_documents'] = json_encode($upload_result);
    }
    
    $this->db->trans_start();
    
    // Insert restructure request
    $this->db->insert('loan_restructures', $data);
    $restructure_id = $this->db->insert_id();
    
    // Backup old schedule
    $this->backup_old_schedule($restructure_id, $loan_id);
    
    // Update loan status
    $this->db->update('loans', [
        'status' => 'restructure_pending'
    ], ['id' => $loan_id]);
    
    $this->db->trans_complete();
    
    if($this->db->trans_status() === FALSE) {
        $this->session->set_flashdata('error', 'Failed to save restructure request');
    } else {
        // Notify approvers
        $this->notify_restructure_request($restructure_id);
        
        $this->session->set_flashdata('success', 'Restructure request submitted for approval');
    }
    
    redirect('admin/loans/view/' . $loan_id);
}

public function approve_restructure($restructure_id) {
    $restructure = $this->db->get_where('loan_restructures', ['id' => $restructure_id])->row();
    
    if(!$restructure || $restructure->status != 'pending') {
        $this->session->set_flashdata('error', 'Invalid restructure request');
        redirect('admin/loans/restructures');
        return;
    }
    
    $this->db->trans_start();
    
    // Update restructure status
    $this->db->update('loan_restructures', [
        'status' => 'approved',
        'approved_by' => $this->session->userdata('admin_id'),
        'approved_date' => date('Y-m-d')
    ], ['id' => $restructure_id]);
    
    // Update loan with new terms
    $this->db->update('loans', [
        'interest_rate' => $restructure->new_interest_rate,
        'tenor' => $restructure->new_tenor,
        'emi_amount' => $restructure->new_emi,
        'maturity_date' => $restructure->new_maturity_date,
        'status' => 'active',
        'is_restructured' => 1
    ], ['id' => $restructure->loan_id]);
    
    // Delete future unpaid installments
    $this->db->delete('installments', [
        'loan_id' => $restructure->loan_id,
        'status' => 'pending'
    ]);
    
    // Generate new installment schedule
    $this->generate_new_installment_schedule($restructure);
    
    // Record restructuring charges
    if($restructure->restructuring_fee > 0 || $restructure->legal_charges > 0) {
        $total_charges = $restructure->restructuring_fee + $restructure->legal_charges;
        
        $this->db->insert('fines', [
            'loan_id' => $restructure->loan_id,
            'member_id' => $restructure->requested_by,
            'type' => 'restructure_charges',
            'amount' => $total_charges,
            'reason' => 'Loan restructuring charges',
            'status' => 'pending',
            'due_date' => date('Y-m-d')
        ]);
    }
    
    $this->db->trans_complete();
    
    if($this->db->trans_status() === FALSE) {
        $this->session->set_flashdata('error', 'Failed to approve restructure');
    } else {
        // Notify member
        $this->notify_restructure_approval($restructure_id);
        
        $this->session->set_flashdata('success', 'Loan restructured successfully');
    }
    
    redirect('admin/loans/view/' . $restructure->loan_id);
}

private function generate_new_installment_schedule($restructure) {
    $loan_id = $restructure->loan_id;
    $new_emi = $restructure->new_emi;
    $new_tenor = $restructure->new_tenor;
    $outstanding = $restructure->outstanding_principal;
    
    // Get last paid installment date
    $last_paid = $this->db->where('loan_id', $loan_id)
                          ->where('status', 'paid')
                          ->order_by('due_date', 'DESC')
                          ->get('installments')
                          ->row();
    
    $start_date = $last_paid ? $last_paid->due_date : date('Y-m-d');
    
    // Generate installments
    $installments = $this->Loan_model->generate_installment_schedule(
        $loan_id,
        $outstanding,
        $restructure->new_interest_rate,
        $new_tenor,
        $new_emi,
        $start_date
    );
    
    $this->db->insert_batch('installments', $installments);
}
```

5. VIEWS
   - Restructure request form
   - Impact calculator (AJAX)
   - Comparison table (old vs new terms)
   - Approval interface
   - Restructure history

DELIVERABLES:
1. Database tables for restructuring
2. Restructure controller with all methods
3. Restructure request form with calculator
4. Approval workflow
5. New installment schedule generation
6. Email notifications
7. Detailed restructure report
8. Test with various scenarios

Please implement comprehensive loan restructuring with proper validations and audit trail.
```

---

### PROMPT 3.3: Staff Performance Tracking

**Priority:** LOW 🟢  
**Time:** 12 hours  
**Difficulty:** Medium

```
I need to implement a staff performance tracking module to monitor collection efficiency and targets.

CONTEXT:
- Project: Windeep Finance
- Purpose: Track collection staff performance, set targets, calculate incentives
- KPIs: Collection amount, member onboarding, loan disbursal, recovery rate

REQUIREMENTS:

1. PERFORMANCE METRICS
   KPIs:
   - Collection Amount (daily/monthly)
   - Number of loans disbursed
   - Members onboarded
   - Recovery rate (% of overdue collected)
   - Customer satisfaction score
   - Portfolio quality (NPA %)
   
   Calculations:
   - Achievement % = (Actual / Target) × 100
   - Efficiency Score = Weighted average of all KPIs
   - Incentive Amount = Base + (Achievement % × Variable)

2. TARGET SETTING
   Types:
   - Individual targets
   - Team targets
   - Branch targets
   
   Frequency:
   - Daily
   - Weekly
   - Monthly
   - Quarterly
   
   Configuration:
   - Set targets by role
   - Set targets by region
   - Set targets per staff member
   - Auto-rollover if not achieved

3. DATABASE SCHEMA
```sql
CREATE TABLE staff_targets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    staff_id INT NOT NULL,
    target_type ENUM('collection','disbursement','onboarding','recovery') NOT NULL,
    target_amount DECIMAL(12,2),
    target_count INT,
    period_type ENUM('daily','weekly','monthly','quarterly') NOT NULL,
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    status ENUM('active','achieved','missed','cancelled') DEFAULT 'active',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES admins(id),
    INDEX idx_staff (staff_id),
    INDEX idx_period (period_start, period_end)
);

CREATE TABLE staff_performance (
    id INT PRIMARY KEY AUTO_INCREMENT,
    staff_id INT NOT NULL,
    date DATE NOT NULL,
    
    -- Collections
    collection_amount DECIMAL(12,2) DEFAULT 0,
    collection_count INT DEFAULT 0,
    
    -- Disbursements
    disbursement_amount DECIMAL(12,2) DEFAULT 0,
    disbursement_count INT DEFAULT 0,
    
    -- Onboarding
    members_onboarded INT DEFAULT 0,
    
    -- Recovery
    overdue_collected DECIMAL(12,2) DEFAULT 0,
    overdue_target DECIMAL(12,2) DEFAULT 0,
    recovery_rate DECIMAL(5,2) DEFAULT 0,
    
    -- Portfolio
    active_loans INT DEFAULT 0,
    npa_loans INT DEFAULT 0,
    portfolio_quality DECIMAL(5,2) DEFAULT 0,
    
    -- Scores
    efficiency_score DECIMAL(5,2) DEFAULT 0,
    customer_satisfaction DECIMAL(3,2) DEFAULT 0,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES admins(id),
    UNIQUE KEY unique_staff_date (staff_id, date),
    INDEX idx_date (date)
);

CREATE TABLE staff_incentives (
    id INT PRIMARY KEY AUTO_INCREMENT,
    staff_id INT NOT NULL,
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    
    -- Achievements
    target_achievement_percent DECIMAL(5,2),
    efficiency_score DECIMAL(5,2),
    
    -- Incentive Calculation
    base_incentive DECIMAL(10,2),
    variable_incentive DECIMAL(10,2),
    bonus DECIMAL(10,2) DEFAULT 0,
    penalty DECIMAL(10,2) DEFAULT 0,
    total_incentive DECIMAL(10,2),
    
    -- Payment
    status ENUM('pending','approved','paid') DEFAULT 'pending',
    approved_by INT,
    paid_date DATE,
    
    remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (staff_id) REFERENCES admins(id),
    INDEX idx_staff (staff_id),
    INDEX idx_status (status)
);
```

4. IMPLEMENTATION

Controller: application/controllers/admin/Staff_performance.php
```php
<?php
class Staff_performance extends Admin_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->model('Performance_model');
    }
    
    public function dashboard() {
        $staff_id = $this->input->get('staff_id') ?? $this->session->userdata('admin_id');
        $period = $this->input->get('period') ?? 'month';
        
        $data['staff'] = $this->get_staff($staff_id);
        $data['period'] = $period;
        
        // Get targets
        $data['targets'] = $this->Performance_model->get_active_targets($staff_id, $period);
        
        // Get achievements
        $data['achievements'] = $this->Performance_model->get_period_performance($staff_id, $period);
        
        // Calculate scores
        $data['scores'] = $this->calculate_performance_scores($staff_id, $period);
        
        // Get trends
        $data['trends'] = $this->get_performance_trends($staff_id);
        
        // Leaderboard
        $data['leaderboard'] = $this->get_team_leaderboard($period);
        
        $this->load->view('admin/performance/dashboard', $data);
    }
    
    public function set_targets() {
        if($this->input->method() == 'post') {
            $this->save_targets();
            return;
        }
        
        $data['staff_list'] = $this->get_collection_staff();
        $data['target_types'] = ['collection', 'disbursement', 'onboarding', 'recovery'];
        
        $this->load->view('admin/performance/set_targets', $data);
    }
    
    private function save_targets() {
        $staff_ids = $this->input->post('staff_ids');
        $target_type = $this->input->post('target_type');
        $target_amount = $this->input->post('target_amount');
        $target_count = $this->input->post('target_count');
        $period_type = $this->input->post('period_type');
        $period_start = $this->input->post('period_start');
        $period_end = $this->input->post('period_end');
        
        $targets = [];
        
        foreach($staff_ids as $staff_id) {
            $targets[] = [
                'staff_id' => $staff_id,
                'target_type' => $target_type,
                'target_amount' => $target_amount,
                'target_count' => $target_count,
                'period_type' => $period_type,
                'period_start' => $period_start,
                'period_end' => $period_end,
                'created_by' => $this->session->userdata('admin_id')
            ];
        }
        
        $this->db->insert_batch('staff_targets', $targets);
        
        // Notify staff
        foreach($staff_ids as $staff_id) {
            $this->notify_target_assigned($staff_id);
        }
        
        $this->session->set_flashdata('success', 'Targets set successfully');
        redirect('admin/staff_performance/set_targets');
    }
    
    public function update_performance() {
        // This runs daily via cron
        
        $date = date('Y-m-d');
        $staff_list = $this->get_collection_staff();
        
        foreach($staff_list as $staff) {
            $performance = $this->calculate_daily_performance($staff->id, $date);
            
            // Insert or update
            $this->db->replace('staff_performance', [
                'staff_id' => $staff->id,
                'date' => $date,
                'collection_amount' => $performance['collection_amount'],
                'collection_count' => $performance['collection_count'],
                'disbursement_amount' => $performance['disbursement_amount'],
                'disbursement_count' => $performance['disbursement_count'],
                'members_onboarded' => $performance['members_onboarded'],
                'overdue_collected' => $performance['overdue_collected'],
                'overdue_target' => $performance['overdue_target'],
                'recovery_rate' => $performance['recovery_rate'],
                'active_loans' => $performance['active_loans'],
                'npa_loans' => $performance['npa_loans'],
                'portfolio_quality' => $performance['portfolio_quality'],
                'efficiency_score' => $this->calculate_efficiency_score($performance)
            ]);
        }
        
        echo "Performance updated for " . count($staff_list) . " staff members\n";
    }
    
    private function calculate_daily_performance($staff_id, $date) {
        // Collection amount
        $collection = $this->db->select_sum('amount')
                               ->where('collected_by', $staff_id)
                               ->where('DATE(payment_date)', $date)
                               ->where('payment_type !=', 'fine')
                               ->get('payments')
                               ->row();
        
        $collection_amount = $collection ? $collection->amount : 0;
        
        // Collection count
        $collection_count = $this->db->where('collected_by', $staff_id)
                                     ->where('DATE(payment_date)', $date)
                                     ->get('payments')
                                     ->num_rows();
        
        // Disbursement amount
        $disbursement = $this->db->select_sum('amount')
                                 ->where('disbursed_by', $staff_id)
                                 ->where('DATE(disbursement_date)', $date)
                                 ->get('loans')
                                 ->row();
        
        $disbursement_amount = $disbursement ? $disbursement->amount : 0;
        
        // Disbursement count
        $disbursement_count = $this->db->where('disbursed_by', $staff_id)
                                       ->where('DATE(disbursement_date)', $date)
                                       ->get('loans')
                                       ->num_rows();
        
        // Members onboarded
        $members_onboarded = $this->db->where('created_by', $staff_id)
                                      ->where('DATE(created_at)', $date)
                                      ->get('members')
                                      ->num_rows();
        
        // Recovery calculation
        // Get overdue payments collected
        $overdue_collected = $this->db->select_sum('p.amount')
                                      ->from('payments p')
                                      ->join('installments i', 'i.id = p.installment_id')
                                      ->where('p.collected_by', $staff_id)
                                      ->where('DATE(p.payment_date)', $date)
                                      ->where('i.due_date <', $date)
                                      ->get()
                                      ->row()
                                      ->amount ?? 0;
        
        // Get total overdue as of today
        $overdue_target = $this->db->select_sum('i.emi_amount - i.paid_amount')
                                   ->from('installments i')
                                   ->join('loans l', 'l.id = i.loan_id')
                                   ->where('l.disbursed_by', $staff_id)
                                   ->where('i.due_date <', $date)
                                   ->where('i.status', 'pending')
                                   ->get()
                                   ->row()
                                   ->amount ?? 0;
        
        $recovery_rate = $overdue_target > 0 ? ($overdue_collected / $overdue_target) * 100 : 0;
        
        // Portfolio quality
        $active_loans = $this->db->where('disbursed_by', $staff_id)
                                 ->where('status', 'active')
                                 ->get('loans')
                                 ->num_rows();
        
        $npa_loans = $this->db->where('disbursed_by', $staff_id)
                              ->where('status', 'npa')
                              ->get('loans')
                              ->num_rows();
        
        $portfolio_quality = $active_loans > 0 ? (($active_loans - $npa_loans) / $active_loans) * 100 : 100;
        
        return [
            'collection_amount' => $collection_amount,
            'collection_count' => $collection_count,
            'disbursement_amount' => $disbursement_amount,
            'disbursement_count' => $disbursement_count,
            'members_onboarded' => $members_onboarded,
            'overdue_collected' => $overdue_collected,
            'overdue_target' => $overdue_target,
            'recovery_rate' => round($recovery_rate, 2),
            'active_loans' => $active_loans,
            'npa_loans' => $npa_loans,
            'portfolio_quality' => round($portfolio_quality, 2)
        ];
    }
    
    private function calculate_efficiency_score($performance) {
        // Weighted average of key metrics
        $weights = [
            'collection' => 0.30,
            'disbursement' => 0.20,
            'recovery' => 0.25,
            'portfolio_quality' => 0.25
        ];
        
        $score = 0;
        
        // Collection score (assuming target of ₹50,000/day)
        $collection_score = min(($performance['collection_amount'] / 50000) * 100, 100);
        $score += $collection_score * $weights['collection'];
        
        // Disbursement score (assuming target of 2 loans/day)
        $disbursement_score = min(($performance['disbursement_count'] / 2) * 100, 100);
        $score += $disbursement_score * $weights['disbursement'];
        
        // Recovery score
        $score += $performance['recovery_rate'] * $weights['recovery'];
        
        // Portfolio quality score
        $score += $performance['portfolio_quality'] * $weights['portfolio_quality'];
        
        return round($score, 2);
    }
    
    public function calculate_incentives() {
        // Run monthly to calculate incentives
        
        $period_start = date('Y-m-01', strtotime('last month'));
        $period_end = date('Y-m-t', strtotime('last month'));
        
        $staff_list = $this->get_collection_staff();
        
        foreach($staff_list as $staff) {
            $this->calculate_staff_incentive($staff->id, $period_start, $period_end);
        }
        
        echo "Incentives calculated for period: {$period_start} to {$period_end}\n";
    }
    
    private function calculate_staff_incentive($staff_id, $period_start, $period_end) {
        // Get targets
        $targets = $this->db->where('staff_id', $staff_id)
                            ->where('period_start', $period_start)
                            ->where('period_end', $period_end)
                            ->get('staff_targets')
                            ->result();
        
        // Get achievements
        $achievements = $this->db->select('
                SUM(collection_amount) as total_collection,
                SUM(disbursement_amount) as total_disbursement,
                SUM(members_onboarded) as total_onboarding,
                AVG(recovery_rate) as avg_recovery_rate,
                AVG(efficiency_score) as avg_efficiency
            ')
            ->where('staff_id', $staff_id)
            ->where('date >=', $period_start)
            ->where('date <=', $period_end)
            ->get('staff_performance')
            ->row();
        
        if(!$achievements) {
            return;
        }
        
        // Calculate achievement percentage for each target
        $achievement_scores = [];
        
        foreach($targets as $target) {
            $actual = 0;
            
            switch($target->target_type) {
                case 'collection':
                    $actual = $achievements->total_collection;
                    break;
                case 'disbursement':
                    $actual = $achievements->total_disbursement;
                    break;
                case 'onboarding':
                    $actual = $achievements->total_onboarding;
                    break;
                case 'recovery':
                    $actual = $achievements->avg_recovery_rate;
                    break;
            }
            
            $achievement_pct = ($actual / $target->target_amount) * 100;
            $achievement_scores[] = $achievement_pct;
            
            // Update target status
            if($achievement_pct >= 100) {
                $this->db->update('staff_targets', ['status' => 'achieved'], ['id' => $target->id]);
            } else {
                $this->db->update('staff_targets', ['status' => 'missed'], ['id' => $target->id]);
            }
        }
        
        // Average achievement
        $avg_achievement = count($achievement_scores) > 0 ? array_sum($achievement_scores) / count($achievement_scores) : 0;
        
        // Incentive calculation
        $base_incentive = 5000; // Base ₹5,000
        $variable_incentive = 0;
        
        if($avg_achievement >= 100) {
            $variable_incentive = $base_incentive * 2; // 200% if target achieved
        } else if($avg_achievement >= 80) {
            $variable_incentive = $base_incentive * 1; // 100% if 80%+ achieved
        } else if($avg_achievement >= 60) {
            $variable_incentive = $base_incentive * 0.5; // 50% if 60%+ achieved
        }
        
        // Bonus for exceptional performance
        $bonus = 0;
        if($avg_achievement >= 120) {
            $bonus = 3000;
        }
        
        // Penalty for poor portfolio quality
        $penalty = 0;
        if($achievements->avg_efficiency < 60) {
            $penalty = 1000;
        }
        
        $total_incentive = $base_incentive + $variable_incentive + $bonus - $penalty;
        
        // Save incentive record
        $this->db->insert('staff_incentives', [
            'staff_id' => $staff_id,
            'period_start' => $period_start,
            'period_end' => $period_end,
            'target_achievement_percent' => round($avg_achievement, 2),
            'efficiency_score' => round($achievements->avg_efficiency, 2),
            'base_incentive' => $base_incentive,
            'variable_incentive' => $variable_incentive,
            'bonus' => $bonus,
            'penalty' => $penalty,
            'total_incentive' => $total_incentive,
            'status' => 'pending'
        ]);
        
        // Notify staff
        $this->notify_incentive_calculated($staff_id, $total_incentive);
    }
    
    public function leaderboard() {
        $period = $this->input->get('period') ?? 'month';
        
        $data['period'] = $period;
        $data['leaderboard'] = $this->get_team_leaderboard($period);
        
        $this->load->view('admin/performance/leaderboard', $data);
    }
    
    private function get_team_leaderboard($period) {
        $date_condition = $this->get_period_condition($period);
        
        return $this->db->select('
                sp.staff_id,
                a.name as staff_name,
                SUM(sp.collection_amount) as total_collection,
                SUM(sp.disbursement_amount) as total_disbursement,
                SUM(sp.members_onboarded) as total_onboarding,
                AVG(sp.efficiency_score) as avg_efficiency_score,
                AVG(sp.recovery_rate) as avg_recovery_rate
            ')
            ->from('staff_performance sp')
            ->join('admins a', 'a.id = sp.staff_id')
            ->where($date_condition)
            ->group_by('sp.staff_id')
            ->order_by('avg_efficiency_score', 'DESC')
            ->limit(10)
            ->get()
            ->result();
    }
}
```

5. VIEWS & DASHBOARDS
   - Individual performance dashboard
   - Team leaderboard
   - Target vs achievement charts
   - Incentive calculator
   - Performance trends (Chart.js)
   - Drill-down reports

DELIVERABLES:
1. Database tables for performance tracking
2. Performance controller with calculations
3. Target setting interface
4. Performance dashboard (individual & team)
5. Leaderboard view
6. Incentive calculation logic
7. Daily performance update cron job
8. Monthly incentive calculation job
9. Email notifications for targets and incentives
10. Performance reports (PDF/Excel)

Please implement comprehensive staff performance tracking with gamification elements.
```

---

## 📝 USAGE SUMMARY

### Phase 1 (Critical) - 12 hours
- Deploy immediately for production stability
- Focus: Automation and validation
- Impact: HIGH - System reliability

### Phase 2 (Important) - 24 hours
- Complete within first month of production
- Focus: Feature completeness and admin tools
- Impact: MEDIUM - Operational efficiency

### Phase 3 (Advanced) - 80 hours
- Plan for next quarter
- Focus: Advanced workflows and mobile
- Impact: LOW - Competitive advantage

---

## ✅ SUCCESS CRITERIA

Each implementation should:
1. Pass all validation tests
2. Have comprehensive error handling
3. Include audit logging
4. Send appropriate notifications
5. Have user-friendly UI
6. Be documented
7. Have test cases

---

**Generated:** January 22, 2026  
**Total Prompts:** 12  
**Total Effort:** 116 hours  
**Priority:** Phase 1 > Phase 2 > Phase 3

**Next Step:** Start with PROMPT 1.1 (Schedule Interest Calculation)
