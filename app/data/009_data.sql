START TRANSACTION;

-- IRSA — PAGES
-- =========================================================
INSERT INTO `page` (`id`, `slug`, `label`, `template`, `enabled_at`)
SELECT v.id, v.slug, v.label, v.template, v.enabled_at
FROM (
  SELECT 1 AS id, 'irsa'        AS slug, 'IRSA'        AS label, 'default' AS template, CURRENT_TIMESTAMP AS enabled_at
  UNION ALL
  SELECT 2,        'irsa-ecoles',        'Les écoles',          'default',           CURRENT_TIMESTAMP
  UNION ALL
  SELECT 3,        'irsa-oa',             'Gouvernance',        'default',           CURRENT_TIMESTAMP
) v
WHERE NOT EXISTS (
  SELECT 1 FROM page p WHERE p.slug = v.slug
);


-- IRSA — SECTIONS
-- =========================================================
INSERT INTO `school` (`slug`, `label`, `enabled_at`)
SELECT v.slug, v.label, v.enabled_at
FROM (
  SELECT
    'secondaire-t1-t6-t7' AS slug,
    'École secondaire spécialisée (T1–T6–T7)' AS label,
    NOW() AS enabled_at
  UNION ALL
  SELECT
    'fondamentale-t2-t6-t8',
    'École fondamentale spécialisée (T2–T6–T8)',
    NOW()
  UNION ALL
  SELECT
    'fondamentale-t7',
    'École fondamentale spécialisée – déficience auditive',
    NOW()
) v
WHERE NOT EXISTS (
  SELECT 1
  FROM school s
  WHERE s.slug = v.slug
);




-- Insert parent (if not already inserted)
INSERT INTO `tag` (`slug`)
VALUES ('organization_type');

INSERT INTO `tag` (`parent_id`, `slug`)
SELECT t.id, v.slug
FROM `tag` t
JOIN (
  SELECT 'partner' AS slug
  UNION ALL SELECT 'donor'
  UNION ALL SELECT 'collaborator'
) v
WHERE t.slug = 'organization_type';



-- IRSA — PARTNERS
-- =========================================================
-- Insert organizations (optional: make idempotent by slug)
INSERT INTO organization (label, slug, url, logo, content, enabled_at)
SELECT v.label, v.slug, v.url, v.logo, v.content, v.enabled_at
FROM (
  SELECT 'AVIQ' AS label, 'aviq' AS slug, 'https://www.aviq.be' AS url, 'aviq.png' AS logo, NULL AS content, NOW() AS enabled_at
  UNION ALL SELECT 'CHS','chs','https://c-h-s.be/','chs.be.webp',NULL,NOW()
  UNION ALL SELECT 'CETH','ceth','https://www.ceth.be','c_eth.png',NULL,NOW()
  UNION ALL SELECT 'Fondation Roi Baudouin','fondation-roi-baudouin','https://www.kbs-frb.be/fr','fondation_roi_baudouin.png',NULL,NOW()
  UNION ALL SELECT 'Fédération Wallonie-Bruxelles','federation-wallonie-bruxelles','https://www.federation-wallonie-bruxelles.be','federation_wallonie_bruxelles.png',NULL,NOW()
  UNION ALL SELECT 'Fondation IRSA','fondation-irsa','https://www.irsa.be','fondation_irsa.png',NULL,NOW()
  UNION ALL SELECT 'Fondation ISEE','fondation-isee','https://fondationisee.be','fondation_isee.svg',NULL,NOW()
  UNION ALL SELECT 'Centres PMS','centres-pms','https://www.enseignement.be/index.php?page=28001','centres_pms.jpg',NULL,NOW()
  UNION ALL SELECT 'ONE','one','https://www.one.be','one.png',NULL,NOW()
  UNION ALL SELECT 'SHC','shc','https://shc.health.belgium.be','shc.png',NULL,NOW()
  UNION ALL SELECT 'COCOF','cocof','https://ccf.brussels/','francophones_bruxelles.png',NULL,NOW()
  UNION ALL SELECT 'Réseau Francophone','reseau-francophone','https://www.reseaudefrance.be','reseau_francophone.png',NULL,NOW()
  UNION ALL SELECT 'Commune d’Uccle','commune-uccle','https://www.uccle.be','uccle.png',NULL,NOW()
  UNION ALL SELECT 'UCLouvain','uclouvain','https://uclouvain.be','uc_louvain.png',NULL,NOW()
) v
WHERE NOT EXISTS (
  SELECT 1 FROM organization o WHERE o.slug = v.slug
);



INSERT INTO tag_organization
(organization_id, tag_id)
SELECT
  o.id,
  t.id
FROM organization o
JOIN tag t
  ON t.slug = 'partner'
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
  FROM tag_organization to2
  WHERE to2.organization_id = o.id
    AND to2.tag_id = t.id
);


-- IRSA — ORGANIZATION
-- =========================================================


INSERT INTO `organization` (`label`, `slug`, `enabled_at`)
SELECT
  'IRSA – Institut Royal pour Sourds et Aveugles',
  'irsa',
  NOW()
WHERE NOT EXISTS (
  SELECT 1
  FROM organization o
  WHERE o.slug = 'irsa'
);



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


-- LINK ORGANIZATION and CONTACT POINT
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


-- SCHOOLS
-- =========================================================


INSERT INTO school (slug, label, enabled_at)
SELECT v.slug, v.label, v.enabled_at
FROM (
  SELECT
    'secondaire-t1-t6-t7' AS slug,
    'École secondaire spécialisée (T1–T6–T7)' AS label,
    NOW() AS enabled_at
  UNION ALL SELECT
    'fondamentale-t2-t6-t8',
    'École fondamentale spécialisée (T2–T6–T8)',
    NOW()
  UNION ALL SELECT
    'fondamentale-t7',
    'École fondamentale spécialisée – déficience auditive',
    NOW()
) v
WHERE NOT EXISTS (
  SELECT 1
  FROM school s
  WHERE s.slug = v.slug
);


INSERT INTO `page` (`id`, `slug`, `label`, `content`, `created_at`, `updated_at`) VALUES (NULL, 'irsa-fondation-pro', 'La Fondation PRO-IRSA', '<p>L’IRSA bénéficie depuis 2006 de l’aide de la Fondation PRO-IRSA, fondation d’utilité publique dont la mission est de soutenir les projets éducatifs et d’infrastructure en faveur des personnes déficientes sensorielles, en priorité par l’aménagement des infrastructures destinées à leur hébergement et à leur formation et par l’installation d’équipements spécifiques et adaptés à l’évolution des handicaps.</p>', '2025-12-14 21:05:11', '2025-12-14 22:07:07');

COMMIT;