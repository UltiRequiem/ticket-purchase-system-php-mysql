# Ticket Fairy - Event Ticket Management System

A robust PHP-based ticket purchasing and management system designed to handle event ticket sales with proper validation, transaction management, and reporting capabilities.

## 🎯 Project Overview

Ticket Fairy is a comprehensive ticket management system that provides:

- **Secure Ticket Purchasing**: Prevents overselling with database-level locking
- **Event Management**: Create and manage events with capacity limits
- **Real-time Reporting**: Track ticket sales and revenue per event
- **Modern Architecture**: Clean separation of concerns with repositories, services, and interfaces
- **Transaction Safety**: ACID-compliant database operations
- **Input Validation**: Comprehensive validation for all user inputs

## 🚀 Quick Start with Docker 

### Prerequisites

- Docker and Docker Compose installed
- Git (to clone the repository)

### Setup Instructions

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd ticket-fairy-challengea
   ```

2. **Start the application**
   ```bash
   docker-compose up -d
   ```

3. **Wait for services to initialize** (about 30-60 seconds)
   ```bash
   docker-compose logs -f
   ```

4. **Access the application**
   - Main application: http://localhost:8080
   - phpMyAdmin: http://localhost:8081 (root/rootpassword)
   - MySQL: localhost:3306

5. **Initialize sample data** (optional)
   ```bash
   docker-compose exec web php scripts/sample_data.php
   ```

6. **Test the setup**
   ```bash
   docker-compose exec web php scripts/test_config.php
   ```

### Docker Services

- **Web Server**: Apache with PHP 8.1 (Port 8080)
- **Database**: MySQL 8.0 (Port 3306)
- **Database Admin**: phpMyAdmin (Port 8081)

## 🛠️ Manual Installation

### Prerequisites

- PHP 7.4 or higher with extensions:
  - PDO
  - pdo_mysql
  - mbstring
  - json
- MySQL 5.7 or higher
- Composer
- Web server (Apache/Nginx)

### Detailed Setup Steps

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd ticket-fairy-challengea
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Configure environment variables**
   ```bash
   cp .env.example .env
   ```
   
   Edit `.env` with your database credentials:
   ```env
   DB_HOST=localhost
   DB_PORT=3306
   DB_DATABASE=ticket_fairy
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```

4. **Create MySQL database**
   ```bash
   mysql -u root -p -e "CREATE DATABASE ticket_fairy;"
   ```

5. **Import database schema**
   ```bash
   mysql -u your_username -p ticket_fairy < sql/schema.sql
   ```

6. **Set proper permissions**
   ```bash
   chmod -R 755 .
   chown -R www-data:www-data . # For Apache
   ```

7. **Configure web server**
   
   **Apache Virtual Host Example:**
   ```apache
   <VirtualHost *:80>
       DocumentRoot /path/to/ticket-fairy-challengea
       ServerName ticket-fairy.local
       
       <Directory /path/to/ticket-fairy-challengea>
           AllowOverride All
           Require all granted
       </Directory>
   </VirtualHost>
   ```

8. **Test configuration**
   ```bash
   php scripts/test_config.php
   ```

9. **Add sample data** (optional)
   ```bash
   php scripts/sample_data.php
   ```

## 📖 Usage Guide

### Purchasing Tickets

**Command Line:**
```bash
php purchase_ticket.php
```

**Web Interface (POST request):**
```bash
curl -X POST http://localhost:8080/purchase_ticket.php \
  -d "event_id=1&quantity=2&customer_email=customer@example.com"
```

**Required Parameters:**
- `event_id`: ID of the event (integer)
- `quantity`: Number of tickets to purchase (integer, > 0)
- `customer_email`: Customer's email address (valid email format)

**Response Example:**
```json
{
  "success": true,
  "message": "Tickets purchased successfully",
  "ticket_id": 123,
  "total_amount": 50.00,
  "event_name": "Concert Night"
}
```

### Generating Reports

**Command Line:**
```bash
php reports.php
```

**Web Interface:**
Access `http://localhost:8080/reports.php` to view:
- Total tickets sold per event
- Revenue per event
- Remaining ticket capacity
- Event details and statistics

### API Endpoints

The system includes RESTful API endpoints:

- `POST /src/Controllers/TicketController.php` - Purchase tickets
- `GET /reports.php` - View sales reports
- `GET /purchase_ticket.php` - Purchase form (if accessed via GET)

## 🧪 Testing

### Run All Tests
```bash
composer test
```

### Run Specific Test Suite
```bash
./vendor/bin/phpunit tests/Services/TicketServiceTest.php
```

### Generate Test Coverage Report
```bash
./vendor/bin/phpunit --coverage-html coverage/
open coverage/index.html
```

### Manual Testing

1. **Test Database Connection:**
   ```bash
   php scripts/test_config.php
   ```

