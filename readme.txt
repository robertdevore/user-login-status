=== User Login Status ===
Contributors: deviodigital
Donate link: https://robertdevore.com/
Tags: user, login, login-status, user-control
Requires at least: 4.7
Tested up to: 6.6.2
Stable tag: 1.1.0
Requires PHP: 7.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Easily view which users are online/offline and log them out en-masse directly from the Users admin table.

== Description ==

The **User Login Status** plugin enables administrators to view and manage user login statuses directly from the WordPress admin dashboard. With this plugin, you can easily see which users are logged in or out and, if necessary, log out multiple users at once using the bulk action feature.

### Key Features
- 🟢 **Monitor Login Status**: Quickly see whether a user is logged in or out via color-coded icons (green = online, red = offline) in the Users table.
- 🚪 **Bulk Log Out Users**: Select multiple users and log them out simultaneously using the bulk action dropdown in the Users table.
- 🔄 **Real-Time Updates**: User statuses are updated via AJAX every 30 seconds to reflect real-time login activity.
- 🔒 **Secure AJAX Requests**: The plugin uses nonces to ensure that AJAX requests are secure and valid.

== Changelog ==

= 1.1.0 =
*   Added `PluginUpdateChecker` to serve plugin updates directly from GitHub instead of wp.org in `user-login-status.php`
*   Added `.pot` file for language translation in `languages/user-login-status.pot`

= 1.0.1 =
*   Updated the ajax calls to include all users in a single call for peformance improvements in `user-login-status.php`
*   Updated the JS for ajax calls to include all users in a single call for performance improvements in `user-login-status.js`
*   Updated status check to also look for unexpired `session_tokens` in `user-login-status.php`

= 1.0.0 =
*   Initial release
