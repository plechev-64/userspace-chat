<?php

/** @noinspection PhpUnused */
function uspc_get_chats( $args ) {
	return ( new USPC_Chats_Query() )->parse( $args )->get_results();
}

function uspc_get_chat( $chat_id ) {
	return ( new USPC_Chats_Query() )->where( [
		'chat_id' => intval( $chat_id )
	] )->get_row();
}

function uspc_get_chat_by_room( $chat_room ) {
	return ( new USPC_Chats_Query() )->where( [
		'chat_room' => $chat_room
	] )->get_row();
}

function uspc_insert_chat( $chat_room, $chat_status ) {
	global $wpdb;

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
	$result = $wpdb->insert(
		USPC_PREF . 'chats', [
			'chat_room'   => $chat_room,
			'chat_status' => $chat_status
		]
	);

	if ( ! $result ) {
		usp_add_log( 'uspc_insert_chat: ' . __( 'Failed to add chat', 'userspace-chat' ), [ $chat_room, $chat_status ] );
	}

	$chat_id = $wpdb->insert_id;

	do_action( 'uspc_insert_chat', $chat_id );

	return $chat_id;
}

function uspc_delete_chat( $chat_id ) {
	global $wpdb;

	$result = $wpdb->delete( USPC_PREF . 'chats', [ 'chat_id' => $chat_id ], [ '%d' ] ); //db call ok; no-cache ok

	do_action( 'uspc_delete_chat', $chat_id, $result );

	return $result;
}

add_action( 'uspc_delete_chat', 'uspc_chat_remove_users', 10 );
function uspc_chat_remove_users( $chat_id ) {
	global $wpdb;

	$result = $wpdb->delete( USPC_PREF . 'chat_users', [ 'chat_id' => $chat_id ], [ '%d' ] ); //db call ok; no-cache ok

	do_action( 'uspc_chat_remove_users', $chat_id, $result );

	return $result;
}

function uspc_chat_delete_user( $chat_id, $user_id ) {
	global $wpdb;

	$result = $wpdb->delete( USPC_PREF . 'chat_users', [ 'chat_id' => $chat_id, 'user_id' => $user_id ], [ '%d', '%d' ] ); //db call ok; no-cache ok

	do_action( 'uspc_user_deleted', $chat_id, $user_id, $result );

	return $result;
}

function uspc_chat_get_users( $chat_id ) {
	return ( new USPC_Chat_Users_Query() )->select( [
		'user_id'
	] )->where( [
		'chat_id' => intval( $chat_id ),
	] )->get_col();
}

function uspc_chat_get_user_status( $chat_id, $user_id ) {
	return ( new USPC_Chat_Users_Query() )->select( [ 'user_status' ] )->where( [
		'chat_id' => intval( $chat_id ),
		'user_id' => intval( $user_id )
	] )->get_var();
}

function uspc_chat_insert_user( $chat_id, $user_id, $status = 1, $activity = 1 ) {
	global $wpdb;

	$user_activity = ( $activity ) ? current_time( 'mysql' ) : '0000-00-00 00:00:00';

	$args = [
		'room_place'    => $chat_id . ':' . $user_id,
		'chat_id'       => $chat_id,
		'user_id'       => $user_id,
		'user_activity' => $user_activity,
		'user_write'    => 0,
		'user_status'   => $status
	];

	//phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
	$result = $wpdb->insert(
		USPC_PREF . 'chat_users', $args
	);

	if ( ! $result ) {
		usp_add_log( 'uspc_chat_insert_user: ' . __( 'Failed to add user to chat', 'userspace-chat' ), $args );
	}

	do_action( 'uspc_insert_user', $args );

	return $result;
}

function uspc_chat_delete_message( $message_id ) {
	global $wpdb;

	do_action( 'uspc_pre_delete_message', $message_id );

	$result = $wpdb->delete( USPC_PREF . 'chat_messages', [ 'message_id' => $message_id ], [ '%d' ] ); //db call ok; no-cache ok

	do_action( 'uspc_delete_message', $message_id, $result );

	return $result;
}

