CREATE TABLE `page` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `slug` varchar(150) NOT NULL UNIQUE,
  `label` varchar(150) NOT NULL,
  `content` longtext DEFAULT NULL,
  `created_at` datetime DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `page` (`id`, `slug`, `label`, `content`, `created_at`, `updated_at`) VALUES
(1, 'irsa-intro', 'L\'IRSA : une histoire qui dure !', '<p>Depuis 1835, l\'IRSA accompagne enfants, jeunes et adultes atteints de déficience auditive, visuelle ou multiple.</p>\r\n<p>Situé à Uccle, l\'institut offre un accompagnement global : scolarité, soins, hébergement, activités éducatives, guidance familiale, etc.</p>\r\n<p>Chaque personne est accueillie avec une attention particulière à ses besoins, son rythme et son projet de vie.</p>\r\n', '2025-12-14 21:01:05', '2025-12-14 21:04:31'),
(2, 'page-irsa-ecole', 'Les écoles de l\'IRSA', '<p>L\'IRSA propose un parcours scolaire complet pour les enfants et adolescents atteints de déficience auditive, visuelle ou avec troubles associés.</p>\r\n<p>Chaque établissement est adapté à un type de public spécifique, avec un accompagnement pédagogique et thérapeutique individualisé.</p>', '2025-12-14 21:01:05', '2025-12-14 21:01:05'),
(3, 'irsa-oa', 'Notre Organisme d\'Administration', '<p>L\'IRSA est administré par un Organisme d\'Administration (OA), composé de femmes et d\'hommes issus du monde associatif,\r\nprofessionnel, social et éducatif.</p>\r\n<p>Ces membres bénévoles assurent la gestion stratégique, éthique et financière de l\'institution.\r\nIls veillent à la continuité des missions, à la qualité de l\'accompagnement et au respect des valeurs fondamentales de l\'IRSA.</p>', '2025-12-14 21:05:11', '2025-12-14 22:07:07');
