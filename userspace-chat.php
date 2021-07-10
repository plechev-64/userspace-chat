<?php

/*
  Plugin Name: UserSpace Chat
  Plugin URI: http://user-space.com/
  Description: Private messages userspace and general chat.
  Version: 1.0.0
  Author: Plechev Andrey
  Author URI: http://user-space.com/
  Text Domain: userspace-chat
  License: GPLv2 or later (license.txt)
 */

global $wpdb;

define( 'USPC_PATH', trailingslashit( plugin_dir_path( __FILE__ ) ) );
define( 'USPC_URL', trailingslashit( plugin_dir_url( __FILE__ ) ) );
define( 'USPC_PREF', $wpdb->base_prefix . 'uspc_' );

// install DB tables
require_once USPC_PATH . 'classes/class-uspc-install.php';
register_activation_hook( __FILE__, [ 'USPC_Install', 'create_tables' ] );

// instance of Userspace Chat
require_once USPC_PATH . 'classes/class-uspc-loader.php';
function USPC() {
    return USPC_Loader::instance();
}

// Check if UserSpace is active
if ( in_array( 'userspace/userspace.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
    // UserSpace is loaded hook
    add_action( 'usp_init', 'USPC' );
} else {
    add_action( 'admin_notices', 'uspc_plugin_not_install' );
    function uspc_plugin_not_install() {
        $url = '/wp-admin/plugin-install.php?s=UserSpace&tab=search&type=term';

        $notice = '<div class="notice notice-error">';
        $notice .= '<p>' . __( 'UserSpace plugin not installed!', 'userspace-chat' ) . '</p>';
        $notice .= sprintf( __( 'Go to the page %sPlugins%s - install and activate the UserSpace plugin', 'userspace-chat' ), '<a href="' . $url . '">', '</a>' );
        $notice .= '</div>';

        echo $notice;
    }

}
