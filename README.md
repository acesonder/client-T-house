# Shelter Management System

A comprehensive, role-based web application for homeless shelter committee management. Built with PHP, MySQL, HTML, CSS, and AJAX for responsive, accessible support services.

## 🌟 Features

### For Clients
- **Personal Portal**: Secure access to messages, resources, and documents
- **Smart Intake Assessment**: Comprehensive needs evaluation with automatic service matching
- **Goal Tracking**: Set and monitor personal goals with case manager support
- **Document Management**: Safely store ID, medical, legal, and other important documents
- **Secure Messaging**: Communicate with case managers, staff, and service providers
- **Resource Access**: Browse and request available resources and services

### For Staff & Volunteers
- **Client Management**: Comprehensive client profiles and case management tools
- **Assessment Tools**: Conduct intake and follow-up assessments
- **Service Coordination**: Match clients with appropriate service providers
- **Communication Hub**: Internal messaging and client communication
- **Progress Tracking**: Monitor client advancement and outcomes
- **Task Management**: Organize and track daily activities

### For Administrators
- **System Management**: Complete control over users, roles, and permissions
- **Analytics Dashboard**: Real-time insights into program effectiveness
- **Error Monitoring**: Comprehensive logging and troubleshooting tools
- **Configuration Tools**: Customize system settings and workflows
- **Reporting**: Generate compliance and outcome reports
- **Service Provider Management**: Maintain directory of external resources

### Technical Features
- **Responsive Design**: Optimized for desktop, tablet, and mobile devices
- **Customizable UI**: Each user can personalize colors, fonts, and layout
- **Role-Based Access**: Granular permissions for different user types
- **Error Handling**: Automatic logging with admin troubleshooting tools
- **AJAX Communication**: Fast, seamless interactions without page reloads
- **Smart Automation**: Automatic service matching and notifications
- **Security**: Session management, password hashing, SQL injection protection

## 🚀 Quick Start

### Requirements
- PHP 7.4+ (8.0+ recommended)
- MySQL 5.7+ (8.0+ recommended)
- Apache or Nginx web server
- 2GB+ RAM, 500MB+ disk space

### Installation

1. **Clone the repository**
```bash
git clone https://github.com/acesonder/client-T-house.git
cd client-T-house
```

2. **Create database**
```bash
mysql -u root -p
CREATE DATABASE shelter_management;
USE shelter_management;
SOURCE database/schema.sql;
EXIT;
```

3. **Configure application**
```bash
cp config/config.php.example config/config.php
# Edit config/config.php with your database credentials
```

4. **Set permissions**
```bash
mkdir -p logs assets/uploads
chmod 755 logs assets/uploads
```

5. **Access the application**
- Navigate to: `http://localhost/client-T-house/public/index.html`
- Default admin login: `admin` / `password` (change immediately!)

📚 **Full installation guide**: [docs/INSTALLATION.md](docs/INSTALLATION.md)

## 📖 Documentation

- **[Installation Guide](docs/INSTALLATION.md)** - Detailed setup instructions
- **[Configuration Questions](docs/CONFIGURATION_QUESTIONS.md)** - 100+ questions to customize the system
- **[Role-Based Features](docs/ROLE_BASED_FEATURES.md)** - Complete feature list for all user roles

## 🏗️ Architecture

```
client-T-house/
├── api/                 # AJAX endpoints
├── admin/              # Admin dashboard and tools
├── assets/
│   ├── css/           # Responsive stylesheets
│   ├── js/            # JavaScript utilities
│   ├── images/        # Images and icons
│   └── uploads/       # User uploaded documents
├── client/             # Client portal
├── config/             # Configuration files
├── database/           # SQL schema and migrations
├── docs/               # Documentation
├── includes/           # PHP classes (Auth, Database, ErrorHandler)
├── logs/               # Error and activity logs
├── public/             # Public landing page and login
└── staff/              # Staff and volunteer portal
```

## 🔒 Security Features

- **Password Hashing**: BCrypt encryption for all passwords
- **Session Management**: Secure session handling with timeout
- **SQL Injection Protection**: Prepared statements throughout
- **Role-Based Access Control**: Granular permission system
- **Error Logging**: Comprehensive logging without exposing sensitive data
- **Input Validation**: Server-side and client-side validation
- **HTTPS Support**: Ready for SSL/TLS encryption

## 🎨 Customization

Each user can customize their interface:
- **Theme Colors**: Primary, navbar, and button colors
- **Font Size**: Small, medium, or large
- **Layout Style**: Compact, comfortable, or spacious
- **Custom CSS**: Advanced users can add custom styles

## 🤝 Contributing

This is an alpha release. Contributions are welcome!

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

## 📝 License

This project is designed for homeless shelter organizations. Please use responsibly and in compliance with local privacy and data protection laws.

## 🙏 Acknowledgments

Built to support homeless shelter committees in providing comprehensive, dignified support services to those in need.

## 📞 Support

For issues, questions, or feature requests:
- GitHub Issues: https://github.com/acesonder/client-T-house/issues
- Review the documentation in `/docs/`

---

**Version**: 1.0.0-alpha
**Status**: Alpha Release - Ready for testing and feedback