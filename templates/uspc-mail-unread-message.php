<?php
/**
 * Notification to the mail about an unread message
 *
 * This template can be overridden by copying it to yourtheme/userspace/templates/uspc-mail-unread-message.php
 * or from a special plugin directory wp-content/userspace/templates/uspc-mail-unread-message.php
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
 * @param int $author_id id of sender
 * @param array $message Array containing some datas.
 * $message = [
 *     [0] => text of message
 * ]
 * @param bool $send_text send full text message of mail
 */
defined( 'ABSPATH' ) || exit;

$url = usp_get_tab_permalink( $author_id, 'chat' );
?>

<div style="overflow:hidden;clear:both;">
    <p><?php _e( 'You were sent a private message.', 'userspace-chat' ); ?></p>
    <div style="float:left;margin-right:18px;"><?php echo usp_get_avatar( $author_id, 60 ); ?></div>

    <p><?php _e( 'From the user:', 'userspace-chat' ); ?>&nbsp;<?php echo usp_user_get_username( $author_id ); ?></p>

	<?php if ( $send_text ): ?>
        <p><b><?php _e( 'Message text', 'userspace-chat' ); ?>:</b></p>
        <p><?php echo implode( '<br>', $message ); ?></p>
	<?php endif; ?>

    <p>
		<?php _e( 'You can read the message by clicking on the link', 'userspace-chat' ); ?>: <a
                href="<?php echo $url; ?>"><?php echo $url; ?></a>
    </p>
</div>
