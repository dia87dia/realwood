-- SQL Script to setup database on Beget
-- Tables based on the provided schema and project needs

CREATE TABLE IF NOT EXISTS requests (
    id_request INT AUTO_INCREMENT PRIMARY KEY,
    client_name VARCHAR(255) DEFAULT NULL,
    phone VARCHAR(50) NOT NULL,
    id_product INT DEFAULT NULL,
    comment TEXT,
    request_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(50) DEFAULT 'новая'
);

CREATE TABLE IF NOT EXISTS products (
    id_product INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    packaging_type VARCHAR(255),
    price DECIMAL(10, 2),
    is_active BOOLEAN DEFAULT TRUE
);

-- Insert initial product if not exists
INSERT INTO products (name, packaging_type) VALUES ('Древесный уголь', 'Биг-беги / мешки');
