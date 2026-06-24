<?php
/**
 * Admin view: Add Expense
 * Variables available: $error, $success, $other_users (array of WP_User)
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap">
    <h1><?php esc_html_e( 'Add Expense', 'splitwise-wp' ); ?></h1>

    <?php if ( $error ) : ?>
        <div class="notice notice-error is-dismissible"><p><?php echo esc_html( $error ); ?></p></div>
    <?php endif; ?>

    <?php if ( $success ) : ?>
        <div class="notice notice-success is-dismissible"><p><?php echo esc_html( $success ); ?></p></div>
    <?php endif; ?>

    <form method="post" action="" novalidate>
        <?php wp_nonce_field( 'splitwise_add_expense_action', 'splitwise_add_expense_nonce' ); ?>

        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="amount"><?php esc_html_e( 'Amount (Rs)', 'splitwise-wp' ); ?></label>
                </th>
                <td>
                    <input type="number" 
                           step="0.01" 
                           min="0.01" 
                           name="amount" 
                           id="amount"
                           class="regular-text"
                           value="<?php echo isset( $_POST['amount'] ) ? esc_attr( wp_unslash( $_POST['amount'] ) ) : ''; ?>"
                           required>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="description"><?php esc_html_e( 'Description', 'splitwise-wp' ); ?></label>
                </th>
                <td>
                    <input type="text" 
                           name="description" 
                           id="description"
                           class="regular-text"
                           placeholder="<?php esc_attr_e( 'e.g. Dinner, Movie, Groceries', 'splitwise-wp' ); ?>"
                           value="<?php echo isset( $_POST['description'] ) ? esc_attr( wp_unslash( $_POST['description'] ) ) : ''; ?>"
                           required>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="date"><?php esc_html_e( 'Date', 'splitwise-wp' ); ?></label>
                </th>
                <td>
                    <input type="date" 
                           name="date" 
                           id="date"
                           value="<?php echo isset( $_POST['date'] ) ? esc_attr( wp_unslash( $_POST['date'] ) ) : esc_attr( current_time( 'Y-m-d' ) ); ?>">
                </td>
            </tr>

            <tr>
                <th scope="row"><?php esc_html_e( 'Split With', 'splitwise-wp' ); ?></th>
                <td>
                    <?php if ( empty( $other_users ) ) : ?>
                        <p class="description"><?php esc_html_e( 'No other users registered yet.', 'splitwise-wp' ); ?></p>
                    <?php else : ?>
                        <?php
                        $previously_selected = isset( $_POST['users'] ) && is_array( $_POST['users'] )
                            ? array_map( 'absint', wp_unslash( $_POST['users'] ) )
                            : [];
                        ?>
                        <fieldset>
                            <?php foreach ( $other_users as $u ) : ?>
                                <label style="display:block; margin-bottom:8px;">
                                    <input type="checkbox" 
                                           name="users[]" 
                                           value="<?php echo esc_attr( $u->ID ); ?>"
                                           <?php checked( in_array( $u->ID, $previously_selected, true ) ); ?>>
                                    <?php echo esc_html( $u->display_name ); ?>
                                </label>
                            <?php endforeach; ?>
                        </fieldset>
                    <?php endif; ?>
                </td>
            </tr>
        </table>

        <?php submit_button( __( 'Add Expense', 'splitwise-wp' ), 'primary', 'splitwise_add_expense_submit' ); ?>
    </form>
</div>