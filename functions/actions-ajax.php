<?php

usp_ajax_action( 'uspc_get_ajax_chat_window' );
function uspc_get_ajax_chat_window() {
	usp_verify_ajax_nonce();

	$user_id = intval( $_POST[ 'user_id' ] );

	$chatdata = uspc_get_chat_private( $user_id );

	wp_send_json( array(
		'dialog' => [
			'content'		 => $chatdata[ 'content' ],
			'title'			 => __( 'Chat with', 'userspace-chat' ) . ' ' . usp_get_username( $user_id ),
			'class'			 => 'uspc-chat-window',
			'size'			 => 'small',
			'buttonClose'	 => false,
			'onClose'		 => [ 'uspc_chat_clear_beat', array( $chatdata[ 'token' ] ) ]
		]
	) );
}

usp_ajax_action( 'uspc_chat_remove_contact', false );
function uspc_chat_remove_contact() {
	usp_verify_ajax_nonce();

	uspc_chat_update_user_status( intval( $_POST[ 'chat_id' ] ), get_current_user_id(), 0 );

	$res[ 'remove' ] = true;

	wp_send_json( $res );
}

usp_ajax_action( 'uspc_get_contacts_navi' );
function uspc_get_contacts_navi() {
	usp_verify_ajax_nonce();

	require_once USPC_PATH . 'classes/class-uspc-contact-list.php';

	$contactlist = new USPC_Contact_List( [ 'current' => intval( $_POST[ 'page' ] ) ] );

	$res[ 'content' ]	 = $contactlist->get_loop();
	$res[ 'nav' ]		 = $contactlist->get_pagination();

	wp_send_json( $res );
}

usp_ajax_action( 'uspc_get_chat_page', true );
function uspc_get_chat_page() {
	usp_verify_ajax_nonce();

	$chat_page	 = intval( $_POST[ 'page' ] );
	$chat_token	 = $_POST[ 'token' ];

	// special marker of get all important navi
	if ( $chat_token == 1 ) {
		return uspc_get_all_important_navi( $chat_page );
	}

	$in_page	 = intval( $_POST[ 'in_page' ] );
	$important	 = intval( $_POST[ 'important' ] );

	return uspc_get_paged_user_to_user( $chat_token, $chat_page, $important, $in_page );
}

function uspc_get_all_important_navi( $chat_page ) {
	$res[ 'content' ] = uspc_important_im_talk_box( get_current_user_id(), $chat_page );

	wp_send_json( $res );
}

function uspc_get_paged_user_to_user( $chat_token, $chat_page, $important, $in_page ) {
	$chat_room = base64_decode( $chat_token );

	if ( ! uspc_get_chat_by_room( $chat_room ) )
		return;

	require_once USPC_PATH . 'classes/class-uspc-chat.php';

	$chat = new USPC_Chat(
		[
		'chat_room'	 => $chat_room,
		'paged'		 => $chat_page,
		'important'	 => $important,
		'in_page'	 => $in_page,
		'userslist'	 => 1
		]
	);

	$res[ 'content' ] = $chat->get_messages_talk();

	wp_send_json( $res );
}

usp_ajax_action( 'uspc_chat_add_message', false );
function uspc_chat_add_message() {
	usp_verify_ajax_nonce();

	$POST = wp_unslash( $_POST[ 'chat' ] );

	$chat_room = base64_decode( $POST[ 'token' ] );

	if ( ! uspc_get_chat_by_room( $chat_room ) )
		return false;

	$antispam_opt	 = usp_get_option( 'uspc_antispam', 0 );
	$antispam		 = apply_filters( 'uspc_antispam_option', $antispam_opt );

	if ( $antispam ) {
		$query = new USPC_Chat_Messages_Query();

		$args = [
			'user_id'				 => get_current_user_id(),
			'private_key__not_in'	 => [ 0 ],
			'message_status__not_in' => [ 1 ],
			'date_query'			 => [
				[
					'column'	 => 'message_time',
					'compare'	 => '=',
					'last'		 => '24 HOUR'
				]
			],
			'groupby'				 => 'private_key'
		];

		$cntLastMess = $query->parse( $args )->get_count();

		if ( $cntLastMess > $antispam )
			wp_send_json( [
				'error' => __( 'Your activity has sings of spam!', 'userspace-chat' )
			] );
	}

	$attach = (isset( $POST[ 'attachment' ] )) ? $POST[ 'attachment' ] : false;

	$content = '';

	$newMessages = uspc_chat_get_new_messages( ( object ) array(
			'last_activity'		 => $_POST[ 'last_activity' ],
			'token'				 => $POST[ 'token' ],
			'user_write'		 => 0,
			'update_activity'	 => 0
		) );

	if ( isset( $newMessages[ 'content' ] ) && $newMessages[ 'content' ] ) {
		$res[ 'new_messages' ] = 1;

		$content .= $newMessages[ 'content' ];
	}

	require_once USPC_PATH . 'classes/class-uspc-chat.php';

	$chat = new USPC_Chat( [ 'chat_room' => $chat_room ] );

	$result = $chat->add_message( $POST[ 'message' ], $attach );

	if ( isset( $result->errors ) && $result->errors ) {
		$res[ 'errors' ] = $result->errors;
		wp_send_json( $res );
	}

	if ( $attach )
		usp_delete_temp_media( $attach );

	if ( isset( $result[ 'errors' ] ) ) {
		wp_send_json( $result );
	}

	$res[ 'content' ]			 = $content . $chat->include_template_message_item( $result );
	$res[ 'last_activity' ]	 = current_time( 'mysql' );

	wp_send_json( $res );
}

