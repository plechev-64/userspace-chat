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


if ( ! defined( 'USPC_PATH' ) ) {
    define( 'USPC_PATH', trailingslashit( plugin_dir_path( __FILE__ ) ) );
}

if ( ! defined( 'USPC_URL' ) ) {
    define( 'USPC_URL', trailingslashit( plugin_dir_url( __FILE__ ) ) );
}

if ( ! defined( 'USPC_PREF' ) ) {
    global $wpdb;

    define( 'USPC_PREF', $wpdb->base_prefix . 'uspc_' );
}


// set the first settings
register_activation_hook( __FILE__, 'uspc_activate' );
function uspc_activate() {

    global $wpdb;

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

    $collate = '';

    if ( $wpdb->has_cap( 'collation' ) ) {
        if ( ! empty( $wpdb->charset ) ) {
            $collate .= "DEFAULT CHARACTER SET $wpdb->charset";
        }
        if ( ! empty( $wpdb->collate ) ) {
            $collate .= " COLLATE $wpdb->collate";
        }
    }

    $chats_table = USPC_PREF . "chats";
    $chats_sql   = "CREATE TABLE IF NOT EXISTS " . $chats_table . " (
        chat_id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        chat_room varchar(100) NOT NULL,
        chat_status varchar(20) NOT NULL,
        PRIMARY KEY  chat_id (chat_id)
    ) $collate;";

    dbDelta( $chats_sql );

    $chat_users_table = USPC_PREF . "chat_users";
    $chat_users_sql   = "CREATE TABLE IF NOT EXISTS " . $chat_users_table . " (
        room_place varchar(20) NOT NULL,
        chat_id bigint(20) UNSIGNED NOT NULL,
        user_id bigint(20) UNSIGNED NOT NULL,
        user_activity datetime NOT NULL,
        user_write tinyint(1) UNSIGNED NOT NULL,
        user_status tinyint(1) UNSIGNED NOT NULL,
        UNIQUE KEY room_place (room_place),
        KEY chat_id (chat_id),
        KEY user_id (user_id)
    ) $collate;";

    dbDelta( $chat_users_sql );

    $chat_messages_table = USPC_PREF . "chat_messages";
    $chat_messages_sql   = "CREATE TABLE IF NOT EXISTS " . $chat_messages_table . " (
        message_id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        chat_id  bigint(20) UNSIGNED NOT NULL,
        user_id  bigint(20) UNSIGNED NOT NULL,
        message_content longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
        message_time datetime NOT NULL,
        private_key bigint(20) UNSIGNED NOT NULL,
        message_status tinyint(1) UNSIGNED NOT NULL,
        PRIMARY KEY  message_id (message_id),
        KEY chat_id (chat_id),
        KEY user_id (user_id),
        KEY private_key (private_key),
        KEY message_status (message_status)
    ) $collate;";

    dbDelta( $chat_messages_sql );

    $chat_messagemeta_table = USPC_PREF . "chat_messagemeta";
    $chat_messagemeta_sql   = "CREATE TABLE IF NOT EXISTS " . $chat_messagemeta_table . " (
        meta_id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        message_id bigint(20) UNSIGNED NOT NULL,
        meta_key varchar(255) NOT NULL,
        meta_value longtext NOT NULL,
        PRIMARY KEY  meta_id (meta_id),
        KEY message_id (message_id),
        KEY meta_key (meta_key)
    ) $collate;";

    dbDelta( $chat_messagemeta_sql );
}

/**
 * Check if UserSpace is active
 * */
if ( in_array( 'userspace/userspace.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
    require_once 'uspc-init.php';
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
