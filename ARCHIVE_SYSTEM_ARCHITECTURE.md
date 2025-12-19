# Student Archive Feature - System Architecture & Flow Diagrams

## 1. System Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                         STAFF INTERFACE                          │
│                     staff-archive.php                             │
├─────────────────────────────────────────────────────────────────┤
│                                                                   │
│  ┌─────────────────────┐  ┌──────────────────────────┐           │
│  │  Export Students    │  │  Archive & Transition    │           │
│  │  - Select Grades    │  │  - Enter School Years    │           │
│  │  - Choose Format    │  │  - Select Grades         │           │
│  │  - Download File    │  │  - Add Notes             │           │
│  └──────────┬──────────┘  └──────────┬───────────────┘           │
│             │                        │                            │
│             └────────────┬───────────┘                            │
│                          │                                         │
│             ┌────────────▼──────────┐                            │
│             │   Archive History     │                            │
│             │   - View Archives     │                            │
│             │   - See Details       │                            │
│             │   - Grade Distribution│                            │
│             └──────────────────────┘                            │
│                                                                   │
└────────────────────────┬────────────────────────────────────────┘
                         │ AJAX Calls
                         │
┌────────────────────────▼────────────────────────────────────────┐
│                      API BACKEND                                 │
│                 staff-archive-api.php                            │
├─────────────────────────────────────────────────────────────────┤
│                                                                   │
│  ┌─────────────────┐  ┌──────────────────────────────────────┐  │
│  │  Export Module  │  │  Archive Module                      │  │
│  │  - CSV Export   │  │  - Grade Promotion Logic             │  │
│  │  - Excel Export │  │  - Student Record Archiving          │  │
│  │  - JSON Export  │  │  - Database Updates                  │  │
│  │  - PDF Export   │  │  - Transaction Management            │  │
│  └────────┬────────┘  └──────────────────┬───────────────────┘  │
│           │                              │                       │
│           └──────────────┬───────────────┘                       │
│                          │                                        │
│                    [Validation]                                  │
│                 [Error Handling]                                 │
│                                                                   │
└────────────────────────┬────────────────────────────────────────┘
                         │ Database Queries
                         │
┌────────────────────────▼────────────────────────────────────────┐
│                      DATABASE LAYER                              │
│                        MySQL/MariaDB                             │
├─────────────────────────────────────────────────────────────────┤
│                                                                   │
│  ┌──────────────────┐  ┌──────────────────────────────────────┐ │
│  │ Existing Tables  │  │ New Archive Tables                   │ │
│  │ - users          │  │ - student_archives                   │ │
│  │ - user_logs      │  │ - student_archive_records            │ │
│  │ - activities     │  │                                      │ │
│  │ - submissions    │  │ [student_archives]                   │ │
│  └────────────────┬─┘  │ - archive_id (PK)                    │ │
│                  │     │ - school_year                        │ │
│                  │     │ - archive_status                     │ │
│                  │     │ - archived_by (FK)                   │ │
│                  │     │ - notes                              │ │
│                  │     │ - archived_date                      │ │
│                  │     │                                      │ │
│                  │     │ [student_archive_records]            │ │
│                  │     │ - record_id (PK)                     │ │
│                  │     │ - archive_id (FK)                    │ │
│                  │     │ - user_id                            │ │
│                  │     │ - student_data (backup)              │ │
│                  │     │ - action (promoted/graduated)        │ │
│                  │     │ - archived_date                      │ │
│                  │     └──────────────────────────────────────┘ │
│                  │                                                │
└──────────────────┴────────────────────────────────────────────────┘
```

---

## 2. Export Flow Diagram

```
User Selects Export
        │
        ▼
┌─────────────────────┐
│ Select Grades (7-10)│
│ Select Format (4)   │
│ Select Fields (opt) │
└────────────┬────────┘
             │
             ▼
