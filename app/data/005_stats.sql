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


INSERT INTO `statistics` (`label`,`slug`,`value`,`enabled_at`) VALUES ('Personnes accompagnées chaque année','personnes-accompagnees-par-an','600+',NOW());
INSERT INTO `statistics` (`label`,`slug`,`value`,`enabled_at`) VALUES ('Professionnels engagés','professionnels-engages','300+',NOW());
INSERT INTO `statistics` (`label`,`slug`,`value`,`enabled_at`) VALUES ('Établissements scolaires spécialisés','etablissements-scolaires-specialises','4',NOW());
INSERT INTO `statistics` (`label`,`slug`,`value`,`enabled_at`) VALUES ('Services et asbl annexes','services-asbl-annexes','10',NOW());
INSERT INTO `statistics` (`label`,`slug`,`value`,`enabled_at`) VALUES ('Lieux de vie pour enfants et jeunes','lieux-de-vie-enfants-jeunes','2',NOW());
INSERT INTO `statistics` (`label`,`slug`,`value`,`enabled_at`) VALUES ('Depuis','depuis','1835',NOW());
INSERT INTO `statistics` (`label`,`slug`,`value`,`enabled_at`) VALUES ('Moyenne d\'enfants réintégrés dans l\'enseignement traditionnel par an','moyenne-reintegration-enseignement-traditionnel','15',NOW());
INSERT INTO `statistics` (`label`,`slug`,`value`,`enabled_at`) VALUES ('Hectares de parc','hectares-de-parc','5',NOW());
