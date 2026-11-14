# Check Collection & Verification System - Integration Guide

## Overview
**Admin-Only Check Collection Workflow**
- Members apply for loans and add guarantors (NO check details on member side)
- Admins collect physical check proof from applicants and guarantors
- Admins manually enter check details in the admin panel
- System tracks all checks with verification status and audit trail

All information is stored in database for record-keeping and verification purposes.

## Files Created

### 1. Database Table ✅
**File:** `database/add_check_details_table.sql`
- Creates `loan_check_details` table
- Fields: check number, bank name, account number, IFSC code, amount, date, status, verification audit trail
- **Status:** Already executed - table is ready to use

### 2. Helper Class ✅
**File:** `application/helpers/Check_helper.php`
- Load in autoload.php: `$autoload['helpers'] = array('check');`
- **Key Functions:**
  - `save_check_details()` - Save check information
  - `get_application_checks()` - Get all checks for an application
  - `get_loan_checks()` - Get all checks for a loan
  - `update_check_status()` - Update check status (pending → verified → encashed/bounced)
  - `validate_check()` - Validate check data
  - `delete_application_checks()` - Delete checks for an application
  - `link_checks_to_loan()` - Link application checks to loan when disbursed

### 3. Admin Manual Entry Form (NEW) ✅
**File:** `application/views/admin/loans/check_details_admin_form.php`
- **Include in:** `application/views/admin/loans/view_application.php`
- **Features:**
  - Shows all collected checks in a table
  - Simple form for admin to manually add new checks
  - Dropdown to select applicant or guarantor
  - Fields: check number, bank name, IFSC, account number, amount, date, notes
  - Action buttons: Edit, Delete
  - **Not included in member-facing apply.php**

### 4. Legacy Form (DEPRECATED - DO NOT USE)
**File:** `application/views/admin/loans/check_details_form.php`
- ⚠️ **This was for member collection - DO NOT include in apply.php**
- Kept for reference only
- Replaced by admin-only form above

---

## Integration Steps (Admin-Only Workflow)

### Step 1: Load the Helper
Edit `application/config/autoload.php`:
```php
$autoload['helpers'] = array('url', 'form', 'check');  // Add 'check'
```

### Step 2: Update View Application Page (ADMIN SIDE ONLY)
Edit `application/views/admin/loans/view_application.php`:

**Add at the end of the card body (after guarantors section, around line 280):**
```php
<?php include 'check_details_admin_form.php'; ?>
```

✅ **Do NOT add anything to member-facing apply.php** - members should not see check forms

### Step 3: Update Loans Controller - view_application() Method
Edit `application/controllers/admin/Loans.php`:

**In `view_application()` method, add this before passing data to view:**
```php
// Load check details for admin view
$this->load->helper('check');
$checks = $this->check->get_application_checks($application->id);
$data['checks'] = $checks;

// Load guarantors for member selection dropdown in check form
$guarantors = $this->db->select('id, guarantor_member_id, guarantor_code, guarantor_name')
    ->where('application_id', $application_id)
    ->get('loan_guarantors')
    ->result();
$data['guarantors'] = $guarantors;
```

### Step 4: Add Controller AJAX Method for Saving Checks
Add this method to `application/controllers/admin/Loans.php`:

```php
/**
 * Save check details via AJAX (Admin Only)
 */
public function save_check_details() {
    if (!$this->input->is_ajax_request()) {
        redirect('admin/loans');
    }
    
    // Check admin access
    if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_admin()) {
        $this->output->set_output(json_encode(['success' => false, 'message' => 'Unauthorized']));
        return;
    }
    
    $application_id = $this->input->post('application_id');
    $check_type = $this->input->post('check_type');
    
    $check_data = array(
        'check_number' => $this->input->post('check_number'),
        'bank_name' => $this->input->post('bank_name'),
        'account_number' => $this->input->post('account_number'),
        'ifsc_code' => strtoupper($this->input->post('ifsc_code')),
        'amount' => $this->input->post('amount'),
        'check_date' => $this->input->post('check_date'),
        'notes' => $this->input->post('notes'),
        'check_type' => $check_type,
        'member_id' => $this->input->post('member_id'),
        'status' => 'pending'
    );
    
    if ($check_type === 'guarantor') {
        $check_data['guarantor_id'] = $this->input->post('guarantor_id');
    }
    
    $this->load->helper('check');
    $validation = $this->check->validate_check($check_data);
    
    if ($validation['valid']) {
        $this->check->save_check_details($application_id, $check_data, $this->session->userdata('user_id'));
        $this->output->set_output(json_encode(['success' => true, 'message' => 'Check saved successfully']));
    } else {
        $this->output->set_output(json_encode(['success' => false, 'errors' => $validation['errors']]));
    }
}

/**
 * Delete check details (Admin Only)
 */
public function delete_check($check_id) {
    if (!$this->input->is_ajax_request()) {
        redirect('admin/loans');
    }
    
    // Check admin access
    if (!$this->ion_auth->logged_in() || !$this->ion_auth->is_admin()) {
        $this->output->set_output(json_encode(['success' => false, 'message' => 'Unauthorized']));
        return;
    }
    
    $this->load->helper('check');
    $check = $this->check->get_check($check_id);
    
    if ($check) {
        // Only allow deletion if check is not linked to a disbursed loan
        if (is_null($check->loan_id)) {
            $this->db->where('id', $check_id)->delete('loan_check_details');
            $this->output->set_output(json_encode(['success' => true, 'message' => 'Check deleted successfully']));
        } else {
            $this->output->set_output(json_encode(['success' => false, 'message' => 'Cannot delete check linked to disbursed loan']));
        }
    } else {
        $this->output->set_output(json_encode(['success' => false, 'message' => 'Check not found']));
    }
}
```

