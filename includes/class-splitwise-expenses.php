<?php
/**
 * Handles expense operations for Splitwise WP.
 */
class Splitwise_Expenses {

    /**
     * Add a new expense with splits.
     *
     * @param array $data {
     *   @type string $description
     *   @type float  $amount
     *   @type string $date  (Y-m-d)
     *   @type array  $splits  [ ['user_id'=>int, 'share_amount'=>float, 'paid_amount'=>float] ]
     *   @type string $group_id (optional)
     * }
     * @return array{ success: bool, message: string, expense_id?: int }
     */
    public static function add_expense( $data ) {
        global $wpdb;

        // ── Validation ──────────────────────────────────────────
        if ( empty( $data['description'] ) ) {
            return [ 'success' => false, 'message' => __( 'Please enter a description.', 'splitwise-wp' ) ];
        }

        if ( ! isset( $data['amount'] ) || ! is_numeric( $data['amount'] ) || floatval( $data['amount'] ) <= 0 ) {
            return [ 'success' => false, 'message' => __( 'Please enter a valid positive amount.', 'splitwise-wp' ) ];
        }

        if ( empty( $data['splits'] ) || ! is_array( $data['splits'] ) ) {
            return [ 'success' => false, 'message' => __( 'Please provide at least one split entry.', 'splitwise-wp' ) ];
        }

        $date = ! empty( $data['date'] ) ? sanitize_text_field( $data['date'] ) : current_time( 'Y-m-d' );
        if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
            return [ 'success' => false, 'message' => __( 'Please enter a valid date (YYYY-MM-DD).', 'splitwise-wp' ) ];
        }

        // ── Insert Expense ───────────────────────────────────────
        $expense_table = $wpdb->prefix . 'splitwise_expenses';

        $inserted = $wpdb->insert(
            $expense_table,
            [
                'user_id'     => get_current_user_id(),
                'group_id'    => isset( $data['group_id'] ) ? sanitize_text_field( $data['group_id'] ) : null,
                'description' => sanitize_text_field( $data['description'] ),
                'amount'      => floatval( $data['amount'] ),
                'date'        => $date,
            ],
            [ '%d', '%s', '%s', '%f', '%s' ]
        );

        if ( ! $inserted ) {
            return [ 'success' => false, 'message' => __( 'Failed to save expense. Please try again.', 'splitwise-wp' ) ];
        }

        $expense_id = $wpdb->insert_id;
        self::add_expense_splits( $expense_id, $data['splits'] );

        return [
            'success'    => true,
            'message'    => __( 'Expense added successfully.', 'splitwise-wp' ),
            'expense_id' => $expense_id,
        ];
    }

    /**
     * Insert split rows for an expense.
     */
    private static function add_expense_splits( $expense_id, $splits ) {
        global $wpdb;
        $splits_table = $wpdb->prefix . 'splitwise_expense_splits';

        foreach ( $splits as $split ) {
            if ( empty( $split['user_id'] ) ) continue;

            $wpdb->insert(
                $splits_table,
                [
                    'expense_id'   => $expense_id,
                    'user_id'      => intval( $split['user_id'] ),
                    'share_amount' => floatval( $split['share_amount'] ?? 0 ),
                    'paid_amount'  => floatval( $split['paid_amount'] ?? 0 ),
                ],
                [ '%d', '%d', '%f', '%f' ]
            );
        }
    }

    /**
     * Get expenses paid BY a specific user.
     *
     * @param array $args { user_id, group_id, limit }
     * @return array
     */
    public static function get_expenses( $args = [] ) {
        global $wpdb;

        $defaults = [
            'user_id'  => get_current_user_id(),
            'group_id' => null,
            'limit'    => 20,
        ];
        $args = wp_parse_args( $args, $defaults );

        $expense_table = $wpdb->prefix . 'splitwise_expenses';
        $splits_table  = $wpdb->prefix . 'splitwise_expense_splits';

        $where  = [ '1=1' ];
        $params = [];

        if ( $args['user_id'] ) {
            $where[]  = 'e.user_id = %d';
            $params[] = $args['user_id'];
        }

        if ( $args['group_id'] ) {
            $where[]  = 'e.group_id = %s';
            $params[] = $args['group_id'];
        }

        $where_sql   = implode( ' AND ', $where );
        $full_params = array_merge( $params, [ intval( $args['limit'] ) ] );

        $query = $wpdb->prepare(
            "SELECT e.*,
                    GROUP_CONCAT(CONCAT(s.user_id, ':', s.share_amount, ':', s.paid_amount)) AS splits
             FROM $expense_table e
             LEFT JOIN $splits_table s ON e.id = s.expense_id
             WHERE $where_sql
             GROUP BY e.id
             ORDER BY e.date DESC, e.id DESC
             LIMIT %d",
            ...$full_params
        );

        return $wpdb->get_results( $query );
    }

    /**
     * FIX: Get ALL expenses involving a user — both paid by them AND split with them.
     * Used for Recent Activity on the dashboard.
     *
     * @param int $user_id
     * @param int $limit
     * @return array
     */
    public static function get_all_expenses_for_user( $user_id, $limit = 10 ) {
        global $wpdb;

        $expense_table = $wpdb->prefix . 'splitwise_expenses';
        $splits_table  = $wpdb->prefix . 'splitwise_expense_splits';

        return $wpdb->get_results( $wpdb->prepare(
            "SELECT DISTINCT e.*
             FROM $expense_table e
             LEFT JOIN $splits_table s ON s.expense_id = e.id
             WHERE e.user_id = %d OR s.user_id = %d
             ORDER BY e.date DESC, e.created_at DESC
             LIMIT %d",
            $user_id, $user_id, intval( $limit )
        ) );
    }

    /**
     * Get expenses where user is a split participant but did NOT pay.
     *
     * @param int $user_id
     * @param int $limit
     * @return array
     */
    public static function get_owed_expenses( $user_id, $limit = 20 ) {
        global $wpdb;

        $expense_table = $wpdb->prefix . 'splitwise_expenses';
        $splits_table  = $wpdb->prefix . 'splitwise_expense_splits';

        return $wpdb->get_results( $wpdb->prepare(
            "SELECT e.id, e.description, e.date, e.amount,
                    e.user_id AS paid_by, s.share_amount
             FROM $splits_table s
             INNER JOIN $expense_table e ON s.expense_id = e.id
             WHERE s.user_id = %d AND e.user_id != %d
             ORDER BY e.date DESC, e.id DESC
             LIMIT %d",
            $user_id, $user_id, intval( $limit )
        ) );
    }

    /**
     * Get split participants for a single expense.
     *
     * @param int $expense_id
     * @return array
     */
    public static function get_split_members( $expense_id ) {
        global $wpdb;
        $splits_table = $wpdb->prefix . 'splitwise_expense_splits';

        return $wpdb->get_results( $wpdb->prepare(
            "SELECT user_id, share_amount, paid_amount FROM $splits_table WHERE expense_id = %d",
            intval( $expense_id )
        ) );
    }

    /**
     * Count total expenses paid by a user.
     *
     * @param int|null $user_id
     * @return int
     */
    public static function count_expenses( $user_id = null ) {
        global $wpdb;

        if ( ! $user_id ) $user_id = get_current_user_id();

        $expense_table = $wpdb->prefix . 'splitwise_expenses';

        return (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM $expense_table WHERE user_id = %d",
            $user_id
        ) );
    }
}