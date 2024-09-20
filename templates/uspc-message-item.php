<?php
/**
 * Message item in chat or PM
 *
 * This template can be overridden by copying it to yourtheme/userspace/templates/uspc-message-item.php
 * or from a special plugin directory wp-content/userspace/templates/uspc-message-item.php
 *
 * HOWEVER, on occasion UserSpace Chat will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.user-space.com/document/template-structure/
 *
 * @version 1.0.0
 *
 * @param array $message
 * $message = [
 *  'message_id'=>1                         // (int)    ID of message
 *  'chat_id'=>1                            // (int)    ID of chat
 *  'user_id'=>1                            // (int)    The ID of the user who sent the message
 *  'message_content'=>'hi!'                // (string) Message text
 *  'message_time'=>'2021-08-03 19:45:54'   // (string) Time last message
 *  'private_key'=>1                        // (int)    ID personal account last message
 *  'message_status'=>0                     // (int)    Status message. 1 read, 0 - unread
 *  'important'=>0                          // (bool)   Mark if important message. 1 - important, 0 - don't
 *  'attachment'=>0                         // (int)    ID of attachment file. If the message has an attachment file
 * ]
 *
 * @var array $message Data message item
 * @var int $user_id ID of current user
 * @var int $avatar_size Size avatars in chat
 * @var bool $user_can 1 - if the user can delete messages
 * @var array $allowed_tags Array allowed tags in current chat
 * @var string $chat_status general or private chat
 * @var string $day_date once day in loop
 */
defined( 'ABSPATH' ) || exit;
?>

<?php
$sender_id = $message['user_id'];

$class = ( $sender_id == $user_id ) ? ' uspc-you' : '';
$class .= ( isset( $message['important'] ) && $message['important'] ) ? ' uspc-post__saved' : ''
?>

<?php if ( $day_date ) : ?>
    <div class="uspc-date usps usps__jc-center">
        <span class="uspc-date__day"><?php echo esc_html( usp_human_days( $message['message_time'] ) ); ?></span>
    </div>
<?php endif; ?>
<div class="uspc-post <?php echo esc_html( $class ); ?> usps usps__nowrap usps__relative"
     data-message="<?php echo absint( $message['message_id'] ); ?>"
     data-user_id="<?php echo absint( $sender_id ); ?>">

	<?php if ( $sender_id != $user_id ): ?>
        <div class="uspc-post__ava usps__shrink-0" style="width:<?php echo absint( $avatar_size ); ?>px">
			<?php echo wp_kses( usp_get_avatar( $sender_id, $avatar_size, usp_get_tab_permalink( $sender_id, 'chat' ), [ 'class' => 'uspc-post__ava-img usps__radius-50' ] ), uspc_allowed_tags() ); ?>
        </div>
	<?php endif; ?>

    <div class="uspc-post__content">
        <div class="uspc-post__meta usps usps__ai-center usps__jc-between">
			<?php if ( ( 'general' === $chat_status && $sender_id != $user_id ) || ( isset( $message['important'] ) && 'general' === $chat_status ) ) : ?>
                <div class="uspc-post__name usps__line-1"><?php echo esc_html( usp_user_get_username( $sender_id ) ); ?></div>
			<?php endif; ?>
            <div class="uspc-post__time usps usps__grow usps__jc-end usps__line-1"><?php echo esc_html( gmdate( 'H:i', strtotime( $message['message_time'] ) ) ); ?></div>
        </div>
        <div class="uspc-post__message">
			<?php echo uspc_get_the_content( $message['message_content'], $allowed_tags ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

			if ( isset( $message['attachment'] ) && $message['attachment'] ) {
				echo uspc_get_the_attachment( $message['attachment'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			} ?>
        </div>
    </div>

	<?php if ( $user_id ) : ?>
        <div class="uspc-post__do usps usps__column usps__ai-center">
			<?php
			$menu = new DropdownMenu( 'uspc_dialog' );

			$menu->add_button( [
				'class'   => 'uspc-post-do__bttn uspc-post-do__important',
				'onclick' => 'uspc_chat_message_important( ' . $message['message_id'] . ' ); return false;',
				'icon'    => ( isset( $message['important'] ) && $message['important'] ) ? 'fa-star-fill' : 'fa-star',
			] );

			if ( $user_can ) {
				$menu->add_button( [
					'type'    => 'clear',
					'class'   => 'uspc-post-do__bttn uspc-post-do__delete',
					'onclick' => 'uspc_chat_delete_message( ' . $message['message_id'] . ' ); return false;',
					'icon'    => 'fa-trash',
				] );
			}

			echo $menu->get_content(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			?>
        </div>
	<?php endif; ?>

</div>
