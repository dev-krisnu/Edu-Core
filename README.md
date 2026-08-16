# EduCore — Unified AI-Powered Educational ERP & LMS

**EduCore** is an enterprise-grade, modular Web ERP/LMS designed for modern schools, colleges, and training institutes. It unifies institutional administration, proctored examinations, AI-driven question setting, result analytics, anti-plagiarism checking, automated financial operations, QR-code library management, and 24/7 AI student/faculty helpdesks into a single platform.

![PHP](https://img.shields.io/badge/PHP-8.0-777BB4?style=flat-square&logo=php)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=flat-square&logo=mysql)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=flat-square&logo=bootstrap)
![AI](https://img.shields.io/badge/AI-Gemini%20%2F%20Ollama-4285F4?style=flat-square)

## Features

### AI Engine & Academics
- **AI Question Setter** — Automated exam generation using Bloom's Taxonomy
- **Proctored Online Terminal** — Tab-switch detection & auto-grading
- **Plagiarism Inspector** — Code AST matching, text similarity & AI detection
- **AI Remedial Analytics** — Performance radar with targeted study materials
- **24/7 AI Helpdesk** — Context-aware virtual assistant

### Administration & Infrastructure
- **Executive Command Center** — Real-time campus metrics & system logs
- **Role-Based Access Control** — Super Admin, Faculty, Student, Finance, Librarian, TPO

### Finance & Fee Operations
- **Dynamic Fee Engine** — Customizable templates with penalty rules
- **Financial Dashboard** — Invoicing, ledger & collection analytics

### QR Library & Facilities
- **QR Circulation Desk** — Sub-second book issue/return
- **Book Catalog** — ISBN metadata & shelf management

### Internship & Placement Management
- **Placement Drive Portal** — Company registration & eligibility filtering
- **AI Resume Matcher** — Automated fitment scoring against job descriptions

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | PHP 8.x (Modular MVC) |
| Database | MySQL / MariaDB (PDO) |
| Frontend | HTML5, CSS3, Bootstrap 5, Chart.js |
| AI | Google Gemini API / Ollama (local) |

## Quick Start

### 1. Prerequisites
- XAMPP (Apache + MySQL + PHP 8.x)
- Git

### 2. Clone & Setup
```bash
git clone https://github.com/dev-krisnu/Edu-Core.git
```

Place in your XAMPP `htdocs` folder.

### 3. Database Setup
1. Start Apache & MySQL in XAMPP
2. Open phpMyAdmin → Import `database/schema.sql`
3. This creates the `educore` database with demo data

### 4. Configure
- **Database:** Edit `config/database.php` if your MySQL credentials differ
- **AI (optional):** Copy `.env.example` to `.env` and add your Gemini API key
- **Base URL:** Update `BASE_URL` in `config/app.php` to match your local path

### 5. Run
Open in browser:
```
http://localhost/Ardent Internship 2026/Ardent PHP Internship 2026 Final Project/
```

## Demo Accounts

| Role | Email | Password |
|------|-------|----------|
| Super Admin | admin@educore.edu | password123 |
| Faculty | faculty@educore.edu | password123 |
| Student | krrishjeswar@educore.edu | password123 |
| Parent | parent@educore.edu | password123 |
| Finance | finance@educore.edu | password123 |
| Librarian | librarian@educore.edu | password123 |
| TPO | tpo@educore.edu | password123 |

## Project Structure

```
educore/
├── config/          # Database, AI & app configuration
├── controllers/     # Business logic (AI, Exam, Fee, Plagiarism)
├── api/             # JSON/AJAX endpoints
├── includes/        # Header, sidebar, footer, auth middleware
├── views/           # Role-based UI modules
│   ├── admin/       # Executive command center
│   ├── faculty/     # AI questions, plagiarism, exams
│   ├── student/     # Exam terminal, AI tutor, fees
│   ├── finance/     # Invoicing & ledger
│   ├── library/     # QR desk & catalog
│   └── placements/  # Drives & resume matcher
├── assets/          # CSS, JS, images
├── database/        # SQL schema & seed data
├── index.php        # Login page
└── dashboard.php    # Role-based router
```

## AI Configuration

EduCore supports two AI providers (configured in `config/ai_config.php`):

1. **Google Gemini** (recommended, free tier) — Set `GEMINI_API_KEY`
2. **Ollama** (local, offline) — Set `AI_PROVIDER` to `'ollama'` and run Ollama locally

Without an API key, the system runs in **demo mode** with intelligent fallback responses.

## License

Built as part of the Ardent PHP Internship 2026 Final Project.
