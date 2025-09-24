-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : jeu. 11 sep. 2025 à 16:33
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `irsa_website`
--

-- --------------------------------------------------------

--
-- Structure de la table `contact`
--
CREATE DATABASE IF NOT EXISTS irsa_website;
USE irsa_website;

-- --------------------------------------------------------
-- Table `contact_form`
-- --------------------------------------------------------
CREATE TABLE `contact_form` ( 
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `firstname` VARCHAR(100) NOT NULL,
  `lastname` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `subject` VARCHAR(150) DEFAULT NULL,
  `description` TEXT NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table `person_contact`
-- --------------------------------------------------------
CREATE TABLE `person_contact` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `firstname` VARCHAR(100) NOT NULL,
  `lastname` VARCHAR(100) NOT NULL,
  `mail` VARCHAR(150) DEFAULT NULL,
  `phone` VARCHAR(50) DEFAULT NULL,
  `role` VARCHAR(100) DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table `donation`
-- --------------------------------------------------------
CREATE TABLE `donation` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `amount` DECIMAL(10,2) NOT NULL CHECK (`amount` > 0),
  `description` TEXT NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table `news`
-- --------------------------------------------------------
CREATE TABLE `news` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(150) NOT NULL,
  `content` TEXT NOT NULL,
  `image` VARCHAR(255) DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table `organization_type`
-- --------------------------------------------------------
CREATE TABLE `organization_type` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table `organization`
-- --------------------------------------------------------
CREATE TABLE `organization` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL,
  `logo` VARCHAR(255) DEFAULT NULL,
  `web_site` VARCHAR(255) DEFAULT NULL,
  `description` TEXT DEFAULT NULL,
  `organization_type_id` INT NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`organization_type_id`) REFERENCES `organization_type`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `organization_type` (`name`) VALUES 
('partner'),
('donor'),
('collaborator');

-- --------------------------------------------------------
-- Table `page`
-- --------------------------------------------------------
CREATE TABLE `page` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(150) NOT NULL,
  `slug` VARCHAR(150) NOT NULL UNIQUE,
  `content` LONGTEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table `service_category`
-- --------------------------------------------------------
CREATE TABLE `service_category` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `service_category` (`name`) VALUES
('accommodation'),
('day_care'),
('service'),
('other');

-- --------------------------------------------------------
-- Table `service`
-- --------------------------------------------------------
CREATE TABLE `service` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(150) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `service_category_id` INT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`service_category_id`) REFERENCES `service_category`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `service` (`name`, `description`, `service_category_id`) VALUES
('Centre d\'hébergement', NULL, 1),
('Centre d\'hébergement pour enfant', NULL, 1),
('Avec déficience auditive (enfant)', NULL, 1),
('Avec déficience visuelle (enfant)', NULL, 1),
('Avec troubles autistique (enfant)', NULL, 1),
('Centre d\'hébergement pour adulte', NULL, 1),
('Avec déficience visuelle - Aubier (adulte)', NULL, 1),
('Centre de jour', NULL, 2),
('Centre de jour pour enfant', NULL, 2),
('Scolarisé (enfant)', NULL, 2),
('Avec déficience auditive (scolarisé)', NULL, 2),
('Avec déficience visuelle (scolarisé)', NULL, 2),
('Avec troubles autistique (scolarisé)', NULL, 2),
('Non-scolarisé (enfant)', NULL, 2),
('Avec déficience auditive (non-scolarisé)', NULL, 2),
('Avec déficience visuelle (non-scolarisé)', NULL, 2),
('Avec troubles autistique (non-scolarisé)', NULL, 2),
('Centre de jour pour adulte', NULL, 2),
('Avec déficience visuelle (adulte)', NULL, 2),
('Ludothèque Oasis', NULL, 3),
('Location du château d\'Orangeraie', NULL, 3),
('Centre de documentation', NULL, 3),
('Restaurant d\'application', NULL, 3),
('Conférences', NULL, 3),
('Formations', NULL, 3);

-- --------------------------------------------------------
-- Table `statistics`
-- --------------------------------------------------------
CREATE TABLE `statistics` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `label` VARCHAR(150) NOT NULL,
  `value` VARCHAR(50) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `statistics` (`label`, `value`) VALUES
('Personnes accompagnées chaque année', '1000+'),
('Professionnels engagés', '600+'),
('Établissements scolaires spécialisés', '4'),
('Services et asbl annexes', '10'),
('Lieux de vie pour enfants et jeunes', '2'),
('Lieux de vie pour adulte', '1'),
('Depuis', '1835'),
('Moyenne d\'enfants réintégrés dans l\'enseignement traditionnel par an', '15'),
('Hectares de parc', '');

-- --------------------------------------------------------
-- Table `media`
-- --------------------------------------------------------
CREATE TABLE `media` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(150),
  `path` VARCHAR(255) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;