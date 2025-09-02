# Cosmetics POS System

A comprehensive Point of Sale (POS) system built with Laravel 11 and Filament 3, specifically designed for cosmetics retail stores.

## 🌟 Features

### 📦 Inventory Management
- **Product Management**: Organize products by brands and categories with subcategory support
- **Product Variants**: Handle different sizes, colors, and variations of products
- **Stock Tracking**: Real-time stock quantity monitoring with automatic stock movement logging
- **Barcode Support**: SKU and barcode management for efficient product identification

### 🚨 Stock Alert System (Priority Feature)
- **Low Stock Alerts**: Configurable minimum stock levels with color-coded alerts
- **Dashboard Widgets**: Real-time stock overview with immediate visibility
- **Out of Stock Tracking**: Automatic detection and highlighting of zero-stock items
- **Stock Adjustment Tools**: Quick stock adjustment with movement history

### 💼 Sales Management
- **Sales Processing**: Complete sales workflow with customer assignment
- **Sale Items**: Detailed line-item tracking with pricing and quantities
- **Payment Methods**: Support for cash, card, and digital payments
- **Receipt Generation**: Automated sale number generation

### 👥 Customer Management
- **Customer Profiles**: Phone-based customer identification system
- **Loyalty Points**: Automatic points calculation and redemption tracking
- **Purchase History**: Complete customer transaction history

### 🏪 Supplier & Purchasing
- **Supplier Management**: Comprehensive supplier contact and information system
- **Purchase Orders**: Create and track purchase orders with delivery dates
- **Receiving**: Track ordered vs received quantities
- **Cost Management**: Monitor cost prices and profit margins

### 📊 Dashboard & Reporting
- **Stock Overview**: Real-time inventory statistics and alerts
- **Daily Sales**: Today's sales performance and transaction counts
- **Low Stock Widget**: Immediate visibility of items requiring attention
- **Quick Actions**: Direct links to reorder and stock adjustment functions

## 🛠 Technical Stack

- **Backend**: Laravel 11
- **Admin Panel**: Filament 3
- **Database**: MySQL/SQLite
- **Frontend**: Livewire 3, Alpine.js, Tailwind CSS
- **File Storage**: Local/Cloud storage for product images

## 📋 Database Schema

### Core Tables
- `brands` - Product brands (L'Oréal, MAC, etc.)
- `categories` - Product categories with subcategory support
- `products` - Main product information
- `product_variants` - Product variations with stock tracking
- `customers` - Customer information and loyalty points
- `suppliers` - Supplier contact information

### Transaction Tables
- `sales` - Sales transactions with payment details
- `sale_items` - Individual items within sales
- `purchases` - Purchase orders from suppliers
- `purchase_items` - Items within purchase orders
- `stock_movements` - Complete audit trail of stock changes
- `loyalty_point_transactions` - Customer points history

### Configuration
- `settings` - System configuration and preferences

## 🚀 Installation

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd cosmetics-pos
   ```

2. **Install dependencies**
   ```bash
   composer install
   npm install
   ```

3. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Database setup**
   ```bash
   # Configure your database in .env
   php artisan migrate
   php artisan db:seed
   ```

5. **Storage setup**
   ```bash
   php artisan storage:link
   ```

6. **Start development server**
   ```bash
   php artisan serve
   ```

7. **Access the admin panel**
   - URL: `http://localhost:8000/admin`
   - Default login: `admin@cosmetics-pos.com` / `password`

## 📱 Usage

### Stock Alert System
The stock alert system is the core feature of this POS system:

1. **Dashboard Overview**: The main dashboard shows:
   - Total products count
   - Low stock alerts with direct links
   - Out of stock items count
   - Today's sales performance

2. **Product Variant Management**:
   - Color-coded stock status (Red: Out of stock, Orange: Low stock, Green: In stock)
   - Quick stock adjustment actions
   - Bulk activate/deactivate options
   - Filterable by stock status

3. **Low Stock Widget**:
   - Real-time list of items requiring attention
   - Direct reorder and adjustment links
   - Auto-refresh every 30 seconds

### Product Management
1. **Brands**: Manage cosmetic brands with logos and descriptions
2. **Categories**: Hierarchical category system (Face > Foundation)
3. **Products**: Main product information with brand and category assignment
4. **Variants**: Specific SKUs with individual pricing and stock levels

### Sales Workflow
1. **Customer Selection**: Find existing or create new customers
2. **Product Selection**: Add product variants to the sale
3. **Payment Processing**: Record payment method and complete sale
4. **Loyalty Points**: Automatic points calculation and assignment

### Inventory Receiving
1. **Create Purchase Orders**: Order from suppliers
2. **Track Deliveries**: Monitor expected vs actual delivery dates
3. **Receive Stock**: Update received quantities and stock levels
4. **Cost Tracking**: Monitor cost prices and margins

## ⚙️ Configuration

### Settings Management
Access the Settings resource to configure:

- **Store Information**: Name, address, phone
- **Tax Configuration**: Tax rates and calculations
- **Loyalty Program**: Points per dollar spent
- **Stock Alerts**: Default minimum stock levels
- **Email Notifications**: Low stock alert emails

### Stock Alert Configuration
- Set minimum stock levels per product variant
- Configure email alerts for low stock
- Customize alert thresholds and colors

## 🔒 Security Features

- **User Authentication**: Secure admin panel access
- **Role-based Access**: Filament's built-in authorization
- **Audit Trail**: Complete stock movement history
- **Data Validation**: Comprehensive form validation

## 📈 Future Enhancements

### Planned Features
1. **POS Interface**: Dedicated cashier interface for fast sales processing
2. **Barcode Scanner Integration**: Hardware barcode scanner support
3. **Advanced Reporting**: Sales analytics and inventory reports
4. **Multi-location Support**: Multiple store locations
5. **API Integration**: Third-party integrations (accounting, e-commerce)

### Stock Alert Enhancements
1. **Email Notifications**: Automated low stock alerts
2. **Supplier Integration**: Direct reorder functionality
3. **Predictive Analytics**: Smart reorder suggestions
4. **Mobile Alerts**: Push notifications for critical stock levels

## 🐛 Troubleshooting

### Common Issues

1. **Stock not updating**: Check stock movement logs in the database
2. **Images not displaying**: Run `php artisan storage:link`
3. **Permissions errors**: Check file permissions on storage directories
4. **Database errors**: Verify database connection and run migrations

### Stock Alert Issues
- Ensure minimum stock levels are set correctly
- Check that product variants are marked as active
- Verify dashboard widgets are enabled in AdminPanelProvider

## 📞 Support

For technical support or feature requests, please create an issue in the repository or contact the development team.

## 📄 License

This project is proprietary software developed for cosmetics retail operations.

---

**Built with ❤️ for the cosmetics retail industry**