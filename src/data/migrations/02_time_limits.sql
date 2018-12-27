#
# FIX competition
#

ALTER TABLE competition
  ADD COLUMN create_group_start INTEGER,
  ADD COLUMN create_group_end INTEGER,
  ADD COLUMN edit_group_start INTEGER,
  ADD COLUMN edit_group_end INTEGER;

ALTER TABLE competition
  DROP COLUMN accept_signup_from,
  DROP COLUMN accept_signup_until;

#
# FIX form
#

ALTER TABLE form
  ADD COLUMN submit_response_start INTEGER,
  ADD COLUMN submit_response_end INTEGER;

ALTER TABLE form
  DROP COLUMN accept_responses_from,
  DROP COLUMN accept_responses_until;

#
# FIX form_question_response
#

ALTER TABLE form_question_response
  ADD COLUMN created_at INTEGER;

UPDATE form_question_response
SET
  created_at = unix_timestamp(created);

ALTER TABLE form_question_response
  DROP COLUMN created;

#
# FIX form_question_points
#

ALTER TABLE form_question_points
  ADD COLUMN created_at INTEGER;

UPDATE form_question_points
SET
  created_at = unix_timestamp(created);

ALTER TABLE form_question_points
  DROP COLUMN created;
