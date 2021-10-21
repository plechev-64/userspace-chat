<?php

defined( 'ABSPATH' ) || exit;

class USPC_Chat extends USPC_Chat_Messages_Query {

	public $chat_id = 0;
	public $chat = [];
	public $chat_room = 'default';
	public $chat_token;
	public $chat_status = 'general';
	public $important = false;
	public $file_upload = 0;
	public $user_id = 0;
	public $paged = 1;
	public $user_list = false;
	public $avatar_size = 50;
	public $office_id;
	public $delay = 0;
	public $timeout = 1;
	public $user_write;
	public $max_words;
	public $user_can;
	public $form = true;
	public $beat = true;
	public $errors = [];
	public $allowed_tags;
	private $once_date;

	function __construct( $args = [] ) {
		parent::__construct();

		$this->return_as = ARRAY_A;

		if ( ! isset( $args['per_page'] ) ) {
			$args['per_page'] = usp_get_option( 'uspc_in_page', 50 );
		}

		if ( ! isset( $args['orderby'] ) ) {
			$args['orderby'] = 'message_time';
		}

		$this->init_properties( $args );

		$this->parse( $args );

		add_filter( 'uspc_message', 'wpautop', 11 );

		if ( ! $this->user_id ) {
			$this->user_id = get_current_user_id();
		}

		if ( ! $this->office_id ) {
			$this->office_id = ( isset( $_POST['office_ID'] ) ) ? intval( $_POST['office_ID'] ) : 0;
		}

		if ( ! $this->max_words ) {
			$this->max_words = usp_get_option( 'uspc_words', 300 );
		}

		if ( ! $this->chat_room ) {
			return;
		}

		USP()->use_module( 'forms' );

		$this->load_resources();

		$this->chat_token = base64_encode( $this->chat_room );

		$this->chat = $this->get_chat_data( $this->chat_room );

		if ( ! $this->user_write ) {
			$this->user_write = ( isset( $_POST['chat']['message'] ) && wp_kses( wp_unslash( $_POST['chat']['message'] ), uspc_allowed_tags() ) ) ? 1 : 0;
		}

		if ( ! $this->chat ) {
			$this->setup_chat();
		} else {
			$this->chat_id = $this->chat->chat_id;
		}

		$updateActivity = $args['update_activity'] ?? 1;

		if ( $updateActivity ) {
			$this->set_activity();
		}

		$this->query['where'][] = "uspc_chat_messages.chat_id = '$this->chat_id'";

		if ( $this->important ) {
			add_filter( 'uspc_main_query', [ &$this, 'add_important_query' ], 10 );
		}

		$this->user_can = ( $this->is_user_can() ) ? 1 : 0;

		$this->query = apply_filters( 'uspc_main_query', $this->query );

		$this->allowed_tags = uspc_allowed_tags();

		do_action( 'uspc_chat_is_load', $this );
	}

	public function load_resources() {
		usp_enqueue_style( 'uspc-chat', USPC_URL . 'assets/css/uspc-chat.css' );
		usp_enqueue_script( 'uspc-chat', USPC_URL . 'assets/js/uspc-chat.js' );
	}

	function init_properties( $args ) {
		$properties = get_class_vars( get_class( $this ) );

		foreach ( $properties as $name => $val ) {
			if ( isset( $args[ $name ] ) ) {
				$this->$name = $args[ $name ];
			}
		}
	}

	function get_chat_data( $chat_room ) {
		if ( ! $chat_room ) {
			return false;
		}

		return uspc_get_chat_by_room( $chat_room );
	}

	function read_chat( $chat_id ) {
		uspc_set_read_chat( $chat_id, $this->user_id );
	}

	function set_activity() {
		global $wpdb;

		// phpcs:disable
		$wpdb->query( "INSERT INTO " . USPC_PREF . "chat_users "
		              . "(`room_place`, `chat_id`, `user_id`, `user_activity`, `user_write`, `user_status`) "
		              . "VALUES('$this->chat_id:$this->user_id', $this->chat_id, $this->user_id, '" . current_time( 'mysql' ) . "', 0, 1) "
		              . "ON DUPLICATE KEY UPDATE user_activity = '" . current_time( 'mysql' ) . "', user_write='$this->user_write'" );
		// phpcs:enable
	}

