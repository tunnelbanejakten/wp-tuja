<?php
/**
 * Alternative solution for database migration: https://developer.wordpress.org/reference/functions/dbdelta/
 */
function tuja_db_migrate()
{
    global $wpdb;
    $executed_scripts = get_option("tuja_db_migrations", []);

    $existing_scripts = glob(__DIR__ . '/data/migrations/*.sql');
    sort($existing_scripts);
    error_log("Database migrate. STARTING.");
    foreach (array_keys($existing_scripts) as $key) {
        if (!isset($executed_scripts[$key])) {
            $failed = false;
            $migration_script_path = $existing_scripts[$key];
            error_log("Database migrate. Executing script " . $migration_script_path);
            $full_script = file_get_contents($migration_script_path);
            foreach (explode(';', $full_script) as $command) {
                $affected_rows = $wpdb->query($command);
                error_log("Database migrate. Executing command " . $command);
                if ($affected_rows === false) {
                    error_log("Database migrate. FAILED executing command.");
                    $failed = true;
                }
            }
            if (!$failed) {
                $executed_scripts[] = $migration_script_path;
                update_option("tuja_db_migrations", $executed_scripts);
            }
        }
    }
    error_log("Database migrate. DONE.");
}