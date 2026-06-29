<?php
/**
 * Handles balance calculations for Splitwise WP Plugin.
 * 
 * Calculates what a user owes, is owed, and detailed balances with others.
 */
class Splitwise_Balance {

    /**
     * Calculate overall balance summary for a user.
     *
     * @param int|null $user_id Defaults to current logged-in user.
     * @return array{owes: float, owed: float, net: float}
     */

    //it return the users overall balance
    //here owes, owed and net are return as float
    public static function get_user_balances( $user_id = null ) {
        if ( ! $user_id ) {
            $user_id = get_current_user_id(); /**this function uses currently logged-in
             user, automatically if no user-id id found. */
        }

//since we are under a class method so without writing global php wouldn't recongnize 
// $wpdb variable, so we can get error.

        global $wpdb;//wordpress built-in database object.

        /**Using $wpdb->prefix makes our plugin work on any wordpress installation
         * it will adapt on any wordpress site accordingly
         */
        $expenses_table = $wpdb->prefix . 'splitwise_expenses';
        $splits_table   = $wpdb->prefix . 'splitwise_expense_splits';

        // Total user has paid (as payer of expenses)
        //it calculates the total amount the user has paid
        //get_var() is used for the getting only one single value here in this
        //  case we will get total paid amount.
        $total_paid = (float) $wpdb->get_var( $wpdb->prepare(
            "SELECT COALESCE(SUM(e.amount), 0) 
             FROM $expenses_table e 
             WHERE e.user_id = %d",
            $user_id
        ) );

        // Total user is supposed to pay (their share)
        $total_share = (float) $wpdb->get_var( $wpdb->prepare(
            "SELECT COALESCE(SUM(s.share_amount), 0) 
             FROM $splits_table s 
             WHERE s.user_id = %d",
            $user_id
        ) );

        // Considering paid_amount for more accuracy (if implemented)
        $total_paid_by_user = (float) $wpdb->get_var( $wpdb->prepare(
            "SELECT COALESCE(SUM(s.paid_amount), 0) 
             FROM $splits_table s 
             WHERE s.user_id = %d",
            $user_id
        ) );

        return [
            'owes' => max( 0, $total_share - $total_paid_by_user ),// What user still needs to pay
            'owed' => max( 0, $total_paid - $total_share ),        // What others owe user
            'net'  => $total_paid - $total_share,                  // Positive = others owe you
        ];
    }

    /**
     * Get detailed balance breakdown with each person.
     *
     * @param int|null $user_id Defaults to current logged-in user.
     * @return array Keyed by other_user_id => ['owes' => float, 'owed' => float]
     */
    public static function get_detailed_balances( $user_id = null ) {
        if ( ! $user_id ) {
            $user_id = get_current_user_id();
        }

        global $wpdb;

        $splits_table   = $wpdb->prefix . 'splitwise_expense_splits';
        $expenses_table = $wpdb->prefix . 'splitwise_expenses';

        $query = $wpdb->prepare(
            "SELECT 
                CASE 
                    WHEN e.user_id = %d THEN s.user_id 
                    ELSE e.user_id 
                END as other_user,
                SUM(CASE WHEN e.user_id = %d THEN s.share_amount ELSE 0 END) as i_am_owed,
                SUM(CASE WHEN e.user_id != %d THEN s.share_amount ELSE 0 END) as i_owe
             FROM $splits_table s
             INNER JOIN $expenses_table e ON s.expense_id = e.id
             WHERE s.user_id = %d OR e.user_id = %d
               AND (s.user_id != e.user_id)
             GROUP BY other_user",
            $user_id, $user_id, $user_id, $user_id, $user_id
        );

        $results = $wpdb->get_results( $query );

        $balances = [];

        foreach ( $results as $row ) {
            $balances[ $row->other_user ] = [
                'owes' => floatval( $row->i_owe ),   // Amount I owe them
                'owed' => floatval( $row->i_am_owed ), // Amount they owe me
            ];
        }

        return $balances;
    }
}