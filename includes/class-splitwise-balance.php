<?php
/**
 * Handles balance calculations for Splitwise WP Plugin.
 */
class Splitwise_Balance {

    /**
     * Calculate overall balance summary for a user.
     * FIX: owes now correctly shows only what user owes on OTHER people's expenses.
     *
     * @param int|null $user_id
     * @return array{ owes: float, owed: float, net: float }
     */
    public static function get_user_balances( $user_id = null ) {
        if ( ! $user_id ) {
            $user_id = get_current_user_id();
        }

        global $wpdb;

        $expenses_table = $wpdb->prefix . 'splitwise_expenses';
        $splits_table   = $wpdb->prefix . 'splitwise_expense_splits';

        // Total amount THIS user paid as the expense payer
        $total_paid = (float) $wpdb->get_var( $wpdb->prepare(
            "SELECT COALESCE(SUM(e.amount), 0)
             FROM $expenses_table e
             WHERE e.user_id = %d",
            $user_id
        ) );

        // FIX: owes = only what user owes on expenses OTHERS paid
        $total_owed_to_others = (float) $wpdb->get_var( $wpdb->prepare(
            "SELECT COALESCE(SUM(s.share_amount), 0)
             FROM $splits_table s
             JOIN $expenses_table e ON s.expense_id = e.id
             WHERE s.user_id = %d AND e.user_id != %d",
            $user_id, $user_id
        ) );

        // Total others owe this user
        $total_owed_by_others = (float) $wpdb->get_var( $wpdb->prepare(
            "SELECT COALESCE(SUM(s.share_amount), 0)
             FROM $splits_table s
             JOIN $expenses_table e ON s.expense_id = e.id
             WHERE e.user_id = %d AND s.user_id != %d",
            $user_id, $user_id
        ) );

        return [
            'owes' => max( 0, $total_owed_to_others ),
            'owed' => max( 0, $total_owed_by_others ),
            'net'  => $total_owed_by_others - $total_owed_to_others,
        ];
    }

    /**
     * Get detailed balance breakdown per person.
     *
     * @param int|null $user_id
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
             WHERE (s.user_id = %d OR e.user_id = %d)
               AND (s.user_id != e.user_id)
             GROUP BY other_user",
            $user_id, $user_id, $user_id, $user_id, $user_id
        );

        $results  = $wpdb->get_results( $query );
        $balances = [];

        foreach ( $results as $row ) {
            $balances[ $row->other_user ] = [
                'owes' => floatval( $row->i_owe ),
                'owed' => floatval( $row->i_am_owed ),
            ];
        }

        return $balances;
    }
}