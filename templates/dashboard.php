<?php
/**
 * Template: Dashboard
 * Shortcode: [splitwise_dashboard]
 *
 * Variables available (passed by Splitwise_Shortcodes::dashboard_shortcode):
 * - $user_name      string
 * - $balances       array( owes, owed, net )
 * - $expenses       array of recent expense rows (from Splitwise_Expenses::get_expenses)
 * - $total_expenses int
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$currency = splitwise_get_currency_symbol();
$add_expense_url = splitwise_get_page_url( 'add_expense' );
$balance_url      = splitwise_get_page_url( 'balance' );

if ( $balances['net'] > 0 ) {
    $net_label = __( 'Others owe you', 'splitwise-wp' );
    $net_class = 'green';
} elseif ( $balances['net'] < 0 ) {
    $net_label = __( 'You owe others', 'splitwise-wp' );
    $net_class = 'orange';
} else {
    $net_label = __( 'All settled up', 'splitwise-wp' );
    $net_class = 'green';
}
?>

<div class="splitwise-wrapper">

    <!-- Navigation -->
    <nav class="splitwise-nav">
        <span class="splitwise-nav-brand">SplitWise</span>
        <div class="splitwise-nav-right">
            <span class="splitwise-nav-user">Hello, <?php echo esc_html( $user_name ); ?></span>
            <a href="<?php echo esc_url( wp_logout_url( get_permalink() ) ); ?>" class="splitwise-nav-logout">Logout</a>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="splitwise-container">

        <!-- Page Header -->
        <div class="splitwise-page-header">
            <h1>Dashboard</h1>
            <p>Overview of your expenses and balance</p>
        </div>

        <!-- Stat Cards -->
        <div class="splitwise-stats-grid">
            <div class="splitwise-stat-card <?php echo esc_attr( $net_class ); ?>">
                <div class="splitwise-stat-label">Net Balance</div>
                <div class="splitwise-stat-value <?php echo esc_attr( $net_class ); ?>">
                    <?php echo esc_html( $currency ); ?> <?php echo number_format( abs( $balances['net'] ), 2 ); ?>
                </div>
                <div class="splitwise-stat-sublabel"><?php echo esc_html( $net_label ); ?></div>
            </div>

            <div class="splitwise-stat-card blue">
                <div class="splitwise-stat-label">You Owe</div>
                <div class="splitwise-stat-value blue">
                    <?php echo esc_html( $currency ); ?> <?php echo number_format( $balances['owes'], 2 ); ?>
                </div>
                <div class="splitwise-stat-sublabel">Your remaining share</div>
            </div>

            <div class="splitwise-stat-card orange">
                <div class="splitwise-stat-label">Owed To You</div>
                <div class="splitwise-stat-value orange">
                    <?php echo esc_html( $currency ); ?> <?php echo number_format( $balances['owed'], 2 ); ?>
                </div>
                <div class="splitwise-stat-sublabel">What others owe you</div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="splitwise-actions">
            <a href="<?php echo esc_url( $add_expense_url ); ?>" class="splitwise-btn splitwise-btn-primary">+ Add Expense</a>
            <a href="<?php echo esc_url( $balance_url ); ?>"     class="splitwise-btn splitwise-btn-secondary">View Full Balance</a>
        </div>

        <!-- Recent Activity -->
        <div class="splitwise-section">
            <h2 class="splitwise-section-title">Recent Activity</h2>
            <div class="splitwise-table-wrap">
                <?php if ( ! empty( $expenses ) ) : ?>
                <table class="splitwise-table">
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th>Date</th>
                            <th style="text-align:right;">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $expenses as $expense ) :
                            $expense_date = date_i18n( get_option( 'date_format' ), strtotime( $expense->date ) );
                        ?>
                        <tr>
                            <td class="td-desc"><?php echo esc_html( $expense->description ); ?></td>
                            <td class="td-date"><?php echo esc_html( $expense_date ); ?></td>
                            <td class="td-amount paid">
                                <?php echo esc_html( $currency ); ?> <?php echo number_format( floatval( $expense->amount ), 2 ); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php if ( ! empty( $total_expenses ) && $total_expenses > count( $expenses ) ) : ?>
                <p style="text-align:center; margin-top:12px;">
                    <a href="<?php echo esc_url( $balance_url ); ?>">
                        View All Expenses (<?php echo absint( $total_expenses ); ?>)
                    </a>
                </p>
                <?php endif; ?>

                <?php else : ?>
                <div class="splitwise-empty">No expenses yet. Add your first expense to get started!</div>
                <?php endif; ?>
            </div>
        </div>

    </div><!-- .splitwise-container -->
</div><!-- .splitwise-wrapper -->