<?php

// template uspc-contacts-panel.php css classes
function uspc_get_class_contacts_panel( $unread ) {
	$class = [ 'uspc-mini', 'usps', 'usps__nowrap' ];

	if ( $unread ) {
		$class[] = 'uspc-message-for-you';
	}

	$class[] = ( ! usp_get_option( 'uspc_set_chat_bar', 1 ) ) ? 'uspc-on-left' : 'uspc-on-right';

	$class[] = ( ! empty( $_COOKIE['uspc_contacts_panel_full'] ) ) ? '' : 'uspc-mini__hide';

	return trim( implode( ' ', $class ) );
}

// formatting links, oembed and emojis in the chat
function uspc_get_the_content( $content_in, $allowed_tags ) {
	$content_target = links_add_target( make_clickable( $content_in ) );

	$content = apply_filters( 'uspc_message', wp_kses( $content_target, $allowed_tags ) );

	$oembed = usp_get_option( 'uspc_oembed', 0 );

	if ( $oembed && function_exists( 'wp_oembed_get' ) ) {
		$links = [];

		preg_match_all( '/href="([^"]+)"/', $content, $links );

		foreach ( $links[1] as $link ) {
			$m_lnk = wp_oembed_get( $link, [ 'width' => 500, 'height' => 300 ] );
			if ( $m_lnk ) {
				$content = str_replace( '<a href="' . $link . '" rel="nofollow">' . $link . '</a>', '', $content );
				$content .= $m_lnk;
			}
		}
	}

	if ( function_exists( 'convert_smilies' ) ) {
		$content = convert_smilies( $content );
	}

	return $content;
}

// file formatting
function uspc_get_the_attachment( $attachment_id ) {
	$post = get_post( $attachment_id );
	if ( ! $post ) {
		return false;
	}

	$file = get_attached_file( $attachment_id );
	if ( ! $file ) {
		return false;
	}

	$check = wp_check_filetype( $file );
	if ( empty( $check['ext'] ) ) {
		return false;
	}

	$ext        = $check['ext'];
	$attach_url = wp_get_attachment_url( $attachment_id );

	if ( in_array( $ext, [ 'jpeg', 'jpg', 'png', 'gif' ] ) ) {
		$type  = 'image';
		$media = '<a class="uspc-post__image usps" target="_blank" rel="fancybox" href="' . $attach_url . '"><img src="' . wp_get_attachment_image_url( $attachment_id, [
				300,
				300,
			] ) . '" class="uspc-post__img usps__img-reset" alt=""></a>';
	} else if ( in_array( $ext, wp_get_audio_extensions() ) ) {
		$type  = 'audio';
		$media = wp_audio_shortcode( [ 'mp3' => $attach_url ] );
	} else if ( in_array( $ext, wp_get_video_extensions() ) ) {
		$type  = 'video';
		$media = wp_video_shortcode( [ 'src' => $attach_url ] );
	} else {
		$type      = 'archive';
		$media_img = wp_get_attachment_image( $attachment_id, [ 30, 30 ], true );
		$media     = '<a class="uspc-post__archive usps usps__nowrap usps__ai-center" target="_blank" href="' . $attach_url . '">' . $media_img . '<span>' . $post->post_title . '.' . $ext . '</span></a>';
	}

	return '<div class="uspc-post-message__file uspc-post-message__file-' . $type . '" data-attachment="' . $attachment_id . '">' . $media . '</div>';
}

// get the excerpt for contact list
function uspc_get_the_excerpt( $string ) {
	if ( ! $string ) {
		return '...';
	}

	$string_nl2br = nl2br( $string );

	$allowed_html = [
		'br'     => [],
		'em'     => [],
		'strong' => [],
	];

	$string_kses = wp_kses( $string_nl2br, $allowed_html );

	$max = 100;
	if ( mb_strlen( $string_kses ) <= $max ) {
		return convert_smilies( $string_kses );
	}

	$string_smilies = convert_smilies( $string_kses );

	// if at the end of <br /> - delete it
	$del_br = preg_replace( '/(<br>|<br \/>)/', '', $string_smilies );

	$string_substr = mb_substr( $del_br, 0, $max );

	// Delete last word. Replace on ...
	return preg_replace( '~(.*)\s[^\s]*$~s', '\\1...', $string_substr );
}

function uspc_get_count_unread_by_user( $count ) {
	if ( 0 == $count ) {
		return false;
	}

	return '<div class="uspc_unread usps__relative" title="' . __( 'Unread', 'userspace-chat' ) . '">'
	       . '<i class="uspi fa-envelope" aria-hidden="true"></i>'
	       . '<span class="uspc_unread_count usps usps__jc-center usps__ai-center usps__radius-50 usps__line-1">' . $count . '</span>'
	       . '</div>';
}

// open/closed contacts panel
function uspc_shift_contact_panel_button( $unread = '' ) {
	$args = [
		'onclick' => 'return uspc_shift_contacts_panel();',
		'class'   => 'uspc-mini__count uspc_js_counter_unread',
		'size'    => 'no',
		'type'    => 'clear',
		'icon'    => 'fa-envelope',
		'counter' => $unread,
	];

	return usp_get_button( $args );
}
