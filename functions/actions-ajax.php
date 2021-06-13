<?php

usp_ajax_action( 'uspc_get_ajax_chat_window' );
function uspc_get_ajax_chat_window() {
    usp_verify_ajax_nonce();

    $user_id = intval( $_POST['user_id'] );

    $chatdata = uspc_get_chat_private( $user_id );

    wp_send_json( array(
        'dialog' => array(
            'content'     => $chatdata['content'],
            'title'       => __( 'Chat with', 'userspace-chat' ) . ' ' . get_the_author_meta( 'display_name', $user_id ),
            'class'       => 'uspc-chat-window',
            'size'        => 'small',
            'buttonClose' => false,
            'onClose'     => array( 'uspc_chat_clear_beat', array( $chatdata['token'] ) )
        )
    ) );
}

usp_ajax_action( 'uspc_chat_remove_contact', false );
function uspc_chat_remove_contact() {
    usp_verify_ajax_nonce();

    global $user_ID;

    $chat_id = intval( $_POST['chat_id'] );

    uspc_chat_update_user_status( $chat_id, $user_ID, 0 );

    $res['remove'] = true;

    wp_send_json( $res );
}

usp_ajax_action( 'uspc_get_chat_page', true );
function uspc_get_chat_page() {
    usp_verify_ajax_nonce();

    $chat_page  = intval( $_POST['page'] );
    $in_page    = intval( $_POST['in_page'] );
    $important  = intval( $_POST['important'] );
    $chat_token = $_POST['token'];
    $chat_room  = uspc_chat_token_decode( $chat_token );

    if ( ! uspc_get_chat_by_room( $chat_room ) )
        return;

    require_once USPC_PATH . 'classes/class-uspc-chat.php';

    $chat = new USPC_Chat(
        array(
        'chat_room' => $chat_room,
        'paged'     => $chat_page,
        'important' => $important,
        'in_page'   => $in_page
        )
    );

    $res['content'] = $chat->get_messages_box();

    wp_send_json( $res );
}

usp_ajax_action( 'uspc_chat_add_message', false );
function uspc_chat_add_message() {
    usp_verify_ajax_nonce();

    $POST = wp_unslash( $_POST['chat'] );

    $chat_room = uspc_chat_token_decode( $POST['token'] );

    if ( ! uspc_get_chat_by_room( $chat_room ) )
        return false;

    $antispam = usp_get_option( [ 'uspc_opt', 'antispam' ], 0 );

    if ( $antispam = apply_filters( 'uspc_chat_antispam_option', $antispam ) ) {
        global $user_ID;

        $query = new USPC_Chat_Messages_Query();

        $args = [
            'user_id'                => $user_ID,
            'private_key__not_in'    => [ 0 ],
            'message_status__not_in' => [ 1 ],
            'date_query'             => [
                [
                    'column'  => 'message_time',
                    'compare' => '=',
                    'last'    => '24 HOUR'
                ]
            ],
            'groupby'                => 'private_key'
        ];

        $cntLastMess = $query->parse( $args )->get_count();

        if ( $cntLastMess > $antispam )
            wp_send_json( [
                'error' => __( 'Your activity has sings of spam!', 'userspace-chat' )
            ] );
    }

    $attach = (isset( $POST['attachment'] )) ? $POST['attachment'] : false;

    $content = '';

    $newMessages = uspc_chat_get_new_messages( ( object ) array(
            'last_activity'   => $_POST['last_activity'],
            'token'           => $POST['token'],
            'user_write'      => 0,
            'update_activity' => 0
        ) );

    if ( isset( $newMessages['content'] ) && $newMessages['content'] ) {
        $res['new_messages'] = 1;
        $content             .= $newMessages['content'];
    }

    require_once USPC_PATH . 'classes/class-uspc-chat.php';

    $chat = new USPC_Chat( array( 'chat_room' => $chat_room ) );

    $result = $chat->add_message( $POST['message'], $attach );

    if ( isset( $result->errors ) && $result->errors ) {
        $res['errors'] = $result->errors;
        wp_send_json( $res );
    }

    if ( $attach )
        usp_delete_temp_media( $attach );

    if ( isset( $result['errors'] ) ) {
        wp_send_json( $result );
    }

    $res['content']       = $content . $chat->get_message_box( $result );
    $res['last_activity'] = current_time( 'mysql' );

    wp_send_json( $res );
}

usp_ajax_action( 'uspc_get_chat_private_ajax', false );
function uspc_get_chat_private_ajax() {
    usp_verify_ajax_nonce();

    $user_id = intval( $_POST['user_id'] );

    $chatdata = uspc_get_chat_private( $user_id, array( 'avatar_size' => 30, 'userslist' => 0 ) );

    $chat = '<div class="uspc-chat-panel">'
        . '<a href="' . usp_get_tab_permalink( $user_id, 'chat' ) . '"><i class="uspi fa-search-plus" aria-hidden="true"></i></a>'
        . '<a href="#" onclick="uspc_chat_close(this);return false;"><i class="uspi fa-times" aria-hidden="true"></i></a>'
        . '</div>';
    $chat .= $chatdata['content'];

    $result['content']    = $chat;
    $result['chat_token'] = $chatdata['token'];

    wp_send_json( $result );
}

usp_ajax_action( 'uspc_chat_message_important', false );
function uspc_chat_message_important() {
    usp_verify_ajax_nonce();

    global $user_ID;

    $message_id = intval( $_POST['message_id'] );

    $important = uspc_chat_get_message_meta( $message_id, 'important:' . $user_ID );

    if ( $important ) {
        uspc_chat_delete_message_meta( $message_id, 'important:' . $user_ID );
    } else {
        uspc_chat_add_message_meta( $message_id, 'important:' . $user_ID, 1 );
    }

    $result['important'] = ($important) ? 0 : 1;

    wp_send_json( $result );
}

usp_ajax_action( 'uspc_chat_important_manager_shift', false );
function uspc_chat_important_manager_shift() {
    usp_verify_ajax_nonce();

    $chat_token       = wp_slash( $_POST['token'] );
    $status_important = intval( $_POST['status_important'] );
    $chat_room        = uspc_chat_token_decode( $chat_token );

    if ( ! uspc_get_chat_by_room( $chat_room ) )
        return false;

    require_once USPC_PATH . 'classes/class-uspc-chat.php';

    $chat = new USPC_Chat( array( 'chat_room' => $chat_room, 'important' => $status_important ) );

    $res['content'] = $chat->get_messages_box();

    wp_send_json( $res );
}

usp_ajax_action( 'uspc_chat_delete_attachment', false );
function uspc_chat_delete_attachment() {
    usp_verify_ajax_nonce();

    $attachment_id = intval( $_POST['attachment_id'] );

    if ( ! $attachment_id )
        return false;

    if ( ! $post = get_post( $attachment_id ) )
        return false;

    global $user_ID;

    if ( $post->post_author != $user_ID )
        return false;

    wp_delete_attachment( $attachment_id );

    $result['remove'] = true;

    wp_send_json( $result );
}

usp_ajax_action( 'uspc_chat_ajax_delete_message', false );
function uspc_chat_ajax_delete_message() {
    usp_verify_ajax_nonce();

    if ( ! $message_id = intval( $_POST['message_id'] ) )
        return false;

    global $current_user;

    if ( $current_user->user_level >= usp_get_option( 'usp_consol_access', 7 ) ) {
        uspc_chat_delete_message( $message_id );
    }

    $result['remove'] = true;

    wp_send_json( $result );
}
