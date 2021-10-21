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
 * @var int $author_id id of sender
 * @var array $message Array containing some data's.
 * $message = [
 *     [0] => text of message
 * ]
 * @var bool $send_text send full text message of mail
 */
defined( 'ABSPATH' ) || exit;

$url = usp_get_tab_permalink( $author_id, 'chat' );
?>

<div style="overflow:hidden;clear:both;">
    <p><?php esc_html_e( 'You were sent a private message.', 'userspace-chat' ); ?></p>
    <div style="float:left;margin-right:18px;"><?php echo wp_kses( usp_get_avatar( $author_id, 60 ), uspc_allowed_tags() ); ?></div>

    <p><?php esc_html_e( 'From the user:', 'userspace-chat' ); ?>&nbsp;<?php echo wp_kses( usp_user_get_username( $author_id ), uspc_allowed_tags() ); ?></p>

	<?php if ( $send_text ): ?>
        <p><b><?php esc_html_e( 'Message text', 'userspace-chat' ); ?>:</b></p>
        <p><?php echo wp_kses( implode( '<br>', $message ), uspc_allowed_tags() ); ?></p>
	<?php endif; ?>

    <p>
		<?php esc_html_e( 'You can read the message by clicking on the link', 'userspace-chat' ); ?>: <a href="<?php echo esc_url( $url ); ?>"><?php echo esc_url( $url ); ?></a>
    </p>
</div>
