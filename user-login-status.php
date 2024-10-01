<?php

/**
 * The plugin bootstrap file.
 *
 * @link              https://robertdevore.com
 * @since             1.0.0
 * @package           User_Login_Status
 *
 * @wordpress-plugin
 *
 * Plugin Name: User Login Status
 * Description: A plugin to quickly see which users are online or offline, and log them out en masse.
 * Plugin URI:  https://robertdevore.com/
 * Version:     1.0.1
 * Author:      Robert DeVore
 * Author URI:  https://robertdevore.com/
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: user-login-status
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Add a new column in the Users admin table.
 *
 * @param array $columns Existing columns in the Users table.
 * 
 * @return array Modified columns with a "Status" column.
 */
function add_user_status_column( $columns ) {
    // Add a new column for "Status" with proper localization.
    $columns['user_status'] = esc_html__( 'Status', 'user-login-status' );
    return $columns;
}
add_filter( 'manage_users_columns', 'add_user_status_column' );

/**
 * Populate the new "Status" column with the user's status (logged in/out).
 *
 * @param string $value The custom column value (empty by default).
 * @param string $column_name The name of the column being processed.
 * @param int    $user_id The ID of the current user row.
 * 
 * @return string The HTML output of the column value.
 */
function show_user_status_column_data( $value, $column_name, $user_id ) {
    if ( 'user_status' === $column_name ) {
        // Get the user's session tokens to check if the user is logged in.
        $session_tokens = get_user_meta( $user_id, 'session_tokens', true );

        if ( ! empty( $session_tokens ) ) {
            // If there are session tokens, the user is logged in.
            return '<span class="user-status-circle user-status-online" id="user-status-' . esc_attr( $user_id ) . '" title="' . esc_attr__( 'Online', 'user-login-status' ) . '"></span>';
        } else {
            // If there are no session tokens, the user is logged out.
            return '<span class="user-status-circle user-status-offline" id="user-status-' . esc_attr( $user_id ) . '" title="' . esc_attr__( 'Offline', 'user-login-status' ) . '"></span>';
        }
    }
    
    return $value; // Return original value if not handling the user_status column.
}
add_action( 'manage_users_custom_column', 'show_user_status_column_data', 10, 3 );

/**
 * Enqueue custom CSS and JavaScript for the User Status indicator.
 *
 * @return void
 */
function enqueue_user_status_styles() {
    // Securely get the plugin directory URI for CSS and JS assets.
    $plugin_directory_uri = plugin_dir_url( __FILE__ );

    // Enqueue the CSS for user status circles (located in the plugin directory).
    wp_enqueue_style( 'user-status-style', $plugin_directory_uri . 'user-login-status.css', [], null );

    // Enqueue the JS for dynamic behavior (AJAX polling).
    wp_enqueue_script( 'user-status-js', $plugin_directory_uri . 'user-login-status.js', [ 'jquery' ], null, true );

    // Pass the AJAX URL and nonce to JavaScript.
    wp_localize_script( 'user-status-js', 'ajax_object', [
        'ajax_url'    => admin_url( 'admin-ajax.php' ),
        'nonce'       => wp_create_nonce( 'user_status_nonce' ),
    ]);
}
add_action( 'admin_enqueue_scripts', 'enqueue_user_status_styles' );

/**
 * AJAX Handler to check user status via a REST endpoint.
 *
 * @return void
 */
/**
 * AJAX Handler to check user status via a REST endpoint.
 *
 * @return void
 */
function check_user_status_ajax_handler() {
    // Verify nonce
    if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'user_status_nonce' ) ) {
        wp_send_json_error( 'Nonce verification failed', 403 );
    }

    if ( ! current_user_can( 'list_users' ) ) {
        wp_send_json_error( 'Unauthorized', 403 );
    }

    $user_ids = isset( $_POST['user_ids'] ) ? array_map( 'intval', $_POST['user_ids'] ) : [];

    if ( empty( $user_ids ) ) {
        wp_send_json_error( 'No user IDs provided', 400 );
    }

    // Create variables.
    $statuses     = [];
    $current_time = time();

    // Loop through each user and check the validity of session tokens.
    foreach ( $user_ids as $user_id ) {
        $session_tokens = get_user_meta( $user_id, 'session_tokens', true );

        if ( is_array( $session_tokens ) ) {
            // Filter tokens to find any valid (unexpired) session.
            $has_valid_session = false;
            foreach ( $session_tokens as $token_data ) {
                if ( isset( $token_data['expiration'] ) && $token_data['expiration'] > $current_time ) {
                    $has_valid_session = true;
                    break;
                }
            }
            // Set user status based on valid session presence.
            $statuses[$user_id] = $has_valid_session ? 'online' : 'offline';
        } else {
            // No session tokens, user is offline.
            $statuses[$user_id] = 'offline';
        }
    }
    wp_send_json_success( $statuses );
}
add_action( 'wp_ajax_check_user_status', 'check_user_status_ajax_handler' );

/**
 * Add a bulk action to log out users.
 *
 * @param array $bulk_actions Existing bulk actions in the Users table.
 * @return array Modified bulk actions.
 */
function add_bulk_logout_action( $bulk_actions ) {
    // Add the "Log Out" bulk action.
    $bulk_actions['logout_users'] = esc_html__( 'Log Out Users', 'user-login-status' );
    return $bulk_actions;
}
add_filter( 'bulk_actions-users', 'add_bulk_logout_action' );

/**
 * Handle the bulk action to log out users.
 *
 * @param string $redirect_to The redirect URL after the action.
 * @param string $action The action being performed.
 * @param array $user_ids The IDs of the users selected for the action.
 * @return string The modified redirect URL.
 */
function handle_bulk_logout_action( $redirect_to, $action, $user_ids ) {
    if ( $action !== 'logout_users' ) {
        return $redirect_to;
    }

    foreach ( $user_ids as $user_id ) {
        // Log out the user by clearing their session tokens.
        if ( ! current_user_can( 'edit_user', $user_id ) ) {
            continue; // Skip users that the current user cannot edit.
        }

        // Clear the session tokens.
        delete_user_meta( $user_id, 'session_tokens' );
    }

    // Redirect with a success message.
    $redirect_to = add_query_arg( 'bulk_logout_success', count( $user_ids ), $redirect_to );
    return $redirect_to;
}
add_filter( 'handle_bulk_actions-users', 'handle_bulk_logout_action', 10, 3 );

/**
 * Display an admin notice after users are logged out.
 */
function bulk_logout_success_notice() {
    if ( ! empty( $_REQUEST['bulk_logout_success'] ) ) {
        $count = intval( $_REQUEST['bulk_logout_success'] );
        printf(
            '<div id="message" class="updated notice is-dismissible"><p>' .
            esc_html( _n( '%s user has been logged out.', '%s users have been logged out.', $count, 'user-login-status' ) ) .
            '</p></div>',
            esc_html( number_format_i18n( $count ) )
        );
    }
}
add_action( 'admin_notices', 'bulk_logout_success_notice' );