┌─────────────────────────────────────────┐
│    Validation                           │
│    - Grades selected? ✓                 │
│    - Format valid? ✓                    │
│    - Session valid? ✓                   │
└────────────┬────────────────────────────┘
             │
             ▼
    ┌────────────────────┐
    │  Query Database    │
    │  WHERE grade IN    │
    │  (selected grades) │
    └────────────┬───────┘
                 │
                 ▼
    ┌─────────────────────────────┐
    │  Format Data                │
    │  ├─ CSV → fputcsv()         │
    │  ├─ Excel → PhpSpreadsheet  │
    │  ├─ JSON → json_encode()    │
    │  └─ PDF → TCPDF             │
    └────────────┬────────────────┘
                 │
                 ▼
    ┌──────────────────────────────┐
    │  Send Headers                │
    │  Content-Type: [format]      │
    │  Content-Disposition: attach │
    └────────────┬─────────────────┘
                 │
                 ▼
    ┌──────────────────────────────┐
    │  Stream to Browser           │
    │  fopen('php://output', 'w')  │
    └────────────┬─────────────────┘
                 │
                 ▼
         File Downloads
         to Computer
```

---

## 3. Archive & Transition Flow Diagram

```
User Submits Archive Form
        │
        ▼
┌─────────────────────────────────────────┐
│  Form Validation                        │
│  - Years entered? ✓                     │
│  - Grades selected? ✓                   │
│  - Confirmation checked? ✓              │
│  - Session valid? ✓                     │
└────────────┬────────────────────────────┘
             │
             ▼
┌─────────────────────────────────────────┐
│  BEGIN TRANSACTION                      │
└────────────┬────────────────────────────┘
             │
             ▼
┌─────────────────────────────────────────┐
│  Create Archive Batch Record            │
│  INSERT INTO student_archives:          │
│  - school_year                          │
│  - archive_status = 'pending'           │
│  - archived_by = current_staff_id       │
│  - notes = user_notes                   │
│  - archived_date = NOW()                │
└────────────┬────────────────────────────┘
             │
             ▼
┌─────────────────────────────────────────┐
│  For Each Selected Grade:               │
│  IF Grade = 10 THEN                     │
│      HANDLE GRADUATION                  │
│  ELSE                                   │
│      HANDLE PROMOTION                   │
│  END IF                                 │
└────────────┬────────────────────────────┘
             │
      ┌──────┴──────┐
      │ Grade 10?   │
      └─┬─────────┬─┘
        │ YES     │ NO
        │         │
        ▼         ▼
    ┌────────┐ ┌──────────────┐
    │GRADUATE│ │ PROMOTE      │
    └────┬───┘ └─────┬────────┘
         │           │
         ▼           ▼
    ┌─────────────────────────────────┐
    │ SELECT users WHERE grade = [X]  │
    │ and role_id = 4                 │
    └──────────┬──────────────────────┘
               │
               ▼
    ┌──────────────────────────────────────┐
    │ For Each Student:                    │
    │                                      │
    │ 1. INSERT INTO                       │
    │    student_archive_records:          │
    │    - student data snapshot           │
    │    - action = promoted/graduated     │
    │                                      │
    │ 2. IF PROMOTE:                       │
    │    UPDATE users SET                  │
    │    grade_level = grade + 1           │
    │                                      │
    │ 3. IF GRADUATE:                      │
    │    DELETE FROM users                 │
    │                                      │
    └──────────┬───────────────────────────┘
               │
               ▼
    ┌──────────────────────────────────────┐
    │ Log Activity                         │
    │ INSERT INTO user_logs:               │
    │ action = 'Archived students'         │
    │ affected_user_id = archive_id        │
    └──────────┬───────────────────────────┘
               │
               ▼
    ┌──────────────────────────────────────┐
    │ Update Archive Status                │
    │ UPDATE student_archives              │
    │ archive_status = 'completed'         │
    └──────────┬───────────────────────────┘
               │
               ▼
    ┌──────────────────────────────────────┐
    │ COMMIT TRANSACTION                   │
    │ (All or Nothing)                     │
    └──────────┬───────────────────────────┘
               │
        ┌──────┴────────┐
        │               │
     SUCCESS         ERROR
        │               │
        ▼               ▼
    ✓ Complete    ✗ ROLLBACK
    Show Result   Show Error
    Reload Page   Keep Data
