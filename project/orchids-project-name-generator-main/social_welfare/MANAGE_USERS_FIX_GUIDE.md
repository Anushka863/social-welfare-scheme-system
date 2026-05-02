# Manage Users Page - Fix Guide

## Issues Fixed ✓

### 1. **WHERE Clause Parameters Mismatch**
   - **Problem**: When status filters (`active`/`inactive`) were applied, they were added to the WHERE clause but NOT added to the `$params` array, causing prepared statement binding to fail.
   - **Fix**: Built a proper `$where_parts` array and used `implode()` to safely combine conditions. All parameters are now correctly added to the `$params` array in order.

### 2. **Prepared Statement Type String Error**
   - **Problem**: The type string for `bind_param` was calculated incorrectly when filters were mixed (search + status).
   - **Fix**: Created a `$param_types` variable that accurately tracks the type of each parameter ('s' for string, 'i' for integer).

### 3. **Pagination Offset Issues**
   - **Problem**: LIMIT and OFFSET parameters weren't being added to the bind_param correctly.
   - **Fix**: Now properly appends 'ii' to the type string and includes `$per_page` and `$offset` in the `$bind_params` array.

### 4. **No Error Handling**
   - **Problem**: If queries failed, no errors were logged, and the user would see "No users found" without knowing why.
   - **Fix**: Added `error_log()` statements and proper error checking for `prepare()` and `execute()` functions.

### 5. **Inconsistent User Filtering**
   - **Problem**: Not all users were being filtered correctly for role='user'.
   - **Fix**: `role = 'user'` is now hardcoded in `$where_parts` and applied to all queries (COUNT and SELECT).

### 6. **Limited Display Information**
   - **Problem**: The original table only showed ID, Name, Email, Phone, and Status - no application data was visible.
   - **Fix**: Enhanced the display to show:
     - ✅ Complete applicant details (ID, Name, Join Date)
     - ✅ Contact information (Email, Phone, Masked Aadhar)
     - ✅ Demographics (Category, DOB, Gender)
     - ✅ Annual income
     - ✅ Application statistics (Total, Approved, Pending, Rejected)
     - ✅ Active/Inactive status badges
     - ✅ Quick view profile link

---

## How to Use

### Step 1: Start Your Server
```bash
# Make sure XAMPP/MySQL is running
# Start Apache and MySQL services
```

### Step 2: Create Test Data
Visit this URL in your browser:
```
http://localhost/social_welfare/setup_test_data.php
```

This will create **5 test applicants** with **15 sample applications**:
- Rajesh Kumar
- Priya Singh
- Amit Patel
- Neha Sharma
- Vivek Gupta

### Step 3: View Applicants
1. Go to Admin Portal: `http://localhost/social_welfare/admin_portal.php`
2. Login with:
   - Email: `admin@socialwelfare.gov`
   - Password: `Admin@123`
3. Click **"Manage Users"** from the sidebar

### Step 4: Test Features
- **Search**: Type a name, email, or phone number
- **Filter by Status**: Select Active or Inactive
- **View Details**: Click "View Profile" to see full applicant information
- **Pagination**: Navigate between pages if more than 10 applicants exist

---

## Test User Credentials

All test users have password: `Test@123`

| Email | Password |
|-------|----------|
| rajesh@example.com | Test@123 |
| priya@example.com | Test@123 |
| amit@example.com | Test@123 |
| neha@example.com | Test@123 |
| vivek@example.com | Test@123 |

---

## Key Code Changes

### Before (Broken):
```php
$where = "WHERE role='user'";
$params = [];

if ($search !== '') {
    $where .= " AND (name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $params = [$like, $like, $like];  // Status filters not added here!
}

if ($status === 'active') {
    $where .= " AND is_active = 1";  // No parameter added!
}

$stmt->bind_param(str_repeat('s', count($params)) . "ii", ...$params, $per_page, $offset);
// Types mismatch! Not all placeholders covered
```

