<?php
/**
 * Fired during plugin activation.
 */
class Splitwise_Activator {

    /**
     * Runs on plugin activation.
     * Creates required database tables.
     */
    public static function activate() {

        // Create database tables
        Splitwise_DB::create_tables();
    }
}