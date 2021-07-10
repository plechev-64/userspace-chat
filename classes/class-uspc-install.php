<?php

defined( 'ABSPATH' ) || exit;

class USPC_Install {
    public function create_tables() {
        if ( ! current_user_can( 'activate_plugins' ) )
            return;

        global $wpdb;

        $wpdb->hide_errors();

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

        $chats_sql = "CREATE TABLE IF NOT EXISTS " . USPC_PREF . "chats (
            chat_id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            chat_room varchar(100) NOT NULL,
            chat_status varchar(20) NOT NULL,
            PRIMARY KEY  chat_id (chat_id)
        ) $collate;";

        dbDelta( $chats_sql );

        $chat_users_sql = "CREATE TABLE IF NOT EXISTS " . USPC_PREF . "chat_users (
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

        $chat_messages_sql = "CREATE TABLE IF NOT EXISTS " . USPC_PREF . "chat_messages (
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

        $chat_messagemeta_sql = "CREATE TABLE IF NOT EXISTS " . USPC_PREF . "chat_messagemeta (
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

}
