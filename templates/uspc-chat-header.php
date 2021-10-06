<?php
/**
 * Float top panel for chat
 *
 * This template can be overridden by copying it to yourtheme/userspace/templates/uspc-chat-header.php
 * or from a special plugin directory wp-content/userspace/templates/uspc-chat-header.php
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
 * @var int $user_id id of user
 *
 * @var array $chatdata chat data
 * @var array $args additional options
 */

defined( 'ABSPATH' ) || exit;

$data_head = '';
$name      = '';
if ( $user_id > 0 ) {
	$data_head = 'data-head-id="' . $user_id . '"';
	$name      = usp_user_get_username( $user_id, get_author_posts_url( $user_id ), [ 'class' => 'usps__text-cut' ] );

	$chatdata['user_id'] = $user_id;
}

?>

<div class="uspc-head-box usps usps__nowrap usps__as-center usps__grow" <?php echo $data_head; ?>>
	<?php if ( isset( $chatdata['token'] ) && $args['button'] != 'hide' ) {
		echo usp_get_button( [
			'type'    => 'clear',
			'icon'    => 'fa-arrow-left',
			'size'    => 'no',
			'onclick' => "usp_load_tab(\"chat\", 0, this);return false;",
			'class'   => 'uspc-head__bttn',
			'data'    => [ 'token-dm' => $chatdata['token'] ],
		] );

		?>

	<?php } ?>
    <div class="uspc-head__top usps usps__nowrap usps__grow usps__jc-between usps__ai-center">
        <div class="uspc-head__left">
			<?php if ( isset( $args['left'] ) ) { ?>
				<?php echo $args['left']; ?>
			<?php } else { ?>
                <div class="uspc-head-left__link"><?php echo $name; ?></div>
                <div class="uspc-head-action usps">
					<?php echo USP()->user( $user_id )->get_action( 'mixed' ); ?>
                    <div class="uspc-head__status"></div>
                </div>
			<?php } ?>
        </div>

        <div class="uspc-head__right usps usps__relative">
			<?php
			$menu = new USP_Dropdown_Menu( 'uspc_header' );
			$icon = ( isset( $_COOKIE['uspc_sound_off'] ) && $_COOKIE['uspc_sound_off'] ) ? 'fa-volume-off' : 'fa-volume-up';

			if ( is_user_logged_in() ) {
				if ( isset( $chatdata['chat_status'] ) && $chatdata['chat_status'] == 'private' ) {
					$menu->add_button( [
						'class'   => 'uspc-head-right__bttn',
						'label'   => __( 'User info', 'userspace-chat' ),
						'onclick' => 'usp_get_user_info(this,' . $chatdata['user_id'] . ');return false;',
						'icon'    => 'fa-info-circle',
					] );
				}

				$menu->add_button( [
					'icon'    => 'fa-expand-arrows',
					'class'   => 'uspc-im__modal',
					'label'   => __( 'Focus mode', 'userspace-chat' ),
					'onclick' => 'uspc_focus_modal_shift(this);return false;',
				] );

				$status = ( $args['important'] ) ? 0 : 1;
				$class  = ( $args['important'] ) ? 'fa-star-fill' : 'fa-star';

				$menu->add_button( [
					'icon'    => $class,
					'class'   => 'uspc-im__important',
					'label'   => __( 'Important messages', 'userspace-chat' ),
					'onclick' => 'uspc_chat_important_manager_shift(this,' . $status . ');return false;',
				] );
			}

			$menu->add_button( [
				'icon'    => $icon,
				'class'   => 'uspc-im-form__on-off',
				'label'   => __( 'Sound on/off', 'userspace-chat' ),
				'onclick' => 'uspc_on_off_sound(this);return false;',
			] );

			echo $menu->get_content();
			?>
        </div>

    </div>
</div>
