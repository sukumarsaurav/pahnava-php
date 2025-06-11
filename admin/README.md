# Pahnava Admin Panel

A comprehensive, secure, and feature-rich admin panel for the Pahnava ecommerce platform.

## 🚀 Features

### 📊 Dashboard
- Real-time statistics and analytics
- Sales charts and performance metrics
- Recent orders overview
- Quick action buttons
- Low stock alerts

### 🛍️ Product Management
- Complete product catalog management
- Bulk operations (activate, deactivate, delete)
- Product variants (size, color)
- Image gallery management
- SEO optimization
- Inventory tracking
- SKU generation

### 📦 Order Management
- Order status tracking and updates
- Bulk status changes
- Order details view
- Print functionality
- Customer information
- Payment status tracking

### 👥 Customer Management
- Customer profiles and information
- Order history per customer
- Account status management
- Bulk operations
- Customer analytics

### ⚙️ Settings Management
- General store settings
- Email configuration (SMTP)
- Payment gateway setup (Razorpay)
- Shipping configuration
- Tax settings
- SEO settings
- Social media integration

### 🔐 Security Features
- Role-based access control
- CSRF protection
- Rate limiting
- Session management
- Activity logging
- Secure password hashing (Argon2ID)
- Remember me functionality

## 📁 File Structure

```
admin/
├── index.php              # Main entry point and router
├── logout.php             # Logout handler
├── README.md              # This file
├── includes/              # Core admin files
│   ├── admin-auth.php     # Authentication class
│   ├── admin-functions.php # Helper functions
│   ├── header.php         # Admin header template
│   └── footer.php         # Admin footer template
├── pages/                 # Admin pages
│   ├── dashboard.php      # Main dashboard
│   ├── login.php          # Login page
│   ├── products.php       # Product management
│   ├── add-product.php    # Add new product
│   ├── edit-product.php   # Edit product
│   ├── orders.php         # Order management
│   ├── customers.php      # Customer management
│   ├── settings.php       # Settings management
│   ├── categories.php     # Category management
│   ├── brands.php         # Brand management
│   ├── coupons.php        # Coupon management
│   ├── reviews.php        # Review management
│   ├── inventory.php      # Inventory management
│   ├── reports.php        # Reports and analytics
│   ├── shipping.php       # Shipping settings
│   ├── taxes.php          # Tax settings
│   └── profile.php        # Admin profile
├── ajax/                  # AJAX handlers
│   ├── update-order-status.php
│   ├── generate-sku.php
│   ├── bulk-actions.php
│   ├── get-order-details.php
│   ├── get-customer-details.php
│   └── test-email.php
└── assets/               # Frontend assets
    ├── css/
    │   └── admin.css     # Admin styles
    └── js/
        └── admin.js      # Admin JavaScript
```

## 🔧 Installation

1. **Database Setup**
   ```sql
   -- Run the updated schema.sql to create admin tables
   SOURCE database/schema.sql;
   ```

2. **Default Admin Account**
   - Username: `admin`
   - Email: `admin@pahnava.com`
   - Password: `admin123`
   
   **⚠️ IMPORTANT: Change the default password immediately after first login!**

3. **File Permissions**
   ```bash
   chmod 755 admin/
   chmod 644 admin/*.php
   chmod 644 admin/includes/*.php
   chmod 644 admin/pages/*.php
   chmod 644 admin/ajax/*.php
   ```

## 🎯 Usage

### Accessing the Admin Panel
1. Navigate to `yoursite.com/admin/`
2. Login with admin credentials
3. You'll be redirected to the dashboard

### Managing Products
1. Go to **Products** → **All Products**
2. Use filters to find specific products
3. Click **Add Product** to create new products
4. Use bulk actions for multiple products

### Managing Orders
1. Go to **Orders**
2. View order status overview
3. Click on orders to view details
4. Update order status as needed
5. Use bulk actions for multiple orders

### Managing Customers
1. Go to **Customers**
2. View customer statistics
3. Search and filter customers
4. View customer details and order history

### Configuring Settings
1. Go to **Settings**
2. Configure different tabs:
   - **General**: Basic store information
   - **Email**: SMTP configuration
   - **Payment**: Razorpay setup
   - **Shipping**: Shipping rules
   - **Tax**: Tax configuration
   - **SEO**: Meta tags and analytics
   - **Social**: Social media links

## 🔐 Security

### Authentication
- Secure login with rate limiting
- Password hashing using Argon2ID
- Session management with timeout
- Remember me functionality with secure tokens

### Authorization
- Role-based permissions:
  - **Super Admin**: Full access
  - **Admin**: Most features except critical settings
  - **Manager**: Products, orders, customers
  - **Staff**: Orders and inventory only

### Protection
- CSRF tokens on all forms
- Input sanitization and validation
- SQL injection prevention
- XSS protection
- Rate limiting on sensitive actions

### Logging
- All admin activities are logged
- Security events tracking
- Failed login attempts monitoring

## 🎨 Customization

### Styling
- Modify `admin/assets/css/admin.css` for custom styles
- Bootstrap 5 framework for responsive design
- CSS variables for easy theming

### Functionality
- Add new pages in `admin/pages/`
- Create AJAX handlers in `admin/ajax/`
- Extend permissions in `AdminAuth` class
- Add new settings in the settings page

## 📊 Performance

### Optimization
- Efficient database queries with proper indexing
- AJAX for dynamic content loading
- Pagination for large datasets
- Image optimization recommendations

### Caching
- Session-based caching for user data
- Database query optimization
- Static asset caching headers

## 🐛 Troubleshooting

### Common Issues

1. **Can't login**
   - Check database connection
   - Verify admin user exists
   - Check password hash

2. **Permission denied**
   - Verify user role and permissions
   - Check session validity

3. **AJAX errors**
   - Check CSRF tokens
   - Verify admin authentication
   - Check browser console for errors

### Debug Mode
Enable debug mode by adding to your config:
```php
define('ADMIN_DEBUG', true);
```

## 🔄 Updates

### Version History
- **v1.0.0**: Initial release with core functionality
- Complete admin panel with all essential features
- Security-first approach with comprehensive protection

### Future Enhancements
- Advanced reporting and analytics
- Email marketing integration
- Multi-language support
- Advanced inventory management
- API integration capabilities

## 📞 Support

For support and questions:
- Check the main project README
- Review the code documentation
- Check error logs in your server

## 🔒 Security Notice

This admin panel contains sensitive functionality. Always:
- Use HTTPS in production
- Keep admin credentials secure
- Regularly update passwords
- Monitor access logs
- Restrict admin access by IP if possible
- Keep the system updated

---

**Built with security, performance, and usability in mind for the Pahnava ecommerce platform.**
