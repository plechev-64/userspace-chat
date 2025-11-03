<?php
/**
 * @var array $attributes
 */
?>
<div class="usp-chat-container"
     data-user-id="<?php echo esc_attr($attributes['user_id'] ?? ''); ?>"
     data-topic-id="<?php echo esc_attr($attributes['topic-id'] ?? ''); ?>"
     data-title="<?php echo esc_attr($attributes['title'] ?? ''); ?>"
>
    <div class="usp-chat-sidebar">
        <div class="usp-chat-sidebar-header">
            <h3><?php _e('Chats', 'userspace-chat'); ?></h3>
        </div>
        <div class="usp-chat-list">
            <!-- Список чатов будет загружен сюда через JS -->
            <p><?php _e('Loading chats...', 'userspace-chat'); ?></p>
        </div>
    </div>
    <div class="usp-chat-main">
        <div class="usp-chat-main-header">
            <h3 class="usp-chat-title"><?php _e('Select a chat', 'userspace-chat'); ?></h3>
        </div>
        <div class="usp-chat-messages-window">
            <!-- Сообщения будут загружены сюда через JS -->
        </div>
        <div class="usp-chat-message-form-container">
            <form class="usp-chat-message-form">
                <textarea
                        class="usp-chat-message-input"
                        placeholder="<?php esc_attr_e('Type a message...', 'userspace-chat'); ?>"
                        aria-label="<?php esc_attr_e('Message input', 'userspace-chat'); ?>"
                        rows="1"
                        disabled></textarea>
                <button type="submit" disabled><?php _e('Send', 'userspace-chat'); ?></button>
            </form>
        </div>
    </div>
</div>