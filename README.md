# CashFlow - Cash Flow Prediction System

A professional Laravel-based application for managing multiple bank accounts, importing bank statements, and predicting future cash flow requirements using advanced algorithms.

## Features

### Multi-Organization Support
- Create and manage multiple organisations
- Segment cash flow tracking by business entity
- Role-based access control for team members

### Bank Account Management
- Add and manage multiple bank accounts per organisation
- Support for different account types (checking, savings, credit, etc.)
- Track account balances in real-time

### Bank Statement Import
- Import transactions from CSV files
- Flexible column mapping for various CSV formats
- Automatic duplicate detection
- Support for multiple date and amount formats
- Detailed import history and error reporting

### Cash Flow Predictions
- **Moving Average Method**: Calculates daily average and identifies trends
- **Trend Analysis Method**: Linear regression with R-squared confidence calculation
- Configurable analysis periods (30-365 days)
- Configurable forecast periods (7-90 days)
- Confidence level indicators
- Trend direction analysis (increasing, decreasing, stable)

### Dashboard & Analytics
- Real-time balance overview
- 30-day cash flow visualization
- Recent transaction history
- Prediction summaries
- Organisation switcher

## System Requirements

- PHP 8.1+
- MySQL 5.7+
- Composer
- 2GB disk space minimum

## Installation

### Local Development

```bash
# Clone the repository
git clone https://github.com/yourusername/cashflow.git
cd cashflow

# Install dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate app key
php artisan key:generate

# Create database
mysql -u root -p -e "CREATE DATABASE cashflow_dev;"

# Update .env with database credentials
# DB_DATABASE=cashflow_dev
# DB_USERNAME=root
# DB_PASSWORD=your_password

# Run migrations
php artisan migrate

# Start development server
php artisan serve
```

Access the application at `http://localhost:8000`

### SiteGround Deployment

See [DEPLOYMENT.md](DEPLOYMENT.md) for detailed SiteGround deployment instructions.

## Usage

### Creating an Organisation

1. Log in to your account
2. Click "New Organisation"
3. Enter organisation details (name, description, currency, fiscal year start)
4. Click "Create"

### Adding a Bank Account

1. Navigate to "Bank Accounts"
2. Click "Add Account"
3. Enter account details:
   - Account Name
   - Account Number
   - Bank Name
   - Account Type
   - Currency
   - Opening Balance
4. Click "Create"

### Importing Bank Statements

1. Navigate to "Bank Accounts"
2. Click "Import" on the desired account
3. Select your CSV file
4. Map columns:
   - Date Column (required)
   - Description Column (required)
   - Amount Column (required)
   - Type Column (required) - should contain 'credit', 'debit', 'in', 'out', '+', or '-'
   - Reference Column (optional)
5. Click "Import"

### Creating Cash Flow Predictions

1. Navigate to "Predictions"
2. Click "Create Prediction"
3. Enter prediction details:
   - Prediction Name
   - Select Bank Accounts (one or more)
   - Analysis Period (30, 60, 90, 180, or 365 days)
   - Forecast Period (7, 14, 30, 60, or 90 days)
   - Prediction Method (Moving Average or Trend Analysis)
4. Click "Create"

## Prediction Algorithms

### Moving Average Method

Calculates the average daily cash flow over the analysis period and projects it forward:

```
Predicted Balance = Current Balance + (Average Daily Flow × Forecast Days)
Confidence = 100 - (Standard Deviation / Average Flow × 50)
```

### Trend Analysis Method

Uses linear regression to identify trends and project future cash flow:

```
Predicted Balance = Current Balance + Sum of (Slope × Day + Intercept)
Confidence = R² × 100
```

## CSV Format

Supported CSV formats with flexible column mapping:

```
Date,Description,Amount,Type,Reference
2024-01-15,Deposit,5000.00,credit,DEP001
2024-01-16,Office Supplies,250.50,debit,INV123
2024-01-17,Client Payment,3000.00,in,PAY001
```

Supported date formats:
- YYYY-MM-DD
- MM/DD/YYYY
- DD/MM/YYYY
- DD-MM-YYYY
- YYYY/MM/DD
- M d, Y
- d M Y

Supported amount formats:
- 1000.00
- 1,000.00
- $1,000.00
- 1.000,00 (European format)

## Project Structure

```
cashflow/
├── app/
│   ├── Http/
│   │   ├── Controllers/          # Application controllers
│   │   └── Middleware/           # Custom middleware
│   ├── Models/                   # Eloquent models
│   ├── Policies/                 # Authorization policies
│   └── Services/                 # Business logic services
├── database/
│   ├── migrations/               # Database migrations
│   └── seeders/                  # Database seeders
├── resources/
│   └── views/                    # Blade templates
├── routes/
│   └── web.php                   # Web routes
└── public/
    └── index.php                 # Application entry point
```

## Database Schema

### Users
- id, name, email, password, phone, timezone, is_active, timestamps

### Organisations
- id, owner_id, name, description, currency, fiscal_year_start, is_active, timestamps

### Organisation Members
- id, organisation_id, user_id, role, permissions, timestamps

### Bank Accounts
- id, organisation_id, account_name, account_number, bank_name, account_type, currency, opening_balance, current_balance, is_active, timestamps

### Transactions
- id, bank_account_id, category_id, transaction_date, description, amount, transaction_type, reference, is_reconciled, timestamps

### Transaction Categories
- id, organisation_id, name, description, color, is_active, timestamps

### Cash Flow Predictions
- id, organisation_id, prediction_name, analysis_period_days, forecast_period_days, prediction_method, predicted_balance, confidence_level, trend, created_by, timestamps

### Prediction Account Selections
- id, cash_flow_prediction_id, bank_account_id, timestamps

### Import Histories
- id, organisation_id, bank_account_id, filename, file_path, imported_by, total_records, successful_records, failed_records, status, error_message, timestamps

## Security Features

- Bcrypt password hashing
- SQL injection prevention (prepared statements)
- XSS prevention (HTML entity encoding)
- CSRF token protection
- Session timeout (1 hour)
- Secure cookies (HttpOnly, Secure, SameSite)
- Role-based access control
- Authorization policies for all resources

## API Endpoints

The application provides RESTful API endpoints:

- `GET /api/organisations` - List organisations
- `POST /api/organisations` - Create organisation
- `GET /api/bank-accounts` - List bank accounts
- `POST /api/bank-accounts` - Create bank account
- `GET /api/transactions` - List transactions
- `POST /api/predictions` - Create prediction
- `GET /api/predictions/{id}` - Get prediction details

## Performance Optimization

- Database query optimization with eager loading
- Indexed database columns for fast queries
- Pagination for large datasets
- Caching of frequently accessed data
- Optimized CSV import processing

## Troubleshooting

### Import Fails with "File not found"
- Ensure the CSV file exists and is readable
- Check file permissions

### Predictions Show Zero Confidence
- Ensure sufficient transaction history (minimum 30 days)
- Check that transactions span the analysis period

### Database Connection Error
- Verify database credentials in `.env`
- Ensure MySQL service is running
- Check database user permissions

## Contributing

Contributions are welcome! Please follow these guidelines:
1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Submit a pull request

## License

This project is licensed under the MIT License.

## Support

For issues or questions, please create an issue on GitHub or contact support@cashflow.local

## Changelog

### Version 1.0.0 (2026-01-13)
- Initial release
- Multi-organization support
- Bank account management
- CSV import functionality
- Cash flow prediction engine
- Dashboard and analytics
- User authentication and authorization
