# Student Archive Feature - Quick Start Guide

## ⚡ 30-Second Setup

### 1. Run Database Setup
```sql
-- Execute in phpMyAdmin or MySQL CLI:
-- File: install_archive_tables.sql
```

### 2. Access the Feature
- Login as Staff
- Look for "Student Archive" in sidebar menu
- Click to open

### 3. Done! You're ready to use it

---

## 🎯 Main Tasks

### Task 1: Export Student Records

```
1. Click "Student Archive" in sidebar
2. Select grade levels (7, 8, 9, 10)
3. Click export format button
4. File downloads automatically
```

**Formats Available:**
- 📄 CSV - Simple spreadsheet format
- 📊 Excel - Professional spreadsheets
- 📋 JSON - Data integration
- 📑 PDF - Print-friendly report

---

### Task 2: Promote Students to Next Grade

```
1. Go to "Archive & Transition Students" section
2. Enter current year: 2024-2025
3. Enter next year: 2025-2026
4. Check boxes for grades to promote:
   ☑ Grade 7 → Grade 8
   ☑ Grade 8 → Grade 9
   ☑ Grade 9 → Grade 10
5. (Optional) Add notes
6. Check: "I understand this will archive..."
7. Click "Archive & Transition Students"
```

**What happens next:**
- ✅ Grade 7 students become Grade 8
- ✅ Grade 8 students become Grade 9
- ✅ Grade 9 students become Grade 10
- ✅ All old records are backed up
- ✅ Complete audit trail is created

---

### Task 3: View Archive History

```
1. Scroll to "Archive History" section
2. See list of all previous archives
3. Click "View" button to see details
4. View grade distribution and dates
```

---

## 📊 Statistics Dashboard

At the top of the page, you'll see:

```
Active Students: [Number]    - Current students in system
Archived Records: [Number]   - Total students archived
Pending Archives: [Number]   - Archives in progress
```

---

## 🔧 Advanced Options

### Custom Export Fields

Click **"Advanced Options"** button to:
- Choose specific fields to include
- Select format
- Pick grades
- Download with custom settings

### Archive Notes

When archiving students, add notes like:
```
"End of SY 2024-2025, all Grade 10 students graduated"
"Summer grade promotion batch"
"Mid-year student transition"
```

---

## ⚠️ Important Reminders

### Before Archiving:
- ⚠️ **Backup data** - Export students first
- ⚠️ **Verify grades** - Double-check selections
- ⚠️ **Can't undo** - Archive is permanent
- ⚠️ **Check marks** - Must confirm action

### Safe Workflow:
```
1. EXPORT all students (backup)
2. Review export file
3. THEN run archive
4. Verify students were promoted
```

---

## 🚀 Common Workflows

### End of School Year
```
Monday:   Export all students as backup
Tuesday:  Archive & transition all grades
Wednesday: Verify new grade levels
Thursday:  Issue new credentials
```

### Mid-Year Transfer
```
1. Export Grade 10 students
2. Archive only Grade 10 (Graduated)
3. New students enroll as Grade 7
4. Continue with existing students
```

### Batch Re-enrollment
```
1. Export current class list
2. Archive promoted grades
3. Import new students
4. Update section assignments
```

---

## 🔍 Checking Results

### After Archive:
1. Go to User Management
2. Check student list updated
3. Verify grade levels changed
4. Review archive history for confirmation

### Download Archive Report:
```
Student Archive → View → Download PDF
(Shows all students archived in batch)
```

---

## 📁 File Locations

- **Main Page:** `staff-archive.php`
- **API Backend:** `staff-archive-api.php`
- **Database Setup:** `install_archive_tables.sql`
- **Documentation:** `ARCHIVE_FEATURE_GUIDE.md`

---

## ✅ Verification Checklist

After setup:
- [ ] Can access Student Archive page
- [ ] Can select grade levels
- [ ] Can export in CSV format
- [ ] Can fill archive form
- [ ] Can view archive history
- [ ] Can see statistics at top

---

## 💡 Tips & Tricks

1. **Bulk Export** - Select all grades to export entire student roster
2. **Auto-Calculation** - Next grade automatically calculated (7→8, 8→9, etc.)
3. **Safe Grade 10** - Grade 10 students typically removed (graduation)
4. **Audit Trail** - Every archive is logged for compliance
5. **Quick Download** - No need to refresh page, auto-downloads

---

## 🆘 Quick Troubleshooting

| Problem | Solution |
|---------|----------|
| No grades appear | Click grade buttons to select them |
| Export won't download | Check download folder, clear cache |
| Archive button disabled | Confirm the checkbox at bottom |
| Can't see Student Archive menu | Login again, refresh page |
| Database error | Run `install_archive_tables.sql` |

---

## 📱 Mobile Access

The archive feature works on mobile, but:
- Better on desktop for exporting
- Use landscape mode for tables
- Mobile-optimized dialogs available
- All functions work on tablet

---

## 🔐 Security

- Only staff can access (automatic role check)
- All actions logged
- Records preserved before deletion
- Session validation required
- Database transactions for safety

---

**Need Help?** Check the full guide: `ARCHIVE_FEATURE_GUIDE.md`