function uspc_chat_get_messages( $args ) {
	return ( new USPC_Chat_Messages_Query() )->parse( $args )->get_results();
}

function uspc_chat_count_messages( $args ) {
	return ( new USPC_Chat_Messages_Query() )->parse( $args )->get_count();
}

function uspc_chat_get_message( $message_id ) {
	return ( new USPC_Chat_Messages_Query() )->where( [
		'message_id' => intval( $message_id )
	] )->get_row();
}

function uspc_chat_get_message_meta( $message_id, $meta_key ) {
	return ( new USPC_Chat_Messagemeta_Query() )->select( [ 'meta_value' ] )->where( [
		'message_id' => intval( $message_id ),
		'meta_key'   => $meta_key // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
	] )->get_var();
}

function uspc_chat_add_message_meta( $message_id, $meta_key, $meta_value ) {
	global $wpdb;

	$args = [
		'message_id' => intval( $message_id ),
		'meta_key'   => $meta_key,  // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		'meta_value' => intval( $meta_value ) // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
	];

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
	$result = $wpdb->insert(
		USPC_PREF . 'chat_messagemeta', $args
	);

	if ( ! $result ) {
		usp_add_log( 'uspc_chat_add_message_meta: ' . __( 'Failed to send message metadata', 'userspace-chat' ), $args );
	}

	do_action( 'uspc_add_message_meta', $args );

	return $result;
}

// insert new message
function uspc_add_chat_message( $message ) {
	global $wpdb;

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
	return $wpdb->insert(
		USPC_PREF . 'chat_messages', $message
	);
}

function uspc_chat_delete_message_meta( $message_id, $meta_key = false ) {
	global $wpdb;

	$message_id = intval( $message_id );

	do_action( 'uspc_pre_delete_message_meta', $message_id, $meta_key );

	$sql = "DELETE FROM " . USPC_PREF . "chat_messagemeta WHERE message_id = '$message_id'";

	if ( $meta_key ) {
		$sql .= "AND meta_key = '$meta_key'";
	}

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
	$result = $wpdb->query( $sql );

	do_action( 'uspc_delete_message_meta', $message_id, $meta_key, $result );

	return $result;
}

function uspc_chat_update_user_status( $chat_id, $user_id, $status ) {
	global $wpdb;

	$chat_id = intval( $chat_id );
	$user_id = intval( $user_id );

	// phpcs:disable
	$result = $wpdb->query( "INSERT INTO " . USPC_PREF . "chat_users "
	                        . "(`room_place`, `chat_id`, `user_id`, `user_activity`, `user_write`, `user_status`) "
	                        . "VALUES('$chat_id:$user_id', $chat_id, $user_id, '" . current_time( 'mysql' ) . "', 0, $status) "
	                        . "ON DUPLICATE KEY UPDATE user_status='$status'" );
	// phpcs:enable

	if ( ! $result ) {
		usp_add_log( 'uspc_chat_update_user_status: ' . __( 'Failed to refresh user status in the chat', 'userspace-chat' ), [ $chat_id, $user_id, $status ] );
	}

	return $result;
}

function uspc_chat_noread_messages_amount( $user_id ) {
	global $uspc_counter_unread;

	if ( ! $uspc_counter_unread ) {
		$uspc_counter_unread = ( new USPC_Chat_Messages_Query() )->where(
			[
				'private_key'    => intval( $user_id ),
				'message_status' => 0
			]
		)->get_count();
	}

	return $uspc_counter_unread;
}

//  count unread messages for an individual contact
function uspc_count_unread_by_user( $user_id ) {
	return ( new USPC_Chat_Messages_Query() )->where( [
		'user_id'        => intval( $user_id ),
		'message_status' => 0,
		'private_key'    => get_current_user_id()
	] )->get_count();
}

// mark as read chat
function uspc_set_read_chat( $chat_id, $user_id ) {
	global $wpdb;

	$chat_id = intval( $chat_id );
	$user_id = intval( $user_id );

	// phpcs:ignore
	$wpdb->query( "UPDATE " . USPC_PREF . "chat_messages SET message_status = '1' WHERE chat_id = '$chat_id' AND user_id != '$user_id'" );
}