```

---

## 4. Data Flow Diagram

```
┌──────────────────────────────────────────────────────────────────┐
│                        USERS TABLE                               │
│                      (Active Students)                           │
├──────────────────────────────────────────────────────────────────┤
│ user_id │ name    │ username  │ grade │ section │ role_id │ ...  │
├─────────┼─────────┼───────────┼───────┼─────────┼─────────┼──┤
│ 101     │ John    │ johndoe7a │ 7     │ A       │ 4       │     │ Grade 7
│ 102     │ Maria   │ maria8b   │ 8     │ B       │ 4       │     │ Grade 8
│ 103     │ Antonio │ antonio9  │ 9     │ A       │ 4       │     │ Grade 9
│ 104     │ Rosa    │ rosa10c   │ 10    │ C       │ 4       │     │ Grade 10
│ 105     │ Pedro   │ pedro7b   │ 7     │ B       │ 4       │     │ Grade 7
└─────────┴─────────┴───────────┴───────┴─────────┴─────────┴──────┘
                              │
                              │ ARCHIVE PROCESS
                              │
        ┌─────────────────────┼─────────────────────┐
        │                     │                     │
        ▼                     ▼                     ▼
    Grade 7→8             Grade 8→9             Grade 10
    Updates               Updates              (Deleted)
    Grade: 7→8            Grade: 8→9           
    user_id: 101, 105     user_id: 102         user_id: 104
                                               
        │                     │                     │
        └─────────────────────┼─────────────────────┘
                              │
                              ▼
┌──────────────────────────────────────────────────────────────────┐
│                   STUDENT_ARCHIVES TABLE                         │
│                   (Archive Batches)                              │
├──────────────────────────────────────────────────────────────────┤
│ archive_id │ school_year          │ status    │ archived_by │... │
├────────────┼──────────────────────┼───────────┼─────────────┼──┤
│ 1          │ 2024-2025 → 2025-26 │ completed │ 5           │   │
└────────────┴──────────────────────┴───────────┴─────────────┴────┘
                              │
                              │ Contains
                              │
                              ▼
┌──────────────────────────────────────────────────────────────────┐
│            STUDENT_ARCHIVE_RECORDS TABLE                         │
│            (Individual Student Snapshots)                        │
├──────────────────────────────────────────────────────────────────┤
│ record_id │ archive_id │ user_id │ name   │ grade │ action    │  │
├───────────┼────────────┼─────────┼────────┼───────┼───────────┼──┤
│ 1         │ 1          │ 101     │ John   │ 7     │ promoted  │  │
│ 2         │ 1          │ 102     │ Maria  │ 8     │ promoted  │  │
│ 3         │ 1          │ 103     │ Antonio│ 9     │ promoted  │  │
│ 4         │ 1          │ 104     │ Rosa   │ 10    │ graduated │  │
│ 5         │ 1          │ 105     │ Pedro  │ 7     │ promoted  │  │
└───────────┴────────────┴─────────┴────────┴───────┴───────────┴──┘

AFTER ARCHIVE:
  Grade 7: user_id 101, 105 (0 users - all promoted)
  Grade 8: user_id 101, 105, 102 (3 users: 2 promoted + Maria)
  Grade 9: user_id 102, 103 (2 users: Maria promoted + Antonio)
  Grade 10: user_id 103, 105, ? (all promoted from 9)
  Graduated: user_id 104 (removed from users table)
```

---

## 5. State Machine Diagram

```
                        ┌──────────────┐
                        │ NOT ARCHIVED  │
                        │ (Active User) │
                        └───────┬───────┘
                                │
                    Archive Process Triggered
                                │
                                ▼
