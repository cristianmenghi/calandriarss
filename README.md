# Calandria RSS Aggregator

A modern, terminal-style RSS aggregator with admin panel, authentication, and PWA support.

![Version](https://img.shields.io/badge/version-2.0-green)
![PHP](https://img.shields.io/badge/PHP-8.0+-blue)
![License](https://img.shields.io/badge/license-MIT-blue)

## ✨ Features

### Core Features
- 📰 **RSS Aggregation** - Automated fetching from multiple sources
- 🔍 **Full-Text Search** - Search across all articles
- 📱 **PWA Support** - Install as native app, works offline
- 🎨 **Terminal UI** - Dark, minimalist interface inspired by CLI tools
- 🔐 **Authentication** - Secure login with RBAC
- 👥 **User Management** - Admin and moderator roles
- 📊 **Analytics** - Track views, trending articles, statistics

### Admin Panel
- ✅ Sources management (CRUD)
- ✅ Categories management (CRUD)
- ✅ User management (CRUD)
- ✅ Fetch logs viewer
- ✅ Dashboard with statistics
- ✅ Export data (CSV, JSON)

### Technical Features
- 🚀 **MVC Architecture** - Clean separation of concerns
- 🔒 **Security** - CSRF protection, rate limiting, bcrypt hashing
- 💾 **Dual DB Support** - MySQL and SQLite
- 📦 **Service Worker** - Offline caching, background sync
- 🎯 **RESTful API** - JSON endpoints for all operations

---

## 🚀 Quick Start

### Prerequisites

- PHP 8.0 or higher
- MySQL 5.7+ or SQLite 3
- Composer
- Web server (Apache/Nginx) or PHP built-in server

### Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/yourusername/calandriarss.git
   cd calandriarss
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Configure environment**
   ```bash
   cp .env.example .env
   # Edit .env with your database credentials
   ```

4. **Create database and run migrations**
   ```bash
   # MySQL
   mysql -u root -p -e "CREATE DATABASE calandria_rss"
   mysql -u root -p calandria_rss < database/schema.sql
   mysql -u root -p calandria_rss < database/migrations.sql
   
   # Or use the setup script
   php setup.php
   ```

5. **Start development server**
   ```bash
   php -S localhost:8000 -t public/
   ```

6. **Access the application**
   - Frontend: http://localhost:8000
   - Admin Panel: http://localhost:8000/admin
   - Default credentials: `admin` / `admin123`

### Cron Setup

Add to your crontab to fetch feeds every 15 minutes:

```bash
*/15 * * * * cd /path/to/calandriarss && php cron/fetch-feeds.php >> /var/log/calandria-cron.log 2>&1
```

---

## 📖 Usage

### Admin Panel

The admin panel features a terminal-inspired dark UI with dual-panel layout:

**Left Panel:**
- Navigation (Topics, Regions, Filters, Settings)
- Statistics charts
- Export options (CSV, JSON)
- Quick filters

**Right Panel:**
- Tabbed content (Latest, Sources, Users, Logs)
- Data tables with CRUD operations
- Real-time search and filtering

### API Endpoints

#### Public API

```bash
# Get articles (with pagination and filters)
GET /api/articles?page=1&source_id=1&search=tech

# Get sources
GET /api/sources

# Get categories
GET /api/categories
```

#### Admin API (Requires Authentication)

```bash
# Sources
GET    /api/admin/sources
POST   /api/admin/sources
PUT    /api/admin/sources/{id}
DELETE /api/admin/sources/{id}

# Categories
GET    /api/admin/categories
POST   /api/admin/categories
PUT    /api/admin/categories/{id}
DELETE /api/admin/categories/{id}

# Users
GET    /api/admin/users
POST   /api/admin/users
PUT    /api/admin/users/{id}
DELETE /api/admin/users/{id}
POST   /api/admin/users/{id}/password
```

### PWA Installation

1. Visit the site in Chrome/Edge/Safari
2. Click the install button in the address bar
3. App will be added to your home screen/app drawer
4. Works offline with cached articles

---

## 🎨 Design System

### Terminal Theme

The UI uses a dark terminal aesthetic with:

- **Font**: JetBrains Mono (monospace)
- **Colors**:
  - Background: `#0a0a0a`
  - Foreground: `#e0e0e0`
  - Accent: `#00ff00` (terminal green)
  - Error: `#ff5555`
  - Warning: `#ffaa00`

### Components

- Terminal-style inputs with cursor effects
- ASCII art headers
- Monospace typography throughout
- Minimal borders and shadows
- Hover effects with accent color
- Responsive dual-panel layout

---

## 🔧 Configuration

### Environment Variables

```env
# Application
APP_ENV=local|production
APP_DEBUG=true|false
APP_URL=http://localhost:8000

# Database
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=calandria_rss
DB_USERNAME=root
DB_PASSWORD=

# Session
SESSION_LIFETIME=7200
SESSION_SECURE=false
SESSION_HTTPONLY=true

# Authentication
BCRYPT_COST=12
LOGIN_MAX_ATTEMPTS=5
LOGIN_LOCKOUT_TIME=900

# PWA
ENABLE_PUSH_NOTIFICATIONS=false
VAPID_PUBLIC_KEY=
VAPID_PRIVATE_KEY=
```

---

## 🗄️ Database Schema

### Main Tables

- `sources` - RSS feed sources
- `articles` - Aggregated articles
- `categories` - Content categories
- `users` - Admin/moderator users
- `sessions` - User sessions
- `login_attempts` - Rate limiting
- `saved_filters` - User-saved filter contexts
- `user_follows` - Followed sources
- `saved_articles` - Bookmarked articles
- `article_views` - Analytics
- `push_subscriptions` - PWA notifications

---

## 🔒 Security

- ✅ **CSRF Protection** - Tokens on all forms
- ✅ **SQL Injection** - PDO prepared statements
- ✅ **XSS Protection** - Output escaping
- ✅ **Password Hashing** - Bcrypt with cost 12
- ✅ **Rate Limiting** - Login attempt tracking
- ✅ **Session Security** - HttpOnly, Secure, SameSite cookies
- ✅ **RBAC** - Role-based access control

---

## 🧪 Testing

```bash
# Run all tests
vendor/bin/phpunit

# Run specific test suite
vendor/bin/phpunit tests/Unit
vendor/bin/phpunit tests/Feature

# With coverage
vendor/bin/phpunit --coverage-html coverage
```

---

## 📦 Deployment

### Production Checklist

- [ ] Set `APP_ENV=production` in `.env`
- [ ] Set `APP_DEBUG=false`
- [ ] Use strong database password
- [ ] Enable `SESSION_SECURE=true` (HTTPS only)
- [ ] Change default admin password
- [ ] Set up SSL certificate
- [ ] Configure cron jobs
- [ ] Set proper file permissions
- [ ] Enable opcache
- [ ] Configure log rotation

### Apache Configuration

```apache
<VirtualHost *:80>
    ServerName calandria.example.com
    DocumentRoot /var/www/calandriarss/public
    
    <Directory /var/www/calandriarss/public>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/calandria-error.log
    CustomLog ${APACHE_LOG_DIR}/calandria-access.log combined
</VirtualHost>
```

---

## 🤝 Contributing

Contributions are welcome! Please follow these guidelines:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

---

## 📝 License

This project is licensed under the AGPL-3.0 - see the [LICENSE](LICENSE) file for details.

---

## 👤 Author

**Cristian Menghi**
- Email: cristian@menghi.uy
- GitHub: [@cristianmenghi](https://github.com/cristianmenghi)

---

## 🙏 Acknowledgments

- Inspired by [Foorilla](https://foorilla.com/media)
- Built with [SimplePie](https://simplepie.org/)
- Terminal design inspired by classic Unix tools

---

## 📚 Documentation

For more detailed documentation, see:

- [API Documentation](docs/API.md)
- [Admin Guide](docs/ADMIN.md)
- [Development Guide](docs/DEVELOPMENT.md)
- [Deployment Guide](docs/DEPLOYMENT.md)
