<?php

defined( 'ABSPATH' ) || exit;

class USPC_Chat_All_Important extends USPC_Chat {

	public $user_id = 0;
	public $current_page = 1;
	public $count_important = 0;

	function __construct( $args ) {
		parent::__construct();

		if ( $args['user_id'] ) {
			$this->user_id = $args['user_id'];
		}
		if ( $args['current_page'] ) {
			$this->current_page = $args['current_page'];
		}

		$this->count_important = $this->count_important_messages();
	}

	function count_important_messages() {
		return ( new USPC_Chat_Messages_Query() )
			->join( 'message_id', ( new USPC_Chat_Messagemeta_Query() )
				->where( [ 'meta_key' => 'important:' . $this->user_id ] ) // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			)
			->get_count();
	}

	function get_important_messages( $limit ) {
		$messagesData = ( new USPC_Chat_Messages_Query() )
			->join( 'message_id', ( new USPC_Chat_Messagemeta_Query() )
				->where( [ 'meta_key' => 'important:' . $this->user_id ] ) // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			)
			->orderby( 'message_time' )
			->limit( $limit[1], $limit[0] )
			->get_results( false, ARRAY_A );

		$messages = uspc_chat_messages_add_attachments_meta( $messagesData );

		return stripslashes_deep( $messages );
	}

	function message_no_important() {
		return usp_get_notice( [
			'text'  => __( 'No important messages yet', 'userspace-chat' ),
			'class' => 'uspc_no_important',
		] );
	}

	public function get_box_important_messages() {
		if ( ! $this->count_important ) {
			return $this->message_no_important();
		}

		$pagenavi = new Pager( [
			'total'   => $this->count_important,
			'current' => $this->current_page,
			'class'   => 'uspc-im__nav',
			'onclick' => 'uspc_chat_navi',
		] );

		$messages_important = $this->get_important_messages( [ $pagenavi->offset, 30 ] );

		$messages = uspc_chat_messages_add_important_meta( $messages_important );

		$content = '<div class="uspc-im__talk">' . $this->get_loop( $messages ) . '</div>';

		$content .= '<div class="uspc-im__footer usps__relative">' . $pagenavi->get_navi() . '</div>';

		return $content;
	}

}
