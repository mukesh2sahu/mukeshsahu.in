# MukeshSahu – Personal Learning & Technology Platform

A modern, professional, dynamic, and responsive web platform built with **Core PHP (PDO)** and **MySQL/MariaDB**. The platform serves as a personal branding hub for Mukesh Sahu featuring members-only IT job opportunities, online courses with YouTube video player integration, a curated AI tools directory, public technology blogs, a contact messaging system, user dashboards, and a comprehensive administrative panel.

---

## 🔑 Default Credentials

| Role | Email / Username | Password | Access Area |
|---|---|---|---|
| **Super Admin** | `admin@mukeshsahu.in` | `Admin@12345` | **Admin Panel** (`/admin` or `/admin/dashboard.php`) & Full Platform |
| **Registered User** | `user@mukeshsahu.in` | `User@12345` | **User Portal** (`/jobs.php`, `/courses.php`, `/ai-tools.php`, `/user/dashboard.php`) |

> [!NOTE]
> All passwords in the database are secured using PHP's native `password_hash()` with the `PASSWORD_BCRYPT` algorithm.

---

## 🚀 Quick Setup & Installation (XAMPP)

### 1. Prerequisites
- **XAMPP** (with Apache, PHP 8.0+, and MySQL/MariaDB)
- **Web Browser** (Chrome, Edge, Firefox, etc.)

### 2. Database Configuration
1. Start **Apache** and **MySQL** from the XAMPP Control Panel.
2. Open **phpMyAdmin** (`http://localhost/phpmyadmin`) or MySQL CLI.
3. Import the database file:
   - File path: `database/mukeshsahu.sql`
   - Database name: `mukeshsahu_db`
4. The database credentials are pre-configured in `config/database.php`:
   ```php
   define('DB_HOST', '127.0.0.1');
   define('DB_PORT', '3306');
   define('DB_NAME', 'mukeshsahu_db');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   ```

### 3. Launching the Application
- **Via XAMPP Apache**:
  Navigate to: `http://localhost/mukeshsahu.in`
- **Via PHP Built-in Server**:
  Open terminal in project root and run:
  ```powershell
  php -S 127.0.0.1:8000
  ```
  Then visit: `http://127.0.0.1:8000`

---

## 🛠️ Technology Stack

- **Backend**: Core PHP 8+ with Object-Oriented PDO database abstraction and prepared statements.
- **Database**: MySQL / MariaDB (24 normalized relational tables with foreign keys and indexes).
- **Frontend**: HTML5, CSS3, JavaScript (ES6+), Bootstrap 5.3, Font Awesome 6.5.
- **Libraries & Integrations**:
  - **YouTube IFrame API**: Embedded video streaming with syllabus and lecture tracking.
  - **Chart.js**: Admin dashboard growth metrics, category distributions, and analytics.
  - **DataTables**: Responsive table pagination, search, and sorting across all admin management modules.
  - **AJAX**: Instant bookmarking for IT jobs and AI tools, and real-time lecture progress saving.

---

## 📋 Platform Sections & Navigation Rules

| Page / Module | Guest Access | Logged-in User | Admin Access |
|---|---|---|---|
| **Home** (`/index.php`) | ✅ Yes | ✅ Yes | ✅ Yes |
| **About Me** (`/about.php`) | ✅ Yes | ✅ Yes | ✅ Yes |
| **IT Jobs** (`/jobs.php`) | 🔒 Login Required | ✅ Yes | ✅ Yes |
| **Courses** (`/courses.php`) | 🔒 Login Required | ✅ Yes | ✅ Yes |
| **AI Tools** (`/ai-tools.php`) | 🔒 Login Required | ✅ Yes | ✅ Yes |
| **Blogs** (`/blogs.php`) | ✅ Yes | ✅ Yes | ✅ Yes |
| **Contact** (`/contact.php`) | ✅ Yes | ✅ Yes | ✅ Yes |
| **User Dashboard** (`/user/dashboard.php`) | 🔒 Login Required | ✅ Yes | ✅ Yes |
| **Admin Console** (`/admin/dashboard.php`) | ⛔ Restricted | ⛔ Restricted | ✅ Yes |

*Guests attempting to access IT Jobs, Courses, or AI Tools are automatically redirected to the login page with a notification. Upon successful login, users are returned to their originally requested page.*

---

## 🌟 Key Features

### 1. Authentication & Security
- Secure registration with duplicate email validation, mobile verification, and terms agreement.
- Login system with brute-force rate limiting (locks account for 15 minutes after 5 failed attempts).
- Session-based authentication with `HttpOnly` and `SameSite` cookie flags.
- CSRF token validation on all POST requests and AJAX endpoints.
- Password recovery with expiring 1-hour secure tokens.

