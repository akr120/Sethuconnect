# 🌍 Digital Public Infrastructure (DPI) for Migrant Workers

A secure and scalable PHP-based platform designed to support **migrant workers** by providing:
✔ Digital Identity  
✔ Job Connectivity  
✔ Welfare Access  
✔ Verified Documentation  
✔ Multilingual UI & Accessibility

---

## 🚀 Key Features

### 👤 Worker Module
- **Secure Registration (with Document Upload)**
- **JWT-based Login + Refresh Tokens**
- **SMS OTP Authentication**
- **Upload & Store ID Documents (Aadhaar, Voter ID, etc.)**
- **Track Document Verification Status**
- **Multi-language Portal (Hindi, Telugu, Tamil, English)**

### 🛠 Admin Module
- Web Dashboard to:
  - Verify Worker Identity
  - Approve / Reject ID Documents
  - Export Migrant Database (CSV/Excel)
  - Manage Notifications / Messages

---

## 🔐 Security Highlights
🔒 **JWT-Based Authentication**  
🔒 **Encryption for Aadhar / Phone numbers**  
🔒 **Hashed Passwords (bcrypt)**  
🔒 **Secure File Storage using random names**  
🔒 **CSRF + SQL Injection Protection (PDO Prepared Statements)**  

---

## 📦 Tech Stack

| Component | Technology |
|----------|------------|
| Backend | PHP 8+ |
| Database | MySQL |
| Auth | JWT (Firebase PHP-JWT) |
| SMS | Fast2SMS / Twilio API |
| UI | HTML5, CSS3, Bootstrap 5 |
| Multilingual | PHP-Lang Localisation |

---

## 🗄 Database (MySQL)

### 📌 Run Migration

> Execute in MySQL:

```sql
CREATE DATABASE dpi_migrant;

USE dpi_migrant;

CREATE TABLE workers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100),
  mobile VARCHAR(15) UNIQUE,
  password VARCHAR(255),
  aadhaar_encrypted VARCHAR(255),
  language VARCHAR(20) DEFAULT 'en',
  status ENUM('pending','verified','rejected') DEFAULT 'pending',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE documents (
  id INT AUTO_INCREMENT PRIMARY KEY,
  worker_id INT,
  file_path VARCHAR(255),
  file_type VARCHAR(50),
  status ENUM('pending','approved','rejected') DEFAULT 'pending',
  uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (worker_id) REFERENCES workers(id) ON DELETE CASCADE
);

CREATE TABLE admin_users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) UNIQUE,
  password VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
