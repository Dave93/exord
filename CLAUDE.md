# CLAUDE.md - Repository Guide for AI Assistants

## Project Overview

**Project Name**: Les-Exord  
**Type**: Yii2 PHP Web Application  
**Domain**: Restaurant/Food Service Management System  
**Language**: Russian (ru-RU)  
**Database**: MySQL  

This is a comprehensive inventory and order management system for a restaurant chain called "EXORD". The system handles orders, stock management, oil inventory tracking, supplier management, and various business processes for restaurant operations.

## Architecture & Technology Stack

### Core Framework
- **Framework**: Yii2 (PHP 5.4+)
- **Pattern**: MVC (Model-View-Controller)
- **Database**: MySQL with Active Record ORM
- **Session Storage**: Database sessions
- **Caching**: File-based caching

### Key Dependencies
- `yiisoft/yii2` (~2.0.14) - Core framework
- `yiisoft/yii2-bootstrap` - UI components
- `phpoffice/phpspreadsheet` - Excel export functionality
- `kartik-v/yii2-mpdf` - PDF generation
- `kartik-v/yii2-widget-datepicker` - Date picker widgets
- `kartik-v/yii2-widget-select2` - Enhanced select widgets

### Development Tools
- **Testing**: Codeception (unit, functional, acceptance tests)
- **Debug**: Yii2 Debug Toolbar (dev environment)
- **Code Generation**: Gii (dev environment)
- **Containerization**: Docker support (docker-compose.yml)

## Directory Structure

```
/
├── assets/           # Asset bundles (CSS/JS dependencies)
├── commands/         # Console commands (CLI controllers)
├── config/           # Application configuration
├── controllers/      # Web controllers (business logic)
├── migrations/       # Database migrations
├── models/           # Data models and business logic
├── runtime/          # Generated files, logs, cache
├── tests/            # Test suites (unit, functional, acceptance)
├── views/            # View templates (PHP/HTML)
├── web/              # Web root (entry point, assets)
├── widgets/          # Custom widgets
└── vendor/           # Composer dependencies
```

## Business Domain & Features

### Core Modules

1. **Orders Management** (`OrdersController`, `Orders` model)
   - Order creation, tracking, and fulfillment
   - States: New (0), Sent (1), Completed (2), Office Review (3)
   - Integration with suppliers and stores
   - Market orders vs regular orders

2. **Oil Inventory System** (`OilInventoryController`, `OilInventory` model)
   - Daily oil usage tracking for restaurants
   - Conversion between kg and liters (ratio: 1kg = 1.1L)
   - Status workflow: new → filled → rejected/accepted
   - Advanced analytics dashboard

3. **Stock Management**
   - Product stock tracking
   - Stock balance monitoring
   - Daily store balances
   - Write-offs management

4. **User & Store Management**
   - Multi-store restaurant chain support
   - Role-based access control
   - Store-specific data isolation

5. **Reporting & Analytics**
   - Excel exports
   - PDF invoices
   - Dashboard analytics
   - Consumption trends

### Key Business Models

- **Orders**: Central order management with supplier integration
- **OilInventory**: Oil usage tracking with unit conversion
- **Products**: Product catalog with grouping
- **Stores**: Restaurant locations
- **Users**: System users with role-based permissions
- **Stock/StockBalance**: Inventory tracking

## Database Design

### Key Tables
- `orders` - Order records with supplier/store relationships
- `order_items` - Order line items
- `oil_inventory` - Daily oil usage records
- `products` - Product catalog
- `stores` - Restaurant locations
- `users` - System users
- `stock` / `stock_balance` - Inventory tracking

### Recent Schema Changes
- Oil inventory now supports kg input with automatic liter conversion
- Added `return_amount_kg` field for oil returns
- Enhanced oil inventory with status workflow

## Configuration Files

### Core Config (`config/web.php`)
- Application name: 'EXORD'
- Language: Russian (ru-RU)
- Layout: 'panel'
- Session storage: Database
- URL routing with REST API support

### Database (`config/db.php`)
- MySQL connection to `les_exord_db`
- Schema caching enabled for production

### Environment
- Development mode enabled (`YII_DEBUG = true`)
- Debug toolbar allowed for specific IPs
- Error reporting configured

## Development Guidelines

### Code Standards
- Follow Yii2 conventions
- Use Active Record for database operations
- Implement proper access control
- Russian language for user-facing text
- Database sessions for multi-user support

### Testing
- Codeception test framework
- Test suites: unit, functional, acceptance
- Coverage reports available
- Run tests: `vendor/bin/codecept run`

### Common Patterns

1. **Controllers**: Extend `yii\web\Controller` with access control
2. **Models**: Extend `yii\db\ActiveRecord` with validation rules
3. **Views**: PHP templates with Yii widgets
4. **Search Models**: Separate search classes for filtering
5. **Migrations**: Database changes via Yii migrations

## API Endpoints

REST API for Telegram integration:
- `/api/tguser/*` - Telegram user management
- Endpoints: check-user, post-video, post-calendar-message, post-calendar-date

## Recent Major Changes

### Oil Inventory Enhancements (2024)
- **Units Support**: Input in kg, automatic conversion to liters
- **Dashboard Analytics**: Enhanced filtering and role-based access
- **Workflow Management**: Status-based approval process
- **Return Logic Fix**: Corrected return calculations in analytics

See `CHANGES_OIL_INVENTORY_DASHBOARD.md` and `CHANGES_OIL_INVENTORY_UNITS.md` for detailed change logs.

## Development Commands

```bash
# Install dependencies
composer install

# Run migrations
php yii migrate

# Run tests
vendor/bin/codecept run

# Docker development
docker-compose up -d

# Console commands
php yii help
```

## Security Notes

- Access control implemented via `AccessRule` component
- Database credentials in `config/db.php` (should be secured)
- IP restrictions for debug tools in development
- Session-based authentication

## Integration Points

### External Systems
- **iiko**: POS/restaurant management system integration
- **Telegram**: Bot integration for notifications
- **Excel/PDF**: Document generation for reporting

### File Uploads
- PDF documents stored in `web/uploads/`
- Asset management via Yii asset manager

## Troubleshooting

### Common Issues
1. **Permissions**: Ensure `runtime/` and `web/assets/` are writable
2. **Database**: Check connection in `config/db.php`
3. **Dependencies**: Run `composer install` if vendor issues
4. **Migrations**: Run `php yii migrate` for database updates

### Log Files
- Application logs: `runtime/logs/app.log`
- Debug data: `runtime/debug/`

## Working with This Codebase

### For New Features
1. Create migration for database changes
2. Create/update models with validation rules
3. Implement controller actions with access control
4. Create views with proper Yii widgets
5. Add tests for new functionality

### For Bug Fixes
1. Check recent change logs in markdown files
2. Review related models and controllers
3. Test with different user roles
4. Consider impact on analytics/reporting

### Key Files to Understand
- `config/web.php` - Application configuration
- `models/Orders.php` - Core business logic
- `models/OilInventory.php` - Oil tracking system
- `controllers/OrdersController.php` - Main order management
- Recent change documentation files

This system is actively maintained and has recent enhancements to the oil inventory module with advanced analytics and unit conversion features.