<?php

add_filter( 'usp_options', 'uspc_chat_options' );
function uspc_chat_options( $options ) {
	$options->add_box( 'chat', [
		'title' => __( 'DM & chat settings', 'userspace-chat' ),
		'icon'  => 'fa-comments'
	] )->add_group( 'general', [
		'title' => __( 'General settings', 'userspace-chat' )
	] )->add_options( [
		[
			'type'      => 'runner',
			'title'     => __( 'Delay between requests for new messages', 'userspace-chat' ),
			'slug'      => 'uspc_delay',
			'value_min' => 5,
			'value_max' => 60,
			'default'   => 15,
			'help'      => __( 'It is recommended to choose at least 10 seconds', 'userspace-chat' ),
			'notice'    => __( 'In seconds.', 'userspace-chat' ) . ' ' . __( 'Default:', 'userspace-chat' ) . ' 10'
		],
		[
			'type'      => 'runner',
			'title'     => __( 'User downtime', 'userspace-chat' ),
			'slug'      => 'uspc_inactivity',
			'value_min' => 1,
			'value_max' => 20,
			'default'   => 10,
			'help'      => __( "User's inactivity time after which he stops receiving new messages in the chat", 'userspace-chat' ),
			'notice'    => __( 'In minutes.', 'userspace-chat' ) . ' ' . __( 'Default:', 'userspace-chat' ) . ' 10'
		],
		[
			'type'      => 'runner',
			'title'     => __( 'Antispam', 'userspace-chat' ),
			'slug'      => 'uspc_antispam',
			'value_min' => 0,
			'value_max' => 50,
			'default'   => 0,
			'help'      => __( 'Specify a number of users, who other user will be able to send an unread private message for a day. If its value is exceeded the sending of messages will be blocked.', 'userspace-chat' ) . ' '
			               . __( 'If 0 - this function is disabled', 'userspace-chat' ),
			'notice'    => __( 'If 0 - this function is disabled', 'userspace-chat' ),
		],
		[
			'type'      => 'runner',
			'title'     => __( 'Maximum number of characters in a message', 'userspace-chat' ),
			'slug'      => 'uspc_words',
			'value_min' => 100,
			'value_max' => 1000,
			'default'   => 300,
			'notice'    => __( 'Default:', 'userspace-chat' ) . ' 300',
		],
		[
			'type'      => 'runner',
			'title'     => __( 'Posts per page', 'userspace-chat' ),
			'slug'      => 'uspc_in_page',
			'value_min' => 10,
			'value_max' => 200,
			'default'   => 50,
			'notice'    => __( 'Default:', 'userspace-chat' ) . ' 50',
		],
		[
			'type'    => 'switch',
			'title'   => __( 'Using oEmbed', 'userspace-chat' ),
			'slug'    => 'uspc_oembed',
			'text'    => [
				'off' => __( 'No', 'userspace-chat' ),
				'on'  => __( 'Yes', 'userspace-chat' )
			],
			'default' => 0,
			'help'    => __( 'Includes support for WordPress oEmbed in chat. Option is responsible for the incorporation of media content, such as from Youtube or Twitter from the link.', 'userspace-chat' ) . '<br>'
			             . __( 'Note: If the page has a lot of embedded content, this may reduce the page loading speed.', 'userspace-chat' ),
		],
		[
			'type'      => 'switch',
			'title'     => __( 'Attaching files', 'userspace-chat' ),
			'slug'      => 'uspc_file_upload',
			'text'      => [
				'off' => __( 'No', 'userspace-chat' ),
				'on'  => __( 'Yes', 'userspace-chat' )
			],
			'default'   => 0,
			'childrens' => [
				1 => [
					[
						'type'    => 'text',
						'title'   => __( 'Allowed file types', 'userspace-chat' ),
						'slug'    => 'uspc_file_types',
						'default' => 'jpeg, jpg, png',
						'notice'  => __( 'Default:', 'userspace-chat' ) . ' jpeg, jpg, png',
						'help'    => __( 'File extension (without dot). For example:', 'userspace-chat' ) . ' jpeg, jpg, png, gif, zip, mp3'
					],
					[
						'type'       => 'runner',
						'value_min'  => 1,
						'value_max'  => 10,
						'value_step' => 1,
						'title'      => __( 'Maximum file size, MB', 'userspace-chat' ),
						'slug'       => 'uspc_file_size',
						'default'    => 2,
						'notice'     => __( 'Default:', 'userspace-chat' ) . ' 2',
					]
				]
			]
		]
	] );

	$options->box( 'chat' )->add_group( 'personal', [
		'title' => __( 'Personal chat', 'userspace-chat' )
	] )->add_options( [
		[
			'type'    => 'number',
			'title'   => __( 'Number of messages in the conversation', 'userspace-chat' ),
			'slug'    => 'uspc_messages_amount',
			'default' => 100,
			'help'    => __( 'The maximum number of messages in a conversation between two users. WordPress cron clears this value once a day.', 'userspace-chat' ),
			'notice'  => __( 'Default:', 'userspace-chat' ) . ' 100',
		],
		[
			'type'    => 'radio',
			'slug'    => 'uspc_messages_mail',
			'title'   => __( 'Email alert', 'userspace-chat' ),
			'values'  => [
				__( 'Without the text of the message', 'userspace-chat' ),
				__( 'Full text of the message', 'userspace-chat' )
			],
			'default' => 0,
			'help'    => __( 'Unread messages are sent to mail once an hour via WordPress cron', 'userspace-chat' ),
		],
		[
			'type'      => 'switch',
			'title'     => __( 'Contacts panel', 'userspace-chat' ),
			'slug'      => 'uspc_contact_panel',
			'text'      => [
				'off' => __( 'No', 'userspace-chat' ),
				'on'  => __( 'Yes', 'userspace-chat' )
			],
			'default'   => 0,
			'help'      => __( 'Includes a fixed contact bar for private messaging at the bottom of all site pages.', 'userspace-chat' ),
			'childrens' => [
				1 => [
					[
						'type'    => 'radio',
						'title'   => __( 'Output location', 'userspace-chat' ),
						'slug'    => 'uspc_set_chat_bar',
						'values'  => [
							__( 'Left', 'userspace-chat' ),
							__( 'Right', 'userspace-chat' )
						],
						'default' => 1,
					]
				]
			]
		]
	] );

	return $options;
}
