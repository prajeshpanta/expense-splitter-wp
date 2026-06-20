<?php
/**
 * Fired during plugin deactivation.
 */
class Splitwise_Deactivator {

    /**
     * Runs on plugin deactivation.
     * Intentionally does NOT delete any data —
     * data removal only happens on uninstall (see uninstall.php).
     */
    public static function deactivate() {
        // Nothing to do here for now.
        // Reserved for future cleanup that should NOT delete user data
        // (e.g. clearing scheduled cron events, transients, etc.)
    }
}