# Student Archive Feature - Installation Guide

## 🚀 Quick Installation (5 minutes)

### Prerequisites
- ✅ Staff account access
- ✅ phpMyAdmin or MySQL CLI access
- ✅ File upload permissions

### Installation Steps

#### Step 1: Create Database Tables (1 minute)

**Option A: Using phpMyAdmin**
1. Open phpMyAdmin
2. Select database `lars`
3. Click "SQL" tab
4. Open file: `install_archive_tables.sql`
5. Copy entire contents
6. Paste into phpMyAdmin SQL editor
7. Click "Execute"
8. ✅ Tables created

**Option B: Using MySQL CLI**
```bash
cd c:\xampp\htdocs\larss
mysql -u root -p lars < install_archive_tables.sql
```

**Option C: Using File Manager**
1. Copy `install_archive_tables.sql` contents
2. Create new file in phpMyAdmin → SQL tab
3. Paste contents
4. Execute

#### Step 2: Verify Installation (1 minute)

Run this SQL query to verify:
```sql
SHOW TABLES LIKE 'student_archive%';
```

Expected output:
```
student_archives
student_archive_records
```

#### Step 3: Access the Feature (Immediate)

1. Login to system as Staff (role_id = 2)
2. Look for "Student Archive" in sidebar
3. Click to open
4. ✅ Ready to use!

#### Step 4: Test (2 minutes)

**Test Export:**
1. Go to Student Archive page
2. Select Grade 7
3. Click "Export as CSV"
4. File should download
5. ✅ Verify CSV file contents

**Test History View:**
1. Scroll to "Archive History"
2. If no records, that's normal (nothing archived yet)
3. ✅ Table displays correctly

---

## 📁 File Checklist

Verify these files exist in your system:

### Main Files
- ✅ `/staff/staff-archive.php` - Main interface (should exist)
- ✅ `/staff/staff-archive-api.php` - Backend API (should exist)
- ✅ `/install_archive_tables.sql` - Database setup (should exist)

### Documentation
- ✅ `ARCHIVE_FEATURE_GUIDE.md` - Full documentation
- ✅ `ARCHIVE_QUICK_START.md` - Quick reference
- ✅ `ARCHIVE_USAGE_EXAMPLES.md` - Examples
- ✅ `ARCHIVE_IMPLEMENTATION_SUMMARY.md` - This summary

---

## 🔧 Database Setup Details

### What Gets Created

#### Table 1: student_archives
Stores information about each archive batch:
- Archive ID (auto-increment)
- School year being transitioned
- Status (pending/completed)
- Who performed the archive
- Notes and dates

#### Table 2: student_archive_records
Stores individual student records from each archive:
- Student's complete information at time of archive
- Grade level they were in
- Action taken (promoted/graduated)
- Timestamp of archive

### Size Requirements
- Both tables initially empty (0 KB)
- ~1 KB per student record stored
- 1000 archived students = ~1 MB

### Backup Recommendation
```sql
-- Before running archive, backup the users table:
CREATE TABLE users_backup_20250630 AS SELECT * FROM users;
```

---

## 🔐 Security Setup

### Already Configured
✅ Session validation built-in  
✅ Role-based access (staff only)  
✅ Activity logging integrated  
✅ Database transactions enabled  

### Optional: Restrict Access

If you want to restrict to certain staff:
```php
// In staff-archive.php, line 6, you could add:
if ($_SESSION['user_id'] != SPECIFIC_STAFF_ID) {
    header('Location: staff-login.php');
    exit();
}
```

---

## 🐛 Troubleshooting Installation

### Problem: "Table not found" error

**Solution:**
1. Run `install_archive_tables.sql` again
2. Check MySQL user has CREATE TABLE permission
3. Verify database name is `lars`

### Problem: Can't see Student Archive menu

**Solution:**
1. Clear browser cache (Ctrl+F5)
2. Logout and login again
3. Verify staff-archive.php file exists
4. Check file permissions (644 or 755)

### Problem: Export not downloading

**Solution:**
1. Check browser download settings
2. Verify /staff/ folder is writable
3. Check for JavaScript errors (F12)
4. Try different export format

### Problem: Archive execution fails

**Solution:**
1. Verify both tables exist
2. Check MySQL max_allowed_packet setting
3. Ensure no active transactions
4. Check error logs

---

## 📋 Pre-Launch Checklist

### Database
- [ ] `student_archives` table exists
- [ ] `student_archive_records` table exists
- [ ] Tables have correct columns
- [ ] Foreign keys set up
- [ ] Indexes created

### Files
- [ ] `staff-archive.php` exists (850+ lines)
- [ ] `staff-archive-api.php` exists (400+ lines)
- [ ] Both files have correct permissions
- [ ] API file is executable

### Access Control
- [ ] Only staff can access (role check in place)
- [ ] Session validation works
- [ ] Sidebar shows "Student Archive" link
- [ ] Page loads without errors

### Features
- [ ] Grade selection works
- [ ] Export buttons functional
- [ ] Export formats work (test CSV)
- [ ] Form validation works
- [ ] Archive history displays

### Browser Compatibility
- [ ] Chrome/Edge (latest)
- [ ] Firefox (latest)
- [ ] Safari (if using Mac)
- [ ] Mobile responsive (check on phone)

---

## 🎯 First-Time Setup Workflow

### Day 1: Installation
1. Create database tables (5 min)
2. Verify tables exist (2 min)
3. Test export feature (3 min)
4. Read quick start guide (5 min)
5. ✅ Complete

