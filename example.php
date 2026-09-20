<?php 
/**
 * Plugin Name: Example Plugins
 * Plugin URI: https://www.youtube.com/watch?v=xvFZjo5PgG0
 * Description: WordPress example plugins
 * Version: 1.1
 * Author: Matt Petersen
 * Author URI: nesretep.net
 * Text Domain: example-plugin
 */

//---------------------------------------------------------------------------------------------------------------
// *** Place a plugin on the WP site
//---------------------------------------------------------------------------------------------------------------


// This function generates the HTML content for the plugin
function generate_plugin_html() {
    $plugin_text = 'Matt Petersen is a developer and solution architect';

    echo '
    <div class="custom-content">
        <div class="ticker-track">' . esc_html( $plugin_text ) . '</div>
    </div>

    <style>
        .custom-content {
            width: 100%;
            background: #ffffff;
            color: #111111;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            font-size: 14px;
            font-weight: 500;
            padding: 10px 0;
            overflow: hidden;
            white-space: nowrap;
            box-sizing: border-box;
            border-bottom: 2px solid #00D2FF;
        }
        .ticker-track {
            display: inline-block;
            padding-left: 100%;
            animation: tickerEffect 35s linear infinite;
        }
        @keyframes tickerEffect {
            0% { transform: translate3d(0, 0, 0); }
            100% { transform: translate3d(-100%, 0, 0); }
        }
        .ticker-track:hover {
            animation-play-state: paused;
            cursor: pointer;
        }
    </style>';
}

// Hook the function to the 'wp_body_open' action to display the content at the top of the page
add_action( 'wp_body_open', 'generate_plugin_html' );

/* 
some wordpress hooks you can use
 - wp_footer - bottom of the page
 - wp_head - end of the head section
 - wp_body_open - right after the opening body tag
 - admin_menu - add a menu item to the admin dashboard
 - 
*/

//---------------------------------------------------------------------------------------------------------------
// *** Place a plugin on any part of the WP site (shortcode)
//---------------------------------------------------------------------------------------------------------------

// Add a shortcode to display the plugin content - note that this does a return, not an echo, so it can be used in posts and pages
function my_shortcode_callback() {
    return '<div style="background-color: #f0f0f0; padding: 10px;">This is my shortcode output</div>';
}

// Register the shortcode
add_shortcode( 'my_shortcode', 'my_shortcode_callback' );   


//---------------------------------------------------------------------------------------------------------------
// *** Create a settings page on the dashboard and settings menu
//---------------------------------------------------------------------------------------------------------------

function myplugin_register_settings_page() {
    add_options_page(
        'My Plugin Settings',      // Page title
        'My Plugin',               // Menu title
        'manage_options',          // Capability
        'myplugin-settings',       // Menu slug
        'myplugin_settings_page'   // Callback function
    );
}

function myplugin_settings_page() {
    ?>
    <div class="wrap">
        <h1>My Plugin Settings</h1>

        <p>This is a basic settings page.</p>

        <form method="post" action="options.php">
            <?php
                settings_fields( 'myplugin_settings_group' );
                do_settings_sections( 'myplugin-settings' );
            ?>
        <p>blah blah blah</p>
        </form>
    </div>
    <?php
}

add_action( 'admin_menu', 'myplugin_register_settings_page' );

//---------------------------------------------------------------------------------------------------------------
// *** Create a page on the top-level dashboard menu
//---------------------------------------------------------------------------------------------------------------
function myplugin_register_menu_page() {

    add_menu_page(
        'My Plugin Page',       // Page title (shown at top of page)
        'My Plugin - Dashboard',            // Menu title (shown in sidebar)
        'manage_options',       // Capability
        'myplugin-main',        // Menu slug (unique)
        'myplugin_page_html',   // Callback function
        'dashicons-admin-generic', // Icon (Dashicons)
        25                      // Position in menu
    );
}

function myplugin_page_html() {
    ?>
    <div class="wrap">
        <h1>My Plugin Page</h1>
        <p>This is a custom top‑level admin page.</p>
    </div>
    <?php
}

add_action( 'admin_menu', 'myplugin_register_menu_page' );
