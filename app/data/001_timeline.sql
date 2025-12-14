CREATE TABLE timeline (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_year SMALLINT NOT NULL UNIQUE,
    label VARCHAR(255) NOT NULL,
    photo_filename VARCHAR(64),   -- ex: 1835.png, 1948.svg
    position_hint VARCHAR(16)     -- move-lr, move-ud, move-rl, NULL
);

INSERT INTO timeline
(event_year, label, photo_filename, position_hint)
VALUES
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
