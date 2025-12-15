
-- ORGANIZATION
-- =========================================================
CREATE TABLE `organization` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,

  `label` VARCHAR(150) NOT NULL,
  `slug` VARCHAR(160) NOT NULL UNIQUE,

  `logo` VARCHAR(255) DEFAULT NULL,
  `url` VARCHAR(255) DEFAULT NULL,
  `content` TEXT DEFAULT NULL,

  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `enabled_at` DATETIME DEFAULT NULL,
  `revoked_at` DATETIME DEFAULT NULL
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_general_ci;

CREATE TABLE `organization_type` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `slug` VARCHAR(20) NOT NULL
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_general_ci;

CREATE TABLE `organization_organization_type` (
  `organization_id` INT NOT NULL,
  `organization_type_id` INT NOT NULL,

  PRIMARY KEY (`organization_id`, `organization_type_id`),
  KEY `idx_oot_type` (`organization_type_id`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_general_ci;



-- ORGANIZATION ↔ CONTACT POINT (N–N)
-- =========================================================
CREATE TABLE `organization_contact_point` (
  `organization_id` INT NOT NULL,
  `contact_point_id` INT NOT NULL,
  `sort_order` SMALLINT DEFAULT NULL,

  PRIMARY KEY (`organization_id`, `contact_point_id`),
  KEY `idx_ocp_contact_point` (`contact_point_id`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_general_ci;




CREATE TABLE school (
    id INT AUTO_INCREMENT PRIMARY KEY,

    slug VARCHAR(255) NOT NULL UNIQUE,
    label VARCHAR(255) NOT NULL,

    enabled_at DATETIME NULL,
    revoked_at DATETIME NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP
);


CREATE TABLE school_profile (
    school_id INT PRIMARY KEY,

    /* Page header */
    title VARCHAR(255) NOT NULL,
    intro TEXT,

    /* Target & scope */
    public_label VARCHAR(255),
    age_range VARCHAR(64),

    /* Core narrative */
    description TEXT,

    /* Structured informational sections */
    accompaniment TEXT,
    admission_criteria TEXT,
    modalities TEXT,

    /* Enumerations (one item per line) */
    team_overview TEXT,
    supports_overview TEXT,

    enabled_at DATETIME NULL,
    revoked_at DATETIME NULL,

    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (school_id)
        REFERENCES school(id)
        ON DELETE CASCADE
);

CREATE TABLE school_contact_point (
    `school_id` INT NOT NULL,
    `contact_point_id` INT NOT NULL,
    `sort_order` SMALLINT DEFAULT NULL,

    PRIMARY KEY (`school_id`, `contact_point_id`),

    FOREIGN KEY (`school_id`)
        REFERENCES `school`(`id`)
        ON DELETE CASCADE,

    FOREIGN KEY (`contact_point_id`)
        REFERENCES `contact_point`(`id`)
        ON DELETE CASCADE
);