<?php

defined( 'ABSPATH' ) || exit;

class USPC_Contacts_Panel {

	public $user_id = 0;
	public $messages = '';

	function __construct( $args = [] ) {
		if ( ! isset( $args['user_id'] ) ) {
			$this->user_id = get_current_user_id();
		}

		$this->messages = USPC()->private_messages_data->messages;
	}

	function get_messages() {
		$messages = $this->messages;

		if ( ! $messages ) {
			return false;
		}

		$users = [];

		foreach ( $messages as $message ) {
			$user_id                      = ( $message['user_id'] == $this->user_id ) ? $message['private_key'] : $message['user_id'];
			$users[ $user_id ]['status']  = ( ! $message['message_status'] && $message['private_key'] == $this->user_id ) ? 0 : 1;
			$users[ $user_id ]['chat_id'] = $message['chat_id'];
		}

		return $users;
	}

	function get_template() {
		$messages = $this->get_messages();

		if ( ! $messages ) {
			return false;
		}

		$message_in = USPC()->private_messages_data->unread;

		return usp_get_include_template( 'uspc-contacts-panel.php', USPC_PATH . 'templates', [ 'users' => $messages, 'unread' => $message_in ] );
	}

}
