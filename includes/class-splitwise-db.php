<?php
/**
 * Handles creation and management of custom database tables
 * required by the Splitwise plugin.
 */
class Splitwise_DB {

    /**
     * Creates the plugin's database tables.
     * Runs on plugin activation via Splitwise_Activator.
     */
    public static function create_tables() {

        global $wpdb; // WordPress's built-in database object

        $charset_collate = $wpdb->get_charset_collate();

        // ------------------------------------------------------------
        // Expenses table
        // ------------------------------------------------------------
        $expenses_table = $wpdb->prefix . 'splitwise_expenses';

        $sql_expenses = "CREATE TABLE $expenses_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            group_id varchar(255) DEFAULT NULL,
            description text NOT NULL,
            amount decimal(10,2) NOT NULL,
            date date NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY date (date)
        ) $charset_collate;";

        // ------------------------------------------------------------
        // Expense splits table
        // ------------------------------------------------------------
        $expense_splits_table = $wpdb->prefix . 'splitwise_expense_splits';

        $sql_expense_splits = "CREATE TABLE $expense_splits_table (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            expense_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            share_amount decimal(10,2) NOT NULL,
            paid_amount decimal(10,2) DEFAULT 0.00,
            PRIMARY KEY  (id),
            KEY expense_id (expense_id),
            KEY user_id (user_id)
        ) $charset_collate;";

        // dbDelta() is WordPress's safe table creation/update function.
        // It creates tables if they don't exist, or updates structure
        // if the SQL has changed — without deleting existing data.
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        dbDelta( $sql_expenses );
        dbDelta( $sql_expense_splits );
    }

    /**
     * Drops the plugin's database tables.
     * Should only be called on plugin UNINSTALL, never on deactivation —
     * deactivating a plugin should preserve user data.
     */
    public static function drop_tables() {

        global $wpdb;

        $expenses_table        = $wpdb->prefix . 'splitwise_expenses';
        $expense_splits_table  = $wpdb->prefix . 'splitwise_expense_splits';

        $wpdb->query( "DROP TABLE IF EXISTS $expense_splits_table" );
        $wpdb->query( "DROP TABLE IF EXISTS $expenses_table" );
    }
}