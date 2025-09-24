INSERT INTO `service_category` (`name`) VALUES
('accommodation'),
('day_care'),
('service'),
('other');


INSERT INTO `organization_type` (`name`) VALUES 
('partner'),
('donor'),
('collaborator');


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