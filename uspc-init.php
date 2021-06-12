<?php

add_action( 'usp_init', 'uspc_loading_dependencies' );
function uspc_loading_dependencies() {
    require_once USPC_PATH . 'functions/chats-query.php';
    require_once USPC_PATH . 'functions/core.php';

    require_once USPC_PATH . 'functions/actions.php';
    require_once USPC_PATH . 'functions/actions-cron.php';
    require_once USPC_PATH . 'functions/actions-ajax.php';

    if ( is_admin() ) {
        require_once USPC_PATH . 'admin/options.php';
    }

    usp_init_beat( 'uspc_chat_beat_core' );
}

if ( ! is_admin() ) {
    add_action( 'usp_enqueue_scripts', 'uspc_chat_scripts' );
}
function uspc_chat_scripts() {
    if ( ! is_user_logged_in() )
        return;

    usp_enqueue_script( 'uspc-chat-ion', USPC_URL . 'assets/js/ion.sound.min.js' );
    usp_enqueue_style( 'uspc-chat', USPC_URL . 'assets/css/uspc-chat.css' );
    usp_enqueue_script( 'uspc-chat', USPC_URL . 'assets/js/uspc-chat.js' );

    if ( ! usp_get_option( [ 'uspc_opt', 'file_upload' ], 0 ) )
        return;

    if ( usp_get_option( [ 'uspc_opt', 'contact_panel' ], 1 ) || usp_is_office() ) {
        usp_fileupload_scripts();
    }
}

// js chat global variables
add_filter( 'usp_init_js_variables', 'uspc_init_js_chat_variables', 10 );
function uspc_init_js_chat_variables( $data ) {
    if ( ! is_user_logged_in() )
        return $data;

    $data['usp_chat']['sounds']     = USPC_URL . 'assets/audio/';
    $data['usp_chat']['delay']      = usp_get_option( [ 'uspc_opt', 'delay' ], 15 );
    $data['usp_chat']['inactivity'] = usp_get_option( [ 'uspc_opt', 'inactivity' ], 10 );
    $data['usp_chat']['file_size']  = usp_get_option( [ 'uspc_opt', 'file_size' ], 2 );

    $data['local']['uspc_not_written'] = __( 'Write something', 'userspace-chat' );
    $data['local']['uspc_max_words']   = __( 'Exceeds the maximum message size', 'userspace-chat' );

    return $data;
}

add_action( 'template_redirect', 'uspc_chat_filter_attachment_pages', 20 );
function uspc_chat_filter_attachment_pages() {
    global $post;

    if ( ! is_single() || ! in_array( $post->post_type, array(
            'attachment'
        ) ) )
        return;

    if ( stripos( $post->post_excerpt, 'uspc_chat_attachment' ) === false )
        return;

    status_header( 404 );
    include( get_query_template( '404' ) );
    exit;
}

add_action( 'usp_bar_buttons', 'uspc_bar_add_chat_icon', 10 );
function uspc_bar_add_chat_icon() {
    if ( ! is_user_logged_in() )
        return;

    // if the contact panel is displayed
    if ( usp_get_option( [ 'uspc_opt', 'contact_panel' ], 1 ) )
        return;

    global $user_ID;

    $args = [
        'type'    => 'clear',
        'icon'    => 'fa-envelope',
        'class'   => 'rcl-messages',
        'href'    => usp_get_tab_permalink( $user_ID, 'chat' ),
        'counter' => uspc_chat_noread_messages_amount( $user_ID ),
    ];
    echo usp_get_button( $args );
}

add_filter( 'usp_inline_styles', 'uspc_chat_add_inline_styles', 10, 2 );
function uspc_chat_add_inline_styles( $styles, $rgb ) {
    list($r, $g, $b) = $rgb;

    // dark shade from the button
    $rs = round( $r * 0.95 );
    $gs = round( $g * 0.95 );
    $bs = round( $b * 0.95 );

    $styles .= '.rcl-chat .message-box::before{border-right-color:rgba(' . $r . ',' . $g . ',' . $b . ',.15);}'
        . '.rcl-chat .message-box{background:rgba(' . $r . ',' . $g . ',' . $b . ',.15);}'
        . '.rcl-chat .nth .message-box::before{border-right-color:rgba(' . $r . ',' . $g . ',' . $b . ',.35);}'
        . '.rcl-chat .nth .message-box {background:rgba(' . $r . ',' . $g . ',' . $b . ',.35);}';

    if ( ! is_user_logged_in() )
        return $styles;

    $styles .= '.rcl-chat .important-shift{background:rgba(' . $rs . ',' . $gs . ',' . $bs . ',.85);}';

    if ( usp_get_option( [ 'uspc_opt', 'contact_panel' ], 1 ) == 0 )
        return $styles;

    $styles .= '.rcl-noread-users,.rcl-chat-panel{background:rgba(' . $rs . ',' . $gs . ',' . $bs . ',.85);}'
        . '.rcl-noread-users a.active-chat::before{border-right-color:rgba(' . $rs . ',' . $gs . ',' . $bs . ',.85);}'
        . '.left-panel .rcl-noread-users a.active-chat::before{border-left-color:rgba(' . $rs . ',' . $gs . ',' . $bs . ',.85);}'
        . '.messages-icon .chat-new-messages{background:rgb(' . $rs . ',' . $gs . ',' . $bs . ');}';

    return $styles;
}

