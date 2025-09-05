# Cosmetics POS System - Test Suite

This comprehensive test suite provides complete coverage for the Laravel 11 + Filament 3 Cosmetics POS system, focusing on stock management and loyalty points functionality.

## 🧪 Test Structure

### Unit Tests
- **Models**: Comprehensive testing of all model relationships, business logic, and data integrity
- **Services**: Stock alert logic, loyalty points calculations, and POS operations

### Feature Tests
- **Admin Panel**: CRUD operations, bulk actions, filtering, and reporting
- **POS System**: Complete sales workflow, customer management, and payment processing

### Integration Tests
- **Sales Flow**: End-to-end sales process with stock updates and loyalty points
- **Stock Management**: Purchase to stock workflow with alerts and reporting
- **Loyalty Points**: Complete customer loyalty program lifecycle

### Performance Tests
- **Database Performance**: Large dataset handling and query optimization
- **Concurrent Operations**: Multi-user scenarios and race condition prevention

### Security Tests
- **Authentication & Authorization**: Role-based access control
- **Input Validation**: SQL injection, XSS, and data sanitization
- **Data Protection**: Encryption and secure file handling

## 🚀 Getting Started

### Prerequisites
```bash
# Install testing dependencies (already done)
composer require --dev pestphp/pest pestphp/pest-plugin-laravel pestphp/pest-plugin-faker pestphp/pest-plugin-livewire
```

### Setup Testing Database
```bash
# Copy environment file
cp .env .env.testing

# Run migrations for testing database
php artisan migrate --env=testing

# Seed test data (optional)
php artisan db:seed --class=TestDatabaseSeeder --env=testing
```

### Run Tests

#### All Tests
```bash
./vendor/bin/pest
```

#### Specific Test Groups
```bash
# Unit tests only
./vendor/bin/pest --testsuite=Unit

# Feature tests only
./vendor/bin/pest --testsuite=Feature

# Performance tests
./vendor/bin/pest tests/Performance/

# Security tests
./vendor/bin/pest tests/Security/
```

#### Run Specific Tests
```bash
# Run model tests
./vendor/bin/pest tests/Unit/Models/

# Run integration tests
./vendor/bin/pest tests/Feature/Integration/

# Run stock alert tests
./vendor/bin/pest --filter=StockAlert
```

## 📁 Test Directory Structure

```
tests/
├── Unit/
│   ├── Models/
│   │   ├── BrandTest.php
│   │   ├── CategoryTest.php
│   │   ├── CustomerTest.php
│   │   ├── ProductTest.php
│   │   ├── ProductVariantTest.php
│   │   ├── SaleTest.php
│   │   ├── StockMovementTest.php
│   │   └── UserTest.php
│   └── Services/
│       ├── StockAlertServiceTest.php
│       ├── LoyaltyPointsServiceTest.php
│       └── POSTest.php
├── Feature/
│   ├── Admin/
│   │   ├── ProductManagementTest.php
│   │   ├── CustomerManagementTest.php
│   │   ├── SalesManagementTest.php
│   │   └── StockAlertTest.php
│   ├── POS/
│   │   └── POSTest.php
│   └── Integration/
│       ├── SalesFlowTest.php
│       ├── StockManagementTest.php
│       └── LoyaltyPointsTest.php
├── Performance/
│   └── DatabasePerformanceTest.php
├── Security/
│   └── SecurityTest.php
├── Pest.php (Configuration)
├── TestCase.php
└── README.md
```

## 🔧 Test Configuration

### Custom Expectations
The test suite includes custom expectations for stock management:

```php
expect($variant)->toHaveStockStatus('low_stock');
expect($variant)->toBeLowStock();
expect($variant)->toBeOutOfStock();
expect($variant)->toBeInStock();
expect($customer)->toHaveLoyaltyPoints(150);
```

### Helper Functions
```php
actingAsUser();     // Authenticate as regular user
actingAsAdmin();    // Authenticate as admin user
createTestProductWithVariants(); // Create product with variants
```

### Test Database
- Uses SQLite for fast testing
- Automatic database refresh between tests
- Test seeder available for realistic data

