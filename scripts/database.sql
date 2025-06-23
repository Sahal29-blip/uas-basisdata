-- Buat database
CREATE DATABASE IF NOT EXISTS sensor_monitoring;

USE sensor_monitoring;

-- Tabel untuk data sensor DHT11 dan PIR
CREATE TABLE IF NOT EXISTS sensor_data (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    timestamp DATETIME NOT NULL,
    temperature DECIMAL(5, 2) NOT NULL,
    humidity DECIMAL(5, 2) NOT NULL,
    motion_detected TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tabel untuk admin login
CREATE TABLE IF NOT EXISTS admin_users (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert admin default (password: admin123)
INSERT INTO admin_users (username, password) VALUES 
('admin', '$2y$10$DPkL4CBGPSNSen4ZgzPYYOR9RUcU0dCFaIpFUl9Uy/u5LcFDTH6F6');

-- Memasukkan data dummy untuk simulasi
INSERT INTO sensor_data (timestamp, temperature, humidity, motion_detected) VALUES 
('2025-01-08 14:00:00', 25.5, 60.2, 1),
('2025-01-08 14:05:00', 25.8, 59.8, 0),
('2025-01-08 14:10:00', 26.1, 58.5, 1),
('2025-01-08 14:15:00', 26.3, 57.9, 0),
('2025-01-08 14:20:00', 26.0, 58.8, 1),
('2025-01-08 14:25:00', 25.7, 59.5, 0),
('2025-01-08 14:30:00', 25.9, 60.1, 1),
('2025-01-08 14:35:00', 26.2, 58.3, 0),
('2025-01-08 14:40:00', 26.5, 57.6, 1),
('2025-01-08 14:45:00', 26.1, 58.9, 0),
('2025-01-08 14:50:00', 25.8, 59.7, 1),
('2025-01-08 14:55:00', 25.6, 60.3, 0),
('2025-01-08 15:00:00', 25.9, 59.1, 1),
('2025-01-08 15:05:00', 26.4, 57.8, 0),
('2025-01-08 15:10:00', 26.7, 56.9, 1),
('2025-01-08 15:15:00', 26.3, 58.2, 0),
('2025-01-08 15:20:00', 26.0, 59.4, 1),
('2025-01-08 15:25:00', 25.7, 60.0, 0);

