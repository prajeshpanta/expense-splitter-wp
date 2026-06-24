<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
/**
 * Admin View: Dashboard
 * Variables Available:
 * - $balances → array( owes, owed, net )
 * - $expenses → array( list of expenses )
 */
?>
<div class="wrap"> <!--wrap is the wordpress admin class that adds proper styling and padding-->
    <h1><?php esc_html_e( 'Splitwise Dashboard', 'splitwise-wp' ); ?></h1>

    <!-- Balance Summary Cards -->
    <div style="display:flex; gap:15px; margin:20px 0; flex-wrap:wrap;">

        <!-- Net Balance Card -->
        <div style="background:#fff;
                    border:1px solid #ccd0d4;
                    border-left:4px solid <?php echo $balances['net'] >= 0 ? '#46b450' : '#dc3232'; ?>;
                    padding:15px 20px;
                    flex:1;
                    min-width:220px;
                    border-radius:4px;">
            <p style="margin:0; font-size:13px; color:#646970; text-transform:uppercase; font-weight:500;">
                <?php esc_html_e( 'Net Balance', 'splitwise-wp' ); ?>
            </p>
            <p style="margin:8px 0 4px; font-size:28px; font-weight:600;">
                Rs <?php echo esc_html( number_format( abs( $balances['net'] ), 2 ) ); ?>
            </p>
            <p style="margin:0; color:#646970; font-size:14px;">
                <?php
                if ( $balances['net'] > 0 ) {
                    esc_html_e( 'Others owe you', 'splitwise-wp' );
                } elseif ( $balances['net'] < 0 ) {
                    esc_html_e( 'You owe others', 'splitwise-wp' );
                } else {
                    esc_html_e( 'All settled up', 'splitwise-wp' );
                }
                ?>
            </p>
        </div>

        <!-- You Owe Card -->
        <div style="background:#fff;
                    border:1px solid #ccd0d4;
                    border-left:4px solid #f39c12;
                    padding:15px 20px;
                    flex:1;
                    min-width:220px;
                    border-radius:4px;">
            <p style="margin:0; font-size:13px; color:#646970; text-transform:uppercase; font-weight:500;">
                <?php esc_html_e( 'You Owe', 'splitwise-wp' ); ?>
            </p>
            <p style="margin:8px 0 4px; font-size:28px; font-weight:600; color:#d63638;">
                Rs <?php echo esc_html( number_format( $balances['owes'], 2 ) ); ?>
            </p>
        </div>

        <!-- Owed To You Card -->
        <div style="background:#fff;
                    border:1px solid #ccd0d4;
                    border-left:4px solid #2271b1;
                    padding:15px 20px;
                    flex:1;
                    min-width:220px;
                    border-radius:4px;">
            <p style="margin:0; font-size:13px; color:#646970; text-transform:uppercase; font-weight:500;">
                <?php esc_html_e( 'Owed To You', 'splitwise-wp' ); ?>
            </p>
            <p style="margin:8px 0 4px; font-size:28px; font-weight:600; color:#0073aa;">
                Rs <?php echo esc_html( number_format( $balances['owed'], 2 ) ); ?>
            </p>
        </div>

    </div>

    <!-- Action Buttons -->
    <p style="margin-bottom:25px;">
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=splitwise-add-expense' ) ); ?>"
           class="button button-primary button-large">
            <?php esc_html_e( '+ Add New Expense', 'splitwise-wp' ); ?>
        </a>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=splitwise-balance' ) ); ?>"
           class="button button-large">
            <?php esc_html_e( 'View Full Balance', 'splitwise-wp' ); ?>
        </a>
    </p>

    <!-- Recent Expenses -->
    <h2><?php esc_html_e( 'Recent Activity', 'splitwise-wp' ); ?></h2>

    <?php if ( empty( $expenses ) ) : ?>
        <p><?php esc_html_e( 'No expenses found. Add your first expense!', 'splitwise-wp' ); ?></p>
    <?php else : ?>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Description', 'splitwise-wp' ); ?></th>
                    <th><?php esc_html_e( 'Date', 'splitwise-wp' ); ?></th>
                    <th style="text-align:right;"><?php esc_html_e( 'Amount', 'splitwise-wp' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $expenses as $expense ) : ?>
                    <tr>
                        <td><?php echo esc_html( $expense->description ); ?></td>
                        <td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $expense->date ) ) ); ?></td>
                        <td style="text-align:right; font-weight:600;">
                            Rs <?php echo esc_html( number_format( $expense->amount, 2 ) ); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

</div>