-- =========================================================
-- TAG (generic, semantic, i18n-safe)
-- =========================================================

CREATE TABLE tag (
  id INT AUTO_INCREMENT PRIMARY KEY,

  parent_id INT DEFAULT NULL,
  kind VARCHAR(32) DEFAULT NULL,

  slug VARCHAR(64) NOT NULL UNIQUE,
  sort_order SMALLINT DEFAULT NULL,

  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_general_ci;

ALTER TABLE tag
  ADD CONSTRAINT fk_tag_parent
    FOREIGN KEY (parent_id)
    REFERENCES tag(id)
    ON DELETE SET NULL
    ON UPDATE CASCADE;

ALTER TABLE tag
  ADD KEY idx_tag_kind (kind),
  ADD KEY idx_tag_parent (parent_id);


