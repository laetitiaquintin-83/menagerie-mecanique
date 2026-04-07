-- 1. Création de la base de données (si elle n'existe pas)
CREATE DATABASE IF NOT EXISTS atelier_db;
USE atelier_db;

-- 2. Création de la table des créatures
CREATE TABLE IF NOT EXISTS creatures (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    categorie VARCHAR(50),
    description TEXT,
    image_url VARCHAR(255),
    prix DECIMAL(10, 2),
    stock INT DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Insertion de tes deux premiers compagnons
INSERT INTO creatures (nom, categorie, description, image_url, prix) 
VALUES 
('Sir Noisette', 'Explorateur', 'Équipé de ses lunettes d\'aviateur et de son propulseur à vapeur...', 'ecureuil.jpg', 150.00),
('Mademoiselle Boulon', 'Ingénieure', 'Maîtresse des rouages et des étincelles...', 'souris.jpg', 125.50);