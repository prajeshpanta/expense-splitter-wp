<?php
/**
 * Frontend Template: Add Expense
 * Rendered by [splitwise_add_expense] shortcode.
 *
 * Variables:
 * - $error          (string)
 * - $success        (string)
 * - $other_users    (array of WP_User)
 * - $user_name      (string)
 * - $nonce_field    (string) Pre-generated nonce HTML
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$currency = splitwise_get_currency_symbol();

// Restore previously selected users after validation error
$previously_selected = [];
if ( isset( $_POST['users'] ) && is_array( $_POST['users'] ) ) {
    $previously_selected = array_map( 'absint', wp_unslash( $_POST['users'] ) );
}
?>
<div class="splitwise-wrap">

    <!-- Page Header -->
    <header class="splitwise-page-header">
        <h2><?php esc_html_e( 'Add Expense', 'splitwise-wp' ); ?></h2>
        <p class="splitwise-page-sub">
            <?php esc_html_e( 'Fill in the details and choose who to split with.', 'splitwise-wp' ); ?>
        </p>
    </header>

    <?php if ( $error ) : ?>
        <div class="splitwise-notice splitwise-notice--error" role="alert">
            <span class="splitwise-notice__icon" aria-hidden="true">⚠</span>
            <?php echo esc_html( $error ); ?>
        </div>
    <?php endif; ?>

    <?php if ( $success ) : ?>
        <div class="splitwise-notice splitwise-notice--success" role="status">
            <span class="splitwise-notice__icon" aria-hidden="true">✓</span>
            <?php echo esc_html( $success ); ?>
        </div>
    <?php endif; ?>

    <div class="splitwise-card-plain">
        <!-- Payer Info -->
        <div class="splitwise-info-note" role="note">
            <?php
            printf(
                /* translators: %s: current user name */
                esc_html__( 'You (%s) are paying. You will always be included in the split.', 'splitwise-wp' ),
                '<strong>' . esc_html( $user_name ) . '</strong>'
            );
            ?>
        </div>

        <form method="post" action="" id="splitwise-expense-form" novalidate>
            <?php 
            // Nonce is safe to echo directly
            if ( ! empty( $nonce_field ) ) {
                echo $nonce_field; 
            }
            ?>

            <!-- Amount -->
            <div class="splitwise-field-group">
                <label for="sw-amount">
                    <?php esc_html_e( 'Total Amount', 'splitwise-wp' ); ?>
                    <span class="splitwise-field-group__currency">(<?php echo esc_html( $currency ); ?>)</span>
                </label>
                <div class="splitwise-amount-wrap">
                    <span class="splitwise-prefix" aria-hidden="true"><?php echo esc_html( $currency ); ?></span>
                    <input type="number" 
                           id="sw-amount" 
                           name="amount" 
                           step="0.01" 
                           min="0.01" 
                           placeholder="0.00"
                           value="<?php echo isset( $_POST['amount'] ) ? esc_attr( wp_unslash( $_POST['amount'] ) ) : ''; ?>"
                           required
                           autocomplete="off"
                           aria-describedby="sw-split-preview">
                </div>
            </div>

            <!-- Description -->
            <div class="splitwise-field-group">
                <label for="sw-description"><?php esc_html_e( 'Description', 'splitwise-wp' ); ?></label>
                <input type="text" 
                       id="sw-description" 
                       name="description"
                       placeholder="<?php esc_attr_e( 'e.g. Dinner, Movie, Groceries', 'splitwise-wp' ); ?>"
                       value="<?php echo isset( $_POST['description'] ) ? esc_attr( wp_unslash( $_POST['description'] ) ) : ''; ?>"
                       required
                       maxlength="255">
            </div>

            <!-- Date -->
            <div class="splitwise-field-group">
                <label for="sw-date"><?php esc_html_e( 'Date', 'splitwise-wp' ); ?></label>
                <input type="date" 
                       id="sw-date" 
                       name="date"
                       value="<?php echo isset( $_POST['date'] ) ? esc_attr( wp_unslash( $_POST['date'] ) ) : esc_attr( current_time( 'Y-m-d' ) ); ?>"
                       max="<?php echo esc_attr( current_time( 'Y-m-d' ) ); ?>">
            </div>

            <!-- Split With -->
            <fieldset class="splitwise-field-group splitwise-fieldset">
                <legend class="splitwise-fieldset__legend">
                    <?php esc_html_e( 'Split With', 'splitwise-wp' ); ?>
                </legend>

                <?php if ( empty( $other_users ) ) : ?>
                    <div class="splitwise-empty">
                        <p><?php esc_html_e( 'No other registered users found.', 'splitwise-wp' ); ?></p>
                    </div>
                <?php else : ?>
                    <div class="splitwise-users-grid">
                        <?php foreach ( $other_users as $u ) : 
                            $is_selected = in_array( $u->ID, $previously_selected, true );
                        ?>
                            <label class="splitwise-user-item<?php echo $is_selected ? ' splitwise-user-item--selected' : ''; ?>" 
                                   id="sw-label-<?php echo esc_attr( $u->ID ); ?>">
                                <input type="checkbox" 
                                       name="users[]" 
                                       value="<?php echo esc_attr( $u->ID ); ?>"
                                       <?php checked( $is_selected ); ?>>
                                <?php echo esc_html( $u->display_name ); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Live Preview -->
                <div id="sw-split-preview" 
                     class="splitwise-split-preview" 
                     style="display:none;" 
                     aria-live="polite" 
                     aria-atomic="true">
                </div>
            </fieldset>

            <!-- Submit -->
            <button type="submit" 
                    name="splitwise_add_expense_submit"
                    class="splitwise-btn splitwise-btn--primary splitwise-btn--full">
                <?php esc_html_e( 'Add Expense', 'splitwise-wp' ); ?>
            </button>
        </form>
    </div>

    <!-- Back Navigation -->
    <nav class="splitwise-back-nav" aria-label="<?php esc_attr_e( 'Back navigation', 'splitwise-wp' ); ?>">
        <a href="<?php echo esc_url( splitwise_get_balance_url() ); ?>" 
           class="splitwise-btn splitwise-btn--secondary">
            <?php esc_html_e( '← Back to Balance', 'splitwise-wp' ); ?>
        </a>
    </nav>
</div>