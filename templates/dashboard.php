<?php
/**
 * Frontend Template: Dashboard
 * Rendered by [splitwise_dashboard] shortcode.
 *
 * Variables:
 * - $user_name      (string) [current user display name]
 * - $balances       (array)  ['owes', 'owed', 'net']
 * - $expenses       (array)  [list of the expenses]
 * - $total_expenses (int)    [Total expense count]
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$currency = splitwise_get_currency_symbol();
?>
<div class="splitwise-wrap">

    <!-- Page Header -->
    <header class="splitwise-page-header">
        <h2><?php esc_html_e( 'Dashboard', 'splitwise-wp' ); ?></h2>
        <p class="splitwise-page-sub">
            <?php
            printf(
                /* translators: %s: user display name */
                esc_html__( 'Welcome back, %s!', 'splitwise-wp' ),
                '<strong>' . esc_html( $user_name ) . '</strong>'
            );
            ?>
        </p>
    </header>

    <!-- Balance Summary -->
    <div class="splitwise-summary-row">

        <!-- Net Balance -->
        <div class="splitwise-card splitwise-card--net splitwise-card--<?php echo $balances['net'] >= 0 ? 'positive' : 'negative'; ?>">
            <div class="splitwise-card__label"><?php esc_html_e( 'Net Balance', 'splitwise-wp' ); ?></div>
            <div class="splitwise-card__value">
                <?php echo esc_html( $currency ); ?>
                <?php echo esc_html( number_format( abs( $balances['net'] ), 2 ) ); ?>
            </div>
            <div class="splitwise-card__sub">
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
        <div class="splitwise-card splitwise-card--owes">
            <div class="splitwise-card__label"><?php esc_html_e( 'You Owe', 'splitwise-wp' ); ?></div>
            <div class="splitwise-card__value splitwise-card__value--owes">
                <?php echo esc_html( $currency ); ?>
                <?php echo esc_html( number_format( $balances['owes'], 2 ) ); ?>
            </div>
            <div class="splitwise-card__sub">
                <?php esc_html_e( 'Your share of expenses', 'splitwise-wp' ); ?>
            </div>
        </div>

        <!-- Owed To You -->
        <div class="splitwise-card splitwise-card--owed">
            <div class="splitwise-card__label"><?php esc_html_e( 'Owed To You', 'splitwise-wp' ); ?></div>
            <div class="splitwise-card__value splitwise-card__value--owed">
                <?php echo esc_html( $currency ); ?>
                <?php echo esc_html( number_format( $balances['owed'], 2 ) ); ?>
            </div>
            <div class="splitwise-card__sub">
                <?php esc_html_e( 'Others owe you', 'splitwise-wp' ); ?>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="splitwise-action-bar">
        <a href="<?php echo esc_url( splitwise_get_add_expense_url() ); ?>" 
           class="splitwise-btn splitwise-btn--primary">
            <?php esc_html_e( '+ Add New Expense', 'splitwise-wp' ); ?>
        </a>
        <a href="<?php echo esc_url( splitwise_get_balance_url() ); ?>" 
           class="splitwise-btn splitwise-btn--secondary">
            <?php esc_html_e( 'View Full Balance', 'splitwise-wp' ); ?>
        </a>
    </div>

    <!-- Recent Activity -->
    <section class="splitwise-section">
        <div class="splitwise-section__header">
            <h3 class="splitwise-section__title">
                <?php esc_html_e( 'Recent Activity', 'splitwise-wp' ); ?>
            </h3>

            <?php if ( ! empty( $total_expenses ) && $total_expenses > count( $expenses ?? [] ) ) : ?>
                <a href="<?php echo esc_url( splitwise_get_expenses_url() ); ?>" 
                   class="splitwise-view-all">
                    <?php esc_html_e( 'View All', 'splitwise-wp' ); ?>
                </a>
            <?php endif; ?>
        </div>

        <?php if ( empty( $expenses ) ) : ?>
            <div class="splitwise-empty">
                <p><?php esc_html_e( 'No expenses recorded yet.', 'splitwise-wp' ); ?></p>
                <a href="<?php echo esc_url( splitwise_get_add_expense_url() ); ?>" 
                   class="splitwise-btn splitwise-btn--primary splitwise-btn--small">
                    <?php esc_html_e( 'Add Your First Expense', 'splitwise-wp' ); ?>
                </a>
            </div>
        <?php else : ?>
            <table class="splitwise-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Description', 'splitwise-wp' ); ?></th>
                        <th><?php esc_html_e( 'Date', 'splitwise-wp' ); ?></th>
                        <th class="splitwise-table__col--right"><?php esc_html_e( 'Amount', 'splitwise-wp' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $expenses as $expense ) : ?>
                        <tr>
                            <td><?php echo esc_html( $expense->description ); ?></td>
                            <td><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $expense->date ) ) ); ?></td>
                            <td class="splitwise-table__col--right">
                                <strong>
                                    <?php echo esc_html( $currency ); ?>
                                    <?php echo esc_html( number_format( $expense->amount, 2 ) ); ?>
                                </strong>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
</div> 