	function get_users_activity() {
		global $wpdb;

		// phpcs:ignore
		return $wpdb->get_results( "SELECT user_id,user_write FROM " . USPC_PREF . "chat_users WHERE chat_id='$this->chat_id' AND user_id!='$this->user_id' AND user_activity >= ('" . current_time( 'mysql' ) . "' - interval 1 minute)" );
	}

	function get_current_activity() {
		$users = $this->get_users_activity();

		$res = [ $this->user_id => $this->get_user_activity( $this ) ];

		if ( $users ) {
			foreach ( $users as $user ) {
				$res[ $user->user_id ] = $this->get_user_activity( $user );
			}
		}

		return $res;
	}

	function get_user_activity( $user ) {
		if ( ! $user->user_id ) {
			return [
				'link'  => '<span>' . __( 'Guest', 'userspace-chat' ) . '</span>',
				'write' => 0,
			];
		}

		$write = ( $user->user_id == $this->user_id ) ? 0 : $user->user_write;

		return [
			'link'    => usp_user_get_username( $user->user_id, usp_get_tab_permalink( $user->user_id, 'chat' ) ),
			'write'   => $write,
			'user_id' => $user->user_id,
		];
	}

	function add_error( $code, $error_text ) {
		global $wp_errors;

		$wp_errors = new WP_Error();
		$wp_errors->add( $code, $error_text );

		return $wp_errors;
	}

	function is_errors() {
		global $wp_errors;

		if ( isset( $wp_errors->errors ) && $wp_errors->errors ) {
			return true;
		}

		return false;
	}

	function errors() {
		global $wp_errors;

		return $wp_errors;
	}

	function add_message( $message, $attachment = false ) {
		$result = $this->insert_message( $this->chat_id, $this->user_id, trim( $message ) );

		if ( $this->is_errors() ) {
			return $this->errors();
		}

		if ( $attachment ) {
			uspc_chat_add_message_meta( $result['message_id'], 'attachment', $attachment );

			$result['attachment'] = $attachment;
		}

		do_action( 'uspc_add_new_message', $result );

		return $result;
	}

	function setup_chat() {
		if ( ! $this->chat_id ) {
			$this->chat_id = $this->insert_chat( $this->chat_room, $this->chat_status );
		}

		if ( $this->is_errors() ) {
			return $this->errors();
		}

		return $this->chat_id;
	}

	function insert_message( $chat_id, $user_id, $message_text ) {
		$message_text_slash = wp_slash( $message_text );

		$private_key = 0;

		if ( 'private' == $this->chat->chat_status ) {
			$key         = explode( ':', $this->chat->chat_room );
			$private_key = ( $key[1] == $this->user_id ) ? $key[2] : $key[1];

			$user_block = get_user_meta( $private_key, 'usp_black_list:' . $this->user_id );

			if ( $user_block ) {
				$this->add_error( 'insert_message', __( 'You have been blocked on this chat', 'userspace-chat' ) );

				return $this->errors();
			}
		}

		$message_args = [
			'chat_id'         => $chat_id,
			'user_id'         => $user_id,
			'message_content' => $message_text_slash,
			'message_time'    => current_time( 'mysql' ),
			'private_key'     => $private_key,
			'message_status'  => 0,
		];

		$message = apply_filters( 'uspc_pre_insert_message', $message_args );

		if ( ! $message ) {
			$this->add_error( 'insert_message', __( 'The message was not added', 'userspace-chat' ) );

			return $this->errors();
		}

		$result = uspc_add_chat_message( $message );

		if ( ! $result ) {
			$this->add_error( 'insert_message', __( 'The message was not added', 'userspace-chat' ) );

			return $this->errors();
		}

		global $wpdb;

		$message['message_id'] = $wpdb->insert_id;

		do_action( 'uspc_insert_message', $message, $this );

		return wp_unslash( $message );
	}

	function insert_chat( $chat_room, $chat_status ) {
		$chat_id = uspc_insert_chat( $chat_room, $chat_status );

		if ( ! $chat_id ) {
			$this->add_error( 'insert_chat', __( 'Chat was not created', 'userspace-chat' ) );

			return $this->errors();
		}

		return $chat_id;
	}

