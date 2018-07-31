CREATE TABLE competition (
  id                   INTEGER AUTO_INCREMENT PRIMARY KEY,
  random_id            VARCHAR(20) NOT NULL UNIQUE,
  name                 VARCHAR(50) NOT NULL,
  payment_instructions VARCHAR(10000),
  accept_signup_from   DATETIME,
  accept_signup_until  DATETIME
)
  ENGINE = INNODB;

# CREATE TABLE role (
#   id                      INTEGER AUTO_INCREMENT PRIMARY KEY,
#   competition_id          INTEGER     NOT NULL,
#   name                    VARCHAR(50) NOT NULL,
#   signup_fee              INTEGER,
#   assignable_to_team_type VARCHAR(20) NOT NULL CHECK (assignable_to_team_type IN ('crew', 'participant')),
#   CONSTRAINT fk_role_competition FOREIGN KEY (competition_id) REFERENCES competition (id)
#     ON DELETE CASCADE
# )
#   ENGINE = INNODB;

CREATE TABLE team (
  id             INTEGER AUTO_INCREMENT PRIMARY KEY,
  random_id      VARCHAR(20)  NOT NULL UNIQUE,
  competition_id INTEGER      NOT NULL,
  name           VARCHAR(100) NOT NULL,
  type           VARCHAR(20)  NOT NULL,
  CONSTRAINT UNIQUE idx_team_token (random_id),
  CONSTRAINT UNIQUE idx_team_name (competition_id, name),
  CONSTRAINT fk_team_competition FOREIGN KEY (competition_id) REFERENCES competition (id)
    ON DELETE CASCADE
)
  ENGINE = INNODB;

CREATE TABLE person (
  id             INTEGER               AUTO_INCREMENT PRIMARY KEY,
  random_id      VARCHAR(20)  NOT NULL,
  name           VARCHAR(100) NOT NULL,
  team_id        INTEGER      NOT NULL,
  #   role_id        INTEGER      NOT NULL,
  phone          VARCHAR(50),
  phone_verified BOOLEAN      NOT NULL DEFAULT FALSE,
  email          VARCHAR(50),
  email_verified BOOLEAN      NOT NULL DEFAULT FALSE,
  CONSTRAINT UNIQUE idx_person_token (random_id),
  CONSTRAINT fk_person_team FOREIGN KEY (team_id) REFERENCES team (id)
    ON DELETE CASCADE #,
  #   CONSTRAINT fk_person_role FOREIGN KEY (role_id) REFERENCES role (id)
  #     ON DELETE CASCADE
)
  ENGINE = INNODB;

CREATE TABLE form (
  id                                INTEGER AUTO_INCREMENT PRIMARY KEY,
  competition_id                    INTEGER      NOT NULL,
  name                              VARCHAR(100) NOT NULL,
  allow_multiple_responses_per_team BOOLEAN      NOT NULL,
  accept_responses_from             DATETIME,
  accept_responses_until            DATETIME,
  CONSTRAINT fk_form_competition FOREIGN KEY (competition_id) REFERENCES competition (id)
    ON DELETE CASCADE
)
  ENGINE = INNODB;

CREATE TABLE form_question (
  id         INTEGER AUTO_INCREMENT PRIMARY KEY,
  form_id    INTEGER      NOT NULL,
  type       VARCHAR(10)  NOT NULL CHECK (type IN ('text', 'number', 'header', 'pick_one', 'pick_multiple')),
  answer     VARCHAR(500),
  text       VARCHAR(500) NOT NULL,
  sort_order SMALLINT,
  text_hint  VARCHAR(500),
  CONSTRAINT fk_question_form FOREIGN KEY (form_id) REFERENCES form (id)
    ON DELETE CASCADE
)
  ENGINE = INNODB;

-- CREATE TABLE form_response (
--   id       INTEGER AUTO_INCREMENT PRIMARY KEY,
--   form_id  INTEGER NOT NULL REFERENCES form (id) ON DELETE CASCADE,
--   team_id INTEGER NOT NULL REFERENCES team (id) ON DELETE CASCADE
-- );

CREATE TABLE form_question_response (
  id               INTEGER AUTO_INCREMENT PRIMARY KEY,
  form_question_id INTEGER      NOT NULL,
  team_id          INTEGER      NOT NULL,
  answer           VARCHAR(500) NOT NULL,
  points           INTEGER,
  CONSTRAINT fk_form_question_response_question FOREIGN KEY (form_question_id) REFERENCES form_question (id)
    ON DELETE RESTRICT,
  CONSTRAINT fk_form_question_response_team FOREIGN KEY (team_id) REFERENCES team (id)
    ON DELETE CASCADE
)
  ENGINE = INNODB;

CREATE TABLE message (
  id                INTEGER AUTO_INCREMENT PRIMARY KEY,
  form_question_id  INTEGER,
  team_id           INTEGER,
  text              VARCHAR(1000),
  image             VARCHAR(1000),
  source            VARCHAR(10),
  source_message_id VARCHAR(100),
  CONSTRAINT fk_form_question_response_question FOREIGN KEY (form_question_id) REFERENCES form_question (id)
    ON DELETE RESTRICT,
  CONSTRAINT fk_form_question_response_team FOREIGN KEY (team_id) REFERENCES team (id)
    ON DELETE CASCADE
)
  ENGINE = INNODB;