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
            'title'     => __( 'Delay between requests', 'userspace-chat' ),
            'slug'      => 'delay',
            'group'     => 'chat',
            'value_min' => 5,
            'value_max' => 60,
            'default'   => 15,
            'notice'    => __( 'In seconds. It is recommended to choose at '
                . 'least 10 seconds', 'userspace-chat' ),
        ],
        [
            'type'      => 'runner',
            'title'     => __( 'User Downtime', 'userspace-chat' ),
            'slug'      => 'inactivity',
            'group'     => 'chat',
            'value_min' => 1,
            'value_max' => 20,
            'default'   => 10,
            'notice'    => __( 'In minutes. The time of user inactivity '
                . 'after which he ceases to receive new messages in chat', 'userspace-chat' )
        ],
        [
            'type'      => 'runner',
            'title'     => __( 'Antispam', 'userspace-chat' ),
            'slug'      => 'antispam',
            'group'     => 'chat',
            'value_min' => 0,
            'value_max' => 20,
            'default'   => 5,
            'notice'    => __( 'Specify a number of users, who other user will '
                . 'be able to send an unread private message for a day. If its '
                . 'value is exceeded the sending of messages will be blocked. If zero, this function is disabled', 'userspace-chat' )
        ],
        [
            'type'      => 'runner',
            'title'     => __( 'The number of characters in the message', 'userspace-chat' ),
            'slug'      => 'words',
            'group'     => 'chat',
            'value_min' => 100,
            'value_max' => 1000,
            'default'   => 300
        ],
        [
            'type'      => 'runner',
            'title'     => __( 'Posts per page', 'userspace-chat' ),
            'slug'      => 'in_page',
            'group'     => 'chat',
            'value_min' => 10,
            'value_max' => 200,
            'default'   => 50
        ],
        [
            'type'   => 'select',
            'title'  => __( 'Using OEMBED', 'userspace-chat' ),
            'slug'   => 'oembed',
            'group'  => 'chat',
            'values' => [
                __( 'No', 'userspace-chat' ),
                __( 'Yes', 'userspace-chat' )
            ],
            'notice' => __( 'Option is responsible for the incorporation of '
                . 'media content, such as from Youtube or Twitter from the link', 'userspace-chat' ),
        ],
        [
            'type'      => 'select',
            'title'     => __( 'Attaching files', 'userspace-chat' ),
            'slug'      => 'file_upload',
            'group'     => 'chat',
            'values'    => [
                __( 'No', 'userspace-chat' ),
                __( 'Yes', 'userspace-chat' )
            ],
            'childrens' => array(
                1 => array(
                    [
                        'type'    => 'text',
                        'title'   => __( 'Allowed file types', 'userspace-chat' ),
                        'slug'    => 'file_types',
                        'group'   => 'chat',
                        'default' => 'jpeg, jpg, png, zip, mp3',
                        'notice'  => __( 'By default: jpeg, jpg, png, zip, mp3', 'userspace-chat' )
                    ],
                    [
                        'type'       => 'runner',
                        'value_min'  => 1,
                        'value_max'  => 10,
                        'value_step' => 1,
                        'default'    => 2,
                        'title'      => __( 'Maximum file size, MB', 'userspace-chat' ),
                        'slug'       => 'file_size',
                        'group'      => 'chat',
                        'default'    => 2
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
            'group'   => 'chat',
            'default' => 100,
            'notice'  => __( 'The maximum number of messages in the '
                . 'conversation between two users. Default: 100', 'userspace-chat' )
        ],
        [
            'type'   => 'select',
            'slug'   => 'messages_mail',
            'title'  => __( 'Mail alert', 'userspace-chat' ),
            'values' => [
                __( 'Without the text of the message', 'userspace-chat' ),
                __( 'Full text of the message', 'userspace-chat' )
            ]
        ],
        [
            'type'      => 'select',
            'title'     => __( 'Contacts bar', 'userspace-chat' ),
            'slug'      => 'contact_panel',
            'group'     => 'chat',
            'values'    => [
                __( 'No', 'userspace-chat' ),
                __( 'Yes', 'userspace-chat' )
            ],
            'childrens' => array(
                1 => array(
                    [
                        'type'   => 'select',
                        'title'  => __( 'Output location', 'userspace-chat' ),
                        'slug'   => 'place_contact_panel',
                        'group'  => 'chat',
                        'values' => [
                            __( 'Right', 'userspace-chat' ),
                            __( 'Left', 'userspace-chat' )
                        ]
                    ]
                )
            )
        ]
    ) );

    return $options;
}
