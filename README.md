# Cybersecurity Incident Response Management System (CyberIR)

**A complete full-stack web application for managing security incidents in a Security Operations Center (SOC) environment.**

## 🎯 Features

### 1. **Role-Based Access Control (RBAC)**
- **Admin**: Full system control, user management, incident assignment, incident closure
- **Analyst**: Create incidents, investigate assigned incidents, upload evidence, write response logs
- **Viewer**: Read-only access to view incidents, logs, and reports

### 2. **Authentication & Security**
- Secure login system using `password_hash()` and `password_verify()`
- PHP sessions with role-based redirection
- CSRF token protection
- Prepared statements to prevent SQL injection
- Input validation and sanitization

### 3. **Incident Lifecycle Management**
- Status workflow: Open → Investigating → Contained → Resolved → Closed
- Severity levels: Low, Medium, High, Critical
- Incident types: Malware, Phishing, Data Breach, Unauthorized Access, DDoS, etc.
- Automatic incident creation and closure
- Read-only mode for closed incidents

### 4. **Core Modules**

#### A. Incident Management
- Create, view, edit, and close incidents
- Search incidents by title and description
- Filter by severity level and status
- View incident timeline and history

#### B. Assignment Module
- Admin can assign incidents to analysts
- Set assignment deadlines
- Track assignment status
- View assignment history

#### C. Response Logging
- Analysts document investigation actions
- Timeline of all log entries with timestamps
- Automatic analyst and timestamp tracking
- Full text searchable logs

#### D. Evidence Management
- Upload investigation evidence files
- Support for: JPG, PNG, PDF, LOG, TXT, CSV, ZIP
- File size limit: 10MB per file
- Organized storage by incident
- Evidence metadata tracking

#### E. Resolution Module
- Document root cause analysis
- Add resolution summary
- Lock resolved incidents from editing

### 5. **Dashboards**
- **Admin Dashboard**: Total incidents, critical incidents, unassigned incidents, active incidents counter
- **Analyst Dashboard**: Incidents assigned to analyst, active investigations counter
- **Viewer Dashboard**: Read-only statistics and reports

### 6. **Reporting & Analytics**
- Incidents by severity distribution
- Incidents by status breakdown
- Top incident reporters
- Top analysts by assignment count
- Printable reports

---

## 📋 Installation & Setup

### Prerequisites
- XAMPP installed (Apache, MySQL, PHP)
- Windows 10/11 or equivalent
- Administrator rights to modify XAMPP files

### Step 1: Create Directory
1. Navigate to: `C:\xampp\htdocs\`
2. Create a new folder: `cyber_ir`

### Step 2: Copy Files
Copy all project files to `C:\xampp\htdocs\cyber_ir\`

Directory structure should be:
```
cyber_ir/
├── assets/
│   ├── css/style.css
│   └── js/script.js
├── config/
│   └── db.php
├── includes/
│   ├── auth.php
│   ├── header.php
│   └── footer.php
├── uploads/
├── login.php
├── dashboard.php
├── incidents.php
├── create_incident.php
├── incident_details.php
├── edit_incident.php
├── assign_incident.php
├── add_log.php
├── upload_evidence.php
├── close_incident.php
├── reports.php
├── users.php
├── create_user.php
├── schema.sql
└── README.md
```

### Step 3: Create Database

1. **Start XAMPP**:
   - Open XAMPP Control Panel
   - Start Apache and MySQL services

2. **Access PhpMyAdmin**:
   - Open browser: `http://localhost/phpmyadmin`
   - Login with default credentials (no password required)

3. **Create Database**:
   - Click "New" in the left sidebar
   - Database name: `cyber_ir`
   - Collation: `utf8mb4_unicode_ci`
   - Click "Create"

4. **Import Schema**:
   - Select the `cyber_ir` database
   - Click "Import" tab
   - Browse and select `schema.sql` from the project folder
   - Click "Go"

### Step 4: Verify Database Connection

1. Navigate to: `http://localhost/cyber_ir/login.php`
2. If you see the login page without errors, database connection is successful

---

## 🔐 Default Login Credentials

**All demo accounts have password:** `password123`

