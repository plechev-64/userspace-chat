<?php

defined( 'ABSPATH' ) || exit;

class USPC_Loader {

	protected static $_instance = null;
	public $private_messages_data;

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	public function __construct() {
		$this->includes();
		$this->init_hooks();
		$this->init_direct_message_datas();
	}

	private function includes() {
		USP()->use_module( 'uploader' );

		require_once USPC_PATH . 'classes/class-uspc-query-tables.php';
		require_once USPC_PATH . 'functions/core.php';

		require_once USPC_PATH . 'functions/functions-db.php';
		require_once USPC_PATH . 'functions/functions-tabs.php';
		require_once USPC_PATH . 'functions/functions-template.php';

		require_once USPC_PATH . 'functions/actions.php';
		require_once USPC_PATH . 'functions/actions-cron.php';
		require_once USPC_PATH . 'functions/actions-ajax.php';

		if ( (usp_is_office() || usp_get_option( 'uspc_contact_panel', 0 )) && is_user_logged_in() ) {
			require_once USPC_PATH . 'classes/class-uspc-contact-list.php';
		}

		if ( is_admin() ) {
			require_once USPC_PATH . 'admin/options.php';
		}

		usp_init_beat( 'uspc_chat_beat_core' );
	}

	private function init_hooks() {
		add_action( 'template_redirect', [ $this, 'chat_filter_attachment_pages' ], 20 );
		add_action( 'init', [ $this, 'chat_disable_oembeds' ], 9999 );
		add_action( 'uspc_chat_is_load', [ $this, 'chat_reset_oembed_filter' ] );

		if ( is_user_logged_in() ) {
			add_action( 'usp_enqueue_scripts', [ $this, 'chat_fileupload_scripts' ] );
			add_filter( 'usp_init_js_variables', [ $this, 'init_js_chat_variables' ] );

			if ( usp_get_option( 'usp_bar_show' ) ) {
				add_action( 'usp_bar_buttons', [ $this, 'usp_bar_add_chat_icon' ], 10 );
				add_action( 'usp_bar_profile_menu_buttons', [ $this, 'usp_bar_add_chat_link' ], 20 );
			}

			if ( usp_get_option( 'uspc_contact_panel', 0 ) ) {
				add_action( 'usp_enqueue_scripts', [ $this, 'get_contacts_panel_resources' ] );
				add_action( 'wp_footer', [ $this, 'get_contacts_panel' ], 10 );
			}
		}

		add_shortcode( 'userspace-chat', [ $this, 'chat_shortcode' ] );
	}

	private function init_direct_message_datas() {
		if ( ! is_user_logged_in() )
			return;

		require_once USPC_PATH . 'classes/class-uspc-direct-message-datas.php';

		$this->private_messages_data = new USPC_Direct_Message_Datas();
	}

	// use contacts panel
	function get_contacts_panel() {
		require_once USPC_PATH . 'classes/class-uspc-contacts-panel.php';

		echo (new USPC_Contacts_Panel() )->get_template();
	}

	function get_contacts_panel_resources() {
		usp_enqueue_style( 'uspc-contacts-panel', USPC_URL . 'assets/css/uspc-contacts-panel.css' );
		usp_enqueue_script( 'uspc-contacts-panel', USPC_URL . 'assets/js/uspc-contacts-panel.js' );
	}

	// Chat icon with unread messages
	function usp_bar_add_chat_icon() {
		// if the contact panel is displayed
		if ( usp_get_option( 'uspc_contact_panel', 0 ) )
			return;

		echo usp_get_button( [
			'type'		 => 'clear',
			'icon'		 => 'fa-envelope',
			'class'		 => 'uspc-notify',
			'href'		 => usp_get_tab_permalink( get_current_user_id(), 'chat' ),
			'counter'	 => USPC()->private_messages_data->unread,
		] );
	}

	// Link in to chat on userspace bar
	function usp_bar_add_chat_link() {
		echo usp_get_button( [
			'type'	 => 'clear',
			'size'	 => 'medium',
			'class'	 => 'usp-bar-chat',
			'href'	 => usp_get_tab_permalink( get_current_user_id(), 'chat' ),
			'icon'	 => 'fa-comments',
			'label'	 => __( 'Chat', 'userspace-chat' )
		] );
	}

	function chat_fileupload_scripts() {
		if ( usp_is_office() ) {
			usp_fileupload_scripts();
		}
	}

	// load direct messages & chat js & css
	public function chat_resources() {
		usp_enqueue_style( 'uspc-chat', USPC_URL . 'assets/css/uspc-chat.css' );
		usp_enqueue_script( 'uspc-chat', USPC_URL . 'assets/js/uspc-chat.js' );
	}

	function init_js_chat_variables( $data ) {
		$data[ 'usp_chat' ][ 'sounds' ] = apply_filters( 'uspc_sound', USPC_URL . 'assets/audio/e-oh.mp3' );

		$data[ 'usp_chat' ][ 'delay' ]		 = usp_get_option( 'uspc_delay', 15 );
		$data[ 'usp_chat' ][ 'inactivity' ]	 = usp_get_option( 'uspc_inactivity', 10 );

		$data[ 'local' ][ 'uspc_empty' ]		 = __( 'Write something', 'userspace-chat' );
		$data[ 'local' ][ 'uspc_text_words' ]	 = __( 'Exceeds the maximum message size', 'userspace-chat' );

		return $data;
	}

	function chat_filter_attachment_pages() {
		global $post;

		if ( ! is_single() || ! in_array( $post->post_type, [ 'attachment' ] ) )
			return;

		if ( stripos( $post->post_excerpt, 'uspc_chat_attachment' ) === false )
			return;

		status_header( 404 );
		include( get_query_template( '404' ) );
		exit;
	}

	function chat_disable_oembeds() {
		remove_action( 'wp_head', 'wp_oembed_add_host_js' );
	}

	function chat_reset_oembed_filter() {
		remove_filter( 'pre_oembed_result', 'wp_filter_pre_oembed_result', 10 );
	}

	/**
	 * Builds the General Chat.
	 *
	 * @since 1.0.0
	 *
	 * @param array  $attr {
	 *     Attributes of the chat shortcode.
	 *
	 *     @type string $chat_room      (required) unique chat ID.
	 *     @type bool   $userslist      Display a list of users who are in the chat. 1 - show or 0 (default).
	 *     @type bool   $file_upload    Enables/disables attaching files to chat messages. Available values: 1 or 0 (default).
	 *     @type int    $avatar_size    the size of users ' avatars in the chat (in pixels). By default - 50
	 * }
	 * @return string   HTML content to display chat.
	 */
	function chat_shortcode( $attr ) {
		require_once USPC_PATH . 'classes/class-uspc-chat.php';

		if ( ! isset( $attr[ 'chat_room' ] ) || empty( $attr[ 'chat_room' ] ) ) {
			$attr[ 'chat_room' ] = 'default';
		}

		$file_upload = (isset( $attr[ 'file_upload' ] )) ? $attr[ 'file_upload' ] : 0;

		if ( get_current_user_id() && $file_upload ) {
			usp_fileupload_scripts();
		}

		return (new USPC_Chat( $attr ) )->get_chat();
	}

}
