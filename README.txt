========================================================================
             FOOD ORDERING MANAGEMENT SYSTEM (YUM'S BERCHG)
========================================================================

ABOUT THE PROJECT
-----------------
Food Ordering Management System is a full-featured PHP & MySQL web application
designed for food establishments, restaurants, and eateries. It provides a
responsive customer storefront for browsing and placing orders, coupled with
a secure administration dashboard for managing categories, menu items, sales, 
and generating analytical PDF/CSV reports.


KEY FEATURES
------------
1. Unified Authentication System:
   - Single, role-based login portal for both Customers and System Administrators.
   - Automatic routing based on user privilege levels.
   - Secure password hashing using PHP's password_hash() (Bcrypt).

2. Customer Storefront (User Module):
   - Category filtering (Burgers, Pizzas, Drinks, etc.).
   - Interactive shopping cart with real-time quantity adjustments.
   - Seamless checkout process with customer delivery address recording.

3. Admin Management Panel (Admin Module):
   - Category Management: Create and delete menu categories.
   - Menu Item Management: Add, edit, and delete food items with custom images, prices, and descriptions.
   - Order Management: Review placed orders and manage customer order history.
   - Key Metrics Dashboard: Track total sales revenue, total orders count, and monthly sales.

4. Sales Analytics & Reporting:
   - Filter sales reports by custom start and end date ranges.
   - Export financial reports directly to downloadable PDF files.
   - Export order records to CSV spreadsheets.

5. Automatic Database Initialization:
   - Built-in database migration scripts inside config/db.php and config/schema.sql.


TECHNOLOGY STACK
----------------
- Backend: PHP (PDO - PHP Data Objects)
- Database: MySQL / MariaDB (XAMPP compatible)
- Frontend: HTML5, Vanilla CSS3 (Modern Responsive Flexbox/Grid Layouts)
- Reporting: FPDF / Native PHP Export Engine


SYSTEM REQUIREMENTS
-------------------
- Web Server: Apache (XAMPP / WAMP / LAMP)
- PHP Version: PHP 7.4 or higher (PHP 8.x recommended)
- Database: MySQL 5.7+ / MariaDB 10.4+


INSTALLATION & SETUP INSTRUCTIONS
----------------------------------
1. Clone / Copy Repository:
   Place or clone this project folder directly into your XAMPP htdocs directory:
   Target Directory: C:\xampp\htdocs\food_orders\

2. Start Local Web Server:
   Open the XAMPP Control Panel and start:
   - Apache Module
   - MySQL Module

3. Database Setup (Choose Option A or B):
   
   Option A (Automatic Setup - Recommended):
   - Simply open your browser and navigate to: http://localhost/food_orders/
   - The system will automatically create the `food_db` database, all necessary tables,
     sample menu items, and default admin credentials upon first access!

   Option B (Manual Import via phpMyAdmin):
   - Open http://localhost/phpmyadmin/
   - Create a new database named `food_db` (or click the Import tab).
   - Import the SQL schema file located at: config/schema.sql

4. Accessing the Application:
   - Root URL: http://localhost/food_orders/
   - Login Portal: http://localhost/food_orders/auth/login.php
   - Customer Registration: http://localhost/food_orders/auth/register.php


DEFAULT LOGIN CREDENTIALS
-------------------------
1. Administrator Account:
   - Username: admin
   - Password: admin123
   - Access URL: http://localhost/food_orders/auth/login.php (Redirects to Admin Dashboard)

2. Customer Account:
   - Register a new account via http://localhost/food_orders/auth/register.php
   - Or log in with your registered customer credentials.


PROJECT FOLDER STRUCTURE
------------------------
food_orders/
│
├── auth/
│   ├── admin.php       - Admin Management Dashboard & Control Panel
│   ├── login.php       - Unified Login Page (Admin & Customer)
│   ├── logout.php      - Session Destruction & Logout Handler
│   └── register.php    - Customer Account Registration Form
│
├── config/
│   ├── db.php          - Database Connection (PDO) & Auto-Migration Logic
│   └── schema.sql      - Full SQL Database Schema & Seed Data
│
├── css/
│   └── style.css       - Application Design System Stylesheet
│
├── includes/           - Shared Header/Footer Templates & Helpers
│
├── reports/
│   ├── export_csv.php  - Order History CSV Exporter
│   ├── generate_pdf.php- Sales Revenue PDF Report Generator
│   └── index.php       - Sales Analytics Dashboard & Date Filter Interface
│
├── uploads/            - Food Item Images & Assets
│
├── user/
│   ├── cart.php        - Shopping Cart & Order Checkout Page
│   ├── index.php       - Customer Storefront & Food Menu Display
│   └── success.php     - Order Placement Confirmation Screen
│
├── index.php           - Master Smart Entry Router
└── README.txt          - Project Documentation & Setup Guide


GITHUB SHARING & CONTRIBUTIONS
------------------------------
When pushing this repository to GitHub:
1. Ensure your git commit includes all project files.
2. The config/schema.sql and README.txt files allow anyone cloning your repo to
   spin up the system in under 2 minutes!

License: Open Source / MIT
========================================================================
