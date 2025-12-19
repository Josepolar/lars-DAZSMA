# Student Archive Feature - Usage Examples

## Real-World Scenarios

### Scenario 1: End of Year School Transition (Most Common)

**Goal:** Promote all current students and prepare for next school year

**Date:** June 2025 (End of SY 2024-2025)

**Steps:**

1. **Export Backup**
   ```
   - Go to Student Archive
   - Select all grades (7, 8, 9, 10)
   - Export as Excel
   - Save to: Backups/SY2024-2025_StudentList.xlsx
   - Keep safely for records
   ```

2. **Archive & Transition**
   ```
   - Current Year: 2024-2025
   - Next Year: 2025-2026
   - Select to promote:
     ☑ Grade 7 → 8 (45 students)
     ☑ Grade 8 → 9 (50 students)
     ☑ Grade 9 → 10 (55 students)
     ☑ Grade 10 (Graduated - 40 students)
   - Notes: "End of SY 2024-2025, standard promotion"
   - Execute
   ```

3. **Verification**
   ```
   - Go to User Management
   - Check Grade 7 → now shows no students
   - Check Grade 8 → shows 45 new students
   - Check Grade 9 → shows 50 new students
   - Check Grade 10 → shows 55 new students
   - View Archive History → confirms 190 total archived
   ```

4. **Next Step**
   ```
   - Issue new credentials to promoted students
   - Reset passwords if needed
   - Update class assignments
   - Welcome new Grade 7 students
   ```

---

### Scenario 2: Mid-Year Student Transfer

**Goal:** Remove graduating students mid-year

**Date:** April 2025 (Special graduation batch)

**Steps:**

1. **Select Grade 10 for Graduation**
   ```
   - Go to Student Archive
   - Current Year: 2024-2025
   - Next Year: 2025-2026
   - Only check: Grade 10 (Graduated)
   - Notes: "Mid-year graduation batch - 5 students"
   - Execute
   ```

2. **Result**
   ```
   - 5 Grade 10 students removed from system
   - Records archived permanently
   - Audit trail created
   - System shows -5 active students
   ```

---

### Scenario 3: Partial Class Re-enrollment

**Goal:** Some grades promoted, others retained

**Date:** August 2025 (Summer remediation program)

**Steps:**

1. **Export Student List**
   ```
   - Grade 7: Export for summer program review
   - Format: CSV
   - File: Summer_Review_Grade7.csv
   ```

2. **Archive with Conditions**
   ```
   - Promote Grade 8 & 9 only
   - Keep Grade 7 (remedial students stay)
   - Notes: "Summer remediation - Grade 7 retained"
   ```

3. **Result**
   ```
   - Grade 7: Students retained at same level
   - Grade 8: Advanced students promoted
   - Grade 9: Advanced students promoted
   - Grade 10: New students in this grade
   ```

---

### Scenario 4: Compliance & Audit Report

**Goal:** Generate compliance records for inspection

**Date:** October 2025 (Mid-year audit)

**Steps:**

1. **Export All Records with Details**
   ```
   - Go to Advanced Options
   - Format: PDF
   - Include: Name, Grade, Section, Email
   - Select: All grades
   - Download: Audit_Report_Oct2025.pdf
   ```

2. **View Archive History**
   ```
   - Click any archive record
   - View "Grade Distribution"
   - Export data as needed
   ```

3. **Generate Report**
   ```
   Title: Student Enrollment & Transition Report
   Shows:
   - Total students per grade
   - Archive dates
   - Promotion records
   - Graduation records
   ```

---

## Data Examples

### Example 1: CSV Export Format

```csv
First Name,Last Name,Username,Email,Grade Level,Section
John,Doe,johndoe7a,johndoe7a@lars.edu.ph,7,A
Maria,Garcia,maria8b,maria8b@lars.edu.ph,8,B
Antonio,Santos,antonio9a,antonio9a@lars.edu.ph,9,A
Rosa,Martinez,rosa10c,rosa10c@lars.edu.ph,10,C
```

### Example 2: JSON Export Format

```json
[
  {
    "first_name": "John",
    "last_name": "Doe",
    "username": "johndoe7a",
    "email": "johndoe7a@lars.edu.ph",
    "grade_level": "7",
    "section": "A"
  },
  {
    "first_name": "Maria",
    "last_name": "Garcia",
    "username": "maria8b",
    "email": "maria8b@lars.edu.ph",
    "grade_level": "8",
    "section": "B"
  }
]
```

### Example 3: Archive Details View

```
Archive ID: #15
School Year: 2024-2025 → 2025-2026
Status: Completed ✓
Students Archived: 190
Archived Date: June 30, 2025

Grade Distribution:
  Grade 7→8: 45 students
  Grade 8→9: 50 students
  Grade 9→10: 55 students
  Grade 10 (Graduated): 40 students
  Total: 190 students

Notes: "End of SY 2024-2025, standard promotion"
```

---

## Database Records Created

### student_archives Entry

```sql
INSERT INTO student_archives VALUES (
  15,                           -- archive_id
  '2024-2025 → 2025-2026',     -- school_year
  'completed',                   -- archive_status
  1,                            -- archived_by (staff_id)
  'End of SY 2024-2025, standard promotion',  -- notes
  '2025-06-30 14:30:00'        -- archived_date
);
```

