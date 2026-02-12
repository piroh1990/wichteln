# 🎁 Wichtlä.ch - Secret Santa Made Easy

A modern, user-friendly web application for organizing Secret Santa groups. Perfect for families, friends, and colleagues!

![PHP](https://img.shields.io/badge/PHP-7.4+-777BB4?style=flat&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-5.7+-4479A1?style=flat&logo=mysql&logoColor=white)
![License](https://img.shields.io/badge/License-MIT-green.svg)

## ✨ Features

### For Group Admins
- 🎯 **Create Groups** - Easy group creation with Captcha protection
- 📧 **Admin Email** - Admin link sent automatically via email
- 👥 **Participant Management** - Add, edit, and delete participants
- 🚫 **Define Exclusions** - Specify who cannot be paired with whom (e.g., couples)
- 🎲 **Smart Drawing** - Automatic assignment considering all exclusions
- 🔄 **Reset Drawing** - Repeat the draw if necessary
- 🗑️ **Delete Group** - Secure deletion with warnings
- 📱 **WhatsApp Sharing** - Share invitation links directly via WhatsApp

### For Participants
- 📝 **Simple Registration** - Quick signup with name and email
- 🎁 **Wishlist** - Create and edit your own wishlist
- 👤 **View Partner** - See your Secret Santa partner and their wishlist after the draw
- 📬 **Email Notification** - Automatic notification when the draw is complete

### Design & UX
- 🎨 **Modern Design** - Beautiful gradients and animations
- 📱 **Mobile-First** - Fully responsive for all devices
- 🌐 **Internationalized Domains** - Support for wichtlä.ch (IDN)
- 📧 **HTML Emails** - Professional email templates matching the website design
- ❄️ **Christmas Atmosphere** - Snowfall animations and festive design

### Security
- 🔐 **Token-based Authentication** - Secure access control
- 🤖 **Captcha Protection** - Image-based captcha against spam
- 🛡️ **SQL Injection Protection** - Prepared statements for all database access
- ✅ **Input Validation** - Comprehensive validation of all user inputs

### API for Mobile Apps
- 🔌 **REST API** - Full REST API for Android/iOS apps
- 📱 **JSON Responses** - Structured JSON answers
- 🔒 **Token Auth** - Secure API authentication
- 📊 **Rate Limiting** - Protection against abuse
- 📝 **API Documentation** - Comprehensive documentation at `/api/README.md`

## 🚀 Installation

### Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher
- PHP GD Extension (for Captcha)
- Web server (Apache, Nginx, etc.)
- Email function enabled (PHP `mail()` or SMTP)

### Step 1: Clone Repository

```bash
git clone https://github.com/yourusername/wichtel-app.git
cd wichtel-app
```

### Step 2: Setup Database

1. **Create Database:**

```sql
CREATE DATABASE wichtel_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. **Create Database User:**

```sql
CREATE USER 'wichtel_db_user'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON wichtel_db.* TO 'wichtel_db_user'@'localhost';
FLUSH PRIVILEGES;
```

3. **Create Tables:**

```sql
USE wichtel_db;

-- Groups Table
CREATE TABLE `groups` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `admin_token` VARCHAR(64) NOT NULL UNIQUE,
  `invite_token` VARCHAR(64) NOT NULL UNIQUE,
  `admin_email` VARCHAR(255) NULL,
  `budget` DECIMAL(10,2) NULL,
  `description` TEXT NULL,
  `gift_exchange_date` DATE NULL,
  `is_drawn` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Participants Table
CREATE TABLE `participants` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `group_id` INT NOT NULL,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NULL,
  `token` VARCHAR(64) NOT NULL UNIQUE,
  `assigned_to` INT NULL,
  `wishlist` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`group_id`) REFERENCES `groups`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`assigned_to`) REFERENCES `participants`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Exclusions Table
CREATE TABLE `exclusions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `group_id` INT NOT NULL,
  `participant_id` INT NOT NULL,
  `excluded_participant_id` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`group_id`) REFERENCES `groups`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`participant_id`) REFERENCES `participants`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`excluded_participant_id`) REFERENCES `participants`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `unique_exclusion` (`group_id`, `participant_id`, `excluded_participant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Indexes for better performance
CREATE INDEX idx_admin_token ON `groups`(`admin_token`);
CREATE INDEX idx_invite_token ON `groups`(`invite_token`);
CREATE INDEX idx_participant_token ON `participants`(`token`);
CREATE INDEX idx_group_participants ON `participants`(`group_id`);
```

### Step 3: Configuration

1. **Copy Example Configuration:**

```bash
cp includes/config.example.php includes/config.php
```

2. **Edit `includes/config.php` with your data:**

```php
<?php
// Database settings
define('DB_HOST', 'localhost');
define('DB_NAME', 'wichtel_db');
define('DB_USER', 'wichtel_db_user');
define('DB_PASS', 'your_secure_password');

