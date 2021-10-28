<?php /** @noinspection PhpUnused */

usp_ajax_action( 'uspc_get_ajax_chat_window' );
function uspc_get_ajax_chat_window() {
	usp_verify_ajax_nonce();

	if ( empty( $_POST['user_id'] ) ) {
		return;
	}

	$user_id = intval( $_POST['user_id'] );
	$class   = isset( $_POST['class'] ) ? ' ' . sanitize_html_class( wp_unslash( $_POST['class'] ) ) : false;

	$chatdata = uspc_get_chat_private( $user_id );

	$header = uspc_include_chat_header( $user_id, $chatdata );

	wp_send_json( [
		'dialog' => [
			'content'     => uspc_get_chat_box( $chatdata['content'], $header ),
			'class'       => 'uspc-chat-modal ssi-dialog ssi-no-padding' . $class,
			'size'        => 'medium',
			'buttonClose' => false,
			'onClose'     => [ 'uspc_chat_clear_beat', [ $chatdata['token'] ] ],
		],
	] );
}

usp_ajax_action( 'uspc_chat_remove_contact' );
function uspc_chat_remove_contact() {
	usp_verify_ajax_nonce();

	if ( empty( $_POST['chat_id'] ) ) {
		return;
	}

	uspc_chat_update_user_status( intval( $_POST['chat_id'] ), get_current_user_id(), 0 );

	$res['remove'] = true;

	wp_send_json( $res );
}

usp_ajax_action( 'uspc_get_contacts_navi' );
function uspc_get_contacts_navi() {
	usp_verify_ajax_nonce();

	if ( empty( $_POST['page'] ) ) {
		return;
	}

	require_once USPC_PATH . 'classes/class-uspc-contact-list.php';

	$contactlist = new USPC_Contact_List( [ 'current' => intval( $_POST['page'] ) ] );

	$res['content'] = $contactlist->get_loop();
	$res['nav']     = $contactlist->get_pagination();

	wp_send_json( $res );
}

usp_ajax_action( 'uspc_get_chat_page', true );
function uspc_get_chat_page() {
	usp_verify_ajax_nonce();

	$chat_page = isset( $_POST['page'] ) ? intval( $_POST['page'] ) : 1;

	if ( isset( $_POST['token'] ) && 1 == $_POST['token'] ) {
		uspc_get_all_important_navi( $chat_page );
	}

	$chat_room = isset( $_POST['token'] ) ? base64_decode( sanitize_text_field( wp_unslash( $_POST['token'] ) ) ) : '';
	$important = isset( $_POST['important'] ) ? intval( $_POST['important'] ) : 0;
	$in_page   = isset( $_POST['in_page'] ) ? intval( $_POST['in_page'] ) : 30;

	if ( uspc_is_private_room( $chat_room ) ) {
		if ( ! uspc_user_in_room( get_current_user_id(), $chat_room ) ) {
			wp_send_json( [ 'error' => __( 'Error', 'userspace-chat' ) ] );
		}
	}

	uspc_get_paged_user_to_user( $chat_room, $chat_page, $important, $in_page );
}

function uspc_get_all_important_navi( $chat_page ) {
	$res['content'] = uspc_important_im_talk_box( get_current_user_id(), $chat_page );

	wp_send_json( $res );
}

function uspc_get_paged_user_to_user( $chat_room, $chat_page, $important, $in_page ) {
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
			'user_list'   => 1,
		]
	);

	$res['content'] = $chat->get_messages_box();

	wp_send_json( $res );
}

