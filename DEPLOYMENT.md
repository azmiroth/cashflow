# CashFlow System - Deployment Guide for SiteGround

## Prerequisites

- SiteGround hosting account with SSH access
- PHP 8.1 or higher
- MySQL 5.7 or higher
- Composer installed on the server
- Git installed on the server

## Deployment Steps

### 1. Clone Repository on SiteGround

```bash
cd ~/public_html
git clone <your-git-repo-url> .
```

### 2. Install Dependencies

```bash
composer install --no-dev
```

### 3. Environment Configuration

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` with your database credentials:
```
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=cashflow_db
DB_USERNAME=your_username
DB_PASSWORD=your_password

APP_URL=https://yourdomain.com
```

### 4. Create Database

```bash
mysql -u your_username -p your_password -e "CREATE DATABASE cashflow_db;"
```

### 5. Run Migrations

```bash
php artisan migrate
```

### 6. Set Permissions

```bash
chmod -R 755 storage bootstrap/cache
chmod -R 644 storage bootstrap/cache/*
```

### 7. Configure Web Server

Create `.htaccess` in public directory:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
```

### 8. Enable HTTPS

Use SiteGround's AutoSSL feature to enable HTTPS.

### 9. Configure Cron Job (Optional)

For background jobs, add to crontab:
```bash
* * * * * cd /home/username/public_html && php artisan schedule:run >> /dev/null 2>&1
```

## Post-Installation

1. Access your application at `https://yourdomain.com`
2. Create an account
3. Create an organisation
4. Add bank accounts
5. Import CSV bank statements
6. Generate cash flow predictions

## CSV Import Format

Your CSV files should have the following columns (in any order):
- **Date**: Transaction date (various formats supported)
- **Description**: Transaction description
- **Amount**: Transaction amount
- **Type**: 'credit', 'debit', 'in', 'out', '+', or '-'
- **Reference**: (Optional) Transaction reference number

Example:
```
Date,Description,Amount,Type,Reference
2024-01-15,Deposit,5000.00,credit,DEP001
2024-01-16,Office Supplies,250.50,debit,INV123
```

## Troubleshooting

### Database Connection Error
- Verify database credentials in `.env`
- Check MySQL is running on SiteGround
- Ensure database user has proper privileges

### Permission Denied Errors
- Run: `chmod -R 755 storage bootstrap/cache`
- Check file ownership

### Blank Page
- Check Laravel error logs: `storage/logs/laravel.log`
- Enable debug mode in `.env`: `APP_DEBUG=true`

## Security Recommendations

1. Set `APP_DEBUG=false` in production
2. Use strong database passwords
3. Enable HTTPS
4. Regularly backup your database
5. Keep Laravel and dependencies updated

## Support

For issues or questions, check the application logs at `storage/logs/laravel.log`
