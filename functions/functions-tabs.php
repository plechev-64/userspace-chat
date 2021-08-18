<?php

// init Contacts tab
add_action( 'usp_init_tabs', 'uspc_tab_private_contacts', 10 );
function uspc_tab_private_contacts() {
	$tab_data = array(
		'id'       => 'chat',
		'name'     => __( 'Chat', 'userspace-chat' ),
		'supports' => [ 'ajax' ],
		'public'   => 1,
		'icon'     => 'fa-comments',
		'output'   => 'menu',
		'counter'  => uspc_counter_in_tab(),
		'content'  => array(
			[
				'id'       => 'private-contacts',
				'name'     => __( 'Contacts', 'userspace-chat' ),
				'icon'     => 'fa-book',
				'callback' => [
					'name' => 'uspc_chat_tab'
				]
			]
		)
	);

	usp_tab( $tab_data );
}

// number of unread messages in the tab
function uspc_counter_in_tab() {
	if ( ! is_user_logged_in() || ! usp_is_office( get_current_user_id() ) ) {
		return;
	}

	$count = USPC()->private_messages_data->unread;

	if ( ! $count ) {
		return;
	}

	if ( $count > 99 ) {
		$count = apply_filters( 'uspc_number_noread_message', '99+' );
	}

	return $count;
}

function uspc_chat_tab( $office_id ) {
	if ( ! is_user_logged_in() ) {
		$link = usp_get_button( [
			'type'  => 'clear',
			'label' => __( 'Sign in', 'userspace-chat' ),
			'size'  => 'no',
			'href'  => usp_get_loginform_url( 'login' ),
			'class' => 'usp-entry-bttn usp-login'
		] );

		return usp_get_notice( [ 'text' => $link . ' ' . __( 'to send a message to the user', 'userspace-chat' ) ] );
	}

	USPC()->chat_resources();

	if ( USP()->office()->is_owner( get_current_user_id() ) ) {
		return ( new USPC_Contact_List() )->get_box();
	}

	$chatdata = uspc_get_chat_private( $office_id );

	return '<div class="uspc-messenger">' . $chatdata['content'] . '</div>';
}

// init important tab
add_action( 'usp_setup_tabs', 'uspc_tab_important', 10 );
function uspc_tab_important() {
	if ( ! usp_is_office( get_current_user_id() ) ) {
		return;
	}

	usp_add_sub_tab( 'chat', [
		'id'       => 'important-messages',
		'name'     => __( 'Important messages', 'userspace-chat' ),
		'icon'     => 'fa-star',
		'callback' => [
			'name' => 'uspc_get_tab_user_important'
		]
	] );
}

function uspc_get_tab_user_important( $user_id ) {
	$content = '<div class="uspc-im" data-token="' . get_current_user_id() . '" data-important="1">';
	$content .= '<div class="uspc-im__box usps__relative">' . uspc_important_im_talk_box( $user_id ) . '</div>';
	$content .= '</div>';

	return $content;
}

// add in subtab title number of contacts
add_filter( 'usp_subtab_title', 'uspc_add_counter_in_subtitle', 10, 2 );
function uspc_add_counter_in_subtitle( $title, $subtab_id ) {
	if ( $subtab_id == 'private-contacts' && usp_is_office( get_current_user_id() ) ) {
		$contacts_num = USPC()->private_messages_data->contacts;

		if ( $contacts_num ) {
			$title = $title . ': <span>' . $contacts_num . '</span>';
		}
	}

	return $title;
}

// add class to a button in the menu
add_filter( 'usp_tab_class_button', 'uspc_add_class_in_button_chat', 10, 2 );
function uspc_add_class_in_button_chat( $class, $tab_id ) {
	if ( $tab_id == 'chat' && usp_is_office( get_current_user_id() ) ) {
		// uspc_js_counter_unread - special class for the common js function
		array_push( $class, 'uspc_js_counter_unread' );
	}

	return $class;
}
