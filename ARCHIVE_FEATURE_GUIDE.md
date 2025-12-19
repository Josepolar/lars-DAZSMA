# Student Archive Feature - Implementation Guide

## Overview

The Student Archive feature enables staff members to:
- **Export** student records in multiple formats (CSV, Excel, JSON, PDF)
- **Archive & Transition** students to a new school year
- **Maintain records** of all archival operations
- **Promote students** to the next grade level
- **Track graduated** students

## Files Created/Modified

### New Files
1. **staff-archive.php** - Main archive management interface
2. **staff-archive-api.php** - Backend API for archive operations
3. **install_archive_tables.sql** - Database tables setup script

### Modified Files
- None required (uses existing database connection)

## Installation Steps

### Step 1: Create Database Tables

Run the following SQL script in phpMyAdmin or MySQL command line:

```sql
-- Copy the contents of install_archive_tables.sql
-- This creates:
-- - student_archives (stores archive batch information)
-- - student_archive_records (stores individual student records for each archive)
```

Or execute via command line:
```bash
mysql -u root -p lars < install_archive_tables.sql
```

### Step 2: Verify Sidebar Navigation

The navigation link is automatically included in staff-archive.php. No changes needed to staff-userman.php.

### Step 3: Test the Feature

1. Log in as a staff member (role_id = 2)
2. Navigate to "Student Archive" in the sidebar
3. Test export functionality
4. Test archive & transition functionality

## Features Breakdown

### 1. Export Student Records

**Supported Formats:**
- **CSV** - Standard comma-separated values
- **Excel** - .xlsx format (requires PhpSpreadsheet library)
- **JSON** - JSON format for integration
- **PDF** - Portable document format (requires TCPDF library)

**Usage:**
1. Select grade levels (7, 8, 9, 10)
2. Choose export format
3. Click "Export Selected Records"
4. File downloads automatically

**Advanced Options:**
- Include/exclude specific fields (Email, Grade, Section, Password)
- Custom format selection
- Batch export

### 2. Archive & Transition Students

**Purpose:** Move students to the next grade level or graduate them

**Process:**
1. Enter current school year (e.g., 2024-2025)
2. Enter next school year (e.g., 2025-2026)
3. Select grade levels to promote:
   - Grade 7 → Grade 8
   - Grade 8 → Grade 9
   - Grade 9 → Grade 10
   - Grade 10 → Graduated/Removed
4. Add optional notes
5. Confirm and execute

**What Happens:**
- Copies student records to archive table
- Updates grade level for promoted students
- Removes graduated students from active roster
- Creates audit trail with archive ID
- Logs the operation

### 3. Archive History

View all previous archive operations:
- Archive ID
- School year transition
- Number of students archived
- Archive status (Pending/Completed)
- Date of archive
- View detailed breakdown

## Database Schema

### student_archives Table
```
- archive_id (Primary Key)
- school_year (varchar) - e.g., "2024-2025 → 2025-2026"
- archive_status (enum) - pending, completed, failed
- archived_by (FK to users)
- notes (text)
- archived_date (timestamp)
```

### student_archive_records Table
```
- record_id (Primary Key)
- archive_id (FK to student_archives)
- user_id (original student ID)
- first_name, last_name, username, email
- grade_level (archived grade)
- section
- password (encrypted backup)
- action (promoted/graduated)
- archived_date (timestamp)
```

## API Endpoints

### Export Students
**Endpoint:** POST `staff-archive-api.php`

**Parameters:**
```json
{
  "action": "export",
  "format": "csv|excel|json|pdf",
  "grades": "7,8,9,10",
  "fields": ["email", "grade", "section", "password"]
}
```

**Response:** Binary file download

### Archive Students
**Endpoint:** POST `staff-archive-api.php`

**Parameters:**
```json
{
  "action": "archive",
  "current_year": "2024-2025",
  "next_year": "2025-2026",
  "promote_grades": ["7", "8", "9"],
  "notes": "End of school year transition"
}
```

**Response:**
```json
{
  "success": true,
  "archive_id": 1,
  "count": 150,
  "message": "Students archived and transitioned successfully"
}
```

### Get Archive Details
**Endpoint:** POST `staff-archive-api.php`

**Parameters:**
```json
{
  "action": "get_details",
  "archive_id": 1
}
```

**Response:**
```json
{
  "success": true,
  "archive_id": 1,
  "school_year": "2024-2025 → 2025-2026",
  "archive_status": "completed",
  "archived_date": "2024-12-20",
  "notes": "End of year transition",
  "student_count": 150,
  "grade_distribution": [
    {"grade_level": "7", "count": 45},
    {"grade_level": "8", "count": 50},
    {"grade_level": "9", "count": 55}
  ]
}
```

## Usage Scenarios

### Scenario 1: End of Year Export for Records

1. Go to Student Archive page
2. Select all grades (7-10)
3. Click "Export as Excel"
4. Backup file saves to your computer
5. Archive is maintained in system

### Scenario 2: Promote Current Year Students

1. Go to Student Archive page
2. Fill in current year: "2024-2025"
3. Fill in next year: "2025-2026"
4. Check: Grade 7, 8, 9 (not 10 - they graduate)
5. Add notes if needed
6. Confirm and execute
7. System automatically:
   - Creates archive record
   - Updates all Grade 7 students to Grade 8
   - Updates all Grade 8 students to Grade 9
   - Updates all Grade 9 students to Grade 10
   - Removes Grade 10 students from active system

### Scenario 3: Audit Trail

1. Click "View" on any archive in history
2. See:
   - Which students were archived
   - Their original grades
   - Action taken (promoted/graduated)
   - Date of archival

## Error Handling

The system handles:
- ✅ Invalid grade selections
- ✅ Missing school year information
- ✅ Duplicate usernames during re-enrollment
- ✅ File format compatibility
- ✅ Session validation
- ✅ Database transaction rollback on failure

## Security Features

1. **Session Validation** - Only logged-in staff can access
2. **Role Checking** - Only staff (role_id = 2) can archive
3. **Activity Logging** - All operations logged via log_activity()
4. **Transaction Safety** - Rollback on errors
5. **Audit Trail** - Complete history of all archives
6. **Data Backup** - Student records preserved before deletion

## Optional: Library Installation

### For Excel Export (PhpSpreadsheet)
```bash
cd c:\xampp\htdocs\larss
composer require phpoffice/phpspreadsheet
```

### For PDF Export (TCPDF)
```bash
cd c:\xampp\htdocs\larss
composer require tecnickcom/tcpdf
```

**Note:** Without these libraries, the system falls back to CSV format automatically.

## Best Practices

1. **Schedule Archives** - Run at end of school year
2. **Backup Data** - Export before archiving
3. **Verify Grades** - Double-check grade selections
4. **Document Changes** - Use notes field for explanations
5. **Review History** - Check archive history periodically
6. **Test First** - Export and verify data before major operations

## Troubleshooting

### Archives Table Not Found
- Run `install_archive_tables.sql` again
- Check MySQL error logs

### Export File Won't Download
- Clear browser cache
- Check browser download folder
- Verify file size isn't 0 bytes

### Students Not Promoted
- Verify all checkboxes are selected
- Check that student grade_level column exists
- Review error message in browser console

## Maintenance

### Cleanup Old Archives
```sql
-- Delete archives older than 2 years
DELETE FROM student_archives 
WHERE archived_date < DATE_SUB(NOW(), INTERVAL 2 YEAR);
```

### View Archive Statistics
```sql
SELECT 
    YEAR(archived_date) as year,
    COUNT(*) as total_archives,
    SUM(CASE WHEN archive_status = 'completed' THEN 1 ELSE 0 END) as completed
FROM student_archives
GROUP BY YEAR(archived_date);
```

## Support

For issues or questions:
1. Check the troubleshooting section
2. Review logs in `user_logs` table
3. Verify database tables exist
4. Check browser console for JavaScript errors
5. Review PHP error logs

---

**Version:** 1.0  
**Last Updated:** December 2024  
**Status:** Production Ready
