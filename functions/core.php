<?php

function uspc_get_chats( $args ) {
    return RQ::tbl( new USPC_Chats_Query() )->parse( $args )->get_results();
}

function uspc_get_chat( $chat_id ) {
    return RQ::tbl( new USPC_Chats_Query() )->where( array(
            'chat_id' => $chat_id
        ) )->get_row();
}

function uspc_get_chat_by_room( $chat_room ) {
    return RQ::tbl( new USPC_Chats_Query() )->where( array(
            'chat_room' => $chat_room
        ) )->get_row();
}

function uspc_insert_chat( $chat_room, $chat_status ) {
    global $wpdb;

    $result = $wpdb->insert(
        USPC_PREF . 'chats', array(
        'chat_room'   => $chat_room,
        'chat_status' => $chat_status
        )
    );

    if ( ! $result ) {
        usp_add_log( 'uspc_insert_chat: ' . __( 'Failed to add chat', 'userspace-chat' ), array( $chat_room, $chat_status ) );
    }

    $chat_id = $wpdb->insert_id;

    do_action( 'uspc_insert_chat', $chat_id );

    return $chat_id;
}

function uspc_delete_chat( $chat_id ) {
    global $wpdb;

    $result = $wpdb->query( "DELETE FROM " . USPC_PREF . "chats WHERE chat_id='$chat_id'" );

    do_action( 'uspc_delete_chat', $chat_id );

    return $result;
}

add_action( 'uspc_delete_chat', 'uspc_chat_remove_users', 10 );
function uspc_chat_remove_users( $chat_id ) {
    global $wpdb;

    $result = $wpdb->query( "DELETE FROM " . USPC_PREF . "chat_users WHERE chat_id='$chat_id'" );

    do_action( 'uspc_chat_remove_users', $chat_id );

    return $result;
}

add_action( 'uspc_chat_remove_users', 'uspc_chat_remove_messages', 10 );
add_action( 'uspc_chat_delete_user', 'uspc_chat_remove_messages', 10, 2 );
function uspc_chat_remove_messages( $chat_id, $user_id = false ) {

    $args = [
        'chat_id' => $chat_id
    ];

    if ( $user_id ) {
        $args['user_id'] = $user_id;
    }

    //get all the messages in this chat
    $messages = uspc_chat_get_messages( $args );

    if ( $messages ) {
        foreach ( $messages as $message ) {
            //delete the message with the metadata
            uspc_chat_delete_message( $message->message_id );
        }
    }

    do_action( 'uspc_chat_remove_messages', $chat_id, $user_id );
}

function uspc_chat_delete_user( $chat_id, $user_id ) {
    global $wpdb;

    $result = $wpdb->query( "DELETE FROM " . USPC_PREF . "chat_users WHERE chat_id='$chat_id' AND user_id='$user_id'" );

    do_action( 'uspc_chat_delete_user', $chat_id, $user_id );

    return $result;
}

function uspc_chat_get_users( $chat_id ) {
    return RQ::tbl( new USPC_Chat_Users_Query() )->select( [
            'user_id'
        ] )->where( array(
            'chat_id' => $chat_id,
        ) )->get_col();
}

function uspc_chat_get_user_status( $chat_id, $user_id ) {
    return RQ::tbl( new USPC_Chat_Users_Query() )->select( [ 'user_status' ] )->where( array(
            'chat_id' => $chat_id,
            'user_id' => $user_id
        ) )->get_var();
}

function uspc_chat_insert_user( $chat_id, $user_id, $status = 1, $activity = 1 ) {
    global $wpdb;

    $user_activity = ($activity) ? current_time( 'mysql' ) : '0000-00-00 00:00:00';

    $args = array(
        'room_place'    => $chat_id . ':' . $user_id,
        'chat_id'       => $chat_id,
        'user_id'       => $user_id,
        'user_activity' => $user_activity,
        'user_write'    => 0,
        'user_status'   => $status
    );

    $result = $wpdb->insert(
        USPC_PREF . 'chat_users', $args
    );

    if ( ! $result ) {
        usp_add_log( 'uspc_chat_insert_user: ' . __( 'Failed to add user to the chat', 'userspace-chat' ), $args );
    }

    return $result;
}

function uspc_chat_delete_message( $message_id ) {
    global $wpdb;

    do_action( 'uspc_chat_pre_delete_message', $message_id );

    $result = $wpdb->query( "DELETE FROM " . USPC_PREF . "chat_messages WHERE message_id='$message_id'" );

    do_action( 'uspc_chat_delete_message', $message_id );

    return $result;
}

function uspc_chat_get_messages( $args ) {
    return RQ::tbl( new USPC_Chat_Messages_Query() )->parse( $args )->get_results();
}

function uspc_chat_count_messages( $args ) {
    return RQ::tbl( new USPC_Chat_Messages_Query() )->parse( $args )->get_count();
}

function uspc_chat_get_message( $message_id ) {
    return RQ::tbl( new USPC_Chat_Messages_Query() )->where( array(
            'message_id' => $message_id
        ) )->get_row();
}