add_action( 'init', 'uspc_add_chat_tab', 10 );
function uspc_add_chat_tab() {
    global $user_ID;

    $tab_data = array(
        'id'       => 'chat',
        'name'     => __( 'Chat', 'userspace-chat' ),
        'supports' => array( 'ajax' ),
        'public'   => 1,
        'icon'     => 'fa-comments',
        'output'   => 'menu',
        'content'  => array(
            array(
                'id'       => 'private-contacts',
                'name'     => __( 'Contacts', 'userspace-chat' ),
                'icon'     => 'fa-book',
                'callback' => array(
                    'name' => 'uspc_chat_tab'
                )
            )
        )
    );

    if ( usp_is_office( $user_ID ) ) {
        $tab_data['content'][] = array(
            'id'       => 'important-messages',
            'name'     => __( 'Important messages', 'userspace-chat' ),
            'icon'     => 'fa-star',
            'callback' => array(
                'name' => 'uspc_get_tab_user_important'
            )
        );
    }

    usp_tab( $tab_data );
}

function uspc_chat_tab( $office_id ) {
    global $user_ID;

    if ( $office_id == $user_ID ) {
        return uspc_get_tab_user_contacts( $office_id );
    }

    if ( $user_ID ) {
        $chatdata = uspc_get_chat_private( $office_id );
        $chat     = $chatdata['content'];
    } else {
        $chat = usp_get_notice( array(
            'type' => 'error',
            'text' => __( 'Sign in to send a message to the user', 'userspace-chat' )
            ) );
    }

    return $chat;
}

function uspc_get_chat_private( $user_id, $args = array() ) {
    global $user_ID;

    $chat_room = uspc_get_private_chat_room( $user_id, $user_ID );

    return uspc_get_the_chat_by_room( $chat_room, $args );
}

function uspc_get_the_chat_by_room( $chat_room, $args = array() ) {
    $args = array_merge( array(
        'userslist'   => 1,
        'file_upload' => usp_get_option( [ 'uspc_opt', 'file_upload' ], 0 ),
        'chat_status' => 'private',
        'chat_room'   => $chat_room
        ), $args );

    require_once USPC_PATH . 'classes/class-uspc-chat.php';

    $chat = new USPC_Chat( $args );

    return array(
        'content' => $chat->get_chat(),
        'token'   => $chat->chat_token
    );
}

function uspc_chat_add_page_link_attributes( $attrs ) {

    $attrs['onclick']      = 'uspc_chat_navi(this); return false;';
    $attrs['class']        = 'rcl-chat-page-link';
    $attrs['href']         = '#';
    $attrs['data']['post'] = false;

    return $attrs;
}

function uspc_get_tab_user_contacts() {
    global $user_ID;

    $content = '<h3>' . __( 'User contacts', 'userspace-chat' ) . '</h3>';
    $content .= uspc_get_user_contacts_list( $user_ID );

    return $content;
}

function uspc_get_user_contacts( $user_id, $limit ) {
    global $wpdb;

    $messages = $wpdb->get_results(
        "SELECT t.* FROM ( "
        . "SELECT chat_messages.* FROM " . USPC_PREF . "chat_messages AS chat_messages "
        . "INNER JOIN " . USPC_PREF . "chat_users AS chat_users ON chat_messages.chat_id=chat_users.chat_id "
        . "WHERE chat_messages.private_key!='0' "
        . "AND (chat_messages.user_id='$user_id' OR chat_messages.private_key='$user_id') "
        . "AND chat_users.user_id='$user_id' "
        . "AND chat_users.user_status!='0' "
        . "ORDER BY chat_messages.message_time DESC "
        . "LIMIT 18446744073709551615 "
        . ") "
        . "AS t "
        . "GROUP BY t.chat_id "
        . "ORDER BY t.message_time DESC "
        . "LIMIT $limit[0],$limit[1]"
        , ARRAY_A
    );

    $messages = stripslashes_deep( $messages );

    return $messages;
}

