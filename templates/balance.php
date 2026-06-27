<?php
/**
 * Frontend Template: Balance
 * Rendered by [splitwise_balance] shortcode.
 *
 * Variables:
 * - $balances (array) ['owes', 'owed', 'net']
 * - $detailed (array) Per-user breakdown
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$currency = splitwise_get_currency_symbol();

// Determine banner state
$banner_modifier = 'neutral';
$balance_label   = __( 'All Settled Up', 'splitwise-wp' );

if ( $balances['net'] > 0 ) {
    $banner_modifier = 'positive';
    $balance_label   = __( 'Others Owe You', 'splitwise-wp' );
} elseif ( $balances['net'] < 0 ) {
    $banner_modifier = 'negative';
    $balance_label   = __( 'You Owe', 'splitwise-wp' );
}
?>
<div class="splitwise-wrap">

    <!-- Page Header -->
    <header class="splitwise-page-header">
        <h2><?php esc_html_e( 'My Balance', 'splitwise-wp' ); ?></h2>
        <p class="splitwise-page-sub">
            <?php esc_html_e( 'Full breakdown of what you paid and what you owe.', 'splitwise-wp' ); ?>
        </p>
    </header>

    <!-- Net Balance Banner -->
    <div class="splitwise-balance-banner splitwise-balance-banner--<?php echo esc_attr( $banner_modifier ); ?>">
        <div class="splitwise-balance-banner__main">
            <div class="splitwise-balance-banner__label">
                <?php echo esc_html( $balance_label ); ?>
            </div>
            <div class="splitwise-balance-banner__value">
                <?php echo esc_html( $currency ); ?>
                <?php echo esc_html( number_format( abs( $balances['net'] ), 2 ) ); ?>
            </div>
        </div>

        <div class="splitwise-balance-banner__meta">
            <div class="splitwise-meta-item">
                <div class="splitwise-meta-item__label"><?php esc_html_e( 'You Owe', 'splitwise-wp' ); ?></div>
                <div class="splitwise-meta-item__value splitwise-meta-item__value--owes">
                    <?php echo esc_html( $currency ); ?>
                    <?php echo esc_html( number_format( $balances['owes'], 2 ) ); ?>
                </div>
            </div>
            <div class="splitwise-meta-item">
                <div class="splitwise-meta-item__label"><?php esc_html_e( 'Owed To You', 'splitwise-wp' ); ?></div>
                <div class="splitwise-meta-item__value splitwise-meta-item__value--owed">
                    <?php echo esc_html( $currency ); ?>
                    <?php echo esc_html( number_format( $balances['owed'], 2 ) ); ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Buttons -->
    <div class="splitwise-action-bar">
        <a href="<?php echo esc_url( splitwise_get_add_expense_url() ); ?>" 
           class="splitwise-btn splitwise-btn--primary">
            <?php esc_html_e( '+ Add New Expense', 'splitwise-wp' ); ?>
        </a>
        <a href="<?php echo esc_url( splitwise_get_page_url( 'dashboard' ) ); ?>" 
           class="splitwise-btn splitwise-btn--secondary">
            <?php esc_html_e( '← Back to Dashboard', 'splitwise-wp' ); ?>
        </a>
    </div>

    <!-- Per-Person Breakdown -->
    <section class="splitwise-section">
        <div class="splitwise-section__header">
            <h3 class="splitwise-section__title">
                <?php esc_html_e( 'Breakdown by Person', 'splitwise-wp' ); ?>
            </h3>
        </div>

        <?php if ( empty( $detailed ) ) : ?>
            <div class="splitwise-empty">
                <p><?php esc_html_e( 'No balances with other users yet.', 'splitwise-wp' ); ?></p>
                <a href="<?php echo esc_url( splitwise_get_add_expense_url() ); ?>" 
                   class="splitwise-btn splitwise-btn--primary splitwise-btn--small">
                    <?php esc_html_e( 'Add Your First Expense', 'splitwise-wp' ); ?>
                </a>
            </div>
        <?php else : ?>
            <table class="splitwise-table" role="table">
                <thead>
                    <tr>
                        <th scope="col"><?php esc_html_e( 'Person', 'splitwise-wp' ); ?></th>
                        <th scope="col" class="splitwise-table__col--right"><?php esc_html_e( 'They Owe You', 'splitwise-wp' ); ?></th>
                        <th scope="col" class="splitwise-table__col--right"><?php esc_html_e( 'You Owe Them', 'splitwise-wp' ); ?></th>
                        <th scope="col" class="splitwise-table__col--right"><?php esc_html_e( 'Net', 'splitwise-wp' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $detailed as $row ) : 
                        $net = $row['owed'] - $row['owes'];
                    ?>
                        <tr>
                            <td><strong><?php echo esc_html( $row['name'] ); ?></strong></td>
                            
                            <td class="splitwise-table__col--right">
                                <?php if ( $row['owed'] > 0 ) : ?>
                                    <span class="splitwise-amount splitwise-amount--owed">
                                        <?php echo esc_html( $currency ); ?>
                                        <?php echo esc_html( number_format( $row['owed'], 2 ) ); ?>
                                    </span>
                                <?php else : ?>
                                    <span class="splitwise-amount splitwise-amount--nil">—</span>
                                <?php endif; ?>
                            </td>

                            <td class="splitwise-table__col--right">
                                <?php if ( $row['owes'] > 0 ) : ?>
                                    <span class="splitwise-amount splitwise-amount--owes">
                                        <?php echo esc_html( $currency ); ?>
                                        <?php echo esc_html( number_format( $row['owes'], 2 ) ); ?>
                                    </span>
                                <?php else : ?>
                                    <span class="splitwise-amount splitwise-amount--nil">—</span>
                                <?php endif; ?>
                            </td>

                            <td class="splitwise-table__col--right">
                                <?php if ( $net > 0 ) : ?>
                                    <span class="splitwise-amount splitwise-amount--net-positive">
                                        +<?php echo esc_html( $currency ); ?> 
                                        <?php echo esc_html( number_format( $net, 2 ) ); ?>
                                    </span>
                                <?php elseif ( $net < 0 ) : ?>
                                    <span class="splitwise-amount splitwise-amount--net-negative">
                                        -<?php echo esc_html( $currency ); ?> 
                                        <?php echo esc_html( number_format( abs( $net ), 2 ) ); ?>
                                    </span>
                                <?php else : ?>
                                    <span class="splitwise-amount splitwise-amount--nil">
                                        <?php esc_html_e( 'Settled', 'splitwise-wp' ); ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>

                <!-- Footer Total -->
                <tfoot>
                    <tr class="splitwise-table__footer">
                        <th scope="row"><?php esc_html_e( 'Total', 'splitwise-wp' ); ?></th>
                        <th scope="col" class="splitwise-table__col--right splitwise-amount--owed">
                            <?php echo esc_html( $currency ); ?> 
                            <?php echo esc_html( number_format( $balances['owed'], 2 ) ); ?>
                        </th>
                        <th scope="col" class="splitwise-table__col--right splitwise-amount--owes">
                            <?php echo esc_html( $currency ); ?> 
                            <?php echo esc_html( number_format( $balances['owes'], 2 ) ); ?>
                        </th>
                        <th scope="col" class="splitwise-table__col--right <?php echo $balances['net'] >= 0 ? 'splitwise-amount--net-positive' : 'splitwise-amount--net-negative'; ?>">
                            <?php echo $balances['net'] >= 0 ? '+' : '-'; ?>
                            <?php echo esc_html( $currency ); ?> 
                            <?php echo esc_html( number_format( abs( $balances['net'] ), 2 ) ); ?>
                        </th>
                    </tr>
                </tfoot>
            </table>
        <?php endif; ?>
    </section>
</div>