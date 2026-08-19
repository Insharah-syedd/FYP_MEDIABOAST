# MediaBoost 🔗 **Live Demo:** [mediaboost.infinityfreeapp.com/public](http://mediaboost.infinityfreeapp.com/public)

**A Web-Based Client Management & Digital Marketing Dashboard**

MediaBoost is a centralized platform built for **All Media Marketing**, a digital marketing agency based in Lahore, Pakistan. It replaces manual, WhatsApp-based client communication and reporting with a single, role-based system that manages leads, clients, projects, and campaign analytics — all in one place.

> 🎓 Final Year Project — BSIT, Lincoln University Malaysia
 
---

## 📌 Problem It Solves

Before MediaBoost, the agency had:
- No centralized client portal — clients couldn't track project progress online
- Manual, WhatsApp/email-based reporting — slow and inconsistent
- No systematic lead tracking — inquiries were lost or missed
- No online service booking — clients had to call or message to inquire
- No professional, filterable portfolio to showcase completed work

MediaBoost solves all five with a single unified dashboard.

---

## ✨ Key Features

- 🌐 **Public Visitor Section** — homepage, service listings, and a free-consultation booking form
- 🎯 **AI-Powered Lead Scoring** — automatically scores every incoming lead (0–100) based on budget and source, so the team can prioritize high-value prospects instead of manually reviewing every inquiry
- 👥 **Client Management** — client profiles, subscription packages (Basic / Standard / Premium), and assigned account managers
- 📁 **Project Management** — progress tracking, budgets, deadlines, and deliverables linked to each client
- 📊 **Reporting & Analytics** — monthly performance reports (SEO clicks, impressions, Facebook reach, Instagram engagement, website visits, leads) with visual charts
- 🔐 **Role-Based Access Control** — Admin, Manager, and Employee roles, plus a separate Client login
- 🔔 **Real-Time Notifications** — instant alerts for new bookings and leads
- 🖼️ **Portfolio Module** — filterable showcase of completed work with measurable results

---

## 🛠️ Tech Stack

| Layer | Technology |
|---|---|
| Frontend | HTML5, CSS3, JavaScript (ES6+) |
| Backend | PHP 8 (custom MVC structure) |
| Database | MySQL (10 relational tables) |
| Authentication | PHP Sessions, secure password hashing |
| Analytics | Google Analytics API, Google Search Console API |
| Notifications | In-app real-time notifications, PHPMailer |
| Dev Tools | Git, VS Code, XAMPP, phpMyAdmin, Postman |

---

## 🗂️ Database Structure

The MySQL database consists of **10 relational tables**:

`users` · `clients` · `leads` · `bookings` · `projects` · `deliverables` · `reports` · `notifications` · `services` · `portfolio`

See `mediaboost.sql` for the full schema and demo data.

---

## 🚀 Installation (Local Setup — XAMPP)

1. Install [XAMPP](https://www.apachefriends.org/) (Apache + MySQL/MariaDB)
2. Clone this repository into your `htdocs` folder:
   ```bash
   git clone https://github.com/your-username/mediaboost.git
   ```
3. Start **Apache** and **MySQL** from the XAMPP Control Panel
4. Open **phpMyAdmin**, create a new database named `mediaboost`, and import `mediaboost.sql`
5. Open the project's database config file and update the database name, username, and password to match your local MySQL setup (default: `root`, no password)
6. Visit `http://localhost/mediaboost` in your browser

---

## 🔑 Default Access

After importing the database, three pre-configured team accounts exist under the `allmediamarketing.com` domain — **Admin**, **Manager**, and **Employee**. Passwords should be reset immediately after deployment, before using real client data.

---

## 👤 User Roles

| Role | Access |
|---|---|
| **Admin** | Full system access |
| **Manager** | View leads and reports |
| **Employee** | Assigned leads/projects only |
| **Client** | Own project status and reports only |

---

## 📸 Screenshots

 screenshots of the Homepage <img width="1901" height="944" alt="Screenshot 2026-06-30 143722" src="https://github.com/user-attachments/assets/ab6b6e61-3acd-4f37-b760-f328ee4288c5" />

 , Admin Dashboard<img width="1920" height="1032" alt="Screenshot 2026-06-30 150302" src="https://github.com/user-attachments/assets/b8e26d62-1801-4289-a496-e45715be9fe6" />
, 
 Lead Management<img width="1920" height="1032" alt="Screenshot 2026-06-30 150433" src="https://github.com/user-attachments/assets/f79a8d24-d082-404e-9cb9-33cfa9c61c7a" />

---

## 📄 License

This project was developed for academic purposes as part of a BSIT Final Year Project at Lincoln University Malaysia, and is actively used by All Media Marketing.

---

## 👩‍💻 Author

**Insharah Syed**
BSIT — Final Year, Lincoln University Malaysia
