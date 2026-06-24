<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Admin View: Dashboard
 * Variables Available:
 * - $balances → array( owes, owed, net )
 * - $expenses → array of recent expenses
 * - $total_expenses → total number of expenses (for "View All")
 */
?>
<div class="wrap">
    <h1><?php esc_html_e( 'Splitwise Dashboard', 'splitwise-wp' ); ?></h1>

    <!-- Balance Summary Cards -->
    <div class="splitwise-balance-cards">
        <!-- Net Balance -->
        <div class="splitwise-card splitwise-card-net <?php echo $balances['net'] >= 0 ? 'positive' : 'negative'; ?>">
            <div class="splitwise-card-label">
                <?php esc_html_e( 'Net Balance', 'splitwise-wp' ); ?>
            </div>
            <div class="splitwise-card-amount">
                <?php echo esc_html( splitwise_get_currency_symbol() ); ?> 
                <?php echo esc_html( number_format( abs( $balances['net'] ), 2 ) ); ?>
            </div>
            <div class="splitwise-card-status">
                <?php
                if ( $balances['net'] > 0 ) {
                    esc_html_e( 'Others owe you', 'splitwise-wp' );
                } elseif ( $balances['net'] < 0 ) {
                    esc_html_e( 'You owe others', 'splitwise-wp' );
                } else {
                    esc_html_e( 'All settled up', 'splitwise-wp' );
                }
                ?>
            </div>
        </div>

        <!-- You Owe -->
        <div class="splitwise-card">
            <div class="splitwise-card-label">
                <?php esc_html_e( 'You Owe', 'splitwise-wp' ); ?>
            </div>
            <div class="splitwise-card-amount you-owe">
                <?php echo esc_html( splitwise_get_currency_symbol() ); ?> 
                <?php echo esc_html( number_format( $balances['owes'], 2 ) ); ?>
            </div>
        </div>

        <!-- Owed To You -->
        <div class="splitwise-card">
            <div class="splitwise-card-label">
                <?php esc_html_e( 'Owed To You', 'splitwise-wp' ); ?>
            </div>
            <div class="splitwise-card-amount owed-to-you">
                <?php echo esc_html( splitwise_get_currency_symbol() ); ?> 
                <?php echo esc_html( number_format( $balances['owed'], 2 ) ); ?>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="splitwise-actions">
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=splitwise-add-expense' ) ); ?>" 
           class="button button-primary button-large">
            <?php esc_html_e( '+ Add New Expense', 'splitwise-wp' ); ?>
        </a>
        
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=splitwise-balance' ) ); ?>" 
           class="button button-large">
            <?php esc_html_e( 'View Full Balance', 'splitwise-wp' ); ?>
        </a>
    </div>

    <!-- Recent Activity -->
    <h2><?php esc_html_e( 'Recent Activity', 'splitwise-wp' ); ?></h2>

    <?php if ( empty( $expenses ) ) : ?>
        <div class="notice notice-info">
            <p><?php esc_html_e( 'No expenses found. Add your first expense!', 'splitwise-wp' ); ?></p>
        </div>
    <?php else : ?>
        <table class="widefat striped fixed">
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
                            <?php echo esc_html( splitwise_get_currency_symbol() ); ?> 
                            <?php echo esc_html( number_format( $expense->amount, 2 ) ); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ( ! empty( $total_expenses ) && $total_expenses > count( $expenses ) ) : ?>
            <p class="splitwise-view-all">
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=splitwise-expenses' ) ); ?>">
                    <?php esc_html_e( 'View All Expenses', 'splitwise-wp' ); ?> 
                    (<?php echo absint( $total_expenses ); ?>)
                </a>
            </p>
        <?php endif; ?>
    <?php endif; ?>

</div>