### Step 5: Test Workflow
1. **Member side:** Apply for loan, add guarantors (NO check forms visible)
2. **Admin side:** 
   - Go to Applications → View any application
   - Scroll down to "Check Collection Records" section
   - Click "Add New Check"
   - Select "Applicant" or "Guarantor"
   - Fill in check details manually
   - Click "Save Check"
   - Verify check appears in table

---

## Database Queries for Reporting

### View all checks pending verification:
```sql
SELECT lcd.*, m.member_code, m.first_name, m.last_name, la.application_number
FROM loan_check_details lcd
JOIN members m ON lcd.member_id = m.id
JOIN loan_applications la ON lcd.loan_application_id = la.id
WHERE lcd.status = 'pending'
ORDER BY lcd.created_at DESC;
```

### Check summary by bank:
```sql
SELECT bank_name, ifsc_code, COUNT(*) as count, SUM(amount) as total_amount
FROM loan_check_details
WHERE status IN ('pending', 'verified')
GROUP BY bank_name, ifsc_code
ORDER BY total_amount DESC;
```

### Checks for a specific loan:
```sql
SELECT * FROM loan_check_details
WHERE loan_id = ? 
ORDER BY check_type ASC, created_at ASC;
```

### Bounced checks report:
```sql
SELECT lcd.*, la.application_number, m.member_code, m.phone
FROM loan_check_details lcd
JOIN loan_applications la ON lcd.loan_application_id = la.id
JOIN members m ON lcd.member_id = m.id
WHERE lcd.status = 'bounced'
ORDER BY lcd.check_date DESC;
```

---

## Workflow Summary

### Member Side (apply.php)
❌ NO check collection form  
❌ NO IFSC validation  
✅ Only: Apply for loan + Add guarantors  
✅ Submit application  

### Admin Side (view_application.php)
✅ See all collected checks in table  
✅ Click "Add New Check" button  
✅ Select applicant or guarantor  
✅ Fill in check details manually  
✅ Save to database  
✅ Edit/Delete checks  
✅ Track verification status  

### Status Tracking
- **Pending** - Awaiting verification
- **Verified** - Confirmed with bank
- **Encashed** - Funds collected
- **Bounced** - Payment failed
- **Cancelled** - Replaced/withdrawn

---

## Features

### Admin Manual Entry
✅ Dropdown to select applicant or guarantor  
✅ Manual entry of check number, bank name, IFSC code  
✅ Validate IFSC code format (11 characters: XXXXXX0XXXXX)  
✅ Store check dates and amounts  
✅ Add notes/remarks for each check  

### Check Tracking
✅ Table showing all collected checks  
✅ Check status with verification date  
✅ Summary info: Total, Verified, Pending  
✅ Action buttons (Edit, Delete)  

### Audit Trail
✅ Track who created/verified each check  
✅ Track verification timestamps  
✅ Link checks to loans for lifecycle tracking  
✅ User attribution for all changes  

### Security
✅ CSRF protection on all forms  
✅ Admin-only access (ion_auth validation)  
✅ Database constraints (foreign keys)  
✅ Status-based access control  
✅ Cannot delete checks linked to disbursed loans  

---

## Troubleshooting

**Check details not showing:**
- Verify helper is loaded in autoload.php
- Check database table exists: `SELECT * FROM loan_check_details LIMIT 1;`
- Ensure views are properly included

**Form not submitting:**
- Check browser console for JavaScript errors
- Verify CSRF token is correct
- Check that admin user has proper permissions

**IFSC code validation fails:**
- Format must be exactly 11 characters: XXXXXX0XXXXX
- Example valid codes: HDFC0000456, SBIN0011234, AXIS0098765

---

## Future Enhancements

- [ ] Bank API integration for IFSC validation
- [ ] Email notifications when check status changes
- [ ] Check image/document upload capability
- [ ] Automatic check status update on encashment/bounce
- [ ] SMS notifications to members
- [ ] Check portfolio analysis reports

---

**Last Updated:** 2026-09-12  
**Version:** 2.0 - Admin-Only Workflow  
**Status:** Production Ready
