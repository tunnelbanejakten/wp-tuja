ALTER TABLE message
  ADD COLUMN date_imported DATETIME DEFAULT current_timestamp
  AFTER source_message_id,
  ADD COLUMN date_received DATETIME
  AFTER source_message_id;