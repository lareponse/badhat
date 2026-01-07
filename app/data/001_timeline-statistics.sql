CREATE TABLE `timeline` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `event_year` smallint(6) NOT NULL UNIQUE,
  `label` varchar(100) NOT NULL,
  `photo_filename` varchar(64) DEFAULT NULL,
  `position_hint` varchar(16) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;



INSERT INTO `timeline` (`event_year`, `label`, `photo_filename`, `position_hint`) 
VALUES
(1835, "Fondation de l'Institut des Sourds-Muets", "1835.png", "move-lr"),
(1837, "Arrivée des Sœurs de la Charité", "1837.png", NULL),
(1858, "Installation à Uccle", "1858.png", "move-ud"),
(1870, "Accueil des enfants aveugles", "1870.png", "move-rl"),
(1900, "Premiers ateliers professionnels", "1900.png", NULL),
(1948, "Reconnaissance officielle", "1948.svg", "move-ud"),
(1970, "Expansion", "1970.svg", "move-lr"),
(1987, "Les centres de jour", "1987.svg", NULL),
(2000, "Scolarité, soins, vie quotidienne", "2000.svg", "move-ud"),
(2011, "Le restaurant d'application", "2011.svg", "move-rl"),
(2014, "Appui administratif AVIQ", "2014.png", NULL),
(2018, "La ludothèque Oasis", "2018.svg", "move-ud"),
(2020, "Ouverture et expertise", "2020.svg", "move-lr"),
(2025, "Ouverture à l'Autisme", "2025.svg", NULL);


ALTER TABLE `timeline` ADD `content` varchar(500) NOT NULL;

UPDATE `timeline` SET `content` =
"L'institution est créée sous le nom d'<strong>Institut des Sourds-Muets</strong> par le baron de Gérando, dans un ancien hospice situé rue aux Laines, à Bruxelles.

Elle accueille ses premiers élèves sourds avec pour ambition de leur offrir une éducation adaptée, humaine et ambitieuse."
WHERE `event_year` = 1835;

UPDATE `timeline` SET `content` =
"Deux ans après la fondation de l'institut, les <strong>Sœurs de la Charité de Jésus et Marie</strong> rejoignent l'établissement.

Elles assurent l'accompagnement quotidien, l'enseignement et la gestion humaine pendant plus d'un siècle, jouant un rôle essentiel dans l'histoire et l'identité de l'IRSA."
WHERE `event_year` = 1837;

UPDATE `timeline` SET `content` =
"Face à l'essor de ses activités, l'institut déménage à <strong>Uccle</strong>, sur un vaste terrain de cinq hectares.

Ce site reste encore aujourd'hui le cœur battant de l'IRSA."
WHERE `event_year` = 1858;

UPDATE `timeline` SET `content` =
"L'IRSA élargit sa mission : il accueille désormais aussi des <strong>enfants aveugles</strong>, devenant un des rares lieux en Belgique à accompagner ces deux publics."
WHERE `event_year` = 1870;

UPDATE `timeline` SET `content` =
"Avec la création d'<strong>ateliers de vannerie et de formation manuelle</strong>, l'IRSA amorce une réflexion sur <strong>l'autonomie et l'insertion sociale</strong> des jeunes adultes."
WHERE `event_year` = 1900;

UPDATE `timeline` SET `content` =
"L'institut devient un <strong>établissement libre subventionné</strong>. Il est reconnu pour la qualité de son enseignement et son engagement social."
WHERE `event_year` = 1948;

UPDATE `timeline` SET `content` =
"Création des <strong>internats</strong>, modernisation des bâtiments, mise en place de <strong>l'enseignement secondaire</strong>, développement de l'approche <strong>paramédicale</strong> : l'IRSA devient un pôle complet."
WHERE `event_year` = 1970;

UPDATE `timeline` SET `content` =
"L'IRSA ouvre ses <strong>premiers centres de jour</strong>, offrant un accompagnement hors scolarité à des enfants avec des besoins spécifiques plus complexes."
WHERE `event_year` = 1987;

UPDATE `timeline` SET `content` =
"L'institution <strong>diversifie ses services</strong> : soins paramédicaux, accompagnement précoce à domicile, interventions éducatives, soutiens aux familles."
WHERE `event_year` = 2000;

UPDATE `timeline` SET `content` =
"Ouverture d'un <strong>espace de formation professionnelle inclusif</strong>, où les jeunes peuvent apprendre un métier en situation réelle."
WHERE `event_year` = 2011;

UPDATE `timeline` SET `content` =
"Mise en place d'un service d'accompagnement aux démarches administratives, pour soutenir les familles dans <strong>l'accès à leurs droits</strong>."
WHERE `event_year` = 2014;

UPDATE `timeline` SET `content` =
"Création d'un <strong>espace de jeu sensoriel accessible</strong> et adapté, pensé pour les enfants accompagnés par l'IRSA."
WHERE `event_year` = 2018;

UPDATE `timeline` SET `content` =
"Développement de services autour des troubles associés, de l'accessibilité des environnements et de la formation des professionnels externes. L'IRSA affirme sa mission : <strong>accompagner chaque personne</strong>, dans toutes les dimensions de sa vie."
WHERE `event_year` = 2020;

UPDATE `timeline` SET `content` =
"En septembre 2025, l'IRSA élargit sa mission et ouvre un département spécifique dédié à l'accompagnement des <strong>enfants et jeunes autistes</strong>. Cette nouvelle orientation marque une étape importante dans l'histoire de l'institution, fidèle à sa vocation d'inclusion et d'adaptation aux besoins contemporains."
WHERE `event_year` = 2025;



CREATE TABLE `statistics` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `label` VARCHAR(150) NOT NULL,
  `slug` VARCHAR(160) NOT NULL UNIQUE,
  `value` VARCHAR(50) NOT NULL,
  `sort_order` INT DEFAULT 0,

  `enabled_at` DATETIME DEFAULT NULL,
  `revoked_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_general_ci;



INSERT INTO `statistics` (`label`,`slug`,`value`,`enabled_at`) VALUES ('Personnes accompagnées chaque année','personnes-accompagnees-par-an','600+',NOW()),
('Professionnels engagés','professionnels-engages','300+',NOW()),
('Établissements scolaires spécialisés','etablissements-scolaires-specialises','4',NOW()),
('Services et asbl annexes','services-asbl-annexes','10',NOW()),
('Lieux de vie pour enfants et jeunes','lieux-de-vie-enfants-jeunes','2',NOW()),
('Depuis','depuis','1835',NOW()),
('Moyenne d\'enfants réintégrés dans l\'enseignement traditionnel par an','moyenne-reintegration-enseignement-traditionnel','15',NOW()),
('Hectares de parc','hectares-de-parc','5',NOW());
