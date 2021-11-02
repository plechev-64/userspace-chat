<?php /** @noinspection PhpMissingReturnTypeInspection */

function uspc_get_chat_private( $user_id, $args = [] ) {
	$chat_room = uspc_get_private_chat_room( $user_id, get_current_user_id() );

	return uspc_get_the_chat_by_room( $chat_room, $args );
}

function uspc_get_private_chat_room( $user_1, $user_2 ) {
	return ( $user_1 < $user_2 ) ? 'private:' . $user_1 . ':' . $user_2 : 'private:' . $user_2 . ':' . $user_1;
}

function uspc_get_the_chat_by_room( $chat_room, $args = [] ) {
	require_once USPC_PATH . 'classes/class-uspc-chat.php';

	$options = array_merge( [
		'user_list'   => 0,
		'file_upload' => usp_get_option( 'uspc_file_upload', 0 ),
		'chat_status' => 'private',
		'chat_room'   => $chat_room,
	], $args );

	$chat = new USPC_Chat( $options );

	return [
		'content' => $chat->get_chat(),
		'token'   => $chat->chat_token,
	];
}

function uspc_include_chat_header( $user_id, $chatdata, $args = false ) {
	return usp_get_include_template( 'uspc-chat-header.php', USPC_PATH . 'templates', [
		'user_id'  => $user_id,
		'chatdata' => $chatdata,
		'args'     => $args,
	] );
}

function uspc_get_chat_box( $content, $header = false ) {
	return '<div class="uspc-messenger-js"><div class="uspc-messenger-box">' . $header . $content . '</div></div>';
}

add_action( 'uspc_chat_remove_users', 'uspc_remove_messages', 10 );
add_action( 'uspc_user_deleted', 'uspc_remove_messages', 10, 2 );
function uspc_remove_messages( $chat_id, $user_id = false ) {

	$args = [
		'chat_id' => $chat_id,
	];

	if ( $user_id ) {
		$args['user_id'] = $user_id;
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

	if ( ! uspc_get_chat_by_room( $chat_room ) ) {
		return false;
	}

	require_once USPC_PATH . 'classes/class-uspc-chat.php';

	$chat = new USPC_Chat( [
		'chat_room'       => $chat_room,
		'user_write'      => $post->user_write,
		'update_activity' => $post->update_activity,
	] );

	$content = '';

	if ( $post->last_activity ) {
		$chat->query['where'][] = "message_time > '$post->last_activity'";
		if ( is_user_logged_in() ) {
			$chat->query['where'][] = "user_id != '" . get_current_user_id() . "'";
		}

		$messages = $chat->get_messages();

		if ( $messages ) {
			$content .= $chat->get_loop( $messages );

			$chat->read_chat( $chat->chat_id );
		}

		$res['content'] = $content;
	}

	$activity = $chat->get_current_activity();

	if ( $activity ) {
		$res['users'] = $activity;
	}

	$res['success']      = true;
	$res['token']        = $post->token;
	$res['current_time'] = current_time( 'mysql' );

	return $res;
}

// get all important content & ajax pagination
function uspc_important_im_talk_box( $user_id, $current_page = 1 ) {
	require_once USPC_PATH . 'classes/class-uspc-chat.php';
	require_once USPC_PATH . 'classes/class-uspc-chat-all-important.php';

	$chat = new USPC_Chat_All_Important( [ 'user_id' => $user_id, 'current_page' => $current_page ] );

	return $chat->get_box_important_messages();
}

// added color in you messages
add_filter( 'usp_inline_styles', 'uspc_css_variable', 10 );
function uspc_css_variable( $styles ) {
	if ( ! is_user_logged_in() ) {
		return $styles;
	}

	$theme = usp_get_option_customizer( 'uspc_theme', '#beb5ff' );
	[ $r, $g, $b ] = sscanf( $theme, "#%02x%02x%02x" );

	$alpha = usp_get_option_customizer( 'uspc_alpha', '0.2' );

	$styles .= '.uspc-you .uspc-post__message {
		background: linear-gradient(' . $theme . ' 0%, rgba(' . $r . ', ' . $g . ', ' . $b . ', ' . $alpha . ') 51%) no-repeat fixed center center;
	}';

	return $styles;
}

// Link in to chat on userspace bar
add_action( 'usp_dropdown_menu', 'uspc_bar_add_chat_link', 5, 2 );
function uspc_bar_add_chat_link( $menu_id, USP_Dropdown_Menu $menu ) {
	if ( 'usp_bar_profile_menu' !== $menu_id ) {
		return;
	}

	$menu->add_button(
		[
			'class' => 'usp-bar-chat',
			'href'  => usp_get_tab_permalink( get_current_user_id(), 'chat' ),
			'icon'  => 'fa-comments',
			'label' => __( 'Chat', 'userspace-chat' ),
		],
		[ 'order' => 24 ]
	);
}

/**
 * Verification is a direct message
 *
 * @param $room - private:1:3 | custom-room
 *
 * @return bool
 */
function uspc_is_private_room( $room ) {
	return strpos( trim( $room ), 'private:' ) === 0;
}

/**
 * Is there a $user_id in this chat
 *
 * @param $user_id
 * @param $room - private:1:3
 *
 * @return bool
 */
function uspc_user_in_room( $user_id, $room ) {
	[ $prefix, $user_1, $user_2 ] = explode( ':', trim( $room ) );
	unset( $prefix );

	return in_array( $user_id, [ $user_1, $user_2 ] );
}

/**
 * Chat allowed tags
 *
 * @return array
 */
function uspc_allowed_tags() {
	/**
	 * Whitelist allowed tags in chat messages
	 *
	 * @param array $tags allowed tags.
	 *
	 * @since 1.0
	 */
	return apply_filters( 'uspc_allowed_tags', [
		'div'        => [
			'class'   => true,
			'style'   => true,
			'onclick' => true,
			'data-*'  => true,
		],
		'a'          => [
			'href'   => true,
			'title'  => true,
			'target' => true,
			'class'  => true,
		],
		'img'        => [
			'src'   => true,
			'alt'   => true,
			'class' => true,
		],
		'p'          => [
			'class' => true,
		],
		'i'          => [
			'class' => true,
		],
		'blockquote' => [],
		'del'        => [],
		'em'         => [],
		'strong'     => [],
		'details'    => [],
		'summary'    => [],
		'span'       => [
			'class' => true,
			'style' => true,
		],
	] );
}

// add the "Send private message" button to the template user-rows.php
add_action( 'usp_user_fields_after', 'uspc_add_button_in_user_rows', 30 );
function uspc_add_button_in_user_rows( $user ) {
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo uspc_add_button_in_user_template( $user );
}

// add the "Send private message" button to the template user-masonry.php
add_action( 'usp_user_masonry_buttons', 'uspc_add_button_in_user_masonry', 30 );
function uspc_add_button_in_user_masonry( $user ) {
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo uspc_add_button_in_user_template( $user, 1 );
}

function uspc_add_button_in_user_template( $user, $fullwidth = false ) {
	if ( ! is_user_logged_in() || get_current_user_id() == $user->ID ) {
		return false;
	}
	usp_fileupload_scripts();

	return usp_get_button( [
		'label'     => __( 'Send private message', 'userspace-chat' ),
		'icon'      => 'fa-comments',
		'onclick'   => 'uspc_get_chat_window( this, ' . $user->ID . ' ); return false;',
		'fullwidth' => $fullwidth,
	] );
}
