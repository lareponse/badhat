
INSERT INTO `organization_type` (`slug`) VALUES ('partner'),('donor'),('collaborator');

-- =========================================================
-- IRSA — PARTNERS
-- =========================================================

INSERT INTO `organization`
(`label`, `slug`, `url`, `logo`, `content`, `enabled_at`)
VALUES
('AVIQ', 'aviq', 'https://www.aviq.be', 'aviq.png', NULL, NOW()),
('CHS', 'chs', 'https://c-h-s.be/', 'chs.be.webp', NULL, NOW()),
('CETH', 'ceth', 'https://www.ceth.be', 'c_eth.png', NULL, NOW()),
('Fondation Roi Baudouin', 'fondation-roi-baudouin', 'https://www.kbs-frb.be/fr', 'fondation_roi_baudouin.png', NULL, NOW()),
('Fédération Wallonie-Bruxelles', 'federation-wallonie-bruxelles', 'https://www.federation-wallonie-bruxelles.be', 'federation_wallonie_bruxelles.png', NULL, NOW()),
('Fondation IRSA', 'fondation-irsa', 'https://www.irsa.be', 'fondation_irsa.png', NULL, NOW()),
('Fondation ISEE', 'fondation-isee', 'https://fondationisee.be', 'fondation_isee.svg', NULL, NOW()),
('Centres PMS', 'centres-pms', 'https://www.enseignement.be/index.php?page=28001', 'centres_pms.jpg', NULL, NOW()),
('ONE', 'one', 'https://www.one.be', 'one.png', NULL, NOW()),
('SHC', 'shc', 'https://shc.health.belgium.be', 'shc.png', NULL, NOW()),
('COCOF', 'cocof', 'https://ccf.brussels/', 'francophones_bruxelles.png', NULL, NOW()),
('Réseau Francophone', 'reseau-francophone', 'https://www.reseaudefrance.be', 'reseau_francophone.png', NULL, NOW()),
('Commune d’Uccle', 'commune-uccle', 'https://www.uccle.be', 'uccle.png', NULL, NOW()),
('UCLouvain', 'uclouvain', 'https://uclouvain.be', 'uc_louvain.png', NULL, NOW());

INSERT INTO organization_organization_type
(organization_id, organization_type_id)
SELECT
  o.id,
  ot.id
FROM organization o
JOIN organization_type ot ON ot.name = 'partner'
WHERE o.slug IN (
  'aviq',
  'chs',
  'ceth',
  'fondation-roi-baudouin',
  'federation-wallonie-bruxelles',
  'fondation-irsa',
  'fondation-isee',
  'centres-pms',
  'one',
  'shc',
  'cocof',
  'reseau-francophone',
  'commune-uccle',
  'uclouvain'
)
AND NOT EXISTS (
  SELECT 1
  FROM organization_organization_type oot
  WHERE oot.organization_id = o.id
    AND oot.organization_type_id = ot.id
);

-- =========================================================
-- IRSA — ORGANIZATION
-- =========================================================


INSERT INTO `organization` (`label`, `slug`, `enabled_at`)
VALUES('IRSA – Institut Royal pour Sourds et Aveugles', 'irsa', NOW());


-- =========================================================
-- IRSA — CONTACT POINT (ACCUEIL)
-- =========================================================
INSERT INTO `contact_point`
(
  `label`,
  `email`,
  `phone`,
  `address_line1`,
  `postal_code`,
  `city`,
  `country`,
  `enabled_at`
)
SELECT
  'Accueil IRSA',
  'info@irsa.be',
  '+32 (0)2 343 22 27',
  'Chaussée de Waterloo 1502–1508',
  '1180',
  'Uccle',
  'Belgique',
  NOW()
WHERE NOT EXISTS (
  SELECT 1 FROM contact_point
  WHERE label = 'Accueil IRSA'
    AND city = 'Uccle'
);


-- =========================================================
-- LINK ORGANIZATION ↔ CONTACT POINT
-- =========================================================
INSERT INTO `organization_contact_point`
(`organization_id`, `contact_point_id`, `sort_order`)
SELECT
  o.id,
  cp.id,
  1
FROM organization o
JOIN contact_point cp
WHERE o.slug = 'irsa'
  AND cp.label = 'Accueil IRSA'
  AND NOT EXISTS (
    SELECT 1
    FROM organization_contact_point ocp
    WHERE ocp.organization_id = o.id
      AND ocp.contact_point_id = cp.id
  );


-- =========================================================
-- OPENING HOURS — MORNING (MON–FRI)
-- =========================================================
INSERT INTO `opening_hour`
(`contact_point_id`, `day_of_week`, `opens_at`, `closes_at`)
SELECT
  cp.id,
  d.day,
  '08:30:00',
  '12:00:00'
FROM contact_point cp
JOIN (
  SELECT 1 AS day UNION ALL
  SELECT 2 UNION ALL
  SELECT 3 UNION ALL
  SELECT 4 UNION ALL
  SELECT 5
) d
WHERE cp.label = 'Accueil IRSA'
  AND NOT EXISTS (
    SELECT 1 FROM opening_hour oh
    WHERE oh.contact_point_id = cp.id
      AND oh.day_of_week = d.day
      AND oh.opens_at = '08:30:00'
      AND oh.closes_at = '12:00:00'
  );


-- =========================================================
-- OPENING HOURS — AFTERNOON (MON–FRI)
-- =========================================================
INSERT INTO `opening_hour`
(`contact_point_id`, `day_of_week`, `opens_at`, `closes_at`)
SELECT
  cp.id,
  d.day,
  '13:00:00',
  '16:30:00'
FROM contact_point cp
JOIN (
  SELECT 1 AS day UNION ALL
  SELECT 2 UNION ALL
  SELECT 3 UNION ALL
  SELECT 4 UNION ALL
  SELECT 5
) d
WHERE cp.label = 'Accueil IRSA'
  AND NOT EXISTS (
    SELECT 1 FROM opening_hour oh
    WHERE oh.contact_point_id = cp.id
      AND oh.day_of_week = d.day
      AND oh.opens_at = '13:00:00'
      AND oh.closes_at = '16:30:00'
  );
