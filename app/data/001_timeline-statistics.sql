CREATE TABLE `timeline` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
  `event_year` smallint(6) NOT NULL UNIQUE,
  `label` varchar(255) NOT NULL,
  `photo_filename` varchar(64) DEFAULT NULL,
  `position_hint` varchar(16) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `statistics` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `label` VARCHAR(150) NOT NULL,
  `slug` VARCHAR(160) NOT NULL UNIQUE,
  `value` VARCHAR(50) NOT NULL,
  `sort_order` INT DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `enabled_at` DATETIME DEFAULT NULL,
  `revoked_at` DATETIME DEFAULT NULL
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_general_ci;


INSERT INTO `timeline` (`event_year`, `label`, `photo_filename`, `position_hint`) VALUES
(1835, 'Fondation de l\'Institut des Sourds-Muets', '1835.png', 'move-lr'),
(1837, 'Arrivée des Sœurs de la Charité', '1837.png', NULL),
(1858, 'Installation à Uccle', '1858.png', 'move-ud'),
(1870, 'Accueil des enfants aveugles', '1870.png', 'move-rl'),
(1900, 'Premiers ateliers professionnels', '1900.png', NULL),
(1948, 'Reconnaissance officielle', '1948.svg', 'move-ud'),
(1970, 'Expansion', '1970.svg', 'move-lr'),
(1987, 'Les centres de jour', '1987.svg', NULL),
(2000, 'Scolarité, soins, vie quotidienne', '2000.svg', 'move-ud'),
(2011, 'Le restaurant d\'application', '2011.svg', 'move-rl'),
(2014, 'Appui administratif AVIQ', '2014.png', NULL),
(2018, 'La ludothèque Oasis', '2018.svg', 'move-ud'),
(2020, 'Ouverture et expertise', '2020.svg', 'move-lr'),
(2025, 'Ouverture à l\'Autisme', '2025.svg', NULL);


INSERT INTO `statistics` (`label`,`slug`,`value`,`enabled_at`) VALUES ('Personnes accompagnées chaque année','personnes-accompagnees-par-an','600+',NOW()),
('Professionnels engagés','professionnels-engages','300+',NOW()),
('Établissements scolaires spécialisés','etablissements-scolaires-specialises','4',NOW()),
('Services et asbl annexes','services-asbl-annexes','10',NOW()),
('Lieux de vie pour enfants et jeunes','lieux-de-vie-enfants-jeunes','2',NOW()),
('Depuis','depuis','1835',NOW()),
('Moyenne d\'enfants réintégrés dans l\'enseignement traditionnel par an','moyenne-reintegration-enseignement-traditionnel','15',NOW()),
('Hectares de parc','hectares-de-parc','5',NOW());