usp_ajax_action( 'uspc_get_chat_private_ajax', false );
function uspc_get_chat_private_ajax() {
	usp_verify_ajax_nonce();

	$user_id = intval( $_POST[ 'user_id' ] );

	$chatdata = uspc_get_chat_private( $user_id, [ 'avatar_size' => 30, 'userslist' => 0 ] );

	$bttn = usp_get_button( [
		'onclick'	 => 'uspc_close_minichat(this);return false;',
		'class'		 => 'uspc-im__close',
		'icon'		 => 'fa-times'
		] );

	$result[ 'name' ]			 = usp_get_username( $user_id, usp_get_tab_permalink( $user_id, 'chat' ), [ 'class' => 'uspc-im__userlink' ] );
	$result[ 'bttn' ]			 = $bttn;
	$result[ 'content' ]		 = $chatdata[ 'content' ];
	$result[ 'chat_token' ]	 = $chatdata[ 'token' ];

	wp_send_json( $result );
}

usp_ajax_action( 'uspc_chat_message_important', false );
function uspc_chat_message_important() {
	usp_verify_ajax_nonce();

	global $user_ID;

	$message_id = intval( $_POST[ 'message_id' ] );

	$important = uspc_chat_get_message_meta( $message_id, 'important:' . $user_ID );

	if ( $important ) {
		uspc_chat_delete_message_meta( $message_id, 'important:' . $user_ID );
	} else {
		uspc_chat_add_message_meta( $message_id, 'important:' . $user_ID, 1 );
	}

	$result[ 'important' ] = ($important) ? 0 : 1;

	wp_send_json( $result );
}

usp_ajax_action( 'uspc_chat_important_manager_shift', false );
function uspc_chat_important_manager_shift() {
	usp_verify_ajax_nonce();

	$chat_token			 = wp_slash( $_POST[ 'token' ] );
	$status_important	 = intval( $_POST[ 'status_important' ] );
	$chat_room			 = base64_decode( $chat_token );

	if ( ! uspc_get_chat_by_room( $chat_room ) )
		return false;

	require_once USPC_PATH . 'classes/class-uspc-chat.php';

	$userlist = ($status_important == 1) ? false : true;

	$chat = new USPC_Chat(
		[
		'chat_room'	 => $chat_room,
		'important'	 => $status_important,
		'userslist'	 => $userlist
		]
	);

	$res[ 'content' ] = $chat->get_messages_box();

	wp_send_json( $res );
}

usp_ajax_action( 'uspc_chat_delete_attachment', false );
function uspc_chat_delete_attachment() {
	usp_verify_ajax_nonce();

	$attachment_id = intval( $_POST[ 'attachment_id' ] );

	if ( ! $attachment_id )
		return false;

	if ( ! $post = get_post( $attachment_id ) )
		return false;

	if ( $post->post_author != get_current_user_id() )
		return false;

	wp_delete_attachment( $attachment_id );

	$result[ 'remove' ] = true;

	wp_send_json( $result );
}

usp_ajax_action( 'uspc_chat_ajax_delete_message', false );
function uspc_chat_ajax_delete_message() {
	usp_verify_ajax_nonce();

	if ( ! $message_id = intval( $_POST[ 'message_id' ] ) )
		return false;

	global $current_user;

	if ( $current_user->user_level >= usp_get_option( 'usp_consol_access', 7 ) ) {
		uspc_chat_delete_message( $message_id );
	}

	$result[ 'remove' ] = true;

	wp_send_json( $result );
}
