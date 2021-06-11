<?php

add_filter( 'uspc_chat_messages', 'uspc_chat_messages_add_important_meta', 10 );
function uspc_chat_messages_add_important_meta( $messages ) {
    if ( ! $messages )
        return $messages;

    global $wpdb, $user_ID;

    $ids = array();
    foreach ( $messages as $message ) {
        $ids[] = $message['message_id'];
    }

    $metas = $wpdb->get_results( "SELECT * FROM " . USPC_PREF . "chat_messagemeta WHERE message_id IN (" . implode( ',', $ids ) . ") AND meta_key = 'important:$user_ID' AND meta_value = '1'" );

    if ( ! $metas )
        return $messages;

    $important = array();
    foreach ( $metas as $meta ) {
        $important[$meta->message_id] = $meta->meta_value;
    }

    foreach ( $messages as $k => $message ) {
        $messages[$k]['important'] = (isset( $important[$message['message_id']] )) ? 1 : 0;
    }

    return $messages;
}

add_filter( 'uspc_chat_messages', 'uspc_chat_messages_add_attachments_meta', 10 );
function uspc_chat_messages_add_attachments_meta( $messages ) {
    if ( ! $messages )
        return $messages;

    global $wpdb;

    $ids = array();
    foreach ( $messages as $message ) {
        $ids[] = $message['message_id'];
    }

    $metas = $wpdb->get_results( "SELECT * FROM " . USPC_PREF . "chat_messagemeta WHERE message_id IN (" . implode( ',', $ids ) . ") AND meta_key = 'attachment'" );

    if ( ! $metas )
        return $messages;

    $attachments = array();
    foreach ( $metas as $meta ) {
        $attachments[$meta->message_id] = $meta->meta_value;
    }

    foreach ( $messages as $k => $message ) {
        $messages[$k]['attachment'] = (isset( $attachments[$message['message_id']] )) ? $attachments[$message['message_id']] : 0;
    }

    return $messages;
}

add_action( 'uspc_chat_insert_message', 'uspc_chat_add_user_contact', 10 );
function uspc_chat_add_user_contact( $message ) {
    $chat = uspc_get_chat( $message['chat_id'] );

    if ( $chat->chat_status == 'private' ) {
        global $wpdb;

        $result = $wpdb->update(
            USPC_PREF . 'chat_users', array(
            'user_status' => 1
            ), array(
            'chat_id'     => $message['chat_id'],
            'user_status' => 0
            )
        );
    }
}

add_filter( 'uspc_pre_insert_chat_message', 'uspc_chat_check_message_blocked', 10 );
function uspc_chat_check_message_blocked( $message ) {
    if ( ! defined( 'DOING_AJAX' ) || ! DOING_AJAX )
        return $message;

    if ( ! $message['private_key'] )
        return $message;

    global $user_ID;

    if ( get_user_meta( $message['private_key'], 'usp_black_list:' . $user_ID ) ) {
        wp_send_json( array( 'error' => __( 'You have been blocked on this chat', 'userspace-chat' ) ) );
    }

    return $message;
}

add_action( 'uspc_chat_add_message', 'uspc_chat_update_attachment_data', 10 );
function uspc_chat_update_attachment_data( $message ) {
    if ( ! isset( $message['attachment'] ) )
        return false;

    wp_update_post( array(
        'ID'           => $message['attachment'],
        'post_excerpt' => 'uspc_chat_attachment:' . $message['message_id']
    ) );
}

add_action( 'uspc_insert_chat', 'uspc_chat_insert_private_users', 10 );
function uspc_chat_insert_private_users( $chat_id ) {
    $chat = uspc_get_chat( $chat_id );

    if ( $chat->chat_status == 'private' ) {

        $key = explode( ':', $chat->chat_room );

        uspc_chat_insert_user( $chat_id, $key[1], 1, 0 );
        uspc_chat_insert_user( $chat_id, $key[2], 1, 0 );
    }
}

add_action( 'uspc_chat_delete_message', 'uspc_chat_delete_message_data', 10 );
function uspc_chat_delete_message_data( $message_id ) {

    $attachment_id = uspc_chat_get_message_meta( $message_id, 'attachment' );

    if ( $attachment_id ) {
        wp_delete_attachment( $attachment_id );
    }

    uspc_chat_delete_message_meta( $message_id );
}

add_action( 'delete_attachment', 'uspc_chat_delete_message_attachment', 10 );
function uspc_chat_delete_message_attachment( $attachment_id ) {
    global $wpdb;

    return $wpdb->query( "DELETE FROM " . USPC_PREF . "chat_messagemeta WHERE meta_value='$attachment_id' AND meta_key = 'attachment'" );
}

add_action( 'delete_user', 'uspc_chat_delete_userdata', 10 );
function uspc_chat_delete_userdata( $user_id ) {

    $chats = RQ::tbl( new USPC_Chats_Query() )
        ->select( [
            'chat_id',
            'chat_status'
        ] )
        ->join( 'chat_id', RQ::tbl( new USPC_Chat_Users_Query() )->where( [ 'user_id' => $user_id ] ) )
        ->number( -1 )
        ->get_results();

    if ( $chats ) {
        foreach ( $chats as $chat ) {
            //If the chat is private
            if ( $chat->chat_status == 'private' ) {
                //delete chat with users, messages and their metadata
                uspc_delete_chat( $chat->chat_id );

                continue;
            }

            //remove the user from the chat with all his messages and their metadata
            uspc_chat_delete_user( $chat->chat_id, $user_id );
        }
    }
}
