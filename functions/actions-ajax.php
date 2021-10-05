<?php

usp_ajax_action( 'uspc_get_ajax_chat_window' );
function uspc_get_ajax_chat_window() {
	usp_verify_ajax_nonce();

	$user_id = intval( $_POST['user_id'] );

	$chatdata = uspc_get_chat_private( $user_id );

	$header = uspc_include_chat_header( $user_id, $chatdata );

	wp_send_json( [
		'dialog' => [
			'content'     => uspc_get_chat_box( $chatdata['content'], $header ),
			'class'       => 'uspc-chat-modal ssi-dialog ssi-no-padding',
			'size'        => 'medium',
			'buttonClose' => false,
			'onClose'     => [ 'uspc_chat_clear_beat', [ $chatdata['token'] ] ],
		],
	] );
}

usp_ajax_action( 'uspc_chat_remove_contact' );
function uspc_chat_remove_contact() {
	usp_verify_ajax_nonce();

	uspc_chat_update_user_status( intval( $_POST['chat_id'] ), get_current_user_id(), 0 );

	$res['remove'] = true;

	wp_send_json( $res );
}

usp_ajax_action( 'uspc_get_contacts_navi' );
function uspc_get_contacts_navi() {
	usp_verify_ajax_nonce();

	require_once USPC_PATH . 'classes/class-uspc-contact-list.php';

	$contactlist = new USPC_Contact_List( [ 'current' => intval( $_POST['page'] ) ] );

	$res['content'] = $contactlist->get_loop();
	$res['nav']     = $contactlist->get_pagination();

	wp_send_json( $res );
}

usp_ajax_action( 'uspc_get_chat_page', true );
function uspc_get_chat_page() {
	usp_verify_ajax_nonce();

	$chat_page  = intval( $_POST['page'] );
	$chat_token = $_POST['token'];

	// special marker of get all important navi
	if ( $chat_token == 1 ) {
		return uspc_get_all_important_navi( $chat_page );
	}

	$in_page   = intval( $_POST['in_page'] );
	$important = intval( $_POST['important'] );

	return uspc_get_paged_user_to_user( $chat_token, $chat_page, $important, $in_page );
}

function uspc_get_all_important_navi( $chat_page ) {
	$res['content'] = uspc_important_im_talk_box( get_current_user_id(), $chat_page );

	wp_send_json( $res );
}

function uspc_get_paged_user_to_user( $chat_token, $chat_page, $important, $in_page ) {
	$chat_room = base64_decode( $chat_token );

	$chat_data = uspc_get_chat_by_room( $chat_room );

	if ( ! $chat_data ) {
		return;
	}

	require_once USPC_PATH . 'classes/class-uspc-chat.php';

	$chat = new USPC_Chat(
		[
			'chat_room'   => $chat_room,
			'chat_status' => $chat_data->chat_status,
			'paged'       => $chat_page,
			'important'   => $important,
			'in_page'     => $in_page,
			'userslist'   => 1,
		]
	);

	$res['content'] = $chat->get_messages_box();

	wp_send_json( $res );
}

