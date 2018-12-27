CREATE TABLE team_category (
  id             INTEGER          AUTO_INCREMENT PRIMARY KEY,
  competition_id INTEGER NOT NULL,
  is_crew        BOOLEAN NOT NULL DEFAULT FALSE,
  name           VARCHAR(20),
  CONSTRAINT fk_teamcategory_competition FOREIGN KEY (competition_id) REFERENCES competition (id)
    ON DELETE CASCADE
)
  ENGINE = INNODB;

ALTER TABLE team
  ADD COLUMN category_id INTEGER
  AFTER type,
  ADD CONSTRAINT fk_team_category FOREIGN KEY (category_id) REFERENCES team_category (id)
  ON DELETE RESTRICT;