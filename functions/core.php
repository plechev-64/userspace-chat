<?php

function uspc_get_chat_private( $user_id, $args = [] ) {
	$chat_room = uspc_get_private_chat_room( $user_id, get_current_user_id() );

	return uspc_get_the_chat_by_room( $chat_room, $args );
}

function uspc_get_private_chat_room( $user_1, $user_2 ) {
	return ($user_1 < $user_2) ? 'private:' . $user_1 . ':' . $user_2 : 'private:' . $user_2 . ':' . $user_1;
}

function uspc_get_the_chat_by_room( $chat_room, $args = [] ) {
	require_once USPC_PATH . 'classes/class-uspc-chat.php';

	$options = array_merge( [
		'userslist'		 => 0,
		'file_upload'	 => usp_get_option( 'uspc_file_upload', 0 ),
		'chat_status'	 => 'private',
		'chat_room'		 => $chat_room
		], $args );

	$chat = new USPC_Chat( $options );

	return [
		'content'	 => $chat->get_chat(),
		'token'		 => $chat->chat_token
	];
}

add_action( 'uspc_chat_remove_users', 'uspc_remove_messages', 10 );
add_action( 'uspc_user_deleted', 'uspc_remove_messages', 10, 2 );
function uspc_remove_messages( $chat_id, $user_id = false ) {

	$args = [
		'chat_id' => $chat_id
	];

	if ( $user_id ) {
		$args[ 'user_id' ] = $user_id;
	}

	//get all the messages in this chat
	$messages = uspc_chat_get_messages( $args );

	if ( $messages ) {
		foreach ( $messages as $message ) {
			//delete the message with the metadata
			uspc_chat_delete_message( $message->message_id );
		}
	} else {
		return;
	}

	do_action( 'uspc_messages_is_delete', $chat_id, $user_id );
}

function uspc_chat_get_new_messages( $post ) {
	$chat_room = base64_decode( $post->token );

	if ( ! uspc_get_chat_by_room( $chat_room ) )
		return false;

	require_once USPC_PATH . 'classes/class-uspc-chat.php';

	$chat = new USPC_Chat( [
		'chat_room'			 => $chat_room,
		'user_write'		 => $post->user_write,
		'update_activity'	 => $post->update_activity
	] );

	$content = '';

	if ( $post->last_activity ) {
		$chat->query[ 'where' ][]	 = "message_time > '$post->last_activity'";
		if ( is_user_logged_in() )
			$chat->query[ 'where' ][]	 = "user_id != '" . get_current_user_id() . "'";

		$messages = $chat->get_messages();

		if ( $messages ) {
			$content .= $chat->get_loop( $messages );

			$chat->read_chat( $chat->chat_id );
		}

		$res[ 'content' ] = $content;
	}

	$activity = $chat->get_current_activity();

	if ( $activity ) {
		$res[ 'users' ] = $activity;
	}

	$res[ 'success' ]		 = true;
	$res[ 'token' ]			 = $post->token;
	$res[ 'current_time' ]	 = current_time( 'mysql' );

	return $res;
}

// get all important content & ajax pagination
function uspc_important_im_talk_box( $user_id, $current_page = 1 ) {
	require_once USPC_PATH . 'classes/class-uspc-chat.php';
	require_once USPC_PATH . 'classes/class-uspc-chat-all-important.php';

	$chat = new USPC_Chat_All_Important( [ 'user_id' => $user_id, 'current_page' => $current_page ] );

	return $chat->get_box_important_messages();
}
