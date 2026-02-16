# Staff Skills Portal

A comprehensive procedural PHP web application for managing staff information, skills, expertise, and project portfolios. Built with vanilla JavaScript, Bootstrap 5, and MySQL, running on XAMPP with PDO for secure database access. The system allows organizations to manage staff profiles, skills, software expertise, languages, projects, and organizational data through structured role-based access control (RBAC).

## Table of Contents

- [Features](#features)
- [Technology Stack](#technology-stack)
- [Installation & Setup](#installation--setup)
- [Database Configuration](#database-configuration)
- [Login Credentials](#login-credentials)
- [Project Structure](#project-structure)
- [User Roles & Permissions](#user-roles--permissions)
- [Key Features](#key-features)
- [Security Features](#security-features)
- [Testing Checklist](#testing-checklist)
- [Troubleshooting](#troubleshooting)

---

## Features

### User Management
- Create, read, update, delete users (Admin only)
- Auto-generated index numbers with intelligent reuse
- Role-based access control (Admin, HR, Staff)
- Password management with bcrypt hashing
- Search and filter users by multiple criteria

### Skills Portfolio
- Language proficiency management (Beginner to Fluent)
- Software/tool expertise tracking with years of experience
- Project portfolio with detailed descriptions and links
- Proficiency level breakdowns and analytics

### Admin Dashboard
- Organizational analytics (user stats, gender distribution, roles)
- Most skilled staff rankings
- Language and software expertise statistics
- Recent projects overview
- "See More" functionality for expanded lists

### Staff Features
- Personal skills dashboard
- Profile management (view/edit own details)
- View staff directory (Admin/HR only)
- Edit own languages, software skills, and projects

### Reference Data Management
- Manage roles, duty stations, education levels
- Manage available languages and software tools
- Manage current location suggestions (Admin/HR)

### UX Enhancements
- Responsive sidebar navigation
- Card-based skill displays
- Color-coded proficiency badges
- "See More / Show Less" toggle for long lists
- Delete confirmation modals
- Real-time form validation

---

## Technology Stack

| Component           | Technology |
|---------------------|-----------|
| **Backend**         | PHP 8+ (Procedural) |
| **Database**        | MySQL 5.7+ / MariaDB |
| **Frontend**        | HTML5, Bootstrap 5.3.0, Vanilla JavaScript |
| **Icons**           | Bootstrap Icons 1.11.0 |
| **Server**          | Apache (XAMPP) |
| **Database Access** | PDO with Prepared Statements |
| **Authentication**  | Session-based, bcrypt password hashing |

---

## Installation & Setup

### Prerequisites

- XAMPP installed with PHP 8+ and MySQL 5.7+
- Apache and MySQL services running
- Basic knowledge of file management and MySQL

### Step 1: Copy Project Files

```bash
# Copy project to XAMPP htdocs
C:\xampp\htdocs\unep\
```

### Step 2: Start XAMPP Services

1. Open **XAMPP Control Panel**
2. Click **Start** next to **Apache** and **MySQL**
3. Wait for both to show "Running"

### Step 3: Create Database & Import Schema

#### Option A: Using phpMyAdmin (GUI)

1. Open http://localhost/phpmyadmin
2. Click **New** (left sidebar)
3. Database name: `staff_skills_portal`
4. Collation: `utf8mb4_unicode_ci`
5. Click **Create**
6. Click **Import** tab
7. Upload file: `C:\xampp\htdocs\unep\database\schema.sql`
8. Click **Import**

#### Option B: Using MySQL CLI

```bash
cd C:\xampp\mysql\bin
mysql -u root -p

# Press Enter if prompted for password (XAMPP default is no password)

CREATE DATABASE IF NOT EXISTS staff_skills_portal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE staff_skills_portal;
SOURCE C:/xampp/htdocs/unep/database/schema.sql;
EXIT;
```

### Step 4: Configure Database Connection

Update [config/database.php](config/database.php) if needed:

```php
define('DB_HOST', '127.0.0.1');          // MySQL host
define('DB_NAME', 'staff_skills_portal'); // Database name
define('DB_USER', 'root');               // MySQL user
define('DB_PASS', '');                   // MySQL password (empty for XAMPP default)
define('BASE_URL', 'http://localhost/unep');
```

### Step 5: Access the Application

1. Open http://localhost/unep
2. Redirect to login page
3. Login with credentials (see below)

---

## Login Credentials

All demo users have the same password: `User1234`

### Admin Account
- **Email**: `admin@example.com`
- **Index**: ADM001
- **Password**: User1234

### HR Account
- **Email**: `hr@example.com`
- **Index**: HR001
- **Password**: User1234

### Staff Accounts (Sample)
- **Email**: `alice@example.com` - `tina@example.com`
- **Indices**: STF001 - STF020
- **Password**: User1234 (all staff)

---

## Project Structure

```
unep/
├── config/
│   └── database.php              # PDO connection, CSRF helpers
├── includes/
│   ├── auth.php                  # Login/logout, session functions
│   ├── middleware.php            # require_login(), require_role()
│   ├── header.php                # Sidebar navigation + top bar
│   └── footer.php                # Page footer
├── pages/
│   ├── dashboard.php             # Admin/HR analytics dashboard
│   ├── manage_users.php          # List, search users
│   ├── add_user.php              # Create new user
│   ├── edit_user.php             # Edit user details
│   ├── delete_user.php           # Delete user (POST handler)
│   ├── manage_roles.php          # List, manage roles
│   ├── add_role.php              # Create role
│   ├── edit_role.php             # Edit role
│   ├── delete_role.php           # Delete role (POST handler)
│   ├── manage_locations.php      # Manage duty stations
│   ├── manage_current_locations.php     # Manage location suggestions
│   ├── manage_education.php      # Manage education levels
│   ├── manage_languages.php      # Manage languages
│   ├── manage_software_expertise.php    # Manage software/tools
│   ├── user_languages.php        # User language management
│   ├── user_software_expertise.php     # User software management
│   ├── user_projects.php         # User project portfolio
│   ├── profile.php               # User profile + all skills
│   └── unauthorized.php          # Access denied page
├── assets/
│   ├── css/
│   │   └── styles.css            # Custom Bootstrap overrides
│   └── js/
│       └── app.js                # Client-side interactivity
├── database/
│   └── schema.sql                # Complete MySQL schema + seed data
├── index.php                     # Home page / redirect
├── login.php                     # Login form
├── logout.php                    # Logout handler
└── README.md                     # This file
```

---

## User Roles & Permissions

### Admin
- Full system access
- Manage all users, roles, reference data
- View analytics dashboard
- Cannot delete own account

### HR
- Manage staff users (add/edit/delete)
- Manage current location suggestions
- View analytics dashboard
- Cannot delete own account

### Staff
- View own profile and skills
- Edit own profile (limited: password only)
- Edit own languages, software, projects
- View other staff profiles (read-only)
- Cannot access admin features

### Role-Based Navigation

| Feature                  | Admin | HR       | Staff |
|--------------------------|-------|----------|----|
| Dashboard                | ✅ | ✅         | ❌ |
| Manage Users             | ✅ | ✅ (Staff) | ❌ |
| Manage Roles             | ✅ | ❌         | ❌ |
| Manage Reference Data    | ✅ | ❌         | ❌ |
| Manage Current Locations | ✅ | ✅         | ❌ |
| View All Profiles        | ✅ | ✅         | ❌ |
| Edit Own Profile         | ✅ | ✅         | ✅ |
| Edit Own Skills          | ✅ | ✅         | ✅ |

---

## Key Features

### 1. Index Number Management
- **Auto-generated** with prefix: ADM, HR, STF
- **Intelligent reuse**: When role changes, old index is freed for reuse
- **Example**: STF005 becomes ADM003 when promoted to Admin
- Next new Staff gets STF005 (reusing the freed number)

### 2. Staff Skills Portfolio
Each user can manage:
- **Languages**: Add proficiency levels (Beginner, Intermediate, Advanced, Fluent)
- **Software**: Add with proficiency (Beginner, Intermediate, Advanced, Expert) + years of experience
- **Projects**: Title, description, technologies, dates, role, links

### 3. Dashboard Analytics
**Overview Cards**:
- Total users, gender distribution, roles breakdown
- Education distribution, remote availability
- Language and software statistics

**Analytics Tables**:
- Most skilled staff (ranked by skill count)
- Top languages and software tools
- Proficiency breakdown by level
- Recent projects with staff details

### 4. See More Functionality
- Lists showing first 6 items by default
- "See More" button expands to show all items
- "Show Less" collapses back to 6
- Applies to: languages, software, projects, dashboard tables

### 5. Form Validation
- **Client-side**: HTML5 + JavaScript validation
- **Server-side**: All inputs validated before processing
- **Password strength**: Min 8 chars, uppercase, lowercase, number, special char
- **CSRF protection**: Token validation on all POST forms
- **Inline errors**: Displayed next to invalid fields

---

## Security Features

### Authentication & Authorization
- ✅ Session-based authentication
- ✅ bcrypt password hashing (`password_hash()`)
- ✅ Role-based access control (RBAC)
- ✅ Middleware permission checks
- ✅ Unauthorized redirect to restricted pages

### Data Protection
- ✅ **PDO Prepared Statements**: All SQL queries prevent injection
- ✅ **Input Sanitization**: `htmlspecialchars()` for display
- ✅ **Email Validation**: `filter_var()` with FILTER_VALIDATE_EMAIL
- ✅ **Type Casting**: `intval()`, `floatval()` for numeric data
- ✅ **CSRF Protection**: Token generation and validation on forms

### Database Security
- ✅ Foreign key constraints (referential integrity)
- ✅ Unique constraints (email, index_number, language/software combinations)
- ✅ ON DELETE CASCADE for related records
- ✅ UTF-8MB4 encoding for international text
- ✅ InnoDB engine for transaction support

### Business Logic Protection
- ✅ Admin cannot delete themselves
- ✅ Roles with assigned users cannot be deleted
- ✅ Only authorized staff can edit own profiles
- ✅ Password change requires current password verification

---

## Database Schema

### Core Tables (10)
1. **roles** - Admin, HR, Staff role definitions
2. **users** - Staff member records
3. **education_levels** - Diploma, Bachelor, Master, PhD, Certificate
4. **duty_stations** - Office locations
5. **current_locations** - Temporary location suggestions
6. **languages** - Available languages
7. **software_expertise** - Software/tools with categories
8. **user_languages** - User language proficiency (Many-to-Many)
9. **user_software_expertise** - User software skills (Many-to-Many)
10. **projects** - User project portfolio

### Seed Data
- **3 Roles**: Admin, HR, Staff
- **22 Users**: 1 Admin, 1 HR, 20 Staff
- **5 Education Levels**: Diploma, Bachelor, Master, PhD, Certificate
- **21 Duty Stations**: Various office locations
- **10 Languages**: English, Spanish, French, German, Mandarin, Arabic, Portuguese, Japanese, Italian, Swahili
- **15 Software Tools**: Microsoft Office, Programming languages, Web frameworks, Databases, DevOps, Data Visualization, CRM
- **8 Sample Projects**: Various project portfolios
- **60+ User Skill Assignments**: Languages, software, and projects for demo users

---

## Testing Checklist

### Authentication
- [ ] Login with valid credentials - redirects to home/dashboard
- [ ] Login with invalid password - error message displays
- [ ] Login with non-existent email - error message displays
- [ ] Logout - redirects to login page
- [ ] Direct page access without login - redirects to login

### Authorization
- [ ] Staff accessing admin page - redirects to unauthorized
- [ ] HR accessing Admin-only features - redirects to unauthorized
- [ ] Admin can access all pages

### User Management
- [ ] Create user - index number auto-generated
- [ ] Edit user - changes persist
- [ ] Change user role - index number reassigned
- [ ] Delete user - confirm modal appears
- [ ] Attempt to delete self - error message, account not deleted
- [ ] Search/filter users - results filtered correctly

### Role Management
- [ ] Create role - appears in user role dropdowns
- [ ] Edit role - changes reflected
- [ ] Attempt to delete role with users - error message displays
- [ ] Delete role with no users - deletion succeeds

### Skills Management
- [ ] Add language - appears in profile
- [ ] Edit language proficiency - updates correctly
- [ ] Delete language - removed from profile
- [ ] Add software with years - saves both values
- [ ] Add project - displays in portfolio

### Dashboard & Analytics
- [ ] User count statistics display
- [ ] Gender distribution shows percentages
- [ ] Most skilled staff table populates
- [ ] Click "See More" - list expands
- [ ] Click "Show Less" - list collapses to 6 items

### Form Validation
- [ ] Submit empty required field - error message
- [ ] Password < 8 chars - strength indicator shows red
- [ ] Confirm password mismatch - inline error
- [ ] Duplicate email - error message
- [ ] CSRF token validation - protection works

### UI/UX
- [ ] Responsive layout - works on mobile/tablet/desktop
- [ ] Sidebar collapses on mobile
- [ ] Color-coded badges - correct colors
- [ ] Modal confirmations - work as expected
- [ ] Success messages - display after actions

---

## Troubleshooting

### "Cannot modify header information - headers already sent"
**Cause**: Output sent before `header()` call  
**Fix**: Ensure header.php include is AFTER all POST processing logic and before HTML output

### "PDOException: Column not found"
**Cause**: SQL syntax error or typo  
**Fix**: Check for reserved keywords (use `use` as table alias → change to `usp`)

### "SQLSTATE[23000]: Integrity constraint violation"
**Cause**: Foreign key violation or duplicate unique value  
**Fix**: 
- For FK: Ensure referenced record exists
- For duplicates: Check email, index_number uniqueness

### "Cannot delete role that is assigned to users"
**Expected**: Role has active users  
**Solution**: Delete or reassign users first, then delete role

### "You cannot delete your own account"
**Expected**: Admin trying to delete themselves  
**Solution**: Have another admin delete the account if needed

### MySQL not connecting
1. Check XAMPP Control Panel → MySQL status
2. Verify config/database.php credentials
3. Ensure database `staff_skills_portal` exists
4. Test connection with phpMyAdmin

### Apache not starting
1. Check if port 80 is in use: `netstat -ano | findstr :80`
2. If in use, change Apache port in `httpd.conf`
3. Restart Apache

### Page shows blank or error
1. Check browser console (F12 → Console) for JS errors
2. Check XAMPP error logs: `C:\xampp\apache\logs\error.log`
3. Verify all required SQL tables exist: `SHOW TABLES;`

---

## Default Configuration

**Database Connection** (config/database.php)
```php
DB_HOST: 127.0.0.1
DB_NAME: staff_skills_portal
DB_USER: root
DB_PASS: (empty)
BASE_URL: http://localhost/unep
```

**Session Settings**
- Session timeout: PHP default (24 minutes inactivity)
- Session name: PHPSESSID
- CSRF token stored in `$_SESSION['csrf_token']`

**Password Hashing**
- Algorithm: bcrypt
- Cost: $2y$10$ (default)
- Verification: password_verify() function

---

## Project Statistics

| Metric                | Count |
|-----------------------|-------|
| PHP Pages             | 24 |
| Database Tables       | 10 |
| Seed Records          | 170+ |
| Prepared Queries      | 50+ |
| Security Functions    | 8+ |
| Form Validation Rules | 15+ |
| CSS Classes           | 30+ |
| JavaScript Functions  | 5+ |

---

## Future Enhancements

- [ ] Password reset via email
- [ ] User profile photo/avatar upload
- [ ] Activity logs and audit trail
- [ ] Advanced search with filters
- [ ] Export user/skill data to CSV/PDF
- [ ] Skill endorsement between staff
- [ ] Performance metrics and trends
- [ ] API endpoints for mobile apps
- [ ] Two-factor authentication (2FA)
- [ ] Scheduled notifications and reminders

---

## License

This is a demonstration project for educational purposes.

---

## Support

For issues or questions:
1. Check the [Troubleshooting](#troubleshooting) section
2. Verify database schema is complete: `DESCRIBE users;`
3. Check XAMPP service logs for errors
4. Ensure all required tables exist in the database

---

**Last Updated**: February 15, 2026  
**PHP Version**: 8.0+  
**MySQL Version**: 5.7+  
**Bootstrap Version**: 5.3.0