2. **Test Ticket Purchase:**
   ```bash
   curl -X POST http://localhost:8080/purchase_ticket.php \
     -d "event_id=1&quantity=1&customer_email=test@example.com"
   ```

3. **Test Reports:**
   ```bash
   curl http://localhost:8080/reports.php
   ```

## 🗄️ Database Schema

### Events Table
```sql
CREATE TABLE events (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    event_date DATETIME NOT NULL,
    ticket_price DECIMAL(10,2) NOT NULL,
    total_tickets INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_event_date (event_date),
    INDEX idx_name (name)
);
```

### Tickets Table
```sql
CREATE TABLE tickets (
    id INT PRIMARY KEY AUTO_INCREMENT,
    event_id INT NOT NULL,
    customer_email VARCHAR(255) NOT NULL,
    quantity INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    purchase_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    INDEX idx_event_id (event_id),
    INDEX idx_customer_email (customer_email),
    INDEX idx_purchase_date (purchase_date)
);
```

## 🔧 Configuration

### Environment Variables

Create a `.env` file with the following variables:

```env
# Database Configuration
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=ticket_fairy
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Application Settings (optional)
APP_ENV=production
APP_DEBUG=false
```

### Database Connection

The application uses PDO with the following features:
- Prepared statements for SQL injection prevention
- Connection pooling and reuse
- Comprehensive error handling
- Transaction management

## 🚨 Error Handling

The system includes comprehensive error handling:

- **TicketException**: Custom exceptions for business logic errors
- **Database Errors**: Proper PDO exception handling with rollback
- **Validation Errors**: Input validation with meaningful error messages
- **HTTP Status Codes**: Proper status codes for API responses

### Common Error Scenarios

1. **Insufficient Tickets**: When trying to purchase more tickets than available
2. **Invalid Event**: When event ID doesn't exist
3. **Database Connection**: When database is unavailable
4. **Invalid Input**: When required parameters are missing or invalid

## 🔒 Security Features

- **SQL Injection Prevention**: All queries use prepared statements
- **Input Validation**: Comprehensive validation for all user inputs
- **Email Validation**: RFC-compliant email validation
- **Transaction Locking**: Row-level locking to prevent race conditions
- **Error Sanitization**: Safe error messages without sensitive data exposure
- **XSS Prevention**: Output escaping for web interfaces

## 📊 Performance Considerations

- **Database Indexes**: Optimized queries with proper indexing on frequently queried columns
- **Connection Reuse**: Efficient database connection management
- **Transaction Scope**: Minimal transaction duration to reduce lock time
- **Memory Management**: Efficient data handling for large datasets
- **Query Optimization**: Optimized SQL queries for better performance

## 🐛 Troubleshooting

### Common Issues and Solutions

**1. Database Connection Failed**
```bash
# Check database service
sudo systemctl status mysql

# Test connection
php scripts/test_config.php

# Verify credentials in .env file
cat .env
```

**2. Composer Dependencies Missing**
```bash
# Install dependencies
composer install

# Clear composer cache if needed
composer clear-cache
composer install
```

**3. Permission Issues**
```bash
# Fix file permissions
chmod -R 755 /path/to/project
chown -R www-data:www-data /path/to/project
```

**4. Docker Issues**
```bash
# Restart containers
docker-compose down
docker-compose up --build -d

# Check logs
docker-compose logs web
docker-compose logs db
```

**5. Port Already in Use**
```bash
# Check what's using the port
lsof -i :8080

# Kill the process or change port in docker-compose.yml
```

### Debug Mode

Enable debug mode by setting in `.env`:
```env
APP_DEBUG=true
```

### Log Files

Check application logs:
- Docker: `docker-compose logs web`
- Apache: `/var/log/apache2/error.log`
- MySQL: `docker-compose logs db`

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Make your changes
4. Add tests for new functionality
5. Ensure all tests pass (`composer test`)
6. Commit your changes (`git commit -m 'Add amazing feature'`)
7. Push to the branch (`git push origin feature/amazing-feature`)
8. Open a Pull Request

### Development Guidelines

- Follow PSR-12 coding standards
- Write unit tests for new features
- Update documentation for API changes
- Use meaningful commit messages
- Keep functions small and focused

## 📝 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 📞 Support

For support and questions:
- Create an issue in the repository
- Check the troubleshooting section above
- Review the test files for usage examples
- Check Docker logs for runtime issues

## 🔄 Version History

- **v1.0.0**: Initial release with basic ticket purchasing and reporting
- **v1.1.0**: Added Docker support and comprehensive testing
- **v1.2.0**: Enhanced error handling and security features

---

**Quick Start Summary:**
1. `git clone <repo>` 
2. `docker-compose up -d`
3. Visit http://localhost:8080
4. Optional: `docker-compose exec web php scripts/sample_data.php`