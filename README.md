# Jobix — Recruitment & Job Portal System

> A role-based recruitment platform built with **PHP 8**, **MySQL**, **Bootstrap 5**, and vanilla JavaScript.

![PHP](https://img.shields.io/badge/PHP_8-777BB4?style=flat-square&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=flat-square&logo=mysql&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap_5-7952B3?style=flat-square&logo=bootstrap&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-111827?style=flat-square&logo=javascript&logoColor=F7DF1E)

## Overview

**Jobix** covers the recruitment workflow from job publishing to candidate applications and administration. It provides separate experiences for administrators, companies and candidates, backed by a relational database and server-side PHP logic.

This project demonstrates **role-based workflows, authentication, CRUD operations, application tracking, notifications, analytics and selected web-security mechanisms**. These mechanisms have not been comprehensively security-tested.

## Role-Based Experience

| Role | Core workflow |
| --- | --- |
| **Admin** | Monitor analytics and manage users, companies, jobs, categories and applications |
| **Company** | Publish and manage jobs, review applications and maintain a company profile |
| **Candidate** | Discover jobs, apply with a resume and cover letter, save jobs and track application status |

---

## ✨ Features

| Module | Features |
|---|---|
| **Auth** | Register, Login, Logout, CSRF protection, Session management |
| **Admin** | Dashboard analytics, Manage users/companies/jobs/categories/applications |
| **Company** | Post/edit/delete jobs, Review & update applications, Company profile with logo |
| **Candidate** | Browse & search jobs, Apply with resume + cover letter, Save jobs, Track application status |
| **Notifications** | Real-time bell notifications for all role events |
| **Charts** | Chart.js analytics for Admin and Company dashboards |

---

**Naming note:** Jobix is the public project name. The current source retains the legacy `hirehub` folder, MySQL database, and local URL identifiers, as well as `HireHub` in some interface/configuration strings. Follow the paths below for the current version; this README does not imply that the application configuration has been renamed.

## 📁 Project Structure

```
hirehub/
├── admin/              Admin dashboard & management pages
├── api/                AJAX endpoints (save job, notifications)
├── auth/               Login, Register, Logout
├── candidate/          Candidate module (jobs, apply, profile, saved)
├── company/            Company module (jobs, applications, profile)
├── config/             DB config & connection class
├── core/               Helper functions (auth, flash, upload, etc.)
├── public/
│   ├── css/app.css     Main stylesheet
│   ├── js/app.js       Main JavaScript
│   └── uploads/        User uploaded files (logos, resumes, avatars)
├── views/partials/     Shared HTML partials (head, navbar, sidebar, footer)
├── index.php           Public landing page
├── database.sql        Full schema + dummy data
└── .htaccess           Apache security rules
```

---

## 🔒 Security Features

- **Database queries** — PDO-based query helpers support parameterized statements; use across every SQL operation has not been audited.
- **CSRF tokens** — token generation and verification helpers exist; login and registration forms use them. Coverage of every POST route is unverified.
- **Output escaping** — an `htmlspecialchars()` helper exists; comprehensive use across all output is unverified.
- **File uploads** — a helper checks allowed filename extensions and size limits and generates randomized filenames; comprehensive file-content validation is not established.
- **Upload directory protection** — `.htaccess` rules aim to prevent PHP execution in `/public/uploads/`; server enforcement has not been tested.
- **Password hashing** — registration uses `password_hash(..., PASSWORD_DEFAULT)` and login uses `password_verify()`; no claim is made about every authentication path.
- **Directory listing** — Apache `.htaccess` includes `Options -Indexes`; runtime enforcement has not been tested.

These are **source-observed mechanisms, not a security certification**. No comprehensive security audit or penetration test has been performed.

---

## 🛠 Tech Stack

- **Backend:** PHP 8, PDO/MySQL
- **Frontend:** Bootstrap 5.3, Bootstrap Icons, Chart.js 4
- **Database:** MySQL / MariaDB
- **Server:** Apache (XAMPP)
- **Fonts:** Inter + Sora (Google Fonts)

---

## 📊 Database Tables

| Table | Purpose |
|---|---|
| `users` | All users (admin, company, candidate) |
| `companies` | Company profiles |
| `candidates` | Candidate profiles |
| `job_categories` | Job category taxonomy |
| `jobs` | Job listings |
| `applications` | Job applications |
| `saved_jobs` | Candidate bookmarks |
| `notifications` | In-app notification log |

---

## 🎨 Design

Inspired by **LinkedIn Jobs**, **Indeed**, and **Wellfound** with a clean, modern SaaS aesthetic:
- Deep navy sidebar (`#0f172a`)
- Indigo primary (`#4f46e5`)
- Inter + Sora typefaces
- Subtle card shadows, smooth hover transitions
- Fully responsive – works on mobile, tablet, desktop

---

## 🚀 Installation (XAMPP)

### Step 1 – Copy Files
```
Place the `hirehub` folder in:
C:\xampp\htdocs\hirehub\
```

### Step 2 – Import Database
1. Open **phpMyAdmin** → `http://localhost/phpmyadmin`
2. Click **New** → create database named `hirehub`
3. Select `hirehub` → click **Import**
4. Upload `database.sql` → click **Go**

### Step 3 – Configure (if needed)
Open `config/config.php` and update:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'hirehub');
define('DB_USER', 'root');
define('DB_PASS', '');                          // your MySQL password
define('BASE_URL', 'http://localhost/hirehub'); // match your XAMPP URL
```

### Step 4 – Run
Open browser → `http://localhost/hirehub/`

**Troubleshooting:** If links or redirects fail, check that the folder name, `BASE_URL` in `config/config.php`, and `RewriteBase /hirehub/` in `.htaccess` agree. If database access fails, confirm the imported `hirehub` database and local MySQL credentials. Do not rename these identifiers in the README alone.

---

## 🔑 Demo Login Credentials

> **Local development only:** The following seeded example accounts use publicly documented passwords. Never reuse these credentials on a publicly accessible deployment; replace or disable demonstration accounts before deployment.

| Role | Email | Password |
|---|---|---|
| Admin | admin@hirehub.com | password |
| Company | hr@technova.com | password |
| Company | jobs@byteforge.io | password |
| Candidate | ahmed@email.com | password |
| Candidate | sarah@email.com | password |
| Candidate | john@email.com | password |

---

---

## Project Focus

Jobix was built as a portfolio-ready full-stack project to practice how **interface design, application logic, user roles and relational data** work together in one system.

**Built by Muhammad Affan · ByteForge Studio**

[GitHub Profile](https://github.com/byteforge-affan) · [Portfolio](https://byteforge-affan-portfolio.netlify.app/)
