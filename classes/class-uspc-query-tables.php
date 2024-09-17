<?php

defined( 'ABSPATH' ) || exit;

class USPC_Chats_Query extends QueryBuilder {
	function __construct() {

		$table = [
			'name' => USPC_PREF . "chats",
			'as'   => 'uspc_chats',
			'cols' => [
				'chat_id',
				'chat_room',
				'chat_status'
			]
		];

		parent::__construct( $table );
	}

}

class USPC_Chat_Users_Query extends QueryBuilder {
	function __construct() {

		$table = [
			'name' => USPC_PREF . "chat_users",
			'as'   => 'uspc_chat_users',
			'cols' => [
				'room_place',
				'chat_id',
				'user_id',
				'user_activity',
				'user_write',
				'user_status'
			]
		];

		parent::__construct( $table );
	}

}

class USPC_Chat_Messages_Query extends QueryBuilder {
	function __construct() {

		$table = [
			'name' => USPC_PREF . "chat_messages",
			'as'   => 'uspc_chat_messages',
			'cols' => [
				'message_id',
				'chat_id',
				'user_id',
				'message_content',
				'message_time',
				'private_key',
				'message_status'
			]
		];

		parent::__construct( $table );
	}

}

class USPC_Chat_Messagemeta_Query extends QueryBuilder {
	function __construct() {

		$table = [
			'name' => USPC_PREF . "chat_messagemeta",
			'as'   => 'uspc_chat_messagemeta',
			'cols' => [
				'meta_id',
				'message_id',
				'meta_key',
				'meta_value'
			]
		];

		parent::__construct( $table );
	}

}
