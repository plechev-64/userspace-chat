<?php

add_filter( 'usp_options', 'uspc_chat_options' );
function uspc_chat_options( $options ) {

    $options->add_box( 'chat', array(
        'title' => __( 'DM & chat settings', 'userspace-chat' ),
        'icon'  => 'fa-comments'
    ) )->add_group( 'general', array(
        'title' => __( 'General settings', 'userspace-chat' )
    ) )->add_options( array(
        [
            'type'      => 'runner',
            'title'     => __( 'Delay between requests for new messages', 'userspace-chat' ),
            'group'     => 'uspc_opt',
            'slug'      => 'delay',
            'value_min' => 5,
            'value_max' => 60,
            'default'   => 15,
            'help'      => __( 'It is recommended to choose at least 10 seconds', 'userspace-chat' ),
            'notice'    => __( 'In seconds.', 'userspace-chat' ) . ' ' . __( 'Default:', 'userspace-chat' ) . ' 10'
        ],
        [
            'type'      => 'runner',
            'title'     => __( 'User downtime', 'userspace-chat' ),
            'slug'      => 'inactivity',
            'group'     => 'uspc_opt',
            'value_min' => 1,
            'value_max' => 20,
            'default'   => 10,
            'help'      => __( "User's inactivity time after which he stops receiving new messages in the chat", 'userspace-chat' ),
            'notice'    => __( 'In minutes.', 'userspace-chat' ) . ' ' . __( 'Default:', 'userspace-chat' ) . ' 10'
        ],
        [
            'type'      => 'runner',
            'title'     => __( 'Antispam', 'userspace-chat' ),
            'slug'      => 'antispam',
            'group'     => 'uspc_opt',
            'value_min' => 0,
            'value_max' => 50,
            'default'   => 0,
            'help'      => __( 'Specify a number of users, who other user will be able to send an unread private message for a day. '
                . 'If its value is exceeded the sending of messages will be blocked.', 'userspace-chat' ) . ' ' . __( 'If 0 - this function is disabled', 'userspace-chat' ),
            'notice'    => __( 'If 0 - this function is disabled', 'userspace-chat' ),
        ],
        [
            'type'      => 'runner',
            'title'     => __( 'Maximum number of characters in a message', 'userspace-chat' ),
            'slug'      => 'words',
            'group'     => 'uspc_opt',
            'value_min' => 100,
            'value_max' => 1000,
            'default'   => 300,
            'notice'    => __( 'Default:', 'userspace-chat' ) . ' 300',
        ],
        [
            'type'      => 'runner',
            'title'     => __( 'Posts per page', 'userspace-chat' ),
            'slug'      => 'in_page',
            'group'     => 'uspc_opt',
            'value_min' => 10,
            'value_max' => 200,
            'default'   => 50,
            'notice'    => __( 'Default:', 'userspace-chat' ) . ' 50',
        ],
        [
            'type'    => 'switch',
            'title'   => __( 'Using oEmbed', 'userspace-chat' ),
            'slug'    => 'oembed',
            'group'   => 'uspc_opt',
            'text'    => [
                'off' => __( 'No', 'userspace-chat' ),
                'on'  => __( 'Yes', 'userspace-chat' )
            ],
            'default' => 0,
            'help'    => __( 'Includes support for WordPress oEmbed in chat. '
                . 'Option is responsible for the incorporation of media content, such as from Youtube or Twitter from the link.', 'userspace-chat' ) . '<br>'
            . __( 'Note: If the page has a lot of embedded content, this may reduce the page loading speed.', 'userspace-chat' ),
        ],
        [
            'type'      => 'switch',
            'title'     => __( 'Attaching files', 'userspace-chat' ),
            'slug'      => 'file_upload',
            'group'     => 'uspc_opt',
            'text'      => [
                'off' => __( 'No', 'userspace-chat' ),
                'on'  => __( 'Yes', 'userspace-chat' )
            ],
            'default'   => 0,
            'childrens' => array(
                1 => array(
                    [
                        'type'    => 'text',
                        'title'   => __( 'Allowed file types', 'userspace-chat' ),
                        'slug'    => 'file_types',
                        'group'   => 'uspc_opt',
                        'default' => 'jpeg, jpg, png',
                        'notice'  => __( 'Default:', 'userspace-chat' ) . ' jpeg, jpg, png',
                        'help'    => __( 'File extension (without dot). For example:', 'userspace-chat' ) . ' jpeg, jpg, png, gif, zip, mp3'
                    ],
                    [
                        'type'       => 'runner',
                        'value_min'  => 1,
                        'value_max'  => 10,
                        'value_step' => 1,
                        'default'    => 2,
                        'title'      => __( 'Maximum file size, MB', 'userspace-chat' ),
                        'slug'       => 'file_size',
                        'group'      => 'uspc_opt',
                        'default'    => 2,
                        'notice'     => __( 'Default:', 'userspace-chat' ) . ' 2',
                    ]
                )
            )
        ]
    ) );

    $options->box( 'chat' )->add_group( 'personal', array(
        'title' => __( 'Personal chat', 'userspace-chat' )
    ) )->add_options( array(
        [
            'type'    => 'number',
            'title'   => __( 'Number of messages in the conversation', 'userspace-chat' ),
            'slug'    => 'messages_amount',
            'group'   => 'uspc_opt',
            'default' => 100,
            'help'    => __( 'The maximum number of messages in a conversation between two users. WordPress cron clears this value once a day.', 'userspace-chat' ),
            'notice'  => __( 'Default:', 'userspace-chat' ) . ' 100',
        ],
        [
            'type'    => 'radio',
            'slug'    => 'messages_mail',
            'group'   => 'uspc_opt',
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
            'slug'      => 'contact_panel',
            'group'     => 'uspc_opt',
            'text'      => [
                'off' => __( 'No', 'userspace-chat' ),
                'on'  => __( 'Yes', 'userspace-chat' )
            ],
            'default'   => 0,
            'help'      => __( 'Includes a fixed contact bar for private messaging at the bottom of all site pages.', 'userspace-chat' ),
            'childrens' => array(
                1 => array(
                    [
                        'type'    => 'radio',
                        'title'   => __( 'Output location', 'userspace-chat' ),
                        'slug'    => 'set_chat_bar',
                        'group'   => 'uspc_opt',
                        'values'  => [
                            __( 'Left', 'userspace-chat' ),
                            __( 'Right', 'userspace-chat' )
                        ],
                        'default' => 1,
                    ]
                )
            )
        ]
    ) );

    return $options;
}
