<?php
/**
 * Template: Add Expense
 * Shortcode: [splitwise_add_expense]
 *
 * Variables available (passed by Splitwise_Shortcodes::add_expense_shortcode):
 * - $error        string
 * - $success      string
 * - $other_users  array of WP_User
 * - $user_name    string
 * - $nonce_field  string (already-rendered nonce HTML)
 *
 * NOTE: Form submission is handled entirely by Splitwise_Shortcodes::add_expense_shortcode()
 * BEFORE this template is rendered. This template only displays the form + result.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$currency      = splitwise_get_currency_symbol();
$dashboard_url = splitwise_get_page_url( 'dashboard' );

// Re-populate previously selected checkboxes after a failed submission.
$previously_selected = isset( $_POST['users'] ) && is_array( $_POST['users'] )
    ? array_map( 'absint', wp_unslash( $_POST['users'] ) )
    : [];
?>

<div class="splitwise-wrapper">

    <!-- Navigation -->
    <nav class="splitwise-nav">
        <span class="splitwise-nav-brand">SplitWise</span>
        <div class="splitwise-nav-right">
            <a href="<?php echo esc_url( $dashboard_url ); ?>" class="splitwise-nav-back">← Back to Dashboard</a>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="splitwise-container">

        <!-- Page Header -->
        <div class="splitwise-page-header">
            <h1>Add Expense</h1>
            <p>Fill in the details and choose who to split with</p>
        </div>

        <?php if ( $error ) : ?>
            <div class="splitwise-notice error"><?php echo esc_html( $error ); ?></div>
        <?php endif; ?>

        <?php if ( $success ) : ?>
            <div class="splitwise-notice success"><?php echo esc_html( $success ); ?></div>
        <?php endif; ?>

        <!-- Form Card -->
        <div class="splitwise-form-card">

            <!-- Info Banner -->
            <div class="splitwise-info-banner">
                You (<strong><?php echo esc_html( $user_name ); ?></strong>) are paying.
                You will always be included in the split.
            </div>

            <form method="post" id="splitwise-expense-form">
                <?php echo $nonce_field; ?>

                <!-- Amount -->
                <div class="splitwise-form-group">
                    <label for="sw-amount">Total Amount (<?php echo esc_html( $currency ); ?>)</label>
                    <div class="splitwise-amount-wrap">
                        <span class="currency-prefix"><?php echo esc_html( $currency ); ?></span>
                        <input
                            type="number"
                            id="sw-amount"
                            name="amount"
                            class="splitwise-input"
                            min="0.01"
                            step="0.01"
                            placeholder="0.00"
                            value="<?php echo isset( $_POST['amount'] ) ? esc_attr( wp_unslash( $_POST['amount'] ) ) : ''; ?>"
                        >
                    </div>
                </div>

                <!-- Description -->
                <div class="splitwise-form-group">
                    <label for="sw-description">Description</label>
                    <input
                        type="text"
                        id="sw-description"
                        name="description"
                        class="splitwise-input"
                        placeholder="e.g. Dinner, Movie, Groceries"
                        value="<?php echo isset( $_POST['description'] ) ? esc_attr( wp_unslash( $_POST['description'] ) ) : ''; ?>"
                    >
                </div>

                <!-- Date -->
                <div class="splitwise-form-group">
                    <label for="sw-date">Date</label>
                    <input
                        type="date"
                        id="sw-date"
                        name="date"
                        class="splitwise-input"
                        value="<?php echo isset( $_POST['date'] ) ? esc_attr( wp_unslash( $_POST['date'] ) ) : esc_attr( current_time( 'Y-m-d' ) ); ?>"
                    >
                </div>

                <!-- Split With -->
                <div class="splitwise-form-group">
                    <label>Split With</label>

                    <!-- Live split preview (filled in by splitwise-frontend.js) -->
                    <div class="splitwise-split-preview" id="sw-split-preview"></div>

                    <?php if ( ! empty( $other_users ) ) : ?>
                    <div class="splitwise-split-grid" id="sw-split-grid">
                        <?php foreach ( $other_users as $user ) :
                            $checked = in_array( $user->ID, $previously_selected, true );
                        ?>
                        <label class="splitwise-split-item <?php echo $checked ? 'checked' : ''; ?>" id="sw-label-<?php echo esc_attr( $user->ID ); ?>">
                            <input
                                type="checkbox"
                                name="users[]"
                                value="<?php echo esc_attr( $user->ID ); ?>"
                                <?php checked( $checked ); ?>
                            >
                            <?php echo esc_html( $user->display_name ); ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <?php else : ?>
                    <p style="color:#94a3b8; font-size:14px;">No other users found to split with.</p>
                    <?php endif; ?>
                </div>

                <!-- Submit -->
                <button type="submit" name="splitwise_add_expense_submit" class="splitwise-form-submit">Add Expense</button>

            </form>
        </div><!-- .splitwise-form-card -->

    </div><!-- .splitwise-container -->
</div><!-- .splitwise-wrapper -->