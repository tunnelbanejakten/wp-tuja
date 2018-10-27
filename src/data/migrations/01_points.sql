CREATE TABLE form_question_points (
  form_question_id INTEGER NOT NULL,
  team_id          INTEGER NOT NULL,
  # The created value is necessary in order to find if the override is applicable. If the team
  # has submitted a new response after this override was set then the override is probably no
  # longer correct since the team has changed their answer.
  created          DATETIME DEFAULT current_timestamp,
  points           INTEGER,
  CONSTRAINT pk_form_question_points PRIMARY KEY (form_question_id, team_id),
  CONSTRAINT fk_form_question_points_question FOREIGN KEY (form_question_id) REFERENCES form_question (id)
    ON DELETE RESTRICT,
  CONSTRAINT fk_form_question_points_team FOREIGN KEY (team_id) REFERENCES team (id)
    ON DELETE CASCADE
)
  ENGINE = INNODB;

ALTER TABLE form_question_response
  ADD COLUMN created DATETIME DEFAULT current_timestamp
  AFTER team_id,
# is_reviewed will be set to true when the answer has been shown once on the
# "response review page". The user who reviewed the response might have overridden
# the points from the "score calculator" but otherwise is_reviewed=true means that
# the response has been reviewed and that the "score calculator" has correctly
# determined the number of points for this reponse.
  ADD COLUMN is_reviewed BOOLEAN NOT NULL DEFAULT FALSE
  AFTER answer,
  DROP COLUMN points;

