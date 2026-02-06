# Home Service (S24) - Service Provider Directory

## 🏠 Overview
**Home Service** (Project S24) is a premium, real-time service marketplace designed to connect homeowners with verified local professionals. Built with a focus on trust, aesthetic excellence, and seamless user experience, it features a **Premium Glassmorphism Design System**, robust **Admin-Controlled Booking Workflows**, and a modern **Auto-Publish Review Ecosystem**.

## 🚀 Key Features

### 🎨 Premium Design System
- **Global Theme Engine**: 7+ Dynamic Color Themes (Purple, Emerald, Rose, Amber, Slate, Cyan, Pink) that sync across all pages instantly.
- **Glassmorphism UI**: Modern, translucent card designs with vibrant gradients, floating particle animations, and hover effects.
- **Responsive Experience**: Fully optimized interfaces for Desktop, Tablet, and Mobile devices.

### 👤 For Customers
- **Service Discovery**: Advanced search by service category, location, and price range.
- **Real-time Booking**: Schedule services with specific dates and times.
- **Dashboard Hub**: 
  - Track booking status (Pending → Confirmed → Completed).
  - "Elite Selections" to manage preferred providers.
  - Real-time Notifications timeline.
- **Payments**: Secure manual payment logging (Bkash/Nagad) with transaction verification.
- **Reviews**: Auto-publishing 5-star review system with photo uploads.

### 🛠 For Service Providers
- **Professional Profile**: Customizable profiles showcasing skills, rates, and service areas.
- **Booking Management**: Accept/Reject workflows. 
  - *Security Feature*: Customer contact details (Address/Phone) are **locked** until Admin approval.
- **Business Insights**: Dashboard for tracking earnings, impending bookings, and customer feedback.

### 🛡 For Administrators
- **Provider Verification**: Strict document verification process (NID, Certificates) before providers go live.
- **Booking Oversight**: **Critical Security Layer** — Admins review all bookings before sharing sensitive customer data with providers.
- **Content Moderation**: Ability to remove inappropriate reviews (Auto-approval system is active by default).
- **System Analytics**: Comprehensive view of platform health, revenue, and user growth.

## 💻 Technology Stack
- **Backend**: PHP 8.0+
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, Vanilla CSS (Premium UI Assets), JavaScript
- **Styling Framework**: Custom `ui.css` (Glassmorphism engine) + Tailwind CSS (via CDN for utilities)

## 📂 Project Structure

```
home service/
├── admin/                 # 🛡 Admin Control Panel & Verification Tools
├── auth/                  # 🔐 Secure Login & Registration Systems
├── customer/              # 👤 Customer Dashboard & Interaction Tools
│   ├── bookings.php       #    - Booking Management & Status Tracking
│   ├── payments.php       #    - Payment History & Invoices
│   ├── reviews.php        #    - Review Submission & History
│   ├── notifications.php  #    - Real-time Alert Timeline
│   └── ...
├── provider/              # 🛠 Service Provider Portal & Job Management
├── assets/                # 🎨 Core Design Assets
│   ├── ui.css             #    - Global Premium Themes & Glassmorphism
│   └── ui.js              #    - Theme Logic & Animations
├── includes/              # ⚙️ Shared PHP Functions & Database Config
├── uploads/               # 📁 Secure Storage for Documents & Profiles
├── public_reviews.php     # ⭐ Public-facing Review Gallery
└── index.php              # 🏠 Landing Page & Service Search
```

## ⚙️ Installation & Setup

1.  **Clone/Download**:
    Place the project files into your local server directory (e.g., `c:\xampp7\htdocs\home service\`).

2.  **Database Setup**:
    - Create a MySQL database named `s24_services`.
    - Import the main schema: `database_complete.sql`.
    - *Update*: Run `database_migration_approval.sql` to ensure the latest booking approval tables exist.

3.  **Configuration**:
    - Edit `config/database.php` with your credentials:
      ```php
      define('DB_HOST', 'localhost');
      define('DB_USER', 'root'); // Your DB Username
      define('DB_PASS', '');     // Your DB Password
      define('DB_NAME', 's24_services');
      ```

4.  **Permissions**:
    Ensure the `uploads/` directory and its subfolders are writable for file uploads.

## 🔄 Core Workflows

### 1. Booking & Approval Flow
This system prioritizes privacy and safety.
1.  **Customer** books a service. Status: `Pending`.
2.  **Admin** reviews the booking details in the Admin Panel.
3.  **Admin** approves the booking.
    - **Notification** is sent to the Customer.
    - **Contact Details** (Address/Phone) are finally **unlocked** for the Provider.
4.  **Provider** contacts the Customer to confirm arrival.

### 2. Review System (Auto-Publish)
1.  **Customer** completes a service.
2.  **Customer** writes a review via the Dashboard.
3.  **System** automatically approves and publishes the review to `public_reviews.php`.
4.  **Admin** retains the power to **delete** reviews if they violate community standards.

### 3. Theme Synchronization
The platform uses a persistent theme engine.
- **Storage**: User preference is saved in browser `localStorage`
- **Sync**: Changing the theme in the header updates ALL open tabs instantly.
- **Classes**: Uses `premium-bg`, `glass-card`, `glass-nav`, and `btn-primary` for consistent styling.

---
**© 2026 Home Service . Built with excellence.**
