<?php

// realtime customize preview
add_action( 'customize_preview_init', 'uspc_customizer_live_preview' );
function uspc_customizer_live_preview() {
	wp_enqueue_script(
		'uspc-customizer',
		plugins_url( 'assets/js/customizer.js', __FILE__ ),
		[ 'jquery', 'customize-preview' ],
		'1.0.0',
		true
	);
}

// Register customizer
add_action( 'customize_register', 'uspc_customizer' );
function uspc_customizer( $wp_customize ) {
	// if we use classes that extend controls, we definitely check whether the UserSpace plugin is enabled
	if ( ! class_exists( 'USP_Customize_Color' ) ) {
		return;
	}

##  Section #2-will expand the userspace-panel
	$wp_customize->add_section( 'userspace-chat', [ // ID section
		'title' => __( 'UserSpace chat: settings', 'userspace-chat' ),
		'panel' => 'userspace-panel',              // the section is linked to the panel
	] );

	// option #1 in the section
	$wp_customize->add_setting( 'usp_customizer[uspc_theme]', [    // The option ID and its name and key in wp_options in the array
		'type'              => 'option',            // stored in wp_options (for plugins)
		'default'           => '#beb5ff',           // default value
		'transport'         => 'postMessage',       // realtime update. Requires data in the script
		'sanitize_callback' => 'sanitize_hex_color' // sanitize
	] );

	$wp_customize->add_control( new USP_Customize_Color( $wp_customize, 'usp_customizer[uspc_theme]', [
		'section'     => 'userspace-chat',
		'label'       => __( 'You messages background:', 'userspace-chat' ),
		'description' => __( 'Go to the dialog that contains your messages and configure their appearance:', 'userspace-chat' ),
		'palette'     => [
			'#dd498c',
			'#d352d3',
			'#8a51a3',
			'#6a43ad',
			'#4f62a8',
			'#4fa3d1',
			'#389aa5',
			'#4d8e51',
			'#799e5b',
			'#b1b546',
			'#f9c540',
			'#ffb028',
			'#f48a1f',
			'#e56842',
		],
	] ) );

	// option #2 in the section
	$wp_customize->add_setting( 'usp_customizer[uspc_alpha]', [
		'type'              => 'option',
		'default'           => '0.2',
		'transport'         => 'postMessage',
		'sanitize_callback' => 'usp_sanitize_decimal',
	] );

	$wp_customize->add_control( new USP_Customize_Range( $wp_customize, 'usp_customizer[uspc_alpha]', [
		'section'     => 'userspace-chat',
		'label'       => __( 'Transparency - changes the color of the bottom of your messages:', 'userspace-chat' ),
		'description' => __( 'set from 0.1 to 0.4 (default is 0.2). Step: 0.05', 'userspace-chat' ),
		'min'         => 0.1,
		'max'         => 0.4,
		'step'        => 0.05,
	] ) );

}
