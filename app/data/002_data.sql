-- Insert service categories and capture IDs
INSERT INTO `service_category` (`name`) VALUES
('accommodation'),
('day_care'),
('service'),
('other');

SET @accommodation_id = (SELECT id FROM service_category WHERE name = 'accommodation');
SET @day_care_id = (SELECT id FROM service_category WHERE name = 'day_care');
SET @service_id = (SELECT id FROM service_category WHERE name = 'service');
SET @other_id = (SELECT id FROM service_category WHERE name = 'other');

-- Insert organization types
INSERT INTO `organization_type` (`name`) VALUES 
('partner'),
('donor'),
('collaborator');

SET @partner_id = (SELECT id FROM organization_type WHERE name = 'partner');
SET @donor_id = (SELECT id FROM organization_type WHERE name = 'donor');
SET @collaborator_id = (SELECT id FROM organization_type WHERE name = 'collaborator');

-- Insert services using variables
INSERT INTO `service` (`name`, `description`, `service_category_id`) VALUES
-- Accommodation services
('Centre d\'hébergement', NULL, @accommodation_id),
('Centre d\'hébergement pour enfant', NULL, @accommodation_id),
('Avec déficience auditive (enfant)', NULL, @accommodation_id),
('Avec déficience visuelle (enfant)', NULL, @accommodation_id),
('Avec troubles autistique (enfant)', NULL, @accommodation_id),
('Centre d\'hébergement pour adulte', NULL, @accommodation_id),
('Avec déficience visuelle - Aubier (adulte)', NULL, @accommodation_id),

-- Day care services
('Centre de jour', NULL, @day_care_id),
('Centre de jour pour enfant', NULL, @day_care_id),
('Scolarisé (enfant)', NULL, @day_care_id),
('Avec déficience auditive (scolarisé)', NULL, @day_care_id),
('Avec déficience visuelle (scolarisé)', NULL, @day_care_id),
('Avec troubles autistique (scolarisé)', NULL, @day_care_id),
('Non-scolarisé (enfant)', NULL, @day_care_id),
('Avec déficience auditive (non-scolarisé)', NULL, @day_care_id),
('Avec déficience visuelle (non-scolarisé)', NULL, @day_care_id),
('Avec troubles autistique (non-scolarisé)', NULL, @day_care_id),
('Centre de jour pour adulte', NULL, @day_care_id),
('Avec déficience visuelle (adulte)', NULL, @day_care_id),

-- General services
('Ludothèque Oasis', NULL, @service_id),
('Location du château d\'Orangeraie', NULL, @service_id),
('Centre de documentation', NULL, @service_id),
('Restaurant d\'application', NULL, @service_id),
('Conférences', NULL, @service_id),
('Formations', NULL, @service_id);

-- Insert statistics
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



-- Insert allowed MIME types
INSERT INTO `mime_type` (`type`, `category`, `max_size`) VALUES
('image/jpeg', 'image', 10000000),
('image/png', 'image', 10000000),
('image/webp', 'image', 5000000),
('image/svg+xml', 'image', 1000000),
('video/mp4', 'video', 100000000),
('video/webm', 'video', 100000000),
('audio/mp3', 'audio', 20000000),
('audio/wav', 'audio', 50000000),
('audio/ogg', 'audio', 20000000),
('application/pdf', 'document', 25000000);