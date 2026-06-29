<?php
/**Resgister the admin menu page and handles the admin-side rendering
 * Responsible for integrating our plugin with Wordpress Admin Dashboard.
*/

class Splitwise_Admin{
    //Intializing the admin hooks
    public function init(){ //this function is called from main plugin file to 
                            //intialize the admin functionality.

        add_action('admin_menu', [$this, 'register_menu']);//hooks into wordpress 
                                                           //to register the admin menu.

        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);//hooks into wordpress
        //to load CSS and JS file

    }

    //function to create the menu in wordpress admin sidebar.
    public function register_menu(){
        add_menu_page(
            __('Splitwise', 'splitwise-wp'),//Page Title appear in browser tab.
            __('Splitwise', 'splitwise-wp'),//menu title appear in admin sidebar.
            'read', //capability i.e. any logged-in user can access the pllugin.
            'splitwise', //it is unique slog( URL identifier)
            [$this, 'render_dashboard_page'], //callback function that will render 
                                                //the pages.
            'dashicons-money-alt', //incon for menu.
            26 //it is the positio in the admin menu.
        );

        //submenu page for dashboard
        add_submenu_page(
            'splitwise', //Page Slug Title
            'dashboard', //page title
            'dashboard', //menu title
            'read', //capability (who can access)
            'splitwise', //menu slug
            [$this, 'render_dashboard_page'] //callback function
        );

        //submenu page for Add Expense
        add_submenu_page(
            'splitwise', 
            'Add Expense', 
            'Add Expense', 'read', 
            'splitwise-add-expense', 
            [$this, 'render_add_expense_page']
        );
        
        //submenu page for My Balance
        add_submenu_page(
            'splitwise', 
            'My Balance', 
            'My Balance', 
            'read', 
            'splitwise-balance', 
            [$this, 'render_balance_page']
        );    
    }

    /**Enqueue admin assets only on admin page*/
    public function enqueue_assets($hook){
        $allowed_pages = [
            'toplevel_page_splitwise',
            'splitwise_page_splitwise-add-expense',
            'splitwise_page_splitwise-balance',
        ];
        if(! in_array($hook, $allowed_pages, true)){
            return;
        }
        wp_enqueue_style('splitwise-admin', 
        SPLITWISE_WP_PLUGIN_URL . 'assets/css/splitwise-admin.css', [],
        SPLITWISE_WP_VERSION
        );

        wp_enqueue_script('splitwise-admin',
        SPLITWISE_WP_PLUGIN_URL . 'assets/js/splitwise-admin.js',
        [],
        SPLITWISE_WP_VERSION,
        true
        );
    }

    // ==================================================================
    // Page Renderers
    // ==================================================================
    public function render_dashboard_page(){
        if(! current_user_can('read')){
            wp_die(esc_html__('Yo do not have permission to access this page.', 'splitwise-wp'));
        }

        $balances = Splitwise_Balance::get_user_balances();
        $expenses = Splitwise_Expenses::get_expenses(['limit' => 10]);

        include SPLITWISE_WP_PLUGIN_DIR . 'admin/views/admin-dashboard.php';
    }

    public function render_add_expense_page(){
        if(! current_user_can('read')){
            wp_die(esc_html__('You do not have permission to access this page.', 'splitwise-wp'));
        }
        $error = '';
        $success = '';

        //handle form submission
        if(isset($_POST['splitwise_add_expense_submit'])){
            $result = $this->process_add_expense_form();

            if($result['success']){
                $success = $result['message'];
            }
            else{
                $error = $result['message'];
            }
        }
        $current_user_id = get_current_user_id();
        $other_users = get_users([
            'exclude'=>[$current_user_id],
            'orderby'=>'display_name',
            'order'=> 'ASC'    
        ]);
        include SPLITWISE_WP_PLUGIN_DIR . 'admin/views/admin-add-expense.php';
    }

    /**Process add expense form - Can be reused by shortcode too in future. */
    private function process_add_expense_form(){
        if(! isset($_POST['splitwise_add_expense_nonce']) || 
        !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['splitwise_add_expense_nonce'])), 'splitwise_add_expense_action')){
            return[ 'success'=>false, 'message'=> __('Security check failed', 'splitwise-wp')];
        }

        $description = isset($_POST['description']) ? sanitize_text_field(wp_unslash($_POST['description'])) : '';
        $amount = isset($_POST['amount']) ? floatval(wp_unslash($_POST['amount'])) : 0;
        $date = isset($_POST['date']) ? sanitize_text_field(wp_unslash($_POST['date'])) : current_time('Y-m-d');

        $selected_users = [];
        if(! empty($_POST['users']) && is_array($_POST['users'])){
            $selected_users = array_map('absint', wp_unslash($_POST['users']));
        }

        if(empty($description)){
            return [
            'success'=> false,
            'message'=> __('Please enter a description.', 'splitwise-wp')
            ];
        }

        if($amount<=0){
            return [
                'success'=> false,
                'message'=> __('Please enter a valid positive amount.', 'splitwise-wp')
            ];
        }

        if(empty($selected_users)){
            return [
                'success'=> false,
                'message'=> __('Please select at least one person.', 'splitwise-wp')
            ];
        }

        $current_user_id = get_current_user_id();
        $participant_ids = array_unique(array_merge($selected_users, [$current_user_id]));
        $share_amount = round($amount/count($participant_ids), 2);

        $splits = [];
        foreach ($participant_ids as $uid){
            $splits[] = [
                'user_id'=>$uid,
                'share_amount'=>$share_amount,
                'paid_amount'=> ($uid == $current_user_id) ? $amount: 0,
            ];
        }

        return Splitwise_Expenses::add_expense([
            'description'=>$description,
            'amount'=>$amount,
            'date'=>$date,
            'splits'=>$splits,
        ]);
    }

    public function render_balance_page(){
        if(! current_user_can('read')){
            wp_die(esc_html__('You do not have permission to access this page.', 'splitwise-wp'));
        }

        $current_user_id = get_current_user_id();
        $balances = Splitwise_Balance::get_user_balances($current_user_id);
        $detailed = Splitwise_Balance::get_detailed_balances($current_user_id);

        //Add user name
        $detailed_with_names = [];
        foreach($detailed as $other_user_id => $amounts){
            $user = get_userdata($other_user_id);
            $detailed_with_names[]=[
                'user_id'=>$other_user_id,
                'name'=>$user ? $user->display_name: __('Unknown User', 'splitwise-wp'),
                'owes'=> $amounts['owes'] ?? 0,
                'owed'=> $amounts['owed'] ?? 0,
            ];
        }

        include SPLITWISE_WP_PLUGIN_DIR . 'admin/views/admin-balance.php';
    }
}