// Email settings
define('SMTP_FROM_EMAIL', 'noreply@yourdomain.com');
define('SMTP_FROM_NAME', 'Secret Santa Website');

// Master Admin Token (Generate a secure token)
define('MASTER_ADMIN_TOKEN', bin2hex(random_bytes(32)));
?>
```

3. **Generate a secure Master Admin Token:**

```bash
php -r "echo bin2hex(random_bytes(32));"
```

### Step 4: Set Permissions

```bash
# Ensure the web server has write permissions
chmod 755 .
chmod 644 *.php
chmod 644 css/*.css
chmod 644 images/*
```

### Step 5: Email Configuration

The app uses the PHP `mail()` function by default. For better deliverability:

1. **Adjust Sendmail path in `functions.php`** (if necessary):

```php
ini_set('sendmail_path', '/usr/sbin/sendmail -t -i');
```

2. **Or configure SMTP** (optional, requires additional library like PHPMailer)

### Step 6: Testing

1. Open the website in your browser
2. Create a test group
3. Register participants
4. Test the draw

### Step 7: Setup Automatic Cleanup (Cronjob)

To automatically delete old groups (older than 3 months) and archive statistics, set up a daily cronjob:

1. Edit crontab:
```bash
crontab -e
```

2. Add line (runs daily at 03:00 AM):
```
0 3 * * * /usr/bin/php /path/to/wichtel-app/scripts/cleanup_groups.php >> /path/to/wichtel-app/logs/cleanup.log 2>&1
```

*Note: Adjust paths according to your installation.*

## 📁 Project Structure

```
wichtel-app/
├── public/                 # Public directory (Document Root)
│   ├── admin/              # Master Admin Area
│   ├── api/                # REST API
│   ├── css/                # Stylesheets
│   ├── images/             # Images and Icons
│   ├── index.php           # Landing Page
│   ├── admin.php           # Group Admin Area
│   ├── create_group.php    # Group Creation
│   ├── participant.php     # Participant Area
│   ├── register.php        # Registration
│   └── ...                 # Other pages
├── includes/               # Internal logic and configuration
│   ├── config.php          # Configuration (not in repo)
│   ├── api_config.php      # API Configuration (not in repo)
│   ├── functions.php       # Core Functions
│   └── templates/          # Partials (Footer, Nav, etc.)
├── database/               # SQL Scripts
├── logs/                   # Log files
├── tests/                  # Tests
└── README.md               # This file
```

## 🎨 Customization

### Change Colors

Edit CSS variables in `css/styles.css`:

```css
:root {
    --primary: #e63946;     /* Primary Color (Red) */
    --secondary: #2a9d8f;   /* Secondary Color (Turquoise) */
    --accent: #f4a261;      /* Accent Color (Orange) */
    --dark: #264653;        /* Dark Blue */
    --success: #2a9d8f;     /* Success Color */
    --error: #e63946;       /* Error Color */
}
```

### Replace Logo

Replace `images/logo.png` with your own logo (recommended size: 250x60px).

### Customize Email Templates

Email templates are located in `functions.php`:
- `create_html_email()` - Partner Notification
- `create_registration_email()` - Registration Confirmation
- `create_admin_email()` - Admin Welcome Email

## 🔒 Security Notes

1. **Never commit includes/config.php** - Already included in `.gitignore`
2. **Use strong passwords** - For database and admin tokens
3. **Use HTTPS** - Always enable SSL/TLS in production
4. **Regular Updates** - Keep PHP and MySQL updated
5. **Disable Error Reporting** - In production in `includes/functions.php`:

```php
// Comment out in production:
// ini_set('display_errors', 0);
// error_reporting(0);
```

## 🐛 Troubleshooting

### Emails are not being sent

1. Check PHP Mail configuration:
```bash
php -r "mail('test@example.com', 'Test', 'Test');"
```

2. Check Sendmail path in `includes/functions.php`
3. Check server logs for errors

### Captcha not working

1. Ensure PHP GD Extension is installed:
```bash
php -m | grep -i gd
```

2. If not installed:
```bash
# Ubuntu/Debian
sudo apt-get install php-gd

# CentOS/RHEL
sudo yum install php-gd
```

### Database Errors

1. Check connection details in `config.php`
2. Check database permissions
3. Check character set (UTF8MB4)

## 📝 License

MIT License - see LICENSE file for details

## 🤝 Contributing

Contributions are welcome! Please:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 📧 Support

For questions or issues:
- Open an [Issue](https://github.com/yourusername/wichtel-app/issues)
- Email: support@yourdomain.com

## 🎄 Credits

Developed with ❤️ for the holiday season

- Icons: Custom SVG Icons
- Fonts: Google Fonts (Playfair Display, Roboto)
- Design: Custom Design inspired by modern web standards

---

**Merry Christmas and Happy Secret Santa! 🎁**
