<?php
/**
 * Template: My Balance
 * Shortcode: [splitwise_balance]
 *
 * Variables available (passed by Splitwise_Shortcodes::balance_shortcode):
 * - $balances       array( owes, owed, net )
 * - $detailed       array of [ user_id, name, owes, owed ]
 * - $paid_expenses  array of expense rows this user paid for
 * - $owed_expenses  array of expense rows this user owes a share of
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$currency      = splitwise_get_currency_symbol();
$dashboard_url = splitwise_get_page_url( 'dashboard' );

if ( $balances['net'] > 0 ) {
    $net_label = __( 'Others owe you', 'splitwise-wp' );
} elseif ( $balances['net'] < 0 ) {
    $net_label = __( 'You owe others', 'splitwise-wp' );
} else {
    $net_label = __( 'All settled up', 'splitwise-wp' );
}
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
            <h1>My Balance</h1>
            <p>Full breakdown of what you paid and what you owe</p>
        </div>

        <!-- Balance Summary Card -->
        <div class="splitwise-balance-summary">
            <div class="splitwise-balance-summary-left">
                <div class="summary-label"><?php echo esc_html( $net_label ); ?></div>
                <div class="summary-value">
                    <?php echo esc_html( $currency ); ?> <?php echo number_format( abs( $balances['net'] ), 2 ); ?>
                </div>
            </div>
            <div class="splitwise-balance-summary-right">
                <div class="splitwise-balance-summary-stat">
                    <div class="stat-label">You Owe</div>
                    <div class="stat-value orange">
                        <?php echo esc_html( $currency ); ?> <?php echo number_format( $balances['owes'], 2 ); ?>
                    </div>
                </div>
                <div class="splitwise-balance-summary-stat">
                    <div class="stat-label">Owed To You</div>
                    <div class="stat-value blue">
                        <?php echo esc_html( $currency ); ?> <?php echo number_format( $balances['owed'], 2 ); ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Breakdown by Person -->
        <div class="splitwise-section">
            <h2 class="splitwise-section-title">Breakdown by Person</h2>
            <div class="splitwise-table-wrap">
                <?php if ( ! empty( $detailed ) ) : ?>
                <table class="splitwise-table">
                    <thead>
                        <tr>
                            <th>Person</th>
                            <th style="text-align:right;">They Owe You</th>
                            <th style="text-align:right;">You Owe Them</th>
                            <th style="text-align:right;">Net</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $detailed as $row ) :
                            $net = $row['owed'] - $row['owes'];
                        ?>
                        <tr>
                            <td class="td-desc"><strong><?php echo esc_html( $row['name'] ); ?></strong></td>
                            <td class="td-amount" style="color:#3498db;">
                                <?php echo $row['owed'] > 0 ? esc_html( $currency . ' ' . number_format( $row['owed'], 2 ) ) : '—'; ?>
                            </td>
                            <td class="td-amount" style="color:#e67e22;">
                                <?php echo $row['owes'] > 0 ? esc_html( $currency . ' ' . number_format( $row['owes'], 2 ) ) : '—'; ?>
                            </td>
                            <td class="td-amount" style="<?php echo $net >= 0 ? 'color:#27ae60;' : 'color:#dc3232;'; ?>">
                                <?php echo $net >= 0 ? '+' : '-'; ?><?php echo esc_html( $currency . ' ' . number_format( abs( $net ), 2 ) ); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else : ?>
                <div class="splitwise-empty">No balances with other users yet. Add an expense to get started!</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Expenses You Paid -->
        <div class="splitwise-section">
            <h2 class="splitwise-section-title">Expenses You Paid</h2>
            <div class="splitwise-table-wrap">
                <?php if ( ! empty( $paid_expenses ) ) : ?>
                <table class="splitwise-table">
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th>Split With</th>
                            <th>Date</th>
                            <th style="text-align:right;">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $paid_expenses as $expense ) :
                            $split_members = Splitwise_Expenses::get_split_members( $expense->id );
                            $split_names   = [];

                            foreach ( $split_members as $member ) {
                                if ( intval( $member->user_id ) === get_current_user_id() ) {
                                    continue;
                                }
                                $u = get_userdata( $member->user_id );
                                if ( $u ) {
                                    $split_names[] = $u->display_name;
                                }
                            }

                            $expense_date = date_i18n( get_option( 'date_format' ), strtotime( $expense->date ) );
                        ?>
                        <tr>
                            <td class="td-desc">
                                <strong><?php echo esc_html( $expense->description ); ?></strong>
                                <?php if ( count( $split_members ) > 0 ) : ?>
                                <small><?php echo esc_html( count( $split_members ) ); ?> people</small>
                                <?php endif; ?>
                            </td>
                            <td style="color:#94a3b8; font-size:13px;">
                                <?php echo esc_html( implode( ', ', $split_names ) ); ?>
                            </td>
                            <td class="td-date"><?php echo esc_html( $expense_date ); ?></td>
                            <td class="td-amount paid">
                                <?php echo esc_html( $currency ); ?> <?php echo number_format( floatval( $expense->amount ), 2 ); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else : ?>
                <div class="splitwise-empty">You haven't paid for any expenses yet.</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Expenses You Owe -->
        <div class="splitwise-section">
            <h2 class="splitwise-section-title">Expenses You Owe</h2>
            <div class="splitwise-table-wrap">
                <?php if ( ! empty( $owed_expenses ) ) : ?>
                <table class="splitwise-table">
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th>Paid By</th>
                            <th>Date</th>
                            <th style="text-align:right;">Your Share</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $owed_expenses as $expense ) :
                            $paid_by_user = get_userdata( $expense->paid_by );
                            $paid_by_name = $paid_by_user ? $paid_by_user->display_name : 'Unknown';
                            $expense_date = date_i18n( get_option( 'date_format' ), strtotime( $expense->date ) );
                            $your_share   = isset( $expense->share_amount ) ? floatval( $expense->share_amount ) : 0;
                        ?>
                        <tr>
                            <td class="td-desc">
                                <strong><?php echo esc_html( $expense->description ); ?></strong>
                            </td>
                            <td><?php echo esc_html( $paid_by_name ); ?></td>
                            <td class="td-date"><?php echo esc_html( $expense_date ); ?></td>
                            <td class="td-amount owed">
                                <?php echo esc_html( $currency ); ?> <?php echo number_format( $your_share, 2 ); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else : ?>
                <div class="splitwise-empty">You don't owe anything. You're all clear!</div>
                <?php endif; ?>
            </div>
        </div>

    </div><!-- .splitwise-container -->
</div><!-- .splitwise-wrapper -->