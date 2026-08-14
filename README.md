# 🍔 Food Ordering Management System (Yum's berchg)

A full-featured, responsive PHP & MySQL web application designed for food establishments, restaurants, and eateries. Features a customer storefront for online ordering and a comprehensive admin management panel with PDF/CSV revenue reporting.

---

## 🌟 Key Features

- **🔑 Unified Authentication System**: Single login portal for both Customers and Admins with automatic role-based routing.
- **🛒 Customer Storefront**: Browse menu items by categories (Burgers, Pizzas, Drinks), view food images, manage cart items, and place orders.
- **🛠️ Admin Dashboard**: Add/edit/delete categories and menu items, manage orders, and track key metrics (total sales, total orders, monthly analytics).
- **📊 Sales Analytics & Reporting**: Filter revenue reports by custom date ranges and export to PDF or CSV format.
- **⚡ Auto-Database Initialization**: Automatically creates database, tables, and sample data upon first run.

---

## 🚀 Quick Setup Instructions

### 1. Installation
Clone or copy this repository into your XAMPP `htdocs` directory:
```bash
c:\xampp\htdocs\food_orders\
```

### 2. Start Local Server
Open **XAMPP Control Panel** and start **Apache** and **MySQL**.

### 3. Database Connection
Simply visit `http://localhost/food_orders/` in your browser. The system will automatically construct the `food_db` database, tables, sample food items, and default admin account!

Alternatively, you can manually import `config/schema.sql` into **phpMyAdmin**.

---

## 🔑 Default Credentials

- **Admin Account**: Username: `admin` | Password: `admin123`
- **Customer Account**: Register a new account via `http://localhost/food_orders/auth/register.php`

---

## 📁 Project Folder Structure

```
food_orders/
├── auth/
│   ├── admin.php       <-- Admin Management Dashboard
│   ├── login.php       <-- Unified Login Portal
│   ├── logout.php      <-- Session Logout Handler
│   └── register.php    <-- Customer Account Registration
├── config/
│   ├── db.php          <-- PDO Database Connection & Auto-Installer
│   └── schema.sql      <-- Full MySQL Schema & Seed Data
├── css/
│   └── style.css       <-- Application Stylesheet
├── reports/
│   ├── export_csv.php  - Order History CSV Exporter
│   ├── generate_pdf.php- Sales Revenue PDF Report Generator
│   └── index.php       - Sales Analytics Dashboard
├── uploads/            <-- Food Item Images
├── user/
│   ├── cart.php        <-- Shopping Cart & Checkout
│   ├── index.php       <-- Customer Food Storefront
│   └── success.php     <-- Order Confirmation Screen
├── index.php           <-- Master Smart Entry Router
├── README.md           <-- GitHub Markdown Documentation
└── README.txt          - Plain Text Documentation
```

---

## 📜 License
Open Source under the MIT License.
