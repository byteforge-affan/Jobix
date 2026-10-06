# Jobix — Recruitment & Job Portal System

> A role-based recruitment platform built with **PHP 8**, **MySQL**, **Bootstrap 5**, and vanilla JavaScript.

![PHP](https://img.shields.io/badge/PHP_8-777BB4?style=flat-square&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=flat-square&logo=mysql&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap_5-7952B3?style=flat-square&logo=bootstrap&logoColor=white)
![JavaScript](https://img.shields.io/badge/JavaScript-111827?style=flat-square&logo=javascript&logoColor=F7DF1E)

## Overview

**Jobix** covers the recruitment workflow from job publishing to candidate applications and administration. It provides separate experiences for administrators, companies and candidates, backed by a relational database and server-side PHP logic.

This project demonstrates **role-based workflows, authentication, CRUD operations, application tracking, notifications, analytics and practical web-security controls**.

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

- **PDO Prepared Statements** — all SQL uses parameterized queries
- **CSRF Tokens** — all POST forms include CSRF verification
- **XSS Protection** — all output passed through `htmlspecialchars()`
- **File Upload Validation** — type whitelist, size limit, random filenames
- **PHP execution blocked** in `/public/uploads/` via `.htaccess`
- **Password Hashing** — `password_hash()` with `PASSWORD_DEFAULT` (bcrypt)
- **Directory Listing Disabled** — `Options -Indexes`

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

---

## 🔑 Demo Login Credentials

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
