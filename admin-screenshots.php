<?php
/**
 * Plugins primary file, in charge of including all other dependencies.
 *
 * @package Admin Screenshot
 *
 * @wordpress-plugin
 * Plugin Name: Admin Screenshots
 * Plugin URI: https://wordpress.org/plugins/admin-screenshots
 * Description: The easiest way to share a screenshot of any of your site's settings pages, without giving anyone direct access to your dashboard.
 * Author: Senff
 * Version: 1.0.2
 * Author URI: https://www.senff.com/
 * Text Domain: admin-screenshots
 */


defined('ABSPATH') or die('INSERT COIN');

/* --- ADD THE .CSS AND .JS TO ADMIN AREA -------------------------------------------------------------- */
if (!function_exists('admin_screenshots_styles')) {
    function admin_screenshots_styles() {
        wp_register_style('adminScreenshotsStyle', plugins_url('/assets/css/admin-screenshots.css', __FILE__) );
        wp_enqueue_style('adminScreenshotsStyle');  

        wp_register_script('admin-screenshots-library', plugin_dir_url( __FILE__ ) . 'assets/js/html2canvas.min.js', array( 'jquery' ), '1.0.0', true );
        wp_enqueue_script('admin-screenshots-library');

        wp_register_script('admin-screenshots-script', plugin_dir_url( __FILE__ ) . 'assets/js/admin-screenshots.js', array( 'jquery' ), '1.0.0', true );
        wp_enqueue_script('admin-screenshots-script');
    }
}
add_action('admin_enqueue_scripts', 'admin_screenshots_styles' );


/* --- ADD .CSS TO FRONT AREA -------------------------------------------------------------- */
if (!function_exists('admin_screenshots_front_styles')) {
    function admin_screenshots_front_styles() {
        wp_register_style('adminScreenshotsFrontStyle', plugins_url('/assets/css/admin-screenshots-front.css', __FILE__) );
        wp_enqueue_style('adminScreenshotsFrontStyle');  
    }
}
add_action('wp_enqueue_scripts', 'admin_screenshots_front_styles' );


/* --- ADD THE SCREENSHOT BUTTON TO THE TOOLBAR -------------------------------------------------------------- */

if (!function_exists('add_as_toolbar_button')) {
    function add_as_toolbar_button() {
        global $wp_admin_bar;

        $args = array(
            'id' => 'admin-screenshots-button',
            'title' => 'SCREENSHOT THIS PAGE',
            'href' => '#'
        );
        $wp_admin_bar->add_node($args);
    }
}
add_action('admin_bar_menu', 'add_as_toolbar_button', 999);



/* --- THE FUNCTION THAT CREATES A CANVAS AND SAVES IT AS AN IMAGE -------------------------------------------------------------- */
if (!function_exists('save_canvas')) {
    function save_canvas() {
        if ( isset( $_POST['image'] ) ) {
            
            $upload_dir = wp_upload_dir();              // Array
            $upload_dir_path = $upload_dir['path'];     // /serverstuff/app/public/wp-content/uploads
            $upload_dir_base = $upload_dir['basedir'];  // /serverstuff/app/public/wp-content/uploads        
            $upload_path = $upload_dir['basedir'] . '/admin-screenshots';  // /serverstuff/app/public/wp-content/uploads/admin-screenshots        
            $upload_dir_url = $upload_dir['baseurl'] . '/admin-screenshots';       // http://mysite.local/wp-content/uploads

            if (!file_exists($upload_path)) {
                wp_mkdir_p($upload_path);
            }

            $image_data = $_POST['image'];
            $timestamp = time();
            $img = $timestamp . '.png';
            $file = $upload_path . '/' . $img;
            $fullpath = $upload_dir['url'] . '/admin-screenshots/' . $img;
            $success = file_put_contents( $file, base64_decode( str_replace( 'data:image/png;base64,', '', $image_data ) ) );
            if ( $success ) {
                $wp_filetype = wp_check_filetype( $img, null );
                $attachment = array(
                    'post_mime_type' => $wp_filetype['type'],
                    'post_title' => 'Screenshot '.$timestamp.' (generated by Admin Screenshots)',
                    'post_content' => '',
                    'post_status' => 'inherit'
                );
                $attachment_id = wp_insert_attachment( $attachment, $file );
                require_once( ABSPATH . 'wp-admin/includes/image.php' );
                $attachment_data = wp_generate_attachment_metadata( $attachment_id, $file );
                wp_update_attachment_metadata( $attachment_id, $attachment_data );
                _e($upload_dir_url . ',' . $timestamp);  // Sending this to the JS function in screenshotThis()
            } else {
                _e('Failed to save image.');
            }
        }
        wp_die();
    }
}
add_action( 'wp_ajax_save_canvas', 'save_canvas' );
add_action( 'wp_ajax_nopriv_save_canvas', 'save_canvas' );

