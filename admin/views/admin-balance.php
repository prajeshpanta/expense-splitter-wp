<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Admin View: My Balance
 */
?>
<div class="wrap">
    <h1><?php esc_html_e( 'My Balance', 'splitwise-wp' ); ?></h1>

    <!-- Net Balance Banner -->
    <div class="splitwise-balance-banner" 
         style="border-left-color: <?php echo $balances['net'] >= 0 ? '#46b450' : '#dc3232'; ?>;">
        
        <div class="splitwise-balance-main">
            <p class="splitwise-balance-label">
                <?php 
                if ( $balances['net'] > 0 ) {
                    esc_html_e( 'Others owe you', 'splitwise-wp' );
                } elseif ( $balances['net'] < 0 ) {
                    esc_html_e( 'You owe', 'splitwise-wp' );
                } else {
                    esc_html_e( 'All are Settled', 'splitwise-wp' );
                }
                ?>
            </p>
            <p class="splitwise-balance-amount">
                Rs <?php echo esc_html( number_format( abs( $balances['net'] ), 2 ) ); ?>
            </p>
        </div>

        <div class="splitwise-balance-summary">
            <div>
                <span class="splitwise-small-label"><?php esc_html_e( 'You Owe', 'splitwise-wp' ); ?></span>
                <span class="splitwise-owe-amount">
                    Rs <?php echo esc_html( number_format( $balances['owes'], 2 ) ); ?>
                </span>
            </div>
            <div>
                <span class="splitwise-small-label"><?php esc_html_e( 'Owed To You', 'splitwise-wp' ); ?></span>
                <span class="splitwise-owed-amount">
                    Rs <?php echo esc_html( number_format( $balances['owed'], 2 ) ); ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <p style="margin: 20px 0;">
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=splitwise-add-expense' ) ); ?>" 
           class="button button-primary">
           <?php esc_html_e( '+ Add New Expense', 'splitwise-wp' ); ?>
        </a>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=splitwise' ) ); ?>"
           class="button">
           <?php esc_html_e( '← Back to Dashboard', 'splitwise-wp' ); ?>
        </a>
    </p>

    <!-- Per Person Breakdown -->
    <h2><?php esc_html_e( 'Breakdown by Person', 'splitwise-wp' ); ?></h2>

    <?php if ( empty( $detailed_with_names ) ) : ?>
        <div class="notice notice-info">
            <p><?php esc_html_e( 'No balances with other users yet. Add an expense to get started!', 'splitwise-wp' ); ?></p>
        </div>
    <?php else : ?>
        <table class="widefat striped" style="max-width: 800px;">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Person', 'splitwise-wp' ); ?></th>
                    <th style="text-align:right;"><?php esc_html_e( 'They Owe You', 'splitwise-wp' ); ?></th>
                    <th style="text-align:right;"><?php esc_html_e( 'You Owe Them', 'splitwise-wp' ); ?></th>
                    <th style="text-align:right;"><?php esc_html_e( 'Net', 'splitwise-wp' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $detailed_with_names as $row ) : 
                    $net = $row['owed'] - $row['owes'];
                ?>
                    <tr>
                        <td><strong><?php echo esc_html( $row['name'] ); ?></strong></td>

                        <td style="text-align:right;">
                            <?php if ( $row['owed'] > 0 ) : ?>
                                <span style="color:#0073aa; font-weight:600;">
                                    Rs <?php echo esc_html( number_format( $row['owed'], 2 ) ); ?>
                                </span>
                            <?php else : ?>
                                <span style="color:#c3c4c7;">—</span>
                            <?php endif; ?>
                        </td>

                        <td style="text-align:right;">
                            <?php if ( $row['owes'] > 0 ) : ?>
                                <span style="color:#d63638; font-weight:600;">
                                    Rs <?php echo esc_html( number_format( $row['owes'], 2 ) ); ?>
                                </span>
                            <?php else : ?>
                                <span style="color:#c3c4c7;">—</span>
                            <?php endif; ?>
                        </td>

                        <td style="text-align:right; font-weight:700;">
                            <?php if ( $net > 0 ) : ?>
                                <span style="color:#46b450;">+Rs <?php echo esc_html( number_format( $net, 2 ) ); ?></span>
                            <?php elseif ( $net < 0 ) : ?>
                                <span style="color:#dc3232;">-Rs <?php echo esc_html( number_format( abs( $net ), 2 ) ); ?></span>
                            <?php else : ?>
                                <span style="color:#8c8f94;"><?php esc_html_e( 'Settled', 'splitwise-wp' ); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>

            <tfoot>
                <tr style="background:#f6f7f7; font-weight:600;">
                    <th><?php esc_html_e( 'Total', 'splitwise-wp' ); ?></th>
                    <th style="text-align:right; color:#0073aa;">
                        Rs <?php echo esc_html( number_format( $balances['owed'], 2 ) ); ?>
                    </th>
                    <th style="text-align:right; color:#d63638;">
                        Rs <?php echo esc_html( number_format( $balances['owes'], 2 ) ); ?>
                    </th>
                    <th style="text-align:right; color:<?php echo $balances['net'] >= 0 ? '#46b450' : '#dc3232'; ?>;">
                        <?php echo $balances['net'] >= 0 ? '+' : '-'; ?>Rs 
                        <?php echo esc_html( number_format( abs( $balances['net'] ), 2 ) ); ?>
                    </th>
                </tr>
            </tfoot>
        </table>
    <?php endif; ?>
</div>