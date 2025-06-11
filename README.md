# Pahnava - Single Vendor Clothing Ecommerce Platform

A complete, secure, and modern single-vendor clothing ecommerce website built with PHP and MySQL. Features a responsive design, comprehensive admin panel, and production-ready security measures.

## 🚀 Features

### Frontend Features
- **Responsive Design**: Mobile-first approach with Bootstrap 5
- **Mega Menu Navigation**: Multi-level category navigation
- **Product Catalog**: Advanced filtering, search, and sorting
- **Shopping Cart**: Persistent cart with AJAX updates
- **Wishlist**: Save favorite products
- **User Authentication**: Secure login/registration with OAuth support
- **Product Reviews**: Rating and review system
- **Order Tracking**: Complete order management
- **Payment Integration**: Razorpay payment gateway

### Admin Features
- **Dashboard**: Analytics and reporting
- **Product Management**: Complete CRUD operations
- **Order Management**: Process and track orders
- **User Management**: Customer account management
- **Category Management**: Hierarchical categories
- **Inventory Tracking**: Stock management
- **Coupon System**: Discount management
- **Email Templates**: Customizable notifications

### Security Features
- **CSRF Protection**: All forms protected
- **SQL Injection Prevention**: Prepared statements
- **XSS Protection**: Input sanitization
- **Rate Limiting**: Prevent abuse
- **Secure Sessions**: HTTPOnly, Secure cookies
- **Password Hashing**: Argon2ID encryption
- **File Upload Security**: Type and size validation

## 📋 Requirements

- **PHP**: 8.0 or higher
- **MySQL**: 8.0 or higher
- **Web Server**: Apache/Nginx
- **Extensions**: PDO, GD, cURL, OpenSSL
- **Memory**: 256MB minimum
- **Storage**: 1GB minimum

## 🛠️ Installation

### 1. Download and Extract
```bash
git clone https://github.com/your-repo/pahnava-php.git
cd pahnava-php
```

### 2. Set Permissions
```bash
chmod 755 uploads/
chmod 755 config/
chmod 644 *.php
```

### 3. Database Setup
1. Create a MySQL database
2. Import the schema from `database/schema.sql`
3. Or use the web installer (recommended)

### 4. Web Installation
1. Navigate to `http://yoursite.com/install.php`
2. Enter database credentials
3. Set up admin account
4. Complete installation

### 5. Post-Installation
1. Delete `install.php` for security
2. Configure payment gateway settings
3. Set up email configuration
4. Upload product images

## ⚙️ Configuration

### Database Configuration
Edit `config/database.php`:
```php
private $host = 'localhost';
private $username = 'your_username';
private $password = 'your_password';
private $database = 'pahnava_ecommerce';
```

### Payment Gateway (Razorpay)
1. Get API keys from Razorpay dashboard
2. Update in admin settings:
   - Key ID
   - Key Secret

### Email Configuration
Configure SMTP settings in admin panel or update `includes/functions.php`

## 📁 Directory Structure

```
pahnava-php/
├── admin/                  # Admin panel
├── ajax/                   # AJAX handlers
├── assets/                 # CSS, JS, images
│   ├── css/
│   ├── js/
│   └── images/
├── config/                 # Configuration files
├── database/               # Database schema
├── includes/               # Core PHP files
├── pages/                  # Frontend pages
├── uploads/                # User uploads
├── index.php              # Main entry point
├── install.php            # Installation script
└── README.md
```

## 🔐 Security Best Practices

### Implemented Security Measures
1. **Input Validation**: All user input validated and sanitized
2. **CSRF Tokens**: Protect against cross-site request forgery
3. **SQL Injection**: Prepared statements used throughout
4. **XSS Prevention**: Output encoding and CSP headers
5. **Session Security**: Secure session configuration
6. **File Upload**: Strict validation and type checking
7. **Rate Limiting**: Prevent brute force attacks
8. **Error Handling**: Secure error messages

### Additional Recommendations
1. Use HTTPS in production
2. Regular security updates
3. Strong admin passwords
4. Regular database backups
5. Monitor access logs
6. Implement WAF if possible

## 🎨 Customization

### Themes and Styling
- Edit `assets/css/style.css` for custom styles
- Modify `includes/header.php` and `includes/footer.php`
- Update color scheme in CSS variables

### Adding Features
- Create new pages in `pages/` directory
- Add AJAX handlers in `ajax/` directory
- Update navigation in header template

### Database Modifications
- Add new tables as needed
- Update models in `includes/` directory
- Maintain foreign key relationships

## 📱 Mobile Responsiveness

The platform is built with mobile-first design:
- Bootstrap 5 responsive grid
- Touch-friendly navigation
- Optimized images
- Fast loading times
- Progressive enhancement

## 🔧 Troubleshooting

### Common Issues

**Database Connection Error**
- Check database credentials
- Verify MySQL service is running
- Check database permissions

**File Upload Issues**
- Check directory permissions (755)
- Verify PHP upload limits
- Check file type restrictions

**Session Issues**
- Verify session directory permissions
- Check PHP session configuration
- Clear browser cookies

**Performance Issues**
- Enable PHP OPcache
- Optimize database queries
- Use CDN for static assets
- Enable gzip compression

## 📊 Performance Optimization

### Database Optimization
- Proper indexing on frequently queried columns
- Query optimization
- Connection pooling
- Regular maintenance

### Frontend Optimization
- Minified CSS/JS
- Image optimization
- Lazy loading
- Browser caching

### Server Optimization
- PHP OPcache enabled
- Gzip compression
- CDN integration
- Proper caching headers

## 🧪 Testing

### Manual Testing
1. User registration/login
2. Product browsing and search
3. Cart operations
4. Checkout process
5. Admin panel functions

### Security Testing
1. SQL injection attempts
2. XSS payload testing
3. CSRF token validation
4. File upload security
5. Authentication bypass

## 📈 Analytics and Monitoring

### Built-in Analytics
- User activity tracking
- Sales reporting
- Product performance
- Order analytics

### External Integration
- Google Analytics support
- Facebook Pixel ready
- Custom tracking events

## 🤝 Contributing

1. Fork the repository
2. Create feature branch
3. Make changes
4. Test thoroughly
5. Submit pull request

## 📄 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 🆘 Support

For support and questions:
- Create an issue on GitHub
- Check documentation
- Review troubleshooting guide

## 🔄 Updates

### Version 1.0.0
- Initial release
- Core ecommerce functionality
- Admin panel
- Security implementation
- Mobile responsive design

### Planned Features
- Multi-language support
- Advanced analytics
- Social media integration
- API development
- Mobile app support

---

**Note**: This is a production-ready ecommerce platform with comprehensive security measures. Always keep the system updated and follow security best practices.
