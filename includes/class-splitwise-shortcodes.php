<?php
/**
 * Registers and handles all frontend shortcodes:
 * [splitwise_dashboard]
 * [splitwise_add_expense]
 * [splitwise_balance]
 */

//responsible of connecting the core logic of the plugin to the frontend of the wordpress site

class Splitwise_Shortcodes {

    /**
     * Registers all shortcodes.
     * This function is called from the main plugin file during initialization.
     */
    public function init() {
        add_shortcode( 'splitwise_dashboard',   [ $this, 'dashboard_shortcode' ] );
        add_shortcode( 'splitwise_add_expense', [ $this, 'add_expense_shortcode' ] );
        add_shortcode( 'splitwise_balance',     [ $this, 'balance_shortcode' ] );
    }

    // ==================================================================
    // [splitwise_dashboard] - Shortcode
    // ==================================================================

    /**
     * Handles the [splitwise_dashboard] shortcode.
     * Displays a summary dashboard with user's balance and recent expenses.
     */

    /**What it does:
    Checks if user is logged in.
    Fetches user's balance summary.
    Fetches recent expenses.
    Passes data to the dashboard.php template for display.
 
    Use Case: Shows a nice overview homepage for users. */

    public function dashboard_shortcode( $atts ) {
        if ( ! is_user_logged_in() ) {
            return $this->login_required_notice();
        }

        $atts = shortcode_atts( [
            'recent_limit' => 5,
        ], $atts, 'splitwise_dashboard' );

        $balances = Splitwise_Balance::get_user_balances();
        $expenses = Splitwise_Expenses::get_expenses( [
            'limit' => intval( $atts['recent_limit'] ),
        ] );

        // FIX Bug #11: Pass $total_expenses so the "View All" link works
        $total_expenses = Splitwise_Expenses::count_expenses();

        return $this->render_template( 'dashboard', [
            'user_name'      => wp_get_current_user()->display_name,
            'balances'       => $balances,
            'expenses'       => $expenses,
            'total_expenses' => $total_expenses,
        ] );
    }

    // ==================================================================
    // [splitwise_add_expense] - Shortcode
    // ==================================================================

    /**
     * Handles the [splitwise_add_expense] shortcode.
     * Displays a form to add a new expense and processes the form submission.
     */

/**Features:
Displays a form to add a new expense.
Handles form submission (POST request).
Includes security using Nonce (wp_verify_nonce).
Validates input.
Prepares equal splits and calls Splitwise_Expenses::add_expense().
Shows success or error messages.
Loads list of other users for selection. */