usp_ajax_action( 'uspc_chat_add_message' );
function uspc_chat_add_message() {
	usp_verify_ajax_nonce();

	if ( ! isset( $_POST['chat'] ) ) {
		return;
	}

	$message_text = isset( $_POST['chat']['message'] ) ? wp_kses( wp_unslash( $_POST['chat']['message'] ), uspc_allowed_tags() ) : '';
	$token        = isset( $_POST['chat']['token'] ) ? sanitize_text_field( wp_unslash( $_POST['chat']['token'] ) ) : '';
	$attachment   = ( isset( $_POST['chat']['attachment'] ) ) ? intval( $_POST['chat']['attachment'] ) : false;

	$chat_room = sanitize_text_field( base64_decode( $token ) );

	if ( uspc_is_private_room( $chat_room ) && ! uspc_user_in_room( get_current_user_id(), $chat_room ) ) {
		wp_send_json( [ 'error' => __( 'Error', 'userspace-chat' ) ] );
	}

	if ( ! uspc_get_chat_by_room( $chat_room ) ) {
		wp_send_json( [ 'error' => __( 'Error', 'userspace-chat' ) ] );
	}

	$antispam = apply_filters( 'uspc_antispam_option', usp_get_option( 'uspc_antispam', 0 ) );

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
				'error' => __( 'Your activity has signs of spam!', 'userspace-chat' ),
			] );
		}
	}

	$content = '';

	$newMessages = uspc_chat_get_new_messages( ( object ) [
		'last_activity'   => isset( $_POST['last_activity'] ) ? sanitize_text_field( wp_unslash( $_POST['last_activity'] ) ) : null,
		'token'           => $token,
		'user_write'      => 0,
		'update_activity' => 0,
	] );

	if ( isset( $newMessages['content'] ) && $newMessages['content'] ) {
		$res['new_messages'] = 1;

		$content .= $newMessages['content'];
	}

	require_once USPC_PATH . 'classes/class-uspc-chat.php';

	$chat = new USPC_Chat( [ 'chat_room' => $chat_room ] );

	$result = $chat->add_message( $message_text, $attachment );

	if ( isset( $result->errors ) && $result->errors ) {
		$res['errors'] = $result->errors;
		wp_send_json( $res );
	}

	if ( $attachment ) {
		usp_delete_temp_media( $attachment );
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

	if ( empty( $_POST['message_id'] ) ) {
		return;
	}

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

	$chat_token       = isset( $_POST['token'] ) ? sanitize_text_field( wp_unslash( $_POST['token'] ) ) : '';
	$status_important = isset( $_POST['status_important'] ) ? intval( $_POST['status_important'] ) : 0;
	$chat_room        = base64_decode( $chat_token );

	if ( uspc_is_private_room( $chat_room ) ) {
		if ( ! uspc_user_in_room( get_current_user_id(), $chat_room ) ) {
			wp_send_json( [ 'error' => __( 'Error', 'userspace-chat' ) ] );
		}
	}

	if ( ! uspc_get_chat_by_room( $chat_room ) ) {
		return;
	}

	require_once USPC_PATH . 'classes/class-uspc-chat.php';

	$userlist = ! ( ( 1 == $status_important ) );

	$chat = new USPC_Chat(
		[
			'chat_room' => $chat_room,
			'important' => $status_important,
			'user_list' => $userlist,
		]
	);

	$res['content'] = $chat->get_messages_box();

	wp_send_json( $res );
}

usp_ajax_action( 'uspc_chat_delete_attachment' );
function uspc_chat_delete_attachment() {
	usp_verify_ajax_nonce();

	$attachment_id = ( isset( $_POST['attachment_id'] ) ) ? intval( $_POST['attachment_id'] ) : false;

	if ( ! $attachment_id ) {
		return;
	}

	$post = get_post( $attachment_id );
	if ( ! $post ) {
		return;
	}

	if ( get_current_user_id() != $post->post_author ) {
		return;
	}

	wp_delete_attachment( $attachment_id );

	$result['remove'] = true;

	wp_send_json( $result );
}

usp_ajax_action( 'uspc_chat_ajax_delete_message' );
function uspc_chat_ajax_delete_message() {
	usp_verify_ajax_nonce();

	$message_id = ( isset( $_POST['message_id'] ) ) ? intval( $_POST['message_id'] ) : false;
	if ( ! $message_id ) {
		return;
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

	$user_id = ( isset( $_POST['user_id'] ) ) ? intval( $_POST['user_id'] ) : false;

	$chatdata                = uspc_get_chat_private( $user_id );
	$chatdata['chat_status'] = 'private';

	$header = uspc_include_chat_header( $user_id, $chatdata );

	$resp['content'] = uspc_get_chat_box( $chatdata['content'], $header );

	wp_send_json( $resp );
}

usp_ajax_action( 'uspc_get_userlist' );
function uspc_get_userlist() {
	usp_verify_ajax_nonce();

	USP()->use_module( 'users-list' );

	$manager = new USP_Users_Manager( [ 'pagenavi' => 1, 'orderby' => 'date_action', 'custom_data' => 'posts,comments,user_registered', 'id__not_in' => get_current_user_id() ] );

	$content = '<div class="usp-users-list">';
	$content .= $manager->get_manager();
	$content .= '</div>';

	return [
		'dialog' => [
			'content'     => $content,
			'class'       => 'uspc-chat-modal ssi-no-padding',
			'size'        => 'medium',
			'buttonClose' => false,
		],
	];
}