usp_ajax_action( 'uspc_chat_add_message' );
function uspc_chat_add_message() {
	usp_verify_ajax_nonce();

	$POST = wp_unslash( $_POST['chat'] );

	$chat_room = base64_decode( $POST['token'] );

	if ( ! uspc_get_chat_by_room( $chat_room ) ) {
		return false;
	}

	$antispam_opt = usp_get_option( 'uspc_antispam', 0 );
	$antispam     = apply_filters( 'uspc_antispam_option', $antispam_opt );

	if ( $antispam ) {
		$query = new USPC_Chat_Messages_Query();

		$args = [
			'user_id'                => get_current_user_id(),
			'private_key__not_in'    => [ 0 ],
			'message_status__not_in' => [ 1 ],
			'date_query'             => [
				[
					'column'  => 'message_time',
					'compare' => '=',
					'last'    => '24 HOUR',
				],
			],
			'groupby'                => 'private_key',
		];

		$cntLastMess = $query->parse( $args )->get_count();

		if ( $cntLastMess > $antispam ) {
			wp_send_json( [
				'error' => __( 'Your activity has sings of spam!', 'userspace-chat' ),
			] );
		}
	}

	$attach = ( isset( $POST['attachment'] ) ) ? $POST['attachment'] : false;

	$content = '';

	$newMessages = uspc_chat_get_new_messages( ( object ) [
		'last_activity'   => $_POST['last_activity'],
		'token'           => $POST['token'],
		'user_write'      => 0,
		'update_activity' => 0,
	] );

	if ( isset( $newMessages['content'] ) && $newMessages['content'] ) {
		$res['new_messages'] = 1;

		$content .= $newMessages['content'];
	}

	require_once USPC_PATH . 'classes/class-uspc-chat.php';

	$chat = new USPC_Chat( [ 'chat_room' => $chat_room ] );

	$result = $chat->add_message( $POST['message'], $attach );

	if ( isset( $result->errors ) && $result->errors ) {
		$res['errors'] = $result->errors;
		wp_send_json( $res );
	}

	if ( $attach ) {
		usp_delete_temp_media( $attach );
	}

	if ( isset( $result['errors'] ) ) {
		wp_send_json( $result );
	}

	$res['content']       = $content . $chat->include_template_message_item( $result );
	$res['last_activity'] = current_time( 'mysql' );

	wp_send_json( $res );
}

usp_ajax_action( 'uspc_chat_message_important' );
function uspc_chat_message_important() {
	usp_verify_ajax_nonce();

	$message_id = intval( $_POST['message_id'] );

	$metakey = 'important:' . get_current_user_id();

	$important = uspc_chat_get_message_meta( $message_id, $metakey );

	if ( $important ) {
		uspc_chat_delete_message_meta( $message_id, $metakey );
	} else {
		uspc_chat_add_message_meta( $message_id, $metakey, 1 );
	}

	$result['important'] = ( $important ) ? 0 : 1;

	wp_send_json( $result );
}

usp_ajax_action( 'uspc_chat_important_manager_shift' );
function uspc_chat_important_manager_shift() {
	usp_verify_ajax_nonce();

	$chat_token       = wp_slash( $_POST['token'] );
	$status_important = intval( $_POST['status_important'] );
	$chat_room        = base64_decode( $chat_token );

	if ( ! uspc_get_chat_by_room( $chat_room ) ) {
		return false;
	}

	require_once USPC_PATH . 'classes/class-uspc-chat.php';

	$userlist = ( $status_important == 1 ) ? false : true;

	$chat = new USPC_Chat(
		[
			'chat_room' => $chat_room,
			'important' => $status_important,
			'userslist' => $userlist,
		]
	);

	$res['content'] = $chat->get_messages_box();

	wp_send_json( $res );
}

usp_ajax_action( 'uspc_chat_delete_attachment' );
function uspc_chat_delete_attachment() {
	usp_verify_ajax_nonce();

	$attachment_id = intval( $_POST['attachment_id'] );

	if ( ! $attachment_id ) {
		return false;
	}

	if ( ! $post = get_post( $attachment_id ) ) {
		return false;
	}

	if ( $post->post_author != get_current_user_id() ) {
		return false;
	}

	wp_delete_attachment( $attachment_id );

	$result['remove'] = true;

	wp_send_json( $result );
}

usp_ajax_action( 'uspc_chat_ajax_delete_message' );
function uspc_chat_ajax_delete_message() {
	usp_verify_ajax_nonce();

	if ( ! $message_id = intval( $_POST['message_id'] ) ) {
		return false;
	}

	global $current_user;

	if ( $current_user->user_level >= usp_get_option( 'usp_console_access', 7 ) ) {
		uspc_chat_delete_message( $message_id );
	}

	$result['remove'] = true;

	wp_send_json( $result );
}

// from the chat tab went to direct communication
usp_ajax_action( 'uspc_get_direct_message' );
function uspc_get_direct_message() {
	usp_verify_ajax_nonce();

	$user_id = intval( $_POST['user_id'] );

	$chatdata                = uspc_get_chat_private( $user_id );
	$chatdata['chat_status'] = 'private';

	$header = uspc_include_chat_header( $user_id, $chatdata );

	$resp['content'] = uspc_get_chat_box( $chatdata['content'], $header );

	wp_send_json( $resp );
}
