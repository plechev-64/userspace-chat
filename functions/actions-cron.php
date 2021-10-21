<?php

add_action( 'usp_cron_daily', 'uspc_chat_daily_delete_messages', 10 );
function uspc_chat_daily_delete_messages() {
	$max = usp_get_option( 'uspc_messages_amount', 100 );

	if ( ! $max ) {
		return;
	}

	global $wpdb;

	// phpcs:disable
	/** @noinspection SqlDialectInspection */
	$chats = $wpdb->get_results(
		"SELECT chats.*, COUNT(chat_messages.message_id) AS amount_messages "
		. "FROM " . USPC_PREF . "chats AS chats "
		. "INNER JOIN " . USPC_PREF . "chat_messages AS chat_messages ON chats.chat_id=chat_messages.chat_id "
		. "WHERE chats.chat_status='private' "
		. "AND chat_messages.message_id NOT IN ("
		. "SELECT message_id FROM " . USPC_PREF . "chat_messagemeta "
		. "WHERE meta_key LIKE 'important:%'"
		. ") "
		. "GROUP BY chats.chat_id "
		. "HAVING COUNT(chat_messages.message_id) > '$max'"
	);
	// phpcs:enable

	if ( ! $chats ) {
		return;
	}

	foreach ( $chats as $chat ) {

		if ( $chat->amount_messages <= $max ) {
			continue;
		}

		$amount_delete = $chat->amount_messages - $max;

		// phpcs:disable
		/** @noinspection SqlDialectInspection */
		$messages = $wpdb->get_results( "SELECT message_id,message_status,private_key FROM " . USPC_PREF . "chat_messages "
		                                . "WHERE message_id NOT IN ("
		                                . "SELECT message_id FROM " . USPC_PREF . "chat_messagemeta "
		                                . "WHERE meta_key LIKE 'important:%'"
		                                . ") "
		                                . "AND chat_id='" . $chat->chat_id . "' "
		                                . "ORDER BY message_id ASC "
		                                . "LIMIT $amount_delete"
		);
		// phpcs:enable

		if ( ! $messages ) {
			continue;
		}

		foreach ( $messages as $message ) {

			if ( $message->private_key && ! $message->message_status ) {
				continue;
			}

			uspc_chat_delete_message( $message->message_id );
		}
	}
}

add_action( 'usp_cron_hourly', 'uspc_chat_send_notify_messages', 10 );
function uspc_chat_send_notify_messages() {
	global $wpdb;

	// phpcs:ignore
	$mess = $wpdb->get_results( "SELECT * FROM " . USPC_PREF . "chat_messages WHERE message_status='0' && private_key!='0' && message_time  > date_sub('" . current_time( 'mysql' ) . "', interval 1 hour)" );

	if ( ! $mess ) {
		return;
	}

	$messages = [];
	foreach ( $mess as $m ) {
		$messages[ $m->private_key ][ $m->user_id ][] = $m->message_content;
	}

	usp_add_log( __( 'Send notifications on unread messages', 'userspace-chat' ) );

	$mail_text = usp_get_option( 'uspc_messages_mail', 0 );

	foreach ( $messages as $recipient_id => $data ) {
		$content = '';
		$to      = get_the_author_meta( 'user_email', $recipient_id );

		$cnt = count( $data );

		foreach ( $data as $author_id => $message ) {
			$content .= usp_get_include_template(
				'uspc-mail-unread-message.php',
				USPC_PATH . 'templates',
				[
					'author_id' => $author_id,
					'message'   => $message,
					'send_text' => $mail_text
				]
			);
		}

		$title = __( 'For you', 'userspace-chat' ) . ' ' . $cnt . ' ' . __( 'new messages', 'userspace-chat' );

		usp_mail( $to, $title, $content );
	}
}