## 🎯 Key Test Coverage Areas

### Stock Management
- ✅ Stock status calculations (in_stock, low_stock, out_of_stock)
- ✅ Stock movement tracking and audit trails
- ✅ Low stock and out of stock alerts
- ✅ Stock updates after sales and purchases
- ✅ Concurrent stock modifications
- ✅ Stock reporting and analytics

### Loyalty Points System
- ✅ Points calculation based on purchase amounts
- ✅ Points awarding and redemption
- ✅ Customer points balance tracking
- ✅ Transaction history and audit trails
- ✅ Points expiration handling
- ✅ Multi-customer point transfers

### POS System
- ✅ Product search and selection
- ✅ Cart management and calculations
- ✅ Customer creation and lookup
- ✅ Payment processing (cash, card, digital)
- ✅ Receipt generation
- ✅ Stock updates after sales
- ✅ Loyalty points integration

### Admin Panel
- ✅ CRUD operations for all entities
- ✅ Bulk operations and data import/export
- ✅ Advanced filtering and search
- ✅ User role management
- ✅ Reporting and analytics
- ✅ Stock alert management

## 📊 Test Data Factories

Comprehensive factories for all models:

- **Brand**: Name, slug, description, logo, website
- **Category**: Hierarchical categories with parent relationships
- **Product**: Full product information with brand/category relationships
- **ProductVariant**: Stock levels, pricing, SKU management
- **Customer**: Contact info, loyalty points, purchase history
- **Sale**: Complete sales with items, payments, and customer relationships
- **User**: Role-based users (admin, cashier, manager)

## 🔒 Security Test Coverage

- ✅ SQL injection prevention
- ✅ XSS attack prevention
- ✅ CSRF protection
- ✅ File upload security
- ✅ Mass assignment protection
- ✅ Authentication and authorization
- ✅ Data validation and sanitization
- ✅ Session security
- ✅ Rate limiting
- ✅ Directory traversal prevention

## ⚡ Performance Benchmarks

The test suite includes performance benchmarks for:

- Large dataset operations (1000+ products)
- Concurrent user operations
- Complex query optimization
- Memory usage monitoring
- Database backup/restore operations
- Bulk import/export operations

## 🚦 Running Tests in CI/CD

### GitHub Actions Example
```yaml
name: Tests
on: [push, pull_request]
jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
      - name: Install dependencies
        run: composer install
      - name: Copy environment
        run: cp .env.example .env.testing
      - name: Generate key
        run: php artisan key:generate --env=testing
      - name: Run migrations
        run: php artisan migrate --env=testing
      - name: Run tests
        run: ./vendor/bin/pest --coverage
```

## 📈 Code Coverage

To generate coverage reports:

```bash
./vendor/bin/pest --coverage
```

Coverage reports will help identify:
- Untested code paths
- Areas needing additional tests
- Code quality metrics

## 🔧 Maintenance

### Adding New Tests
1. Follow the existing directory structure
2. Use appropriate test traits (`RefreshDatabase`, etc.)
3. Include both positive and negative test cases
4. Test edge cases and error conditions
5. Use factories for test data creation

### Test Data Management
- Use factories for consistent test data
- Avoid hard-coded IDs in tests
- Clean up test data between runs
- Use realistic data for integration tests

## 🎉 Benefits

This comprehensive test suite provides:

- **Confidence**: Thorough validation of business logic
- **Regression Prevention**: Catch bugs before they reach production
- **Documentation**: Tests serve as living documentation
- **Refactoring Safety**: Safe code refactoring with test coverage
- **Performance Monitoring**: Identify performance bottlenecks
- **Security Assurance**: Validate security measures

## 📞 Support

For questions about the test suite:
- Check test failure messages for detailed error information
- Review the Pest PHP documentation
- Examine existing test patterns for guidance
- Run individual tests with `--verbose` flag for debugging

---

**Note**: This test suite is designed to work with Laravel 11, Filament 3, and Pest PHP. Make sure all dependencies are properly installed before running tests.