| Role | Email | Password |
|------|-------|----------|
| Admin | admin@cyber.local | password123 |
| Analyst | analyst@cyber.local | password123 |
| Viewer | viewer@cyber.local | password123 |

---

## 📱 Usage Guide

### For Admins:
1. **Login** with admin credentials
2. **Dashboard**: View system statistics
3. **Incidents**: View all incidents in the system
4. **+ Create Incident**: Report a new security incident
5. **Assign Incident**: Assign incidents to analysts with deadlines
6. **Users**: Manage user accounts and roles
7. **Reports**: View system-wide analytics and statistics
8. **Close Incident**: Document root cause and close incidents

### For Analysts:
1. **Login** with analyst credentials
2. **Dashboard**: View assigned incidents
3. **Incidents**: View all incidents
4. **+ Create Incident**: Report a new security incident
5. **Incident Details**:
   - Add response logs to document investigation
   - Upload evidence files
   - Update incident status

### For Viewers:
1. **Login** with viewer credentials
2. **Dashboard**: View statistics (read-only)
3. **Incidents**: View all incidents (read-only)
4. **Reports**: View analytics and statistics

---

## 🗄️ Database Schema

### Users Table
```sql
- user_id (Primary Key)
- name (VARCHAR 255)
- email (VARCHAR 255, UNIQUE)
- password (VARCHAR 255, hashed)
- role (ENUM: admin, analyst, viewer)
- status (ENUM: active, inactive)
- created_at (TIMESTAMP)
- last_login (TIMESTAMP)
```

### Incidents Table
```sql
- incident_id (Primary Key)
- title (VARCHAR 255)
- description (LONGTEXT)
- type (VARCHAR 100)
- severity (ENUM: Low, Medium, High, Critical)
- status (ENUM: Open, Investigating, Contained, Resolved, Closed)
- reported_by (FK → users.user_id)
- date_reported (TIMESTAMP)
- date_updated (TIMESTAMP)
- FULLTEXT INDEX on title and description
```

### Assignments Table
```sql
- assignment_id (Primary Key)
- incident_id (FK → incidents.incident_id)
- assigned_to (FK → users.user_id)
- assigned_by (FK → users.user_id)
- deadline (DATETIME)
- status (ENUM: pending, accepted, completed)
- assigned_at (TIMESTAMP)
```

### Response Logs Table
```sql
- log_id (Primary Key)
- incident_id (FK → incidents.incident_id)
- analyst_id (FK → users.user_id)
- action_taken (LONGTEXT)
- log_time (TIMESTAMP)
```

### Evidence Table
```sql
- evidence_id (Primary Key)
- incident_id (FK → incidents.incident_id)
- file_name (VARCHAR 255)
- file_path (VARCHAR 500)
- file_type (VARCHAR 50)
- file_size (INT)
- uploaded_by (FK → users.user_id)
- uploaded_at (TIMESTAMP)
```

### Resolutions Table
```sql
- resolution_id (Primary Key)
- incident_id (FK → incidents.incident_id, UNIQUE)
- root_cause (LONGTEXT)
- summary (LONGTEXT)
- resolved_by (FK → users.user_id)
- resolved_date (TIMESTAMP)
```

---

## 🎨 UI Design Features

- **Light Theme**: Professional light background for extended use
- **Responsive Design**: Works on Desktop, Tablet, and Mobile
- **Severity Color Coding**:
  - 🟢 Low: Green
  - 🟡 Medium: Yellow
  - 🟠 High: Orange
  - 🔴 Critical: Red
- **Status Badges**: Visual status indicators for incident states
- **Smooth Animations**: Animated counters and transitions
- **Glassmorphism Elements**: Modern card designs with subtle effects
- **Professional Typography**: Clean, readable fonts and spacing

---

## 🔒 Security Features

1. **Password Security**
   - Passwords hashed using `password_hash()`
   - Verification using `password_verify()`
   - Minimum 6 characters enforced

2. **SQL Injection Prevention**
   - All SQL queries use prepared statements
   - Parameterized binding for user inputs

3. **Cross-Site Request Forgery (CSRF)**
   - CSRF tokens on all forms
   - Token verification before processing

4. **Input Validation**
   - All user inputs validated and sanitized
   - Email format validation
   - Type checking for all parameters

