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

		if ( is_admin() ) {
			require_once USPC_PATH . 'admin/options.php';
		}

		usp_init_beat( 'uspc_chat_beat_core', [ 'uspc_chat_beat_core' ] );
	}

	private function init_hooks() {
		add_action( 'template_redirect', [ $this, 'chat_filter_attachment_pages' ], 20 );
		add_action( 'init', [ $this, 'chat_disable_oembed' ], 9999 );
		add_action( 'uspc_chat_is_load', [ $this, 'chat_reset_oembed_filter' ] );
		add_filter( 'usp_init_js_variables', [ $this, 'init_js_chat_variables' ] );

		add_action( 'init', [ $this, 'customizer_init' ] );

		if ( is_user_logged_in() ) {
			usp_include_modal_user_details();

			add_action( 'usp_init', [ $this, 'init_contact_list' ] );
			add_action( 'usp_office_setup', [ $this, 'init_direct_message_data' ] );
			add_action( 'usp_enqueue_scripts', [ $this, 'chat_file_upload_scripts' ] );

			if ( usp_get_option_customizer( 'usp_bar_show', 1 ) ) {
				add_action( 'usp_bar_buttons', [ $this, 'usp_bar_add_chat_icon' ], 10 );
			}

			if ( usp_get_option( 'uspc_contact_panel', 0 ) ) {
				add_action( 'usp_enqueue_scripts', [ $this, 'get_contacts_panel_resources' ] );
				add_action( 'wp_footer', [ $this, 'get_contacts_panel' ], 10 );
			}
		}

		add_shortcode( 'userspace-chat', [ $this, 'chat_shortcode' ] );
	}

	function customizer_init() {
		require_once USPC_PATH . 'customizer/customizer.php';
	}

	function init_contact_list() {
		if ( usp_is_office() || usp_get_option( 'uspc_contact_panel', 0 ) ) {
			require_once USPC_PATH . 'classes/class-uspc-contact-list.php';
		}
	}

	function init_direct_message_data() {
		require_once USPC_PATH . 'classes/class-uspc-direct-message-data.php';

		$this->private_messages_data = new USPC_Direct_Message_Data();
	}

	// use contacts panel
	function get_contacts_panel() {
		require_once USPC_PATH . 'classes/class-uspc-contacts-panel.php';

		// WPCS: XSS ok, sanitization ok. This sanitized in: templates/uspc-contacts-panel.php
		echo ( new USPC_Contacts_Panel() )->get_template(); // phpcs:ignore
	}

	function get_contacts_panel_resources() {
		USP()->use_module( 'fields' );
		usp_dialog_scripts();
		$this->chat_resources();

		usp_enqueue_style( 'uspc-contacts-panel', USPC_URL . 'assets/css/uspc-contacts-panel.css' );
		usp_enqueue_script( 'uspc-contacts-panel', USPC_URL . 'assets/js/uspc-contacts-panel.js' );
	}

	// Chat icon with unread messages
	function usp_bar_add_chat_icon() {
		// if the contact panel is displayed
		if ( usp_get_option( 'uspc_contact_panel', 0 ) ) {
			return;
		}

		// WPCS: XSS ok, sanitization ok.
		// phpcs:ignore
		echo usp_get_button( [
			'type'    => 'clear',
			'icon'    => 'fa-envelope',
			'class'   => 'uspc-notify uspc_js_counter_unread',
			'href'    => esc_url( usp_get_tab_permalink( get_current_user_id(), 'chat' ) ),
			'counter' => intval( USPC()->private_messages_data->unread ),
		] );
	}

	function chat_file_upload_scripts() {
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
		$data['usp_chat']['sounds']     = apply_filters( 'uspc_sound', USPC_URL . 'assets/audio/e-oh.mp3' );
		$data['usp_chat']['delay']      = usp_get_option( 'uspc_delay', 15 );
		$data['usp_chat']['inactivity'] = usp_get_option( 'uspc_inactivity', 10 );
		$data['usp_chat']['words']      = usp_get_option( 'uspc_words', 300 );

		$data['local']['uspc_empty']        = __( 'Write something', 'userspace-chat' );
		$data['local']['uspc_text_words']   = __( 'Exceeds the maximum message size', 'userspace-chat' );
		$data['local']['uspc_in_chat']      = __( 'In chat', 'userspace-chat' );
		$data['local']['uspc_network_lost'] = __( 'Check your internet connection', 'userspace-chat' );

		if ( is_user_logged_in() ) {
			$theme = usp_get_option_customizer( 'uspc_theme', '#beb5ff' );
			[ $r, $g, $b ] = sscanf( $theme, "#%02x%02x%02x" );

			$data['uspc_css']['from']  = $theme;
			$data['uspc_css']['r']     = $r;
			$data['uspc_css']['g']     = $g;
			$data['uspc_css']['b']     = $b;
			$data['uspc_css']['alpha'] = usp_get_option_customizer( 'uspc_alpha', '0.2' );
		}

		return $data;
	}

	function chat_filter_attachment_pages() {
		global $post;

		if ( ! is_single() || 'attachment' != $post->post_type ) {
			return;
		}

		if ( stripos( $post->post_excerpt, 'uspc_chat_attachment' ) === false ) {
			return;
		}

		status_header( 404 );
		include( get_query_template( '404' ) );
		exit;
	}

	function chat_disable_oembed() {
		remove_action( 'wp_head', 'wp_oembed_add_host_js' );
	}

	function chat_reset_oembed_filter() {
		remove_filter( 'pre_oembed_result', 'wp_filter_pre_oembed_result' );
	}

	/**
	 * Builds the General Chat.
	 *
	 * @param array $attr Attributes of the chat shortcode.
	 * $attr = [
	 *
	 * @type string $chat_room (required) unique chat ID.
	 * @type bool $user_list Display a list of users who are in the chat. 1 - show or 0 (default).
	 * @type bool $file_upload Enables/disables attaching files to chat messages. Available values: 1 or 0 (default).
	 * @type int $avatar_size The size of users avatars in the chat (in pixels). By default - 50.
	 *
	 * ]
	 *
	 * @return string   HTML content to display chat.
	 * @since 1.0.0
	 *
	 */
	function chat_shortcode( $attr ) {
		require_once USPC_PATH . 'classes/class-uspc-chat.php';

		if ( ! isset( $attr['chat_room'] ) || empty( $attr['chat_room'] ) ) {
			$attr['chat_room'] = 'default';
		}

		$file_upload = ( isset( $attr['file_upload'] ) ) ? $attr['file_upload'] : 0;

		if ( get_current_user_id() && $file_upload ) {
			usp_fileupload_scripts();
		}

		return uspc_get_chat_box( ( new USPC_Chat( $attr ) )->get_chat() );
	}

}
