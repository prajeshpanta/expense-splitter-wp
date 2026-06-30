<?php
/**
 * Splitwise WP – Admin Panel
 *
 * Handles all WordPress admin pages:
 *   • Dashboard   (Splitwise → dashboard)
 *   • Add Expense (Splitwise → Add Expense)
 *   • My Balance  (Splitwise → My Balance)
 *
 * @package Splitwise_WP
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Splitwise_Admin {

    private $wpdb;
    private $expenses_table;
    private $splits_table;
    private $currency;

    public function __construct() {
        global $wpdb;
        $this->wpdb           = $wpdb;
        $this->expenses_table = $wpdb->prefix . 'splitwise_expenses';
        $this->splits_table   = $wpdb->prefix . 'splitwise_expense_splits';
        $this->currency       = splitwise_get_currency_symbol();
    }

    /** Register hooks */
    public function init() {
        add_action( 'admin_menu',            [ $this, 'register_menus' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
    }

    /** ── Admin Menu ─────────────────────────────────────────── */
    public function register_menus() {
        add_menu_page(
            'Splitwise',
            'Splitwise',
            'read',
            'splitwise-dashboard',
            [ $this, 'page_dashboard' ],
            'dashicons-money-alt',
            30
        );

        add_submenu_page(
            'splitwise-dashboard',
            'Dashboard',
            'dashboard',
            'read',
            'splitwise-dashboard',
            [ $this, 'page_dashboard' ]
        );

        add_submenu_page(
            'splitwise-dashboard',
            'Add Expense',
            'Add Expense',
            'read',
            'splitwise-add-expense',
            [ $this, 'page_add_expense' ]
        );

        add_submenu_page(
            'splitwise-dashboard',
            'My Balance',
            'My Balance',
            'read',
            'splitwise-my-balance',
            [ $this, 'page_my_balance' ]
        );
    }

    /** ── Enqueue CSS ────────────────────────────────────────── */
    public function enqueue_assets( $hook ) {
        $splitwise_hooks = [
            'toplevel_page_splitwise-dashboard',
            'splitwise_page_splitwise-add-expense',
            'splitwise_page_splitwise-my-balance',
        ];

        if ( in_array( $hook, $splitwise_hooks ) ) {
            wp_enqueue_style(
                'splitwise-admin',
                plugin_dir_url( __FILE__ ) . 'css/splitwise-admin.css',
                [],
                '1.1.2'
            );
        }
    }

    /** ── Helpers ────────────────────────────────────────────── */

    private function money( $amount ) {
        return esc_html( $this->currency ) . ' ' . number_format( floatval( $amount ), 2 );
    }

    /**
     * Get balance summary for a user.
     *
     * wp_splitwise_expenses : user_id = who paid
     * wp_splitwise_expense_splits : user_id = each member, share_amount = their share
     */
    private function get_balance( $user_id ) {

        // Total amount of expenses THIS user paid
        $total_paid = (float) $this->wpdb->get_var( $this->wpdb->prepare(
            "SELECT COALESCE(SUM(amount), 0)
             FROM {$this->expenses_table}
             WHERE user_id = %d",
            $user_id
        ) );

        // Total this user owes others:
        // sum of share_amount on splits where expense was paid by someone else
        $total_owed = (float) $this->wpdb->get_var( $this->wpdb->prepare(
            "SELECT COALESCE(SUM(s.share_amount), 0)
             FROM {$this->splits_table} s
             JOIN {$this->expenses_table} e ON s.expense_id = e.id
             WHERE s.user_id = %d AND e.user_id != %d",
            $user_id, $user_id
        ) );

        // Total others owe this user:
        // sum of share_amount on splits for expenses paid by this user, excluding their own share
        $owed_to_user = (float) $this->wpdb->get_var( $this->wpdb->prepare(
            "SELECT COALESCE(SUM(s.share_amount), 0)
             FROM {$this->splits_table} s
             JOIN {$this->expenses_table} e ON s.expense_id = e.id
             WHERE e.user_id = %d AND s.user_id != %d",
            $user_id, $user_id
        ) );

        return [
            'total_paid'   => $total_paid,
            'total_owed'   => $total_owed,
            'owed_to_user' => $owed_to_user,
            'net'          => $owed_to_user - $total_owed,
        ];
    }

    /** Get recent expenses involving this user */
    private function get_recent_expenses( $user_id, $limit = 10 ) {
        return $this->wpdb->get_results( $this->wpdb->prepare(
            "SELECT DISTINCT e.*
             FROM {$this->expenses_table} e
             LEFT JOIN {$this->splits_table} s ON s.expense_id = e.id
             WHERE e.user_id = %d OR s.user_id = %d
             ORDER BY e.created_at DESC
             LIMIT %d",
            $user_id, $user_id, $limit
        ) );
    }

    /** Per-person breakdown for My Balance page */
    private function get_person_breakdown( $user_id ) {
        $users = get_users( [ 'exclude' => [ $user_id ] ] );
        $rows  = [];

        foreach ( $users as $other ) {
            $oid = $other->ID;

            // They owe me: their share on expenses I paid
            $they_owe = (float) $this->wpdb->get_var( $this->wpdb->prepare(
                "SELECT COALESCE(SUM(s.share_amount), 0)
                 FROM {$this->splits_table} s
                 JOIN {$this->expenses_table} e ON s.expense_id = e.id
                 WHERE e.user_id = %d AND s.user_id = %d",
                $user_id, $oid
            ) );

            // I owe them: my share on expenses they paid
            $i_owe = (float) $this->wpdb->get_var( $this->wpdb->prepare(
                "SELECT COALESCE(SUM(s.share_amount), 0)
                 FROM {$this->splits_table} s
                 JOIN {$this->expenses_table} e ON s.expense_id = e.id
                 WHERE e.user_id = %d AND s.user_id = %d",
                $oid, $user_id
            ) );

            if ( $they_owe > 0 || $i_owe > 0 ) {
                $rows[] = [
                    'name'     => $other->display_name,
                    'they_owe' => $they_owe,
                    'i_owe'    => $i_owe,
                    'net'      => $they_owe - $i_owe,
                ];
            }
        }

        return $rows;
    }

    /** ── PAGE: Dashboard ────────────────────────────────────── */
    public function page_dashboard() {
        $user_id     = get_current_user_id();
        $balance     = $this->get_balance( $user_id );
        $expenses    = $this->get_recent_expenses( $user_id );

        $net         = $balance['net'];
        $net_label   = $net >= 0 ? 'Others owe you' : 'You owe others';
        $net_class   = $net >= 0 ? 'green' : 'orange';

        $add_url     = admin_url( 'admin.php?page=splitwise-add-expense' );
        $balance_url = admin_url( 'admin.php?page=splitwise-my-balance' );
        ?>
        <div class="sw-admin-wrap">

            <div class="sw-page-header">
                <h1>Dashboard</h1>
                <p>Overview of your expenses and balance</p>
            </div>

            <div class="sw-stats-grid">
                <div class="sw-stat-card <?php echo esc_attr( $net_class ); ?>">
                    <div class="sw-stat-label">Net Balance</div>
                    <div class="sw-stat-value <?php echo esc_attr( $net_class ); ?>">
                        <?php echo $this->money( abs( $net ) ); ?>
                    </div>
                    <div class="sw-stat-sub"><?php echo esc_html( $net_label ); ?></div>
                </div>

                <div class="sw-stat-card blue">
                    <div class="sw-stat-label">Total Paid</div>
                    <div class="sw-stat-value blue"><?php echo $this->money( $balance['total_paid'] ); ?></div>
                    <div class="sw-stat-sub">You paid upfront</div>
                </div>

                <div class="sw-stat-card orange">
                    <div class="sw-stat-label">Total Owed</div>
                    <div class="sw-stat-value orange"><?php echo $this->money( $balance['total_owed'] ); ?></div>
                    <div class="sw-stat-sub">Your share of splits</div>
                </div>
            </div>

            <div class="sw-actions">
                <a href="<?php echo esc_url( $add_url ); ?>"     class="sw-btn sw-btn-primary">+ Add Expense</a>
                <a href="<?php echo esc_url( $balance_url ); ?>" class="sw-btn sw-btn-secondary">View Full Balance</a>
            </div>

            <div class="sw-section">
                <h2 class="sw-section-title">Recent Activity</h2>
                <div class="sw-table-wrap">
                    <?php if ( ! empty( $expenses ) ) : ?>
                    <table class="sw-table">
                        <thead>
                            <tr>
                                <th>Description</th>
                                <th>Paid By</th>
                                <th>Date</th>
                                <th class="right">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $expenses as $exp ) :
                                $paid_by  = get_userdata( $exp->user_id );
                                $name     = $paid_by ? $paid_by->display_name : 'Unknown';
                                $date_col = ! empty( $exp->date )
                                    ? date( 'F j, Y', strtotime( $exp->date ) )
                                    : date( 'F j, Y', strtotime( $exp->created_at ) );
                            ?>
                            <tr>
                                <td><?php echo esc_html( $exp->description ); ?></td>
                                <td><?php echo esc_html( $name ); ?></td>
                                <td style="color:#64748b;"><?php echo esc_html( $date_col ); ?></td>
                                <td class="right blue"><?php echo $this->money( $exp->amount ); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php else : ?>
                    <div class="sw-empty">No expenses yet. Add your first expense to get started!</div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
        <?php
    }

    /** ── PAGE: Add Expense ──────────────────────────────────── */
    public function page_add_expense() {
        $user_id      = get_current_user_id();
        $current_user = wp_get_current_user();
        $currency     = $this->currency;
        $notice       = '';

        if ( isset( $_POST['sw_nonce'] ) && wp_verify_nonce( $_POST['sw_nonce'], 'sw_add_expense' ) ) {

            $amount      = isset( $_POST['amount'] )      ? floatval( $_POST['amount'] )               : 0;
            $description = isset( $_POST['description'] ) ? sanitize_text_field( $_POST['description'] ) : '';
            $date        = isset( $_POST['date'] )        ? sanitize_text_field( $_POST['date'] )        : current_time( 'Y-m-d' );
            $split_with  = isset( $_POST['split_with'] )  ? array_map( 'intval', $_POST['split_with'] ) : [];

            if ( $amount <= 0 ) {
                $notice = '<div class="sw-notice error">Please enter a valid amount greater than 0.</div>';
            } elseif ( empty( $description ) ) {
                $notice = '<div class="sw-notice error">Please enter a description.</div>';
            } elseif ( empty( $split_with ) ) {
                $notice = '<div class="sw-notice error">Please select at least one person to split with.</div>';
            } else {
                // All members = payer + selected users
                $all_members = array_unique( array_merge( [ $user_id ], $split_with ) );
                $per_person  = round( $amount / count( $all_members ), 2 );

                // Insert into wp_splitwise_expenses
                $inserted = $this->wpdb->insert(
                    $this->expenses_table,
                    [
                        'user_id'     => $user_id,
                        'description' => $description,
                        'amount'      => $amount,
                        'date'        => $date,
                        'created_at'  => current_time( 'mysql' ),
                    ],
                    [ '%d', '%s', '%f', '%s', '%s' ]
                );

                if ( $inserted ) {
                    $expense_id = $this->wpdb->insert_id;

                    // Insert into wp_splitwise_expense_splits for each member
                    foreach ( $all_members as $mid ) {
                        $this->wpdb->insert(
                            $this->splits_table,
                            [
                                'expense_id'   => $expense_id,
                                'user_id'      => $mid,
                                'share_amount' => $per_person,
                                'paid_amount'  => ( $mid === $user_id ) ? $amount : 0.00,
                            ],
                            [ '%d', '%d', '%f', '%f' ]
                        );
                    }

                    $notice = '<div class="sw-notice success">✓ Expense "' . esc_html( $description ) . '" added! Each person owes ' . $this->money( $per_person ) . '.</div>';
                    $_POST  = [];
                } else {
                    $notice = '<div class="sw-notice error">Database error. Please try again.</div>';
                }
            }
        }

        $all_users     = get_users( [ 'exclude' => [ $user_id ], 'orderby' => 'display_name' ] );
        $dashboard_url = admin_url( 'admin.php?page=splitwise-dashboard' );
        ?>
        <div class="sw-admin-wrap">

            <div class="sw-page-header">
                <h1>Add Expense</h1>
                <p>Fill in the details and choose who to split with</p>
            </div>

            <?php echo $notice; ?>

            <div class="sw-form-card">

                <div class="sw-info-banner">
                    You (<strong><?php echo esc_html( $current_user->display_name ); ?></strong>) are paying.
                    You will always be included in the split.
                </div>

                <form method="post">
                    <?php wp_nonce_field( 'sw_add_expense', 'sw_nonce' ); ?>

                    <!-- Amount -->
                    <div class="sw-form-row">
                        <label for="sw-amount">Total Amount (<?php echo esc_html( $currency ); ?>)</label>
                        <div class="sw-amount-wrap">
                            <span class="sw-prefix"><?php echo esc_html( $currency ); ?></span>
                            <input type="number" id="sw-amount" name="amount"
                                min="0.01" step="0.01" placeholder="0.00"
                                value="<?php echo isset( $_POST['amount'] ) ? esc_attr( $_POST['amount'] ) : ''; ?>">
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="sw-form-row">
                        <label for="sw-desc">Description</label>
                        <input type="text" id="sw-desc" name="description"
                            placeholder="e.g. Dinner, Movie, Groceries"
                            value="<?php echo isset( $_POST['description'] ) ? esc_attr( $_POST['description'] ) : ''; ?>">
                    </div>

                    <!-- Date -->
                    <div class="sw-form-row">
                        <label for="sw-date">Date</label>
                        <input type="date" id="sw-date" name="date"
                            value="<?php echo isset( $_POST['date'] ) ? esc_attr( $_POST['date'] ) : current_time( 'Y-m-d' ); ?>">
                    </div>

                    <!-- Split With -->
                    <div class="sw-form-row">
                        <label>Split With</label>
                        <div class="sw-split-preview" id="sw-preview">
                            Each person pays: <strong id="sw-per-person">—</strong>
                        </div>
                        <?php if ( ! empty( $all_users ) ) : ?>
                        <div class="sw-split-grid" id="sw-split-grid">
                            <?php foreach ( $all_users as $u ) :
                                $checked = isset( $_POST['split_with'] ) && in_array( $u->ID, array_map( 'intval', $_POST['split_with'] ) );
                            ?>
                            <label class="sw-split-item <?php echo $checked ? 'checked' : ''; ?>">
                                <input type="checkbox" name="split_with[]"
                                    value="<?php echo esc_attr( $u->ID ); ?>"
                                    <?php checked( $checked ); ?>>
                                <?php echo esc_html( $u->display_name ); ?>
                            </label>
                            <?php endforeach; ?>
                        </div>
                        <?php else : ?>
                        <p style="color:#94a3b8;font-size:14px;">No other users found.</p>
                        <?php endif; ?>
                    </div>

                    <button type="submit" class="sw-submit">Add Expense</button>
                </form>
            </div>

        </div>

        <script>
        (function(){
            var amt   = document.getElementById('sw-amount');
            var grid  = document.getElementById('sw-split-grid');
            var prev  = document.getElementById('sw-preview');
            var label = document.getElementById('sw-per-person');
            var cur   = '<?php echo esc_js( $currency ); ?>';

            function update() {
                var amount = parseFloat(amt ? amt.value : 0) || 0;
                var count  = 1;
                if (grid) {
                    grid.querySelectorAll('input[type="checkbox"]').forEach(function(cb){
                        cb.closest('.sw-split-item').classList.toggle('checked', cb.checked);
                        if (cb.checked) count++;
                    });
                }
                if (amount > 0 && count > 1) {
                    label.textContent = cur + ' ' + (amount / count).toFixed(2);
                    prev.classList.add('visible');
                } else {
                    prev.classList.remove('visible');
                }
            }

            if (amt)  amt.addEventListener('input', update);
            if (grid) grid.addEventListener('change', update);
            update();
        })();
        </script>
        <?php
    }

    /** ── PAGE: My Balance ───────────────────────────────────── */
    public function page_my_balance() {
        $user_id   = get_current_user_id();
        $balance   = $this->get_balance( $user_id );
        $breakdown = $this->get_person_breakdown( $user_id );

        $net       = $balance['net'];
        $net_label = $net >= 0 ? 'Others owe you' : 'You owe others';

        $add_url  = admin_url( 'admin.php?page=splitwise-add-expense' );
        $dash_url = admin_url( 'admin.php?page=splitwise-dashboard' );

        $total_they_owe = array_sum( array_column( $breakdown, 'they_owe' ) );
        $total_i_owe    = array_sum( array_column( $breakdown, 'i_owe' ) );
        $total_net      = $total_they_owe - $total_i_owe;
        ?>
        <div class="sw-admin-wrap">

            <div class="sw-page-header">
                <h1>My Balance</h1>
                <p>Full breakdown of what you paid and what you owe</p>
            </div>

            <!-- Balance Summary Card -->
            <div class="sw-balance-summary">
                <div>
                    <div class="sw-balance-main-label"><?php echo esc_html( $net_label ); ?></div>
                    <div class="sw-balance-main-value"><?php echo $this->money( abs( $net ) ); ?></div>
                </div>
                <div class="sw-balance-stats">
                    <div class="sw-balance-stat">
                        <div class="sw-balance-stat-label">Total Paid</div>
                        <div class="sw-balance-stat-value blue"><?php echo $this->money( $balance['total_paid'] ); ?></div>
                    </div>
                    <div class="sw-balance-stat">
                        <div class="sw-balance-stat-label">Total Owed</div>
                        <div class="sw-balance-stat-value orange"><?php echo $this->money( $balance['total_owed'] ); ?></div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="sw-actions">
                <a href="<?php echo esc_url( $add_url ); ?>"  class="sw-btn sw-btn-primary">+ Add New Expense</a>
                <a href="<?php echo esc_url( $dash_url ); ?>" class="sw-btn sw-btn-secondary">← Back to Dashboard</a>
            </div>

            <!-- Breakdown by Person -->
            <div class="sw-section">
                <h2 class="sw-section-title">Breakdown by Person</h2>
                <div class="sw-table-wrap">
                    <?php if ( ! empty( $breakdown ) ) : ?>
                    <table class="sw-table">
                        <thead>
                            <tr>
                                <th>Person</th>
                                <th class="right">They Owe You</th>
                                <th class="right">You Owe Them</th>
                                <th class="right">Net</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ( $breakdown as $row ) :
                                $net_val   = $row['net'];
                                $net_class = $net_val > 0 ? 'green' : ( $net_val < 0 ? 'red' : 'muted' );
                                $net_sign  = $net_val >= 0 ? '+' : '';
                            ?>
                            <tr>
                                <td><?php echo esc_html( $row['name'] ); ?></td>
                                <td class="right <?php echo $row['they_owe'] > 0 ? 'blue' : 'muted'; ?>">
                                    <?php echo $row['they_owe'] > 0 ? $this->money( $row['they_owe'] ) : '—'; ?>
                                </td>
                                <td class="right <?php echo $row['i_owe'] > 0 ? 'red' : 'muted'; ?>">
                                    <?php echo $row['i_owe'] > 0 ? $this->money( $row['i_owe'] ) : '—'; ?>
                                </td>
                                <td class="right <?php echo esc_attr( $net_class ); ?>">
                                    <?php echo $net_sign . $this->money( $net_val ); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td><strong>Total</strong></td>
                                <td class="right blue"><strong><?php echo $this->money( $total_they_owe ); ?></strong></td>
                                <td class="right <?php echo $total_i_owe > 0 ? 'red' : ''; ?>">
                                    <strong><?php echo $this->money( $total_i_owe ); ?></strong>
                                </td>
                                <td class="right <?php echo $total_net >= 0 ? 'green' : 'red'; ?>">
                                    <strong><?php echo ( $total_net >= 0 ? '+' : '' ) . $this->money( $total_net ); ?></strong>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                    <?php else : ?>
                    <div class="sw-empty">No shared expenses found. Add an expense to see your breakdown.</div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
        <?php
    }
}