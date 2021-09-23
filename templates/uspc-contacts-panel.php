<?php
/**
 * The contact panel at the bottom of the site
 *
 * This template can be overridden by copying it to yourtheme/userspace/templates/uspc-contacts-panel.php
 * or from a special plugin directory wp-content/userspace/templates/uspc-contacts-panel.php
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
 * @var array $users Array containing some data's.
 * $users = [
 *     [141] => [                   // (int)    ID of the user who is communicating with
 *             [status] => 0        // (bool)   Status message. 1 read, 0 - unread.
 *             [chat_id] => 10      // (int)    ID of chat
 *         ]
 * ]
 *
 * @var int $unread counter all unread messages
 */
defined( 'ABSPATH' ) || exit;
?>

<div id="uspc-mini" class="<?php echo uspc_get_class_contacts_panel( $unread ); ?>">
    <div class="uspc-mini__wrap">
        <div class="uspc-mini__userlist usps usps__column usps__ai-center usps__relative">
			<?php echo uspc_shift_contact_panel_button( $unread ); ?>

            <div class="uspc-mini__contacts">
				<?php
				foreach ( $users as $user_id => $data ) :
					if ( $user_id == usp_office_id() ) {
						continue;
					}
					?>

                    <div class="uspc-mini__person usps__relative" data-contact="<?php echo $user_id; ?>"
                         onclick="uspc_get_chat_window( this, <?php echo $user_id; ?> ); return false;">
						<?php echo usp_get_avatar( $user_id, 42 ); ?>
						<?php if ( ! $data['status'] ) : ?>
                            <i class="uspi fa-comment-dots uspc-mini-person__in usps__radius-50" aria-hidden="true"></i>
						<?php endif; ?>
                    </div>
				<?php endforeach; ?>
            </div>

        </div>
    </div>
</div>
