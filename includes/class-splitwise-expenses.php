<?php
/**Handles expense operations.

This class handles all the core operations related to expenses — specifically:
 *Adding a new expense
 *Splitting that expense among users
 *Retrieving expenses with their splits

This is one of the most important files in the plugin. It contains the actual 
logic for:
-Recording who paid what
-Dividing the expense among participants (the "split")
-Fetching expense history

Without this class, users wouldn't be able to add or view expenses — which is 
the heart of any Splitwise application.
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
//here all method are made static so that it can be called without
//  creating its object.

//$data accepts an array containing expense details (description, amount, date, 
// splits, etc.).
    public static function add_expense( $data ) {
        global $wpdb;

// ================================== Validation ==============================

    //perform input validation 
    //if the description is kept empty then it will show a standard error format
        if ( empty( $data['description'] ) ) {
            return [
                'success' => false,
                'message' => __( 'Please enter a description.', 'splitwise-wp' ),
            ];
        }
/**this condition ensures that amount is set, must be numeric and must be greater
than zero*/
        if ( ! isset( $data['amount'] ) || ! is_numeric( $data['amount'] ) || floatval( $data['amount'] ) <= 0 ) {
            return [
                'success' => false,
                'message' => __( 'Please enter a valid positive amount.', 'splitwise-wp' ),
            ];
        }
/**this condition ensures that there must be at least one participant for split 
the expense*/
        if ( empty( $data['splits'] ) || ! is_array( $data['splits'] ) || count( $data['splits'] ) === 0 ) {
            return [
                'success' => false,
                'message' => __( 'Please provide at least one split entry.', 'splitwise-wp' ),
            ];
        }

        // Date validation
        /**this condition also checks that if the date is not provided then 
        it automatically put the current date.*/
        $date = ! empty( $data['date'] ) ? sanitize_text_field( $data['date'] ) : current_time( 'Y-m-d' );
        if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
            return [
                'success' => false,
                'message' => __( 'Please enter a valid date in YYYY-MM-DD format.', 'splitwise-wp' ),
            ];
        }

        // ====================== Insert Expense ======================
        $expense_table = $wpdb->prefix . 'splitwise_expenses';

    //$wpdb->insert() → Securely inserts data into the database.
    //automatically assigns the expense to currently login user.
    //used format specifiers like ('%d', '%s', '%s', '%f', '%s') to prevent SQL injection attack
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

        //it returns success response with the new expense ID
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
    /**global $wpdb makes the WordPress database object available inside the function 
       so we can interact with the database. */
        global $wpdb;

        $splits_table = $wpdb->prefix . 'splitwise_expense_splits';

        foreach ( $splits as $split ) {
        
        //If the current split entry does not have a valid user_id, skip it.
            if ( empty( $split['user_id'] ) ) { //to prevent incomlpete data to enter 
                                                //into the database
                continue;
            }

/**To insert new row in the database for new expenses in safe way 
wordpress use $wpdb->insert(); to automatically escapes all the values before 
inserting them to prevent sql injection and $wpdb do this using format specifier like 
 '%d', '%d', '%f', '%f', so wordpress use it to properly format and escape 
 value according to it's types*/

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

/**this method is responsible for fetching expenses and its slpits from the database */
    public static function get_expenses( $args = array() ) {
        global $wpdb;
//by default, it will show the expense of currently logged in user.
        $defaults = [
            'user_id'  => get_current_user_id(),
            'group_id' => null,
            'limit'    => 20, //it will show 20 expenses by default
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

        // FIX Bug #7: Use spread operator (...) instead of passing array
        // to $wpdb->prepare(). Passing as a single array is deprecated
        // since WordPress 6.2.
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
     * Get expenses where the given user appears as a split participant
     * but did NOT pay (i.e. expenses they owe a share of).
     *
     * @param int $user_id
     * @param int $limit
     * @return array Rows with: id, description, date, amount (total expense amount),
     *               paid_by (user_id of the payer), share_amount (this user's share).
     */
    public static function get_owed_expenses( $user_id, $limit = 20 ) {
        global $wpdb;

        $expense_table = $wpdb->prefix . 'splitwise_expenses';
        $splits_table  = $wpdb->prefix . 'splitwise_expense_splits';

        $query = $wpdb->prepare(
            "SELECT e.id, e.description, e.date, e.amount,
                    e.user_id AS paid_by, s.share_amount
             FROM $splits_table s
             INNER JOIN $expense_table e ON s.expense_id = e.id
             WHERE s.user_id = %d AND e.user_id != %d
             ORDER BY e.date DESC, e.id DESC
             LIMIT %d",
            $user_id,
            $user_id,
            intval( $limit )
        );

        return $wpdb->get_results( $query );
    }

    /**
     * Get the split participants for a single expense.
     *
     * @param int $expense_id
     * @return array Rows with: user_id, share_amount, paid_amount.
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
     * Count total expenses for the current user.
     *
     * @param int|null $user_id Defaults to current logged-in user.
     * @return int Total number of expenses.
     */
    public static function count_expenses( $user_id = null ) {
        global $wpdb;

        if ( ! $user_id ) {
            $user_id = get_current_user_id();
        }

        $expense_table = $wpdb->prefix . 'splitwise_expenses';

        return (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM $expense_table WHERE user_id = %d",
            $user_id
        ) );
    }
}