function uspc_chat_get_message_meta( $message_id, $meta_key ) {
    return RQ::tbl( new USPC_Chat_Messagemeta_Query() )->select( [ 'meta_value' ] )->where( array(
            'message_id' => $message_id,
            'meta_key'   => $meta_key
        ) )->get_var();
}

function uspc_chat_add_message_meta( $message_id, $meta_key, $meta_value ) {
    global $wpdb;

    $args = [
        'message_id' => $message_id,
        'meta_key'   => $meta_key,
        'meta_value' => $meta_value
    ];

    $result = $wpdb->insert(
        USPC_PREF . 'chat_messagemeta', $args
    );

    if ( ! $result ) {
        usp_add_log( 'uspc_chat_add_message_meta: ' . __( 'Failed to send mets data of the message', 'userspace-chat' ), $args );
    }

    return $result;
}

function uspc_chat_delete_message_meta( $message_id, $meta_key = false ) {
    global $wpdb;

    $sql = "DELETE FROM " . USPC_PREF . "chat_messagemeta WHERE message_id = '$message_id'";

    if ( $meta_key ) {
        $sql .= "AND meta_key = '$meta_key'";
    }

    $result = $wpdb->query( $sql );

    return $result;
}

function uspc_chat_update_user_status( $chat_id, $user_id, $status ) {
    global $wpdb;

    $result = $wpdb->query( "INSERT INTO " . USPC_PREF . "chat_users "
        . "(`room_place`, `chat_id`, `user_id`, `user_activity`, `user_write`, `user_status`) "
        . "VALUES('$chat_id:$user_id', $chat_id, $user_id, '" . current_time( 'mysql' ) . "', 0, $status) "
        . "ON DUPLICATE KEY UPDATE user_status='$status'" );

    if ( ! $result ) {
        usp_add_log( 'uspc_chat_update_user_status: ' . __( 'Failed to refresh user status in the chat', 'userspace-chat' ), array( $chat_id, $user_id, $status ) );
    }

    return $result;
}

function uspc_chat_token_encode( $chat_room ) {
    return base64_encode( $chat_room );
}

function uspc_chat_token_decode( $chat_token ) {
    return base64_decode( $chat_token );
}

function uspc_chat_excerpt( $string ) {
    $max = 120;

    $string = wp_kses( $string, array() );

    if ( iconv_strlen( $string, 'utf-8' ) <= $max )
        return $string;

    $string = substr( $string, 0, $max );
    $string = rtrim( $string, "!,.-" );
    $string = substr( $string, 0, strrpos( $string, ' ' ) );

    return $string . "… ";
}

function uspc_chat_noread_messages_amount( $user_id ) {
    return RQ::tbl( new USPC_Chat_Messages_Query() )->where( array(
            'private_key'    => $user_id,
            'message_status' => 0
        ) )->get_count();
}

function uspc_chat_get_important_messages( $user_id, $limit ) {
    $messagesData = RQ::tbl( new USPC_Chat_Messages_Query() )
        ->join( 'message_id', RQ::tbl( new USPC_Chat_Messagemeta_Query() )
            ->where( [ 'meta_key' => 'important:' . $user_id ] )
        )
        ->orderby( 'message_time' )
        ->limit( $limit[1], $limit[0] )
        ->get_results( false, ARRAY_A );

    return stripslashes_deep( $messagesData );
}

function uspc_chat_count_important_messages( $user_id ) {
    return RQ::tbl( new USPC_Chat_Messages_Query() )
            ->join( 'message_id', RQ::tbl( new USPC_Chat_Messagemeta_Query() )
                ->where( [ 'meta_key' => 'important:' . $user_id ] )
            )
            ->get_count();
}

function uspc_chat_get_new_messages( $post ) {
    $chat_room = uspc_chat_token_decode( $post->token );

    if ( ! uspc_get_chat_by_room( $chat_room ) )
        return false;

    $content = '';

    require_once USPC_PATH . 'classes/class-uspc-chat.php';

    $chat = new USPC_Chat( array(
        'chat_room'       => $chat_room,
        'user_write'      => $post->user_write,
        'update_activity' => $post->update_activity
        ) );

    if ( $post->last_activity ) {
        global $user_ID;

        $chat->query['where'][] = "message_time > '$post->last_activity'";
        if ( $user_ID )
            $chat->query['where'][] = "user_id != '$user_ID'";

        $messages = $chat->get_messages();

        if ( $messages ) {

            krsort( $messages );

            foreach ( $messages as $k => $message ) {
                $content .= $chat->get_message_box( $message );
            }

            $chat->read_chat( $chat->chat_id );
        }

        $res['content'] = $content;
    }

    if ( $activity     = $chat->get_current_activity() )
        $res['users'] = $activity;

    $res['success']      = true;
    $res['token']        = $post->token;
    $res['current_time'] = current_time( 'mysql' );

    return $res;
}
