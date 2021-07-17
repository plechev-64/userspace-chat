<?php

// init Contacts tab
add_action( 'usp_init_tabs', 'uspc_tab_private_contacts', 10 );
function uspc_tab_private_contacts() {
	$tab_data = array(
		'id'		 => 'chat',
		'name'		 => __( 'Chat', 'userspace-chat' ),
		'supports'	 => [ 'ajax' ],
		'public'	 => 1,
		'icon'		 => 'fa-comments',
		'output'	 => 'menu',
		'counter'	 => uspc_counter_in_tab(),
		'content'	 => array(
			[
				'id'		 => 'private-contacts',
				'name'		 => __( 'Contacts', 'userspace-chat' ),
				'icon'		 => 'fa-book',
				'callback'	 => [
					'name' => 'uspc_chat_tab'
				]
			]
		)
	);

	usp_tab( $tab_data );
}

// number of unread messages in the tab
function uspc_counter_in_tab() {
	if ( ! is_user_logged_in() || ! usp_is_office( get_current_user_id() ) )
		return;

	$count = USPC()->private_messages_data->unread;

	if ( ! $count )
		return;

	if ( $count > 99 )
		$count = apply_filters( 'uspc_number_noread_message', '99+' );

	return $count;
}

function uspc_chat_tab( $office_id ) {
	global $user_ID;

	USPC()->chat_resources();

	if ( $office_id == $user_ID ) {
		return uspc_get_user_contacts_list( get_current_user_id() );
	}

	if ( $user_ID ) {
		$chatdata	 = uspc_get_chat_private( $office_id );
		$chat		 = $chatdata[ 'content' ];
	} else {
		$chat = usp_get_notice( array(
			'type'	 => 'error',
			'text'	 => __( 'Sign in to send a message to the user', 'userspace-chat' )
			) );
	}

	return $chat;
}

function uspc_get_user_contacts_list() {
	require_once USPC_PATH . 'classes/class-uspc-contact-list.php';

	$contactlist = new USPC_Contact_List();

	return $contactlist->get_box();
}

// init important tab
add_action( 'usp_setup_tabs', 'uspc_tab_important', 10 );
function uspc_tab_important() {
	if ( ! usp_is_office( get_current_user_id() ) )
		return;

	$subtab = [
		'id'		 => 'important-messages',
		'name'		 => __( 'Important messages', 'userspace-chat' ),
		'icon'		 => 'fa-star',
		'callback'	 => [
			'name' => 'uspc_get_tab_user_important'
		]
	];

	usp_add_sub_tab( 'chat', $subtab );
}

function uspc_get_tab_user_important( $user_id ) {
	$content = '<div class="uspc-im" data-token="' . get_current_user_id() . '" data-important="1">';
	$content .= '<div class="uspc-im__box usps__relative">' . uspc_important_im_talk_box( $user_id ) . '</div>';
	$content .= '</div>';

	return $content;
}
