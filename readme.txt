=== Splitwise WP ===
Contributors: prajeshpanta
Donate link: 
Tags: expenses, splitwise, group expenses, money sharing, balance, split bill
Requires at least: 6.0
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 1.1.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Split group expenses easily. Track who owes whom with beautiful dashboards and real-time balance updates.

== Description ==

**Splitwise WP** is a powerful yet simple WordPress plugin that lets you manage shared group expenses just like Splitwise.

Perfect for roommates, friends, families, travel groups, or office teams.

**Key Features:**

* Add expenses and split with multiple people
* Real-time balance calculations
* Beautiful frontend dashboards
* Admin panel for full control
* Responsive design for mobile and desktop
* Live split preview while adding expenses
* Full per-person breakdown
* Supports multiple currencies (filterable)

**Frontend Shortcodes:**

* `[splitwise_dashboard]` – User dashboard
* `[splitwise_balance]` – Detailed balance breakdown
* `[splitwise_add_expense]` – Add new expense form

== Installation ==

1. Upload the `splitwise-wp` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Create necessary pages and add the shortcodes:
   - Dashboard → `[splitwise_dashboard]`
   - Add Expense → `[splitwise_add_expense]`
   - My Balance → `[splitwise_balance]`
4. Start adding expenses!

== Frequently Asked Questions ==

= Who can see the dashboards? =

Only logged-in users can see their personal balances and add expenses. Each user sees only their own data.

= Can I change the currency? =

Yes. Use the filter:
```php
add_filter('splitwise_currency_symbol', function() {
    return '$';
});