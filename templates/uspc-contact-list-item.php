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
 * @var array $message
 * $message = [
 *  'message_id'=>1                         // (int)    ID of message
 *  'chat_id'=>1                            // (int)    ID of chat
 *  'contact_id'=>2                         // (int)    ID of the user who is communicating with
 *  'message_content'=>'hi!'                // (string) Message text
 *  'message_time'=>'2021-08-03 19:45:54'   // (string) Time last message
 *  'private_key'=>1                        // (int)    ID personal account last message
 *  'message_status'=>0                     // (int)    Status message. 1 read, 0 - unread
 *  'author_id'=>2                          // (int)    ID author last message
 * ]
 *
 * @var int $user_id ID of current user
 * @var int $number_unread number of unread messages
 */
defined( 'ABSPATH' ) || exit;

$class = 'uspc-contact-box usps usps__nowrap preloader-parent ' . ( ( ! $message['message_status'] ) ? 'uspc-unread' : '' );

// I am not the author of this unread
if ( get_current_user_id() != $message['author_id'] && ! $message['message_status'] ) {
	$class .= ' uspc-unread__incoming';
}

$data_unread  = ( $number_unread ) ? 'data-unread="' . $number_unread . '"' : '';
$data_contact = 'data-contact="' . $message['contact_id'] . '"';
$onclick      = 'onclick="uspc_get_chat_dm(this,' . $message['contact_id'] . ');return false;"';
?>

<div class="uspc-contact-wrap usps__relative">
	<?php $menu = new USP_Dropdown_Menu( 'uspc_contactlist' );

	$menu->add_button( [
		'class'   => 'uspc-contact__del',
		'label'   => __( 'Delete contact', 'userspace-chat' ),
		'onclick' => 'uspc_chat_remove_contact( this,' . $message['contact_id'] . ' );return false;',
		'icon'    => 'fa-times',
	] );

	echo $menu->get_content();
	?>
    <div class="<?php echo $class; ?>" <?php echo $data_unread; ?> <?php echo $data_contact; ?> <?php echo $onclick; ?>>
        <div class="uspc-contact__ava usps usps__column usps__ai-center usps__shrink-0 usps__relative">
			<?php echo usp_get_avatar( $message['contact_id'], 50, false, [ 'class' => 'uspc-contact-ava__img usps__radius-50' ] ); ?>
			<?php echo USP()->user( $message['contact_id'] )->get_action_icon(); ?>
			<?php echo uspc_get_count_unread_by_user( $number_unread ); ?>
        </div>

        <div class="uspc-contact__content usps usps__column usps__grow">
            <div class="uspc-contact__meta usps usps__jc-between usps__ai-center">
                <div class="uspc-contact__name"><?php echo usp_user_get_username( $message['contact_id'] ); ?></div>
                <div class="uspc-contact__time usps__text-center usps__line-1">
					<?php echo usp_human_time_diff( $message['message_time'] ); ?>
                    &nbsp;<?php _e( 'ago', 'userspace-chat' ); ?>
                </div>
            </div>
            <div class="uspc-contact__text usps usps__nowrap">
				<?php if ( $user_id == $message['author_id'] ) { ?>
                    <div class="uspc-contact__you usps usps__shrink-0"><?php echo usp_get_avatar( $user_id, 30, false, [ 'class' => 'uspc-contact-you__img usps__radius-50' ] ); ?></div>
				<?php } ?>
                <div class="uspc-contact__excerpt usps__radius-3"><?php echo uspc_get_the_excerpt( $message['message_content'] ); ?></div>
            </div>
        </div>
    </div>
</div>
