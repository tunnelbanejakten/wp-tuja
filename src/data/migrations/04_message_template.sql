CREATE TABLE message_template (
  id             INTEGER AUTO_INCREMENT PRIMARY KEY,
  competition_id INTEGER NOT NULL,
  name           VARCHAR(50),
  subject        VARCHAR(500),
  body           VARCHAR(50000),
  CONSTRAINT fk_messagetemplate_competition FOREIGN KEY (competition_id) REFERENCES competition (id)
    ON DELETE CASCADE
)
  ENGINE = INNODB;

ALTER TABLE competition
  ADD COLUMN message_template_new_team_admin INTEGER,
  ADD CONSTRAINT fk_competition_messagetemplatenewteamadmin FOREIGN KEY (message_template_new_team_admin) REFERENCES message_template (id)
  ON DELETE RESTRICT;

ALTER TABLE competition
  ADD COLUMN message_template_new_team_reporter INTEGER,
  ADD CONSTRAINT fk_competition_messagetemplatenewteamreporter FOREIGN KEY (message_template_new_team_reporter) REFERENCES message_template (id)
  ON DELETE RESTRICT;

ALTER TABLE competition
  ADD COLUMN message_template_new_crew_member INTEGER,
  ADD CONSTRAINT fk_competition_messagetemplatenewcrewmember FOREIGN KEY (message_template_new_crew_member) REFERENCES message_template (id)
  ON DELETE RESTRICT;

ALTER TABLE competition
  ADD COLUMN message_template_new_noncrew_member INTEGER,
  ADD CONSTRAINT fk_competition_messagetemplatenewnoncrewmember FOREIGN KEY (message_template_new_noncrew_member) REFERENCES message_template (id)
  ON DELETE RESTRICT;
