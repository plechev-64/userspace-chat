<?php

defined( 'ABSPATH' ) || exit;

class USPC_Contact_List {

	public $offset = 0;
	public $in_page = 6;
	public $current = 1;
	public $user_id = 0;
	public $chat_ids = [];
	public $messages = '';

	function __construct( $args = [] ) {
		if ( ! isset( $args['user_id'] ) ) {
			$this->user_id = get_current_user_id();
		}

		if ( isset( $args['in_page'] ) ) {
			$this->in_page = $args['in_page'];
		}

		if ( isset( $args['current'] ) ) {
			$this->current = $args['current'];
		}

		$this->get_pagination();

		$this->chat_ids = USPC()->private_messages_data->chat_ids;

		if ( isset( $args['current'] ) && $args['current'] > 1 ) {
			$this->messages = $this->get_messages_by_chat_id();
		} else {
			$this->messages = USPC()->private_messages_data->messages;
		}
	}

	function get_messages_by_chat_id() {
		if ( ! $this->chat_ids ) {
			return false;
		}

		global $wpdb;

		// phpcs:disable
		$messages = $wpdb->get_results(
			"SELECT mess.* FROM " . USPC_PREF . "chat_messages as mess,"
			. "(SELECT chat_id,"
			. "MAX(message_time) as message_time "
			. "FROM " . USPC_PREF . "chat_messages "
			. "WHERE chat_id IN (" . $this->chat_ids . ") "
			. "GROUP BY chat_id) max_time "
			. "WHERE mess.chat_id=max_time.chat_id "
			. "AND mess.message_time=max_time.message_time "
			. "ORDER BY max_time.message_time DESC "
			. "LIMIT " . $this->offset . "," . $this->in_page
			, ARRAY_A
		);

		// phpcs:enable

		return stripslashes_deep( $messages );
	}

	function get_notice() {
		usp_enqueue_style( 'usp-users-rows', USP_URL . 'modules/users-list/assets/css/usp-users-rows.css' );

		$notice = __( 'No contacts yet. Start a chat with another user on his page', 'userspace-chat' ) . '<br>';

		$notice .= usp_get_button( [
			'type'       => 'clear',
			'label'      => __( 'Open userlist in a modal window', 'userspace-chat' ),
			'icon'       => 'fa-address-book',
			'icon_align' => 'right',
			'onclick'    => 'uspc_get_userlist(this);return false;',
			'class'      => 'uspc-userlist',
		] );

		return usp_get_notice( [ 'text' => apply_filters( 'uspc_no_contacts_notice', $notice, $this->user_id ), 'class' => 'uspc-not-contacts' ] );
	}

	function get_box() {
		if ( ! $this->chat_ids ) {
			return $this->get_notice();
		}

		$content = '<div class="uspc-userlist">';

		$content .= $this->get_loop();

		$content .= '</div>';

		$content .= $this->get_pagination();

		return $content;
	}

	function get_loop() {
		$messages = $this->get_data_messages();

		$content = '';

		foreach ( $messages as $message ) {
			$content .= usp_get_include_template( 'uspc-contact-list-item.php', USPC_PATH . 'templates', [
				'message'       => $message,
				'user_id'       => $this->user_id,
				'number_unread' => uspc_count_unread_by_user( $message['contact_id'] ),
			] );
		}

		return $content;
	}

	function get_data_messages() {
		$messages = $this->messages;

		foreach ( $messages as $k => $message ) {
			$messages[ $k ]['contact_id'] = ( $message['user_id'] == $this->user_id ) ? $message['private_key'] : $message['user_id'];
			$messages[ $k ]['author_id']  = $message['user_id'];
		}

		return $messages;
	}

	function get_pagination() {
		$pagenavi = new USP_Pager( [
			'total'   => USPC()->private_messages_data->contacts,
			'number'  => $this->in_page,
			'current' => $this->current,
			'class'   => 'uspc-mini__nav',
			'onclick' => 'uspc_contacts_navi'
		] );

		$this->offset = $pagenavi->offset;

		return $pagenavi->get_navi();
	}

}
