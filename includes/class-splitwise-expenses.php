<?php
/**
 * Handles expense operations.
 */
class Splitwise_Expenses {

    /**
     * Add a new expense.
     *
     * @param array $data {
     *   @type string $group_id       Optional group identifier.
     *   @type string $description    Expense description.
     *   @type float  $amount         Total expense amount.
     *   @type string $date           Expense date (Y-m-d).
     *   @type array  $splits         Array of ['user_id' => int, 'share_amount' => float, 'paid_amount' => float].
     * }
     * @return array{success: bool, message: string, expense_id?: int}
     */
    public static function add_expense( $data ) {
        global $wpdb;

        // ====================== Validation ======================
        if ( empty( $data['description'] ) ) {
            return [
                'success' => false,
                'message' => __( 'Please enter a description.', 'splitwise-wp' ),
            ];
        }

        if ( ! isset( $data['amount'] ) || ! is_numeric( $data['amount'] ) || floatval( $data['amount'] ) <= 0 ) {
            return [
                'success' => false,
                'message' => __( 'Please enter a valid positive amount.', 'splitwise-wp' ),
            ];
        }

        if ( empty( $data['splits'] ) || ! is_array( $data['splits'] ) || count( $data['splits'] ) === 0 ) {
            return [
                'success' => false,
                'message' => __( 'Please provide at least one split entry.', 'splitwise-wp' ),
            ];
        }

        // Date validation
        $date = ! empty( $data['date'] ) ? sanitize_text_field( $data['date'] ) : current_time( 'Y-m-d' );
        if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
            return [
                'success' => false,
                'message' => __( 'Please enter a valid date in YYYY-MM-DD format.', 'splitwise-wp' ),
            ];
        }

        // ====================== Insert Expense ======================
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
            return [
                'success' => false,
                'message' => __( 'Failed to save expense. Please try again.', 'splitwise-wp' ),
            ];
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
     * Add splits for an expense.
     */
    private static function add_expense_splits( $expense_id, $splits ) {
        global $wpdb;

        $splits_table = $wpdb->prefix . 'splitwise_expense_splits';

        foreach ( $splits as $split ) {
            if ( empty( $split['user_id'] ) ) {
                continue;
            }

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
     * Get expenses for current user or group.
     */
    public static function get_expenses( $args = array() ) {
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

        $where_sql = implode( ' AND ', $where );

        // Correct & Safe Query Preparation
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
            $full_params
        );

        return $wpdb->get_results( $query );
    }
}