┌──────────────────────────────────────────────────────────────┐
│                   ARCHIVE PROCESS                           │
│                                                              │
│  ┌─────────────────────────────────────────────────────┐   │
│  │ 1. Create Archive Batch Record                      │   │
│  │    Status: PENDING                                  │   │
│  └────────────────────┬────────────────────────────────┘   │
│                       │                                     │
│  ┌────────────────────▼────────────────────────────────┐   │
│  │ 2. Create Student Archive Records                   │   │
│  │    Store complete student snapshot                  │   │
│  └────────────────────┬────────────────────────────────┘   │
│                       │                                     │
│  ┌────────────────────▼────────────────────────────────┐   │
│  │ 3. Update/Delete from Users Table                   │   │
│  │    - Promote: Update grade_level                    │   │
│  │    - Graduate: Delete from users                    │   │
│  └────────────────────┬────────────────────────────────┘   │
│                       │                                     │
│  ┌────────────────────▼────────────────────────────────┐   │
│  │ 4. Update Archive Status: COMPLETED                 │   │
│  └────────────────────┬────────────────────────────────┘   │
│                       │                                     │
└───────────────────────┼──────────────────────────────────────┘
                        │
        ┌───────────────┴───────────────┐
        │                               │
     SUCCESS                          ERROR
        │                               │
        ▼                               ▼
┌──────────────────┐         ┌──────────────────────┐
│ ARCHIVED         │         │ ROLLBACK             │
│ (Completed)      │         │ (Reverted to before) │
└──────────────────┘         └──────────────────────┘
        │                               │
        │                               │
    Data in:                        Data in:
    - users (updated grades)        - users (unchanged)
    - student_archives (complete)   - student_archives (deleted)
    - user_logs (logged)            - user_logs (unchanged)
```

---

## 6. Permission & Role Flow

```
┌─────────────────────┐
│   User Login        │
└────────────┬────────┘
             │
             ▼
┌─────────────────────────────────────────┐
│  Check Session & Role                   │
│  if (!isset($_SESSION['user_id']))      │
│      → Redirect to login                │
│  if ($_SESSION['role_id'] != 2)         │
│      → Redirect to login                │
└────────────┬────────────────────────────┘
             │
        ┌────┴────┐
        │ role_id │
        └────┬────┘
        ┌────┴──────────────────┬────────────────┬────────────┐
        │                       │                │            │
      role=1                role=2              role=3       role=4
     (Admin)              (STAFF)            (TEACHER)    (STUDENT)
        │                   ✓                  ✗              ✗
        │             Has Access          Denied           Denied
        │                   │
        ▼                   ▼
   Can use all         Can use
   admin panels        ARCHIVE
                       feature
                           │
                    ┌──────┴──────────┐
                    │                 │
                    ▼                 ▼
              Export           Archive &
              Students          Transition
                    │                 │
                    └──────┬──────────┘
                           │
                    Create audit trail
                    via log_activity()
```

---

## 7. File Organization

```
c:\xampp\htdocs\larss\
│
├── staff/
│   ├── staff-archive.php ..................... Main UI
│   ├── staff-archive-api.php ................. Backend API
│   ├── staff-userman.php
│   ├── staff-dashboard.php
│   └── ... other staff files
│
├── Database/
│   └── database.php .......................... DB Connection
│
├── ARCHIVE_INSTALLATION_GUIDE.md ............ Setup Instructions
├── ARCHIVE_QUICK_START.md ................... Quick Reference
├── ARCHIVE_FEATURE_GUIDE.md ................. Full Documentation
├── ARCHIVE_USAGE_EXAMPLES.md ................ Real Examples
├── ARCHIVE_IMPLEMENTATION_SUMMARY.md ........ Summary
│
└── install_archive_tables.sql ............... Database Setup
```

---

## 8. Sequence Diagram: Export Process

```
User         Browser         API            Database
  │             │             │               │
  │─ Click -→   │             │               │
  │  Export     │             │               │
  │             │             │               │
  │             │─ POST /api  │               │
  │             │  action=    │               │
  │             │  export     │               │
  │             │             │               │
  │             │             │─ Validate ──→ │
  │             │             │  Session,     │
  │             │             │  Grade, Role  │
  │             │             │               │
  │             │             │─ Query ──────→│
  │             │             │ SELECT users  │
  │             │             │ WHERE grade=? │
  │             │             │               │
  │             │             │←─ Result ─────│
  │             │             │  (200 rows)   │
  │             │             │               │
  │             │             │─ Format ──────→│
  │             │             │ CSV / Excel   │
  │             │             │               │
  │             │←─ File ─────│               │
  │             │  Download   │               │
  │             │             │               │
  │←─ Download ─│             │               │
  │  Started    │             │               │
  │             │             │               │
  ▼             ▼             ▼               ▼
 File        .csv/.xlsx    Task Done    Query Done
 Saved