### Day 2: Test Run (Optional)
1. Export small student group as backup
2. Review exported data
3. Try archive form (don't submit)
4. Review archive history page
5. ✅ Familiar with interface

### Day 3: Production Use
1. Export all students
2. Execute first archive during non-peak time
3. Verify students promoted in User Management
4. Check archive history
5. ✅ Live and operational

---

## 🚀 Production Recommendations

### Before First Archive
```sql
-- Backup your users table
CREATE TABLE users_backup_before_first_archive AS SELECT * FROM users;

-- Backup all activity records
CREATE TABLE user_logs_backup_before_first_archive AS SELECT * FROM user_logs;
```

### Schedule Archives
- **Best Time:** After school year ends (June/July)
- **Duration:** ~15 minutes for 200 students
- **Frequency:** Once per school year typically
- **Backups:** Always export before archiving

### Monitor Performance
- Check archive history regularly
- Review audit logs monthly
- Verify backups are working
- Test restore procedures quarterly

---

## 💾 Optional: PhpSpreadsheet Setup

For better Excel support, optionally install:

```bash
cd c:\xampp\htdocs\larss
composer require phpoffice/phpspreadsheet
```

Benefits:
- Better Excel formatting
- Larger file support
- Professional appearance
- No installation required (fallback to CSV)

---

## 📧 User Communication

### Announce to Staff
```
Subject: New Student Archive Feature Available

Hi Team,

A new Student Archive feature is now available in the staff dashboard.

Features:
- Export student records (CSV, Excel, JSON, PDF)
- Transition students between grades
- Maintain audit trail
- View archive history

Location: Staff Dashboard → Student Archive Menu

Quick Start: See ARCHIVE_QUICK_START.md for usage

Questions? Contact the tech team.
```

### Training (Optional)
For new staff members:
1. Show sidebar navigation
2. Demonstrate export feature
3. Explain archive process
4. Walk through one example
5. Provide documentation links

---

## 🔍 Post-Installation Verification

### Run These Checks

**Check 1: Database Tables**
```sql
-- Verify tables exist
SHOW TABLES LIKE 'student_archive%';
-- Should show: student_archives, student_archive_records
```

**Check 2: Column Structure**
```sql
-- Verify student_archives columns
DESCRIBE student_archives;
-- Should have: archive_id, school_year, archive_status, archived_by, notes, archived_date
```

**Check 3: Foreign Keys**
```sql
-- Verify relationships
SHOW CREATE TABLE student_archive_records\G
-- Should have foreign key to student_archives
```

**Check 4: Access Rights**
```php
<?php
// Test in browser console
// Should print "Staff user" if logged in as staff
echo ($_SESSION['role_id'] == 2) ? 'Staff user' : 'Not staff';
?>
```

---

## 🎓 Training Resources

### For Staff Using Archive
1. **Quick Start:** `ARCHIVE_QUICK_START.md` (5 min read)
2. **Examples:** `ARCHIVE_USAGE_EXAMPLES.md` (10 min read)
3. **Full Guide:** `ARCHIVE_FEATURE_GUIDE.md` (Reference)

### For Admins/Developers
1. **Implementation:** `ARCHIVE_IMPLEMENTATION_SUMMARY.md`
2. **Tech Details:** `ARCHIVE_FEATURE_GUIDE.md` - Technical Section
3. **API Reference:** `staff-archive-api.php` - Code comments

---

## 🆘 Quick Fixes

### Issue: "Access Denied" Error
```
Solution: Verify you're logged in as staff (role_id = 2)
```

### Issue: Page Won't Load
```
Solution: Check browser console (F12) for JavaScript errors
Fallback: Clear cache and try again
```

### Issue: Export Creates Empty File
```
Solution: Verify students exist in selected grades
Try: Export as CSV instead of Excel first
```

### Issue: Database Error After Archive
```
Solution: Run install_archive_tables.sql again
Backup: Restore from users_backup table if needed
```

---

## 📞 Support Contacts

### Technical Issues
- Check error logs in `/xampp/apache/logs/`
- Review MySQL error logs
- Check PHP error logs
- See troubleshooting section above

### Feature Questions
- Read `ARCHIVE_QUICK_START.md`
- Check `ARCHIVE_USAGE_EXAMPLES.md`
- Review `ARCHIVE_FEATURE_GUIDE.md`

### Performance Issues
- Optimize database indexes
- Check server resources
- Review MySQL slow_log
- Increase PHP memory_limit if needed

---

## ✅ Installation Complete Checklist

After following all steps, verify:

- [ ] Database tables created
- [ ] Tables verified to exist
- [ ] staff-archive.php loads without errors
- [ ] Staff can access page
- [ ] Grade selection works
- [ ] Export feature works
- [ ] Can see archive history
- [ ] Statistics display correctly
- [ ] Form validation works
- [ ] No JavaScript console errors

---

## 🎉 You're Ready!

Once all checklist items are complete:
1. ✅ Feature is fully installed
2. ✅ Feature is tested
3. ✅ Feature is ready for production
4. ✅ Staff can begin using it
5. ✅ You can start archiving students

**Congratulations! Your Student Archive system is live.**

---

## 📚 Next Steps

1. **Explore Features:** Spend 10 minutes exploring the interface
2. **Read Guide:** Review ARCHIVE_QUICK_START.md
3. **Plan First Archive:** Decide when to run first archive
4. **Backup Data:** Always export before archiving
5. **Document Process:** Create internal procedures

---

**Installation Guide Version:** 1.0  
**Last Updated:** December 2024  
**Status:** Production Ready