    public function add_expense_shortcode( $atts ) {
        if ( ! is_user_logged_in() ) {
            return $this->login_required_notice();
        }

        $current_user_id = get_current_user_id();
        $error   = '';
        $success = '';

        // Handle form submission (when user clicks "Add Expense")
        if ( isset( $_POST['splitwise_add_expense_submit'] ) ) {

            // Security: Verify nonce to prevent CSRF attacks
            if ( ! isset( $_POST['splitwise_add_expense_nonce'] ) ||
                 ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['splitwise_add_expense_nonce'] ) ), 'splitwise_add_expense_action' ) ) {
                $error = __( 'Security check failed. Please refresh and try again.', 'splitwise-wp' );
            } else {
                // Sanitize and process form data
                $description = isset( $_POST['description'] ) ? sanitize_text_field( wp_unslash( $_POST['description'] ) ) : '';
                $amount      = isset( $_POST['amount'] ) ? floatval( wp_unslash( $_POST['amount'] ) ) : 0;
                $date        = isset( $_POST['date'] ) ? sanitize_text_field( wp_unslash( $_POST['date'] ) ) : current_time( 'Y-m-d' );

                $selected_users = [];
                if ( ! empty( $_POST['users'] ) && is_array( $_POST['users'] ) ) {
                    $selected_users = array_map( 'absint', wp_unslash( $_POST['users'] ) );
                }

                // Basic validation
                if ( empty( $description ) ) {
                    $error = __( 'Please enter a description.', 'splitwise-wp' );
                } elseif ( $amount <= 0 ) {
                    $error = __( 'Please enter a valid positive amount.', 'splitwise-wp' );
                } elseif ( empty( $selected_users ) ) {
                    $error = __( 'Please select at least one person to split with.', 'splitwise-wp' );
                } else {
                    // Prepare equal splits including the payer
                    $participant_ids = array_unique( array_merge( $selected_users, [ $current_user_id ] ) );
                    $participant_count = count( $participant_ids );
                    $share_amount = round( $amount / $participant_count, 2 );

                    $splits = [];
                    foreach ( $participant_ids as $uid ) {
                        $splits[] = [
                            'user_id'      => $uid,
                            'share_amount' => $share_amount,
                            'paid_amount'  => ( $uid === $current_user_id ) ? $amount : 0.00,
                        ];
                    }

                    // Save the expense using the Expenses class
                    $result = Splitwise_Expenses::add_expense( [
                        'description' => $description,
                        'amount'      => $amount,
                        'date'        => $date,
                        'splits'      => $splits,
                    ] );

                    if ( $result['success'] ) {
                        $success = sprintf(
                            __( 'Expense of Rs %1$s added successfully! Each of %2$d people owes Rs %3$s.', 'splitwise-wp' ),
                            number_format( $amount, 2 ),
                            $participant_count,
                            number_format( $share_amount, 2 )
                        );
                    } else {
                        $error = $result['message'];
                    }
                }
            }
        }

        // Get list of other users for the form
        $other_users = $this->get_other_users( $current_user_id );

        return $this->render_template( 'add-expense', [
            'error'        => $error,
            'success'      => $success,
            'other_users'  => $other_users,
            'user_name'    => wp_get_current_user()->display_name,
            'nonce_field'  => wp_nonce_field( 'splitwise_add_expense_action', 'splitwise_add_expense_nonce', true, false ),
        ] );
    }

    // ==================================================================
    // [splitwise_balance] - Shortcode
    // ==================================================================

    /**
     * Handles the [splitwise_balance] shortcode.
     * Displays overall balance and detailed breakdown of who owes whom.
     */

/**What it does:
Gets overall balance.
Gets detailed person-by-person balance.
Adds user display names for better readability.
Passes data to the balance.php template.
     */
    public function balance_shortcode( $atts ) {
        if ( ! is_user_logged_in() ) {
            return $this->login_required_notice();
        }

        $current_user_id = get_current_user_id();

        $balances = Splitwise_Balance::get_user_balances( $current_user_id );
        $detailed = Splitwise_Balance::get_detailed_balances( $current_user_id );

        // Add display names to detailed balances for better readability
        $detailed_with_names = [];
        foreach ( $detailed as $other_user_id => $amounts ) {
            $user = get_userdata( $other_user_id );
            $detailed_with_names[] = [
                'user_id' => $other_user_id,
                'name'    => $user ? $user->display_name : __( 'Unknown User', 'splitwise-wp' ),
                'owes'    => $amounts['owes'] ?? 0,
                'owed'    => $amounts['owed'] ?? 0,
            ];
        }

        return $this->render_template( 'balance', [
            'balances' => $balances,
            'detailed' => $detailed_with_names,
        ] );
    }

    // ==================================================================
    // Helper Methods
    // ==================================================================

    /**
     * Returns all other WordPress users except the current user.
     * Used in the "Add Expense" form to select who to split with.
     */
    private function get_other_users( $exclude_user_id ) {
        return get_users( [
            'exclude' => [ $exclude_user_id ],
            'orderby' => 'display_name',
            'order'   => 'ASC',
        ] );
    }

    /**
     * Renders a template file from the templates/ folder.
     * Makes the passed data available as variables inside the template.
     */
    private function render_template( $template, $data = [] ) {
        $template_path = SPLITWISE_WP_PLUGIN_DIR . 'templates/' . $template . '.php';

        if ( ! file_exists( $template_path ) ) {
            return '<p style="color:red;">Template not found: ' . esc_html( $template ) . '</p>';
        }

        extract( $data, EXTR_SKIP );
        ob_start();
        include $template_path;
        return ob_get_clean();
    }

    /**
     * Returns a friendly message when a non-logged-in user tries to access shortcodes.
     */
    private function login_required_notice() {
        return sprintf(
            '<p class="splitwise-login-required">%s <a href="%s">%s</a></p>',
            esc_html__( 'Please log in to use Splitwise.', 'splitwise-wp' ),
            esc_url( wp_login_url( get_permalink() ) ),
            esc_html__( 'Log in', 'splitwise-wp' )
        );
    }
}