5. **Access Control**
   - Role-based authorization checks
   - Session verification on every page
   - Automatic redirection for unauthorized access

6. **File Upload Security**
   - Whitelist of allowed file types
   - File size limit (10MB)
   - Secure file storage location

---

## 🚀 Access the System

### Local URLs

| Page | URL |
|------|-----|
| Login | `http://localhost/cyber_ir/login.php` |
| Dashboard | `http://localhost/cyber_ir/dashboard.php` |
| Incidents | `http://localhost/cyber_ir/incidents.php` |
| Create Incident | `http://localhost/cyber_ir/create_incident.php` |
| Incident Details | `http://localhost/cyber_ir/incident_details.php?id=1` |
| Assign Incident | `http://localhost/cyber_ir/assign_incident.php?id=1` |
| Add Response Log | `http://localhost/cyber_ir/add_log.php?id=1` |
| Upload Evidence | `http://localhost/cyber_ir/upload_evidence.php?id=1` |
| Close Incident | `http://localhost/cyber_ir/close_incident.php?id=1` |
| Reports | `http://localhost/cyber_ir/reports.php` |
| Users | `http://localhost/cyber_ir/users.php` |
| Create User | `http://localhost/cyber_ir/create_user.php` |
| Logout | `http://localhost/cyber_ir/logout.php` |

---

## 🛠️ Configuration

### Database Connection
Edit `config/db.php`:
```php
$servername = "localhost";
$username = "root";
$password = "";
$database = "cyber_ir";
```

### Upload Directory
Evidence files are stored in: `uploads/[incident_id]/[filename]`
Ensure directory has write permissions: `chmod 755 uploads`

---

## 📊 Creating Test Data

After login as Admin:

1. **Create Users**:
   - Navigate to Users → Create User
   - Add at least 2 analysts

2. **Create Incidents**:
   - Navigate to Create Incident
   - Fill in incident details
   - Submit to create

3. **Assign Incidents**:
   - View incident details
   - Click "Assign"
   - Select analyst and deadline
   - Submit

4. **Add Logs** (as Analyst):
   - Login as analyst
   - View assigned incident
   - Click "Add Log Entry"
   - Document investigation actions

5. **Upload Evidence** (as Analyst):
   - While investigating incident
   - Click "Upload Evidence"
   - Attach investigation files (screenshots, logs, etc.)

6. **Close Incident** (as Admin):
   - View incident
   - Click "Close"
   - Enter root cause and resolution
   - Submit

---

## 🐛 Troubleshooting

### Database Connection Error
- Ensure MySQL service is running in XAMPP
- Check credentials in `config/db.php`
- Verify database `cyber_ir` exists in PhpMyAdmin

### Login Issues
- Clear browser cookies: Press `Ctrl+Shift+Delete`
- Try demo credentials exactly as shown
- Check user status is "active" in users table

### File Upload Errors
- Check `uploads/` folder exists and is writable
- Verify file is under 10MB
- Confirm file type is in allowed list (jpg, png, pdf, log, txt, csv, zip)

### Permission Issues
- Ensure PHP has permission to write to `uploads/` folder
- On Windows, right-click folder → Properties → Security → Edit → Full Control

---

## 📞 Support

For issues or questions, check:
1. XAMPP log files: `C:\xampp\apache\logs\error.log`
2. MySQL errors in PhpMyAdmin
3. Browser console for JavaScript errors (F12)

---

## 📄 License

This project is for educational and commercial use.

---

## ✨ System Information

- **Framework**: Pure PHP (Procedural)
- **Database**: MySQL
- **Frontend**: HTML5, CSS3, JavaScript
- **PHP Version**: 7.4+
- **Server**: Apache (XAMPP)
- **Response**: Production-ready, fully functional

---

**Version**: 1.0.0  
**Last Updated**: March 2026  
**Status**: ✅ Fully Functional

---

### 🎓 Learning Outcomes

This system demonstrates:
- ✅ Full-stack web application development
- ✅ Database design and normalization
- ✅ User authentication and authorization
- ✅ Role-based access control (RBAC)
- ✅ CRUD operations
- ✅ File upload handling
- ✅ Input validation and sanitization
- ✅ CSRF protection
- ✅ Responsive web design
- ✅ Real-world security best practices
