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
	if ( ! is_user_logged_in() ) {
		return usp_get_notice( [ 'text' => __( 'Sign in to send a message to the user', 'userspace-chat' ) ] );
	}

	USPC()->chat_resources();

	if ( USP()->office()->is_owner( get_current_user_id() ) ) {
		return ( new USPC_Contact_List() )->get_box();
	}

	$chatdata = uspc_get_chat_private( $office_id );

	return $chatdata[ 'content' ];
}

// init important tab
add_action( 'usp_setup_tabs', 'uspc_tab_important', 10 );
function uspc_tab_important() {
	if ( ! usp_is_office( get_current_user_id() ) )
		return;

	usp_add_sub_tab( 'chat', [
		'id'		 => 'important-messages',
		'name'		 => __( 'Important messages', 'userspace-chat' ),
		'icon'		 => 'fa-star',
		'callback'	 => [
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