function uspc_get_user_contacts_list( $user_id ) {
    global $wpdb;

    $amount = $wpdb->query(
        "SELECT COUNT(chat_messages.chat_id) FROM " . USPC_PREF . "chat_messages AS chat_messages "
        . "INNER JOIN " . USPC_PREF . "chat_users AS chat_users ON chat_messages.chat_id=chat_users.chat_id "
        . "WHERE chat_messages.private_key!='0' "
        . "AND (chat_messages.user_id='$user_id' OR chat_messages.private_key='$user_id') "
        . "AND chat_users.user_id='$user_id' "
        . "AND chat_users.user_status!='0' "
        . "GROUP BY chat_messages.chat_id "
    );

    if ( ! $amount ) {

        $notice = __( 'No contacts yet. Start a chat with another user on his page', 'userspace-chat' );

        if ( usp_get_option( 'usp_users_page' ) ) {
            $notice .= '. <a href="' . get_permalink( usp_get_option( 'usp_users_page' ) ) . '">' . __( 'Choose from the list of users', 'userspace-chat' ) . '</a>.';
        }

        return usp_get_notice( [
            'text' => apply_filters( 'uspc_chat_no_contacts_notice', $notice, $user_id )
            ] );
    }

    usp_dialog_scripts();

    $inpage = 20;

    //$pagenavi = new Rcl_PageNavi( 'chat-contacts', $amount, array( 'in_page' => $inpage ) );

    $pagenavi = new USP_Pager( array(
        'total'  => $amount,
        'number' => $inpage,
        'class'  => 'chat-contacts',
        ) );

    $messages = uspc_get_user_contacts( $user_id, array( $pagenavi->offset, $inpage ) );

    foreach ( $messages as $k => $message ) {
        $messages[$k]['user_id']   = ($message['user_id'] == $user_id) ? $message['private_key'] : $message['user_id'];
        $messages[$k]['author_id'] = $message['user_id'];
    }

    $content = '<div class="rcl-chat-contacts">';

    $content .= '<div class="contacts-counter"><span>' . __( 'Total number of contacts', 'userspace-chat' ) . ': ' . $amount . '</span></div>';

    foreach ( $messages as $message ) {

        $class = ( ! $message['message_status']) ? 'noread-message' : '';

        $content .= '<div class="contact-box preloader-parent" data-contact="' . $message['user_id'] . '">';
        $content .= '<a href="#" title="' . __( 'Delete contact', 'userspace-chat' ) . '" onclick="uspc_chat_remove_contact(this,' . $message['chat_id'] . ');return false;" class="chat-remove"><i class="uspi fa-times" aria-hidden="true"></i></a>';
        $content .= '<a href="#" title="' . __( 'Open chat in window', 'userspace-chat' ) . '" onclick="uspc_get_chat_window(this,' . $message['user_id'] . ');return false;" class="chat-window-restore"><i class="uspi fa-window-restore" aria-hidden="true"></i></a>';
        $content .= '<a class="chat-contact ' . $class . '" href="' . usp_get_tab_permalink( $message['user_id'], 'chat' ) . '">';

        $content .= '<div class="avatar-contact">'
            . get_avatar( $message['user_id'], 50 )
            . '</div>';

        $content .= '<div class="message-content">'
            . '<div class="message-meta">'
            . '<span class="author-name">' . get_the_author_meta( 'display_name', $message['user_id'] ) . '</span>'
            . '<span class="time-message">' . usp_human_time_diff( $message['message_time'] ) . ' ' . __( 'ago', 'userspace-chat' ) . '</span>'
            . '</div>'
            . '<div class="message-text">'
            . (($user_id == $message['author_id']) ? '<span class="master-avatar">' . get_avatar( $user_id, 25 ) . '</span>' : '')
            . uspc_chat_excerpt( $message['message_content'] )
            . '</div>'
            . '</div>';

        $content .= '</a>';

        $content .= '</div>';
    }

    $content .= '</div>';

    $content .= $pagenavi->get_navi();

    return $content;
}

function uspc_get_tab_user_important( $user_id ) {

    $amount_messages = uspc_chat_count_important_messages( $user_id );

    if ( ! $amount_messages ) {
        return usp_get_notice( array(
            'type' => 'error',
            'text' => __( 'No important messages yet', 'userspace-chat' )
            ) );
    }

    require_once USPC_PATH . 'classes/class-uspc-chat.php';

    $chat = new USPC_Chat();

    $content = '<div class="rcl-chat">';

    $content .= '<div class="chat-content">';

    $content .= '<div class="chat-messages-box">';

    $content .= '<div class="chat-messages">';

    //$pagenavi = new Rcl_PageNavi( 'rcl-chat', $amount_messages, array( 'in_page' => $chat->query['number'] ) );

    $pagenavi = new USP_Pager( array(
        'total'  => $amount_messages,
        'number' => $chat->query['number'],
        'class'  => 'rcl-chat',
        ) );

    $chat->offset = $pagenavi->offset;

    $messages = uspc_chat_get_important_messages( $user_id, array( $pagenavi->offset, $chat->query['number'] ) );

    $messages = uspc_chat_messages_add_important_meta( $messages );

    krsort( $messages );

    foreach ( $messages as $k => $message ) {
        $content .= $chat->get_message_box( $message );
    }

    $content .= '</div>';

    $content .= '</div>';

    $content .= '</div>';

    $content .= '</div>';

    $content .= $pagenavi->get_navi();

    return $content;
}

