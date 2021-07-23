<?php
/**
 * Contact list item
 *
 * This template can be overridden by copying it to yourtheme/userspace/templates/uspc-contact-list-item.php
 * or from a special plugin directory wp-content/userspace/templates/uspc-contact-list-item.php
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
 * @param array  $message {
 *     @type int        $message_id         ID of message
 *     @type int        $chat_id            ID of chat
 *     @type int        $user_id            ID of the user who is communicating with
 *     @type string     $message_content    Message text
 *     @type string     $message_time       Time last message
 *     @type int        $private_key        ID personal account last message.
 *     @type int        $message_status     Status message. 1 read, 0 - unread.
 *     @type int        $author_id          ID author last message.
 * }
 *
 * @param int   $user_id    ID of current user
 */
defined( 'ABSPATH' ) || exit;

$class = 'uspc-contact-box usps usps__nowrap preloader-parent ' . ( ( ! $message[ 'message_status' ] ) ? 'uspc-unread' : '');

// I am not the author of this unread
if ( get_current_user_id() != $message[ 'author_id' ] && ! $message[ 'message_status' ] ) {
	$class .= ' uspc-unread__incoming';
}
?>

<div class="<?php echo $class; ?>" data-contact="<?php echo $message[ 'user_id' ]; ?>" onclick="uspc_get_chat_dm( this,<?php echo $message[ 'user_id' ]; ?> );return false;">
	<?php
	$args_del = [
		'type'		 => 'clear',
		'size'		 => 'small',
		'class'		 => 'uspc-contact__del',
		'onclick'	 => 'uspc_chat_remove_contact( this,' . $message[ 'chat_id' ] . ' );return false;',
		'title'		 => __( 'Delete contact', 'userspace-chat' ),
		'icon'		 => 'fa-times',
	];
	echo usp_get_button( $args_del );
	?>

    <div class="uspc-contact__ava usps usps__column usps__ai-center usps__shrink-0 usps__relative">
		<?php echo usp_get_avatar( $message[ 'user_id' ], 50, false, [ 'class' => 'uspc-contact-ava__img usps__radius-50' ] ); ?>
		<?php echo USP()->user( $message[ 'user_id' ] )->get_action_icon(); ?>
		<?php echo uspc_get_count_unread_by_user( $message[ 'user_id' ] ); ?>
    </div>
    <div class="uspc-contact__content usps usps__column usps__grow">
        <div class="uspc-contact__meta usps usps__jc-between usps__ai-center">
            <div class="uspc-contact__name"><?php echo usp_user_get_username( $message[ 'user_id' ] ); ?></div>
            <div class="uspc-contact__time usps__text-center usps__line-1"><?php echo usp_human_time_diff( $message[ 'message_time' ] ); ?> <?php _e( 'ago', 'userspace-chat' ); ?></div>
        </div>
        <div class="uspc-contact__text usps">
			<?php if ( $user_id == $message[ 'author_id' ] ) { ?>
				<div class="uspc-contact__you"><?php echo usp_get_avatar( $user_id, 30, false, [ 'class' => 'uspc-contact-you__img usps__radius-50' ] ); ?></div>
			<?php } ?>
            <div class="uspc-contact__excerpt usps__radius-3"><?php echo uspc_get_the_excerpt( $message[ 'message_content' ] ); ?></div>
        </div>
    </div>
</div>
