# License Key Management System

A secure web-based license key management system with user authentication and administrative capabilities. Built with PHP and MySQL, featuring a modern semi-dark theme interface.

## 🌟 Features

### User Management
- **Secure Authentication**
  - User registration with license key verification
  - Secure login system with password hashing
  - Session-based authentication

### User Dashboard
- **License Management**
  - View license key status
  - Track license usage
- **Account Settings**
  - Profile management
  - Password updates

### Admin Dashboard
- **License Key Management**
  - Generate new license keys
  - View all active and used licenses
  - Revoke or deactivate licenses
- **User Management**
  - View registered users
  - Manage user access
- **Changelog Management**
  - Add and edit changelog entries
  - Track system updates and modifications

### Security Features
- Password hashing
- Session management
- SQL injection prevention
- XSS protection

### Interface
- **Modern UI/UX**
  - Responsive design
  - Semi-dark theme
  - Bootstrap 5 components
  - Clean and intuitive interface

## 📋 Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- Modern web browser

## 🚀 Installation

1. **Database Setup**
   ```sql
   # Create a new MySQL database
   CREATE DATABASE license_system;

   # Import database structure
   mysql -u your_username -p license_system < database.sql
   ```

2. **Configuration**
   ```php
   # Open config.php and update database credentials
   $host = "localhost"; 
   $dbname = "license_system";
   $username = "root";
   $password = "";
   ```

3. **Web Server Configuration**
   - Place the project files in your web server directory
   - Ensure proper permissions are set
   - Configure virtual host (optional)

## 🔧 Initial Setup

1. **First Time Setup**
   - The first license key will be created as "admin"
   - Register using this key


2. **Accessing the System**
   - Navigate to `http://your-domain/`
   - Login with your credentials
   - Admin dashboard will be available if you have admin privileges

## 💻 Usage

### User Operations
1. Register with a valid license key
2. Login to access the dashboard
3. View license information
4. Update profile settings

### Admin Operations
1. Access admin dashboard
2. Generate new license keys
3. Manage users and their access
4. Add changelog entries
5. Monitor system usage

## 🔐 Security Recommendations

1. Change default admin credentials immediately
2. Use strong passwords
3. Regularly update PHP and MySQL
4. Keep backups of your database
5. Monitor user activities

## 📝 Changelog

Changes and updates are managed through the admin panel. View the changelog section in the admin dashboard for detailed update history.

## 🌐 Deployment

### Local Development
- Use XAMPP, WAMP, or MAMP
- Configure virtual hosts if needed
- Enable error reporting for debugging

### Production
- Use a secure hosting provider
- Enable HTTPS
- Configure proper file permissions
- Disable error reporting

## ⚠️ Important Notes

- Always backup your database before updates
- Keep your admin credentials secure
- Regularly monitor system logs
- Update dependencies as needed

## 🤝 Contributing

Contributions are welcome! Please feel free to submit pull requests.

1. Fork the repository
2. Create a feature branch
3. Commit your changes
4. Push to the branch
5. Submit a pull request

## 📄 License

This project is licensed under the MIT License - see the LICENSE file for details.