add_action( 'wp_footer', 'uspc_get_last_chats_box', 10 );
function uspc_get_last_chats_box() {
    if ( ! is_user_logged_in() )
        return;

    if ( ! usp_get_option( [ 'uspc_opt', 'contact_panel' ], 1 ) )
        return;

    global $user_ID;

    $messages = uspc_get_user_contacts( $user_ID, array( 0, 5 ) );

    if ( ! $messages )
        return;

    foreach ( $messages as $message ) {
        $user_id                    = ($message['user_id'] == $user_ID) ? $message['private_key'] : $message['user_id'];
        $users[$user_id]['status']  = ( ! $message['message_status'] && $message['private_key'] == $user_ID) ? 0 : 1;
        $users[$user_id]['chat_id'] = $message['chat_id'];
    }

    $new_counter = uspc_chat_noread_messages_amount( $user_ID );

    $class = array();

    $class[] = ( ! usp_get_option( [ 'uspc_opt', 'set_chat_bar' ], 0 ) ) ? 'left-panel' : 'right-panel';

    $class[] = (isset( $_COOKIE['uspc_chat_contact_panel'] ) && $_COOKIE['uspc_chat_contact_panel']) ? '' : 'hidden-contacts';

    echo '<div id="uspc-chat-noread-box" class="' . implode( ' ', $class ) . '">';

    echo '<div class="rcl-mini-chat"></div>';

    echo '<div class="rcl-noread-users">';
    echo '<span class="messages-icon">'
    . '<a href="' . usp_get_tab_permalink( $user_ID, 'chat' ) . '" onclick="return uspc_chat_shift_contact_panel();">'
    . '<i class="uspi fa-envelope" aria-hidden="true"></i>';

    if ( $new_counter ) {
        echo '<span class="chat-new-messages">' . $new_counter . '</span>';
    }

    echo '</a>'
    . '</span>'
    . '<div class="chat-contacts">';

    global $user_LK;

    foreach ( $users as $user_id => $data ) {

        if ( $user_id == $user_LK )
            continue;

        echo '<span class="rcl-chat-user contact-box" data-contact="' . $user_id . '">';
        echo '<a class="chat-delete-contact" href="#" title="' . __( 'Delete contact', 'userspace-chat' ) . '" onclick="uspc_chat_remove_contact(this,' . $data['chat_id'] . ');return false;"><i class="uspi fa-times" aria-hidden="true"></i></a>';
        echo '<a href="#" onclick="uspc_get_mini_chat(this,' . $user_id . '); return false;">';
        if ( ! $data['status'] )
            echo '<i class="uspi fa-comment-dots" aria-hidden="true"></i>';
        echo get_avatar( $user_id, 40 );
        echo '</a>';
        echo '</span>';
    }

    echo '<span class="more-contacts">'
    . '<a href="' . usp_get_tab_permalink( $user_ID, 'chat' ) . '">'
    . '. . .';
    echo '</a>'
    . '</span>';

    echo '</div>';

    echo '</div>';

    echo '</div>';
}

function uspc_get_private_chat_room( $user_1, $user_2 ) {
    return ($user_1 < $user_2) ? 'private:' . $user_1 . ':' . $user_2 : 'private:' . $user_2 . ':' . $user_1;
}

add_action( 'init', 'uspc_chat_disable_oembeds', 9999 );
function uspc_chat_disable_oembeds() {
    remove_action( 'wp_head', 'wp_oembed_add_host_js' );
}

add_shortcode( 'rcl-chat', 'uspc_chat_shortcode' );
function uspc_chat_shortcode( $atts ) {
    if ( ! isset( $atts['chat_room'] ) || empty( $atts['chat_room'] ) ) {
        return __( 'Not set attributes: chat_room', 'userspace-chat' );
    }

    global $user_ID;

    $file_upload = (isset( $atts['file_upload'] )) ? $atts['file_upload'] : 0;

    if ( $user_ID && $file_upload ) {
        usp_fileupload_scripts();
    }


    require_once USPC_PATH . 'classes/class-uspc-chat.php';

    $chat = new USPC_Chat( $atts );

    return $chat->get_chat();
}

add_action( 'uspc_chat_is_load', 'uspc_chat_reset_oembed_filter' );
function uspc_chat_reset_oembed_filter() {
    remove_filter( 'pre_oembed_result', 'wp_filter_pre_oembed_result', 10 );
}
