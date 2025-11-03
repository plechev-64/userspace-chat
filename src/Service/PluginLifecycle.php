<?php

declare(strict_types=1);

namespace UserSpace\Chat\Service;

class PluginLifecycle
{
    /**
     * Создает таблицы плагина при активации.
     */
    public static function onActivation(): void
    {
        //print_r(1);exit;
        global $wpdb;
        $charsetCollate = $wpdb->get_charset_collate();
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $chatsTable = $wpdb->prefix . 'userspace_chats';
        $participantsTable = $wpdb->prefix . 'userspace_chat_participants';
        $messagesTable = $wpdb->prefix . 'userspace_chat_messages';
        $attachmentsTable = $wpdb->prefix . 'userspace_chat_attachments';

        $sql = "CREATE TABLE {$chatsTable} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            type ENUM('private', 'group', 'topic') NOT NULL,
            title VARCHAR(255) NULL,
            topic_id VARCHAR(255) NULL,
            creator_id BIGINT(20) UNSIGNED NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY (topic_id),
            FOREIGN KEY (creator_id) REFERENCES {$wpdb->users}(ID) ON DELETE CASCADE
        ) {$charsetCollate};

        CREATE TABLE {$participantsTable} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            chat_id BIGINT(20) UNSIGNED NOT NULL,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            role ENUM('member', 'admin') NOT NULL DEFAULT 'member',
            joined_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY (chat_id, user_id),
            FOREIGN KEY (chat_id) REFERENCES {$chatsTable}(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES {$wpdb->users}(ID) ON DELETE CASCADE
        ) {$charsetCollate};

        CREATE TABLE {$messagesTable} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            chat_id BIGINT(20) UNSIGNED NOT NULL,
            sender_id BIGINT(20) UNSIGNED NOT NULL,
            content TEXT NOT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            FOREIGN KEY (chat_id) REFERENCES {$chatsTable}(id) ON DELETE CASCADE,
            FOREIGN KEY (sender_id) REFERENCES {$wpdb->users}(ID) ON DELETE CASCADE
        ) {$charsetCollate};

        CREATE TABLE {$attachmentsTable} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            message_id BIGINT(20) UNSIGNED NOT NULL,
            attachment_id BIGINT(20) UNSIGNED NOT NULL,
            PRIMARY KEY (id),
            FOREIGN KEY (message_id) REFERENCES {$messagesTable}(id) ON DELETE CASCADE,
            FOREIGN KEY (attachment_id) REFERENCES {$wpdb->posts}(ID) ON DELETE CASCADE
        ) {$charsetCollate};";

        dbDelta($sql);
    }

    /**
     * Удаляет таблицы плагина при деактивации.
     */
    public static function onDeactivation(): void
    {
        global $wpdb;

        $tables = [
            $wpdb->prefix . 'userspace_chat_attachments',
            $wpdb->prefix . 'userspace_chat_messages',
            $wpdb->prefix . 'userspace_chat_participants',
            $wpdb->prefix . 'userspace_chats',
        ];

        $wpdb->query('SET foreign_key_checks = 0');
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS {$table}");
        }
        $wpdb->query('SET foreign_key_checks = 1');
    }
}