### 2. IT Jobs Portal (`/jobs.php`)
- Verified job listings across 11 IT categories (PHP, Python, React, AI/ML, DevOps, Internship, etc.).
- Multi-criteria filtering by keyword, category, work mode (Remote/Hybrid/On-site), and experience.
- Interactive job bookmarks/saved jobs with AJAX toggle.
- Detailed job pages (`/job-detail.php?id=...`) with company profile, salary range, and application links.

### 3. Online Courses with YouTube Integration (`/courses.php` & `/watch.php`)
- Zero local video storage: 100% powered by embedded YouTube video player integration.
- Courses structured into modules and sequenced video lectures.
- Real-time progress tracking: "Mark as Completed" AJAX toggle recalculates completion percentage dynamically.
- "Continue Watching" feature resumes playback from the student's last accessed lecture.

### 4. Curated AI Tools Directory (`/ai-tools.php`)
- Curated catalog of developer AI assistants, chatbots, and automation tools.
- Filter by category and pricing model (Free, Freemium, Paid).
- Bookmark favorite AI platforms into personalized user dashboard.

### 5. Public Technology Blogs (`/blogs.php`)
- In-depth technical articles with rich content, tags, reading time, and view counts.
- Search and filter by category and topic tags.
- Social sharing shortcuts for Twitter/X, LinkedIn, and WhatsApp.

### 6. User Dashboard (`/user/`)
- Enrolled courses with visual progress bars.
- Saved jobs with quick apply and remove actions.
- Bookmarked AI tools with direct launch links.
- Profile management (headline, bio, skills, location, avatar upload) and password change.

### 7. Comprehensive Admin Panel (`/admin/`)
- **Analytical Dashboard**: Key metrics cards and Chart.js graphs for user signups, course enrollments, and job distributions.
- **User Management**: View, add, edit role (`super_admin`, `admin`, `user`), activate, suspend, or delete users.
- **Job Management**: Full CRUD operations, category manager, and featured job toggles.
- **Course Management**: Course editor, syllabus module builder, interactive YouTube video lecture sequencer, in-place lecture editing modal, live YouTube video ID/thumbnail detector, in-app video preview modal, drag/move sequence reordering, free preview toggles, and student enrollment tracker.
- **AI Tools Management**: Add and update AI platform entries, ratings, features, and links.
- **Blog Management**: Write, edit, publish, or draft articles with SEO metadata.
- **Contact Management**: Inquiries inbox, read/unread status toggle, and internal admin reply notes.
- **Website Settings**: Site brand name, contact details, social links, SEO defaults, and dynamic About Me biographical content.
- **Reports & Export**: Summary tables and one-click **CSV data exports** for Users, Course Enrollments, Job Postings, and Contact Inquiries.

---

## 📁 Project Architecture

```
mukeshsahu.in/
├── config/              # Database connection & application settings
├── includes/            # Reusable header, footer, navbar, auth-check, helper functions
├── database/            # Database schema & seed data (mukeshsahu.sql)
├── auth/                # Login, register, logout, password resets, email verification
├── user/                # User dashboard, profile settings, enrolled courses, saved items
├── modules/             # AJAX endpoints for bookmarks & course progress tracking
├── admin/               # Full administrative console, modules, analytics & settings
│   ├── users/           # User management
│   ├── jobs/            # Job postings & categories
│   ├── courses/         # Courses, modules, lectures & enrollments
│   ├── ai-tools/        # AI directory management
│   ├── blogs/           # Article management
│   ├── contacts/        # Inquiry inbox
│   ├── settings/        # System & About Me settings
│   └── reports/         # Analytical reports & CSV exporter
├── assets/              # Custom CSS, JavaScript, images, uploads
├── index.php            # Homepage
├── about.php            # Dynamic About Me
├── contact.php          # Contact page
├── jobs.php             # IT Jobs portal
├── courses.php          # Courses catalog
├── watch.php            # Video watch player
├── ai-tools.php         # AI directory
├── blogs.php            # Public tech blogs
├── .htaccess            # Clean URLs & security rules
├── sitemap.xml          # XML sitemap
└── robots.txt           # Search engine directives
```

---

## 🔒 Security Best Practices Implemented

- **Prepared Statements Everywhere**: All database interactions use PDO parameterized queries, preventing SQL injection.
- **XSS Prevention**: User and database outputs are escaped using `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`.
- **CSRF Defense**: Unique session CSRF tokens verified on every POST action.
- **Role-Based Authorization**: Middleware functions (`require_login()`, `require_admin()`, `require_super_admin()`) protect all restricted endpoints.
- **MIME-Type & File Upload Verification**: File upload validation ensuring strict file extension and MIME-type integrity with size caps.
- **Activity Logging**: Administrative actions are tracked in the `admin_activity_logs` table with action type, details, and client IP.
