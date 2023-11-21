<?php
/*This file is part of ChildHelloElementor, hello-elementor child theme.

All functions of this file will be loaded before of parent theme functions.
Learn more at https://codex.wordpress.org/Child_Themes.

Note: this function loads the parent stylesheet before, then child theme stylesheet
(leave it in place unless you know what you are doing.)
*/

if ( ! function_exists( 'suffice_child_enqueue_child_styles' ) ) {
	function ChildHelloElementor_enqueue_child_styles() {
	    // loading parent style
	    wp_register_style(
	      'parente2-style',
	      get_template_directory_uri() . '/style.css'
	    );

	    wp_enqueue_style( 'parente2-style' );
	    // loading child style
	    wp_register_style(
	      'childe2-style',
	      get_stylesheet_directory_uri() . '/style.css'
	    );
	    wp_enqueue_style( 'childe2-style');
	 }
}
add_action( 'wp_enqueue_scripts', 'ChildHelloElementor_enqueue_child_styles' );

/*Write here your own functions */

// Cleaner code. Moved a lot of the common functions to elementor-form-custom-functions.php 
// make sure that the elementor-form-custom-functions.php exists in the same directory 
// as the functions.php of the (child)theme.

add_action('elementor_pro/forms/validation', function($record, $ajax_handler) {
/* ******************************** */
/* ******************************** */
	//before uncommenting the debug code (aka error_log)below, 
	//make sure you turn on WP_DEBUG in wp-config.php & set these params
	//define('WP_DEBUG', true);
	//define('WP_DEBUG_LOG', true);
	//define('WP_DEBUG_DISPLAY', false);
	//@ini_set('display_errors', 0);
/* ******************************** */
/* ******************************** */  
   //error_log('This is a debug message from forms validation');
   $form_name = $record->get_form_settings('form_name');
   //error_log('Form Name: ' . $form_name);	
	
   // Ensure our custom functions are loaded.
    require_once get_stylesheet_directory() . '/elementor-form-custom-functions.php';

	if ($form_name == "12345") {
        validateForm($record,$ajax_handler);
    }
	else {
  		// Add an error message
    	$ajax_handler->add_error_message('Error: Form name is not valid or a match was not found');
    }	
	return;
}, 10, 2);

