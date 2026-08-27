-- ============================================================
--  Smart Irrigation API - Base de données MySQL
--  À importer dans phpMyAdmin ou via MySQL CLI
-- ============================================================

CREATE DATABASE IF NOT EXISTS smart_irrigation
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE smart_irrigation;

-- ── Utilisateurs ──────────────────────────────────────────
CREATE TABLE utilisateurs (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  nom             VARCHAR(100)  NOT NULL,
  prenom          VARCHAR(100)  NOT NULL,
  email           VARCHAR(191)  NOT NULL UNIQUE,
  mot_de_passe    VARCHAR(255)  NOT NULL,          -- bcrypt
  telephone       VARCHAR(20)   DEFAULT NULL,
  role            ENUM('admin','user') DEFAULT 'user',
  statut          ENUM('actif','inactif','banni') DEFAULT 'actif',
  date_inscription DATETIME     DEFAULT CURRENT_TIMESTAMP,
  derniere_connexion DATETIME   DEFAULT NULL
) ENGINE=InnoDB;

-- ── Stations (Raspberry Pi) ───────────────────────────────
CREATE TABLE stations (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  nom             VARCHAR(150)  NOT NULL,
  localisation    VARCHAR(255)  DEFAULT NULL,
  latitude        DECIMAL(10,7) DEFAULT NULL,
  longitude       DECIMAL(10,7) DEFAULT NULL,
  adresse_ip      VARCHAR(255)  NOT NULL,          -- URL locale ou Cloudflare
  statut          ENUM('actif','inactif','maintenance') DEFAULT 'actif',
  date_installation DATE         DEFAULT NULL,
  cree_par        INT           DEFAULT NULL,
  FOREIGN KEY (cree_par) REFERENCES utilisateurs(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ── Abonnements utilisateur ↔ station ────────────────────
CREATE TABLE abonnements (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  utilisateur_id  INT NOT NULL,
  station_id      INT NOT NULL,
  date_abonnement DATETIME DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_abo (utilisateur_id, station_id),
  FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
  FOREIGN KEY (station_id)     REFERENCES stations(id)     ON DELETE CASCADE
) ENGINE=InnoDB;

-- ── Parcelles ─────────────────────────────────────────────
CREATE TABLE parcelles (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  utilisateur_id  INT NOT NULL,
  station_id      INT DEFAULT NULL,
  nom             VARCHAR(150) NOT NULL,
  superficie      DECIMAL(10,2) DEFAULT NULL,      -- en hectares
  culture         VARCHAR(100)  DEFAULT NULL,
  localisation    VARCHAR(255)  DEFAULT NULL,
  date_creation   DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
  FOREIGN KEY (station_id)     REFERENCES stations(id)     ON DELETE SET NULL
) ENGINE=InnoDB;

-- ── Mesures capteurs ──────────────────────────────────────
CREATE TABLE mesures (
  id              BIGINT AUTO_INCREMENT PRIMARY KEY,
  station_id      INT NOT NULL,
  temperature     DECIMAL(5,2)  DEFAULT NULL,      -- °C
  humidite        DECIMAL(5,2)  DEFAULT NULL,      -- %
  humidite_sol    DECIMAL(5,2)  DEFAULT NULL,      -- %
  pression        DECIMAL(7,2)  DEFAULT NULL,      -- hPa
  pluie           DECIMAL(7,2)  DEFAULT NULL,      -- valeur brute capteur
  vent            DECIMAL(6,2)  DEFAULT NULL,      -- km/h
  lumiere         DECIMAL(7,2)  DEFAULT NULL,      -- lux (LDR)
  horodatage      DATETIME      DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_station_time (station_id, horodatage),
  FOREIGN KEY (station_id) REFERENCES stations(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ── Alertes ───────────────────────────────────────────────
CREATE TABLE alertes (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  station_id      INT  NOT NULL,
  utilisateur_id  INT  DEFAULT NULL,
  type            VARCHAR(100)  NOT NULL,
  message         TEXT          NOT NULL,
  capteur         VARCHAR(50)   DEFAULT NULL,
  valeur_capteur  DECIMAL(10,2) DEFAULT NULL,
  lue             TINYINT(1)    DEFAULT 0,
  horodatage      DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (station_id)    REFERENCES stations(id)     ON DELETE CASCADE,
  FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ── Journaux de connexion (sécurité / audit) ──────────────
CREATE TABLE journaux_connexion (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  utilisateur_id  INT DEFAULT NULL,
  email_tente     VARCHAR(191) DEFAULT NULL,
  adresse_ip      VARCHAR(45)  DEFAULT NULL,
  action          ENUM('connexion','deconnexion','inscription','echec') DEFAULT 'connexion',
  details         VARCHAR(255) DEFAULT NULL,
  horodatage      DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ── Tokens invalidés (logout) ─────────────────────────────
CREATE TABLE tokens_blacklist (
  id              INT AUTO_INCREMENT PRIMARY KEY,
  token_hash      VARCHAR(64) NOT NULL UNIQUE,     -- SHA256 du token
  expire_le       DATETIME    NOT NULL,
  cree_le         DATETIME    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ── Données initiales ─────────────────────────────────────
-- Admin par défaut : admin@smart-irrigation.com / Admin@1234
INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role, statut)
VALUES (
  'Admin', 'Système',
  'admin@smart-irrigation.com',
  '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- Admin@1234
  'admin', 'actif'
);

INSERT INTO stations (nom, localisation, adresse_ip, statut, cree_par)
VALUES ('Station Principale', 'Site 1', 'http://192.168.43.129:1880/data', 'actif', 1);

INSERT INTO abonnements (utilisateur_id, station_id) VALUES (1, 1);