### student_archive_records Entries

```sql
INSERT INTO student_archive_records VALUES
(1, 15, 101, 'John', 'Doe', 'johndoe7a', 'johndoe7a@lars.edu.ph', '7', 'A', 'pass123', 'promoted', '2025-06-30 14:30:00'),
(2, 15, 102, 'Maria', 'Garcia', 'maria8b', 'maria8b@lars.edu.ph', '8', 'B', 'pass456', 'promoted', '2025-06-30 14:30:00'),
(3, 15, 103, 'Antonio', 'Santos', 'antonio9a', 'antonio9a@lars.edu.ph', '9', 'A', 'pass789', 'promoted', '2025-06-30 14:30:00');
```

---

## API Call Examples

### API Call 1: Export Students

```bash
POST /staff/staff-archive-api.php
Content-Type: multipart/form-data

Parameters:
  action: "export"
  format: "csv"
  grades: "7,8,9,10"
  fields: ["email", "grade", "section"]

Response:
  Binary file (students_export_2025_06_30.csv)
```

### API Call 2: Archive & Transition

```bash
POST /staff/staff-archive-api.php
Content-Type: application/x-www-form-urlencoded

Parameters:
  action: "archive"
  current_year: "2024-2025"
  next_year: "2025-2026"
  promote_grades: ["7","8","9","10"]
  notes: "End of year transition"

Response:
  {
    "success": true,
    "archive_id": 15,
    "count": 190,
    "message": "Students archived and transitioned successfully"
  }
```

### API Call 3: Get Archive Details

```bash
POST /staff/staff-archive-api.php
Content-Type: application/x-www-form-urlencoded

Parameters:
  action: "get_details"
  archive_id: 15

Response:
  {
    "success": true,
    "archive_id": 15,
    "school_year": "2024-2025 → 2025-2026",
    "archive_status": "completed",
    "archived_date": "2025-06-30",
    "notes": "End of SY 2024-2025, standard promotion",
    "student_count": 190,
    "grade_distribution": [
      {"grade_level": "7", "count": 45},
      {"grade_level": "8", "count": 50},
      {"grade_level": "9", "count": 55},
      {"grade_level": "10", "count": 40}
    ]
  }
```

---

## Testing Checklist

### Test 1: Basic Export
- [ ] Login as staff
- [ ] Select Grade 7 only
- [ ] Export as CSV
- [ ] File downloads
- [ ] Open file, verify format

### Test 2: Multiple Grade Export
- [ ] Select Grades 7, 8, 9
- [ ] Export as Excel
- [ ] Open in Excel
- [ ] Verify 3 grades included

### Test 3: Advanced Options
- [ ] Click "Advanced Options"
- [ ] Select format: PDF
- [ ] Uncheck Password
- [ ] Export
- [ ] Verify PDF opens

### Test 4: Archive Single Grade
- [ ] Fill current year: "2024-2025"
- [ ] Fill next year: "2025-2026"
- [ ] Check only "Grade 7 → 8"
- [ ] Add notes: "Test promotion"
- [ ] Confirm checkbox
- [ ] Execute
- [ ] Verify Grade 7 students promoted to Grade 8

### Test 5: Archive History
- [ ] View archive details
- [ ] Check grade distribution
- [ ] Verify student counts
- [ ] Check dates

### Test 6: Data Integrity
- [ ] Check User Management
- [ ] Count Grade 7: should be 0 (all promoted)
- [ ] Count Grade 8: should include promoted Grade 7
- [ ] Verify usernames unchanged
- [ ] Verify emails unchanged

---

## Common Questions & Answers

**Q: Can I undo an archive?**
A: No, but archived records are preserved in student_archive_records table for recovery if needed.

**Q: What if a student shouldn't be promoted?**
A: Export first, check data. If needed, manually update in User Management after archive.

**Q: Can I promote only some students in a grade?**
A: Current system promotes all students in selected grade. For selective promotion, use User Management to update individually.

**Q: What happens to student grades/scores?**
A: They remain in the activities table. Grade level transition doesn't affect their past submissions.

**Q: Can multiple staff members archive?**
A: Yes, but only users with role_id=2 (staff). Each archive is logged with who performed it.

**Q: How often should we archive?**
A: Typically once per school year (June/July). But can be done any time needed.

---

## Performance Notes

- **Export Speed:** 
  - 100 students: ~1 second
  - 1000 students: ~5 seconds
  - 5000+ students: May need timeout adjustment

- **Archive Speed:**
  - 100 students: ~2 seconds
  - 1000 students: ~10 seconds
  - Includes database backup time

- **Recommended:** 
  - Run during low-traffic hours
  - Archive no more than 5000 students per batch
  - Regular database maintenance

---

## Security Audit Trail

All archive operations create:

1. **user_logs entry** - Via log_activity()
2. **student_archives record** - Batch information
3. **student_archive_records** - Individual student snapshots

Example query to audit:
```sql
SELECT * FROM user_logs 
WHERE action = 'Archived students' 
ORDER BY timestamp DESC;
```

---

**Ready to archive students?** Start with the Quick Start Guide!

