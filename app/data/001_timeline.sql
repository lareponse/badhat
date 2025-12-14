CREATE TABLE `timeline` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
  `event_year` smallint(6) NOT NULL UNIQUE,
  `label` varchar(255) NOT NULL,
  `photo_filename` varchar(64) DEFAULT NULL,
  `position_hint` varchar(16) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `timeline` (`id`, `event_year`, `label`, `photo_filename`, `position_hint`) VALUES
(1, 1835, 'Fondation de l\'Institut des Sourds-Muets', '1835.png', 'move-lr'),
(2, 1837, 'Arrivée des Sœurs de la Charité', '1837.png', NULL),
(3, 1858, 'Installation à Uccle', '1858.png', 'move-ud'),
(4, 1870, 'Accueil des enfants aveugles', '1870.png', 'move-rl'),
(5, 1900, 'Premiers ateliers professionnels', '1900.png', NULL),
(6, 1948, 'Reconnaissance officielle', '1948.svg', 'move-ud'),
(7, 1970, 'Expansion', '1970.svg', 'move-lr'),
(8, 1987, 'Les centres de jour', '1987.svg', NULL),
(9, 2000, 'Scolarité, soins, vie quotidienne', '2000.svg', 'move-ud'),
(10, 2011, 'Le restaurant d\'application', '2011.svg', 'move-rl'),
(11, 2014, 'Appui administratif AVIQ', '2014.png', NULL),
(12, 2018, 'La ludothèque Oasis', '2018.svg', 'move-ud'),
(13, 2020, 'Ouverture et expertise', '2020.svg', 'move-lr'),
(14, 2025, 'Ouverture à l\'Autisme', '2025.svg', NULL);

