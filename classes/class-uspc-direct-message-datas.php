<?php

defined( 'ABSPATH' ) || exit;

class USPC_Direct_Message_Datas {

    public $in_page  = 6;
    public $user_id  = 0;
    public $contacts = 0;
    public $unread   = 0;
    public $chat_ids = '';
    public $messages = '';

    function __construct( $args = [] ) {
        $this->user_id = get_current_user_id();

        if ( isset( $args['in_page'] ) )
            $this->in_page = $args['in_page'];

        if ( usp_is_office() || usp_get_option( [ 'uspc_opt', 'contact_panel' ], 0 ) ) {
            $this->chat_ids = $this->get_user_pm_contacts();
            if ( $this->chat_ids ) {
                $this->contacts = count( explode( ',', $this->chat_ids ) );
            }

            $this->messages = $this->get_messages();
        }

        if ( usp_is_office() || usp_get_option( [ 'uspc_opt', 'contact_panel' ], 0 ) || usp_get_option( 'usp_bar_show' ) ) {
            $this->unread = $this->count_noread_messages();
        }
    }

    // get contacts in private messages
    private function get_user_pm_contacts() {
        global $wpdb;

        $chats = $wpdb->get_col(
            "SELECT DISTINCT (chat_id) "
            . "FROM " . USPC_PREF . "chat_messages "
            . "WHERE private_key != '0' "
            . "AND (user_id='$this->user_id' OR private_key='$this->user_id');"
        );

        if ( $chats )
            return implode( ',', $chats );

        return $chats;
    }

    // get messages data
    private function get_messages() {
        if ( ! $this->chat_ids )
            return false;

        global $wpdb;

        $messages = $wpdb->get_results(
            "SELECT mess.* FROM " . USPC_PREF . "chat_messages as mess,"
            . "(SELECT chat_id,"
            . "MAX(message_time) as message_time "
            . "FROM " . USPC_PREF . "chat_messages "
            . "WHERE chat_id IN (" . $this->chat_ids . ") "
            . "GROUP BY chat_id) max_time "
            . "WHERE mess.chat_id=max_time.chat_id "
            . "AND mess.message_time=max_time.message_time "
            . "ORDER BY max_time.message_time DESC "
            . "LIMIT " . $this->in_page
            , ARRAY_A
        );

        return wp_unslash( $messages );
    }

    // count incoming unread messages
    private function count_noread_messages() {
        return ( new USPC_Chat_Messages_Query() )->where(
                [
                    'private_key'    => $this->user_id,
                    'message_status' => 0
                ]
            )->get_count();
    }

}
