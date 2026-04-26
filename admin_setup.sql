-- Admin table for LabourHub Admin Portal
-- Run this after database.sql

USE hiring_portal;

CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default admin (username: admin, password: admin123)
INSERT INTO admins (username, password, name) VALUES
('admin', '$2y$10$Pd5NLrUegBcC4rs5B3XM/evaLTyHnqNrChIbGjNHQyNa4tnVf7eUa', 'Super Admin');