```

---

## 9. Grade Promotion Logic

```
For Grade 7:           For Grade 8:           For Grade 9:           For Grade 10:
┌─────────────────┐   ┌─────────────────┐   ┌─────────────────┐   ┌─────────────────┐
│ Select all      │   │ Select all      │   │ Select all      │   │ Select all      │
│ Grade 7         │   │ Grade 8         │   │ Grade 9         │   │ Grade 10        │
│ students        │   │ students        │   │ students        │   │ students        │
└────────┬────────┘   └────────┬────────┘   └────────┬────────┘   └────────┬────────┘
         │                     │                     │                     │
         ▼                     ▼                     ▼                     ▼
┌─────────────────┐   ┌─────────────────┐   ┌─────────────────┐   ┌─────────────────┐
│ Archive to      │   │ Archive to      │   │ Archive to      │   │ Archive to      │
│ student_archive │   │ student_archive │   │ student_archive │   │ student_archive │
│ _records        │   │ _records        │   │ _records        │   │ _records        │
│ action='prom'   │   │ action='prom'   │   │ action='prom'   │   │ action='grad'   │
└────────┬────────┘   └────────┬────────┘   └────────┬────────┘   └────────┬────────┘
         │                     │                     │                     │
         ▼                     ▼                     ▼                     ▼
┌─────────────────┐   ┌─────────────────┐   ┌─────────────────┐   ┌─────────────────┐
│ UPDATE users    │   │ UPDATE users    │   │ UPDATE users    │   │ DELETE FROM     │
│ SET grade='8'   │   │ SET grade='9'   │   │ SET grade='10'  │   │ users           │
│ WHERE           │   │ WHERE           │   │ WHERE           │   │ WHERE user_id=? │
│ grade='7'       │   │ grade='8'       │   │ grade='9'       │   │                 │
└────────┬────────┘   └────────┬────────┘   └────────┬────────┘   └────────┬────────┘
         │                     │                     │                     │
         ▼                     ▼                     ▼                     ▼
  Grade 7→8          Grade 8→9            Grade 9→10         Graduated/
  Complete          Complete             Complete           Removed
```

---

## 10. Error Handling Flow

```
Archive Process Started
        │
        ▼
  Try Block
  ────────────────────────────────────────────
  
  1. Validate Input ──→ Error? → Throw Exception
  
  2. BEGIN TRANSACTION
  
  3. Insert Archive Record ──→ Error? → Throw Exception
  
  4. For Each Grade:
     - Query Students ──→ Error? → Throw Exception
     - Insert Archives ──→ Error? → Throw Exception
     - Update/Delete ──→ Error? → Throw Exception
  
  5. Log Activity ──→ Error? → Throw Exception
  
  6. COMMIT TRANSACTION
  
  ────────────────────────────────────────────
        │                      │
     SUCCESS              CATCH EXCEPTION
        │                      │
        ▼                      ▼
    Return:            ROLLBACK TRANSACTION
    {                      │
      success: true,       ▼
      count: X,        Return:
      message: "..."   {
    }                    success: false,
                         message: "Error: ..."
                       }
        │                      │
        ▼                      ▼
   JSON Response    JSON Response
   (200 OK)         (200 with error)
        │                      │
        ▼                      ▼
   Show Success        Show Error
   Reload Page         Keep Form
```

---

This architecture ensures:
- ✅ Clean separation of concerns
- ✅ Robust error handling
- ✅ Data integrity with transactions
- ✅ Secure access control
- ✅ Complete audit trails
- ✅ Scalable design
