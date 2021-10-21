<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

// Deleting tables
$tables = [
	$wpdb->base_prefix . 'uspc_chats',
	$wpdb->base_prefix . 'uspc_chat_users',
	$wpdb->base_prefix . 'uspc_chat_messages',
	$wpdb->base_prefix . 'uspc_chat_messagemeta',
];
$wpdb->query( "DROP TABLE IF EXISTS `" . implode( '`, `', $tables ) . "`" ); //phpcs:ignore
