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
 * @param array  $message {
 *     @type int        $message_id         ID of message
 *     @type int        $chat_id            ID of chat
 *     @type int        $user_id            The ID of the user who sent the message
 *     @type string     $message_content    Message text
 *     @type string     $message_time       Time last message
 *     @type int        $private_key        ID personal account last message.
 *     @type bool       $message_status     Status message. 1 read, 0 - unread.
 *     @type bool       $important          Mark if important message. 1 - important, 0 - don't
 *     @type int        $attachment         Id of attachment file. If the message has an attachment file
 * }
 *
 * @param int   $user_id        ID of current user
 * @param int   $avatar_size    Size avatars in chat
 * @param bool  $user_can       1 - if the user can delete messages
 * @param array $allowed_tags   Array allowed tags in current chat
 */
defined( 'ABSPATH' ) || exit;
?>

<?php
$sender_id	 = $message[ 'user_id' ];
$message_id	 = $message[ 'message_id' ];
$class		 = ($sender_id == $user_id) ? ' uspc-you' : '';

$class .= ( isset( $message[ 'important' ] ) && $message[ 'important' ] ) ? ' uspc-post__saved' : ''
?>

<div class="uspc-post <?php echo $class; ?> usps usps__relative" data-message="<?php echo $message_id; ?>" data-user_id="<?php echo $sender_id; ?>">

    <div class="uspc-post__ava usps__shrink-0" style="width:<?php echo $avatar_size; ?>px">
		<?php
		if ( $sender_id != $user_id ) {
			echo usp_get_avatar( $sender_id, $avatar_size, usp_get_tab_permalink( $sender_id, 'chat' ), [ 'class' => 'uspc-post__ava-img usps__radius-50' ] );
		} else {
			echo usp_get_avatar( $sender_id, $avatar_size, false, [ 'class' => 'uspc-post__ava-img usps__radius-50' ] );
		}
		?>
    </div>

    <div class="uspc-post__content">
        <div class="uspc-post-content__box">
            <div class="uspc-post__name usps__line-1"><?php echo usp_user_get_username( $sender_id ); ?></div>
            <div class="uspc-post__message">

				<?php
				echo uspc_get_the_content( $message[ 'message_content' ], $allowed_tags );

				if ( isset( $message[ 'attachment' ] ) && $message[ 'attachment' ] ) {
					echo uspc_get_the_attachment( $message[ 'attachment' ] );
				}
				?>

            </div>
        </div>
        <div class="uspc-post__time usps usps__jc-end usps__line-1"><?php echo $message[ 'message_time' ]; ?></div>
    </div>

	<?php if ( $user_id ) { ?>

		<div class="uspc-post__do usps usps__column usps__ai-center">
			<?php echo apply_filters( 'uspc_post_do_bttns', '', $message, $user_can ); ?>
		</div>

	<?php } ?>

</div>