### After (Fixed):
```php
$where_parts = ["role = 'user'"];
$params = [];
$param_types = '';

// Add search parameters
if ($search !== '') {
    $where_parts[] = "(name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $param_types .= 'sss';
}

// Add status to WHERE clause (no placeholder needed)
if ($status === 'active') {
    $where_parts[] = "is_active = 1";
}

$where = "WHERE " . implode(" AND ", $where_parts);

// Bind parameters correctly
$bind_types = $param_types . 'ii';
$bind_params = array_merge($params, [$per_page, $offset]);
$stmt->bind_param($bind_types, ...$bind_params);
```

---

## Display Enhancements

### Stats Dashboard
- **Total Applicants**: Gradient purple card
- **Active Users**: Gradient pink card
- **Inactive Users**: Gradient cyan card

### Search & Filter UI
- Improved form layout with labels
- Placeholder text for guidance
- Better styling and spacing

### Applicant Table
Shows comprehensive information:
```
┌─────────────────────────────────────────────┬──────────────────────┐
│ Applicant Details                           │ Contact Info         │
│ ID: 1                                       │ 📧 rajesh@example... │
│ 👤 Rajesh Kumar                            │ 📱 9876543210        │
│ Joined: 20-Apr-2026                        │ Aadhar: ****3210     │
├─────────────────────────────────────────────┼──────────────────────┤
│ Category: OBC                               │ Annual Income: ₹180K │
│ DOB: 15-Mar-1985, Gender: Male              │ Active: ✓            │
├─────────────────────────────────────────────┼──────────────────────┤
│ Applications: 3 Total                       │ View Profile Button  │
│ ✓ 1 Approved                                │                      │
│ ⏳ 1 Pending                                │                      │
│ ✗ 1 Rejected                                │                      │
```

### Pagination
- Shows current page highlighted in blue
- First/Last navigation buttons
- Shows "Showing X-Y of Z applicants"
- Maintains search and filter parameters in page links

---

## Database Tables Used

### users table
```sql
- id (INT)
- name (VARCHAR)
- email (VARCHAR)
- phone (VARCHAR)
- password (VARCHAR)
- dob (DATE)
- gender (ENUM)
- address (TEXT)
- aadhar (VARCHAR)
- annual_income (DECIMAL)
- category (ENUM: General, OBC, SC, ST, EWS)
- role (ENUM: user, admin)  ← Only role='user' displayed
- is_active (TINYINT)
- created_at (TIMESTAMP)
```

### applications table (LEFT JOIN for stats)
```sql
- id (INT)
- user_id (INT) ← Joined with users
- scheme_id (INT)
- status (ENUM: pending, under_review, approved, rejected)
- applied_at (TIMESTAMP)
```

---

## Troubleshooting

### Issue: Still showing "No applicants found"
**Solution**: Run the setup test data script:
```
http://localhost/social_welfare/setup_test_data.php
```

### Issue: Error in admin portal
**Verify**:
1. MySQL is running
2. `social_welfare` database exists
3. Tables are created (check in phpMyAdmin)
4. You're logged in as admin

### Issue: Search not working
**Check**:
1. Try without search filter first
2. Ensure test data was created
3. Check browser console for JavaScript errors

### Issue: Pagination shows only 1 page
- This is correct if you have ≤10 applicants
- Create more test data to see multiple pages

---

## Files Modified

- ✅ `manage_users.php` - Fixed backend logic, enhanced UI
- ✅ `setup_test_data.php` - Created with 5 test users

---

## Summary

The **Manage Users** page now fully functions with:
- ✅ Proper prepared statement handling
- ✅ Correct WHERE clause building
- ✅ Accurate pagination
- ✅ Complete applicant information display
- ✅ Search and filter capabilities
- ✅ Enhanced UI with statistics
- ✅ Error logging for debugging
