-- Schema for future database migration
-- Replace CSV storage with these tables and update code to use PDO.

CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'admin',
    created_at DATETIME NOT NULL,
    last_login DATETIME NULL
);

CREATE TABLE IF NOT EXISTS customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL,
    phone VARCHAR(50) NULL,
    created_at DATETIME NOT NULL,
    last_login DATETIME NULL
);

CREATE TABLE IF NOT EXISTS requests (
    id VARCHAR(50) PRIMARY KEY,
    timestamp DATETIME NOT NULL,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(50) NULL,
    service VARCHAR(100) NOT NULL,
    details TEXT NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'New',
    updated_at DATETIME NOT NULL,
    quote_amount DECIMAL(10,2) NULL,
    payment_status VARCHAR(50) NOT NULL DEFAULT 'Pending Quote',
    payment_id VARCHAR(100) NULL,
    INDEX idx_email (email),
    INDEX idx_status (status),
    INDEX idx_service (service)
);
