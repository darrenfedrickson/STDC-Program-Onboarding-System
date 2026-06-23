CREATE DATABASE IF NOT EXISTS stdc_registration_staging;
USE stdc_registration_staging;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    google_id VARCHAR(255) UNIQUE DEFAULT NULL,
    phone_number VARCHAR(20) NOT NULL,
    password_hash VARCHAR(255) NULL,
    role ENUM('admin', 'user', 'developer') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS programs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    capacity INT NOT NULL,
    status ENUM('active', 'closed') NOT NULL DEFAULT 'active',
    poster_image VARCHAR(255) NULL,
    custom_link_url VARCHAR(255) NULL,
    custom_link_text VARCHAR(255) NULL,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS program_fields (
    id INT AUTO_INCREMENT PRIMARY KEY,
    program_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    label VARCHAR(255) NOT NULL,
    description TEXT NULL,
    type VARCHAR(50) NOT NULL,
    options TEXT, -- comma separated or JSON for select/radio options
    required BOOLEAN DEFAULT 1,
    FOREIGN KEY (program_id) REFERENCES programs(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    program_id INT NOT NULL,
    user_id INT NOT NULL,
    application_status ENUM('pending', 'shortlisted', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (program_id) REFERENCES programs(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_registration (program_id, user_id)
);

CREATE TABLE IF NOT EXISTS registration_answers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    registration_id INT NOT NULL,
    field_id INT NOT NULL,
    answer_value TEXT,
    FOREIGN KEY (registration_id) REFERENCES registrations(id) ON DELETE CASCADE,
    FOREIGN KEY (field_id) REFERENCES program_fields(id) ON DELETE CASCADE
);

-- Insert a default admin user
-- Password is 'admin123'
INSERT INTO users (full_name, email, phone_number, password_hash, role) 
VALUES ('System Admin', 'admin@stdc.com', '0123456789', '$2y$10$HIdO62ThlMuhEQyrwQxrReVeYpm3UHHWmADXsnQV7q28r3roCJyeO', 'admin')
ON DUPLICATE KEY UPDATE id=id;

CREATE TABLE IF NOT EXISTS form_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    schema_json TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NULL,
    registration_id INT NOT NULL,
    field_name VARCHAR(255) NOT NULL,
    old_value TEXT,
    new_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (registration_id) REFERENCES registrations(id) ON DELETE CASCADE
);