	function get_chat() {
		global $uspc_chat;

		$content = '';
		if ( $this->chat_id && 'private' == $this->chat_status ) {
			$this->read_chat( $this->chat_id );
		}

		$uspc_chat = $this;

		if ( isset( $this->chat ) && 'general' == $this->chat->chat_status ) {
			$content .= $this->get_messages_header();
		}

		if ( $this->beat ) {
			$content .= '<script>jQuery(function(){uspc_init_chat({'
			            . 'token:"' . $this->chat_token . '",'
			            . 'file_upload:"' . $this->file_upload . '",'
			            . 'max_words:"' . $this->max_words . '",'
			            . 'delay:"' . $this->delay . '",'
			            . 'open_chat:"' . current_time( 'mysql' ) . '",'
			            . 'timeout:"' . $this->timeout . '"
			            })});</script>';
		}

		$content .= '<div class="uspc-im uspc-chat-' . $this->chat_status . ' uspc-chat__room-' . $this->chat_room . ' usps__relative" data-token="' . $this->chat_token . '" data-in_page="' . $this->query['number'] . '">';
		$content .= '<div class="uspc-im__box usps usps__nowrap usps__column usps__relative">';
		$content .= $this->get_messages_box();
		$content .= '</div>';

		if ( $this->form ) {
			$content .= $this->get_form();
		}

		$content .= '</div>';

		$uspc_chat = false;

		return $content;
	}

	function get_form() {
		if ( ! is_user_logged_in() ) {
			$link = usp_get_button( [
				'type'  => 'clear',
				'label' => __( 'to login', 'userspace-chat' ),
				'size'  => 'no',
				'href'  => usp_get_loginform_url( 'login' ),
				'class' => 'usp-entry-bttn usp-login',
			] );

			return usp_get_notice( [
				'type'  => 'error',
				'class' => 'uspc-im__need-login',
				'text'  => __( 'To post messages in the chat you need', 'userspace-chat' ) . ' ' . $link,
			] );
		}

		$content = apply_filters( 'uspc_before_form', '', $this->chat );

		$uploader = false;
		if ( $this->file_upload ) {
			$uploader = new USP_Uploader( 'uspc_chat_uploader', [
				'multiple'     => 0,
				'max_files'    => 1,
				'crop'         => 0,
				'temp_media'   => 1,
				'mode_output'  => 'list',
				'input_attach' => 'chat[attachment]',
				'file_types'   => usp_get_option( 'uspc_file_types', 'jpeg, jpg, png' ),
				'max_size'     => usp_get_option( 'uspc_file_size', 2 ) * 1024,
			] );
		}

		$content .= '<form class="uspc-im__form usps usps__column usp-field usps__relative" action="" method="post">';
		$content .= '<div class="uspc-im-form__input usps usps__nowrap usps__jc-between usps__relative">';

		$content .= '<div class="uspc-im-form__left usps usps__column">';
		if ( $this->file_upload ) {
			$args_uploads = [
				'type'    => 'clear',
				'size'    => 'no',
				'class'   => 'uspc-chat-uploader',
				'content' => $uploader->get_input(),
				'icon'    => 'fa-paperclip',
			];
			$content      .= usp_get_button( $args_uploads );
		}
		$content .= '<span class="uspc-im-form__sign-count">' . $this->max_words . '</span>';
		$content .= '</div>';

		$content .= '<textarea rows="2" maxlength="' . $this->max_words . '" onkeyup="uspc_chat_words_count(event,this);" id="uspc-im-form__area-' . $this->chat_id . '" class="uspc-im-form__textarea" name="chat[message]" placeholder="' . __( 'Write something', 'userspace-chat' ) . '..."></textarea>';

		$content .= usp_get_emoji( 'uspc-im-form__area-' . $this->chat_id, 'uspc-im-emoji' );

		$content .= '</div>';

		$content .= '<div class="uspc-im-form__footer usps usps__nowrap usps__jc-between usps__ai-start">';

		if ( $this->file_upload ) {
			$content .= $uploader->get_gallery();
		}
		$content .= '<div class="uspc-im-form__bttn">' . usp_get_button( [
				'label'      => __( 'Send', 'userspace-chat' ),
				'icon'       => 'fa-paper-plane',
				'icon_align' => 'right',
				'class'      => 'uspc-im-form-bttn__send usps__as-end usp-bttn__disabled',
				'onclick'    => 'uspc_chat_add_message(this);return false;',
			] ) . '</div>';

		$content .= '</div>';

		$hiddens = apply_filters( 'uspc_hidden_fields', [
			'chat[token]'       => $this->chat_token,
			'chat[in_page]'     => $this->query['number'],
			'chat[status]'      => $this->chat_status,
			'chat[user_list]'   => $this->user_list,
			'chat[file_upload]' => $this->file_upload,
		] );

		if ( $hiddens ) {
			foreach ( $hiddens as $name => $val ) {
				$content .= '<input type="hidden" name="' . $name . '" value="' . $val . '">';
			}
		}

		$content .= '</form>';

		$content .= apply_filters( 'uspc_after_form', '', $this->chat );

		return $content;
	}

	function user_list() {
		return '<div class="uspc-im__online uspc-im-header__title usps__grow">'
		       . '<span class="uspc-im-online__title">' . __( 'In chat', 'userspace-chat' ) . ':</span>'
		       . '<div class="uspc-im-online__items usps__inline"></div>'
		       . '</div>';
	}

	function get_messages_header() {
		$args = [];
		if ( $this->user_list ) {
			$args['left'] = $this->user_list();
		}
		$args['important'] = $this->important ? '1' : '';

		return uspc_include_chat_header( $this->user_id, (array) $this->chat, $args );
	}

	function get_messages_box() {
		$navi = false;

		$amount_messages  = $this->count_messages();
		$content_messages = '';
		$class            = '';
		if ( $amount_messages ) {
			$pagenavi = new USP_Pager( [
				'total'   => $amount_messages,
				'number'  => $this->query['number'],
				'current' => $this->paged,
				'class'   => 'uspc-im__nav',
				'onclick' => 'uspc_chat_navi',
			] );

			$this->query['offset'] = $pagenavi->offset;

			$messages = $this->get_messages();

			$content_messages = $this->get_loop( $messages );

			$navi = $pagenavi->get_navi();
		} else {
			if ( $this->important ) {
				$class  = ' uspc-im__talk--empty';
				$notice = __( 'No important messages in this chat', 'userspace-chat' );
			} else {
				$notice = __( 'Chat history will be displayed here', 'userspace-chat' );
			}

			$content_messages .= usp_get_notice( [
				'text'      => $notice,
				'class'     => 'uspc-im-talk__write',
				'no_border' => true,
			] );
		}

		$content = '<div class="uspc-im__talk' . $class . '">';
		$content .= $content_messages;
		$content .= '</div>'; // .uspc-im__talk

		$content .= '<div class="uspc-im__footer usps__relative usps usps__jc-between usps__ai-center">';

		if ( 'private' == $this->chat_status ) {
			$content .= '<div class="uspc-im__writes"><span>......<i class="uspi fa-pencil" aria-hidden="true"></i></span></div>';
		}

		if ( $navi ) {
			$content .= $navi;
		}

		$content .= '</div>';

		return $content;
	}

	function get_messages() {
		$messages = $this->get_data( 'get_results', false, 'ARRAY_A' );

		return apply_filters( 'uspc_messages', $messages );
	}

	function count_messages() {
		return $this->get_count();
	}

	function get_loop( $messages ) {
		krsort( $messages );

		$content = '';
		foreach ( $messages as $k => $message ) {
			$content .= $this->include_template_message_item( $message );
			unset( $k );
		}

		return $content;
	}

	function include_template_message_item( $message ) {
		$item_date = gmdate( 'Y-m-d', strtotime( $message['message_time'] ) );

		return usp_get_include_template( 'uspc-message-item.php', USPC_PATH . 'templates', [
			'message'      => $message,
			'user_id'      => $this->user_id,
			'avatar_size'  => $this->avatar_size,
			'user_can'     => $this->user_can,
			'allowed_tags' => $this->allowed_tags,
			'chat_status'  => $this->chat->chat_status,
			'day_date'     => ( $this->once_date != $item_date ) ? $this->once_date = $item_date : '',
		] );
	}

	function is_user_can() {
		global $current_user;

		$user_can = ( $current_user->user_level >= usp_get_option( 'usp_console_access', 7 ) ) ? 1 : 0;

		return apply_filters( 'uspc_check_user_can', $user_can );
	}

	function add_important_query( $query ) {
		$query['join'][]  = "INNER JOIN " . USPC_PREF . "chat_messagemeta AS chat_messagemeta ON uspc_chat_messages.message_id=chat_messagemeta.message_id";
		$query['where'][] = "chat_messagemeta.meta_key='important:$this->user_id'";

		return $query;
	}

}
