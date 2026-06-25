# 🎓 Academic Portal & Faculty Evaluation System

A highly secure, decentralized web application designed to streamline academic tracking and authenticate faculty evaluations. This system removes traditional centralized admin bottlenecks by empowering faculty members to manage their own specific subject rosters while strictly enforcing data integrity and privacy.

---

## ✨ Core Innovations & Features

* **🛡️ Subject-Isolated Access Control:** Built on the Principle of Least Privilege. Faculty members can only view, modify, and authorize students for their specifically assigned subjects and semesters, completely preventing accidental data overwrites across departments.
* **🔒 Dual-Layer Feedback Security:** Protects the integrity of faculty evaluations. Students are strictly blocked from submitting reviews unless two conditions are met:
  1. Their personal profile (Name, Course, Email, Photo) is 100% complete.
  2. They have been officially approved into the instructor's active roster.
* **⚡ Zero-Delay Student Workspace:** Students gain immediate access to their dashboard upon registration to manage their profiles and view cleared subjects. They are never locked out of the application while waiting for teacher approvals.
* **🎨 Premium UI/UX Architecture:** Features a modern, responsive interface utilizing glassmorphism styling, clean grid/flexbox layouts, and fluid cubic-bezier animations for an executive-level user experience.

---

## 💻 Technology Stack

* **Frontend:** HTML5, CSS3 (Custom animations, Flexbox/Grid), JavaScript
* **Backend:** Core PHP (Procedural & OOP, secure session routing)
* **Database:** MySQL (Relational schema)
* **Server:** Apache (via XAMPP)

---

## 🗄️ Database Schema Overview

The system relies on a tightly integrated relational database utilizing the following core tables:
* `students`: Stores credentials, basic branch information, current semester, and profile records.
* `teachers`: Maps instructors to their handling subjects and authorized semesters.
* `academic_records`: Bridges students and teachers. Holds attendance, internal marks, and the crucial `subject_status` ('Pending' or 'Approved').
* `feedback`: Stores multi-point star rating metrics and written comments.

---

## 🚀 Installation & Setup Guide

To run this project locally on your machine, follow these steps:

### Prerequisites
* Install [XAMPP](https://www.apachefriends.org/index.html) (or any similar local server stack).
* A modern web browser.

### 1. Clone the Repository
Open your terminal and navigate to your XAMPP `htdocs` folder:
```bash
cd C:\xampp\htdocs
git clone [https://github.com/YOUR-USERNAME/Acadamic-Portal-System.git](https://github.com/YOUR-USERNAME/Acadamic-Portal-System.git)
