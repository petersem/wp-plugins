<?php 
/**
 * Plugin Name: 💀💀 Matt's Example Plugins 💀💀
 * Plugin URI: https://www.youtube.com/watch?v=xvFZjo5PgG0
 * Description: WordPress example plugins. One file to rule them all. 😛😛😛
 * Version: 1.2.17
 * Author: Matt Petersen
 * Author URI: https://github.com/petersem
 * GitHub Plugin URI: https://github.com/petersem/wp-plugins
 * GitHub Branch: main
 */

// stop plugin from being executed outside of WP
if (!defined('ABSPATH')) exit;


// * 1 * Place a plugin on the WP site
//

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
add_action( 'wp_head', 'generate_plugin_html' );

/* 
some wordpress hooks you can use
 - wp_footer - bottom of the page
 - wp_head - end of the head section
 - wp_body_open - right after the opening body tag
 - admin_menu - add a menu item to the admin dashboard
 - 
*/

// * 2 * Place a plugin on any part of the WP site (shortcode)
//

// Add a shortcode to display the plugin content - note that this does a return, not an echo, so it can be used in posts and pages
function my_shortcode_callback() {
    return '<div style="background-color: #f0f0f0; padding: 10px;">This is my shortcode output</div>';
}

// Register the shortcode
add_shortcode( 'my_shortcode', 'my_shortcode_callback' );   


//---------------------------------------------------------------------------------------------------------------
// * 3 * Create a settings page on the dashboard - settings menu
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
// * 4 * Create a page on the top-level dashboard menu
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



//---------------------------------------------------------------------------------------------------------------
// * 5 * Read and write from the wp_options table - via a top level menu page and shortcode
//---------------------------------------------------------------------------------------------------------------

// Register a setting stored in wp_options
function myplugin_wpo_register_menu_page() {

    add_menu_page(
        'Saving to WP_Options table',        // Page title
        'WP_Options Test',                 // Menu title
        'manage_options',           // Capability
        'mypluginxx-main',            // Menu slug
        'myplugin_wpo_settings_page',   // Callback
        'dashicons-admin-generic',  // Icon
        25                          // Position
    );
}

// Register a setting stored in wp_options
add_action( 'admin_init', 'myplugin_wpo_register_settings' );

function myplugin_wpo_register_settings() {
    register_setting( 'myplugin_settings_group', 'myplugin_message' );
}

// Settings page HTML
function myplugin_wpo_settings_page() {

    // Read the saved option
    $value = get_option( 'myplugin_message', '' );
    ?>

    <div class="wrap">
        <h1>MyPlugin Settings</h1>

        <form method="post" action="options.php">
            <?php settings_fields( 'myplugin_settings_group' ); ?>

            <table class="form-table">
                <tr>
                    <th scope="row">Message</th>
                    <td>
                        <input type="text"
                               name="myplugin_message"
                               value="<?php echo esc_attr( $value ); ?>"
                               class="regular-text">
                    </td>
                </tr>
            </table>

            <?php submit_button(); ?>
        </form>
    </div>

    <?php
}

function myplugin_message_shortcode() {

    $value = get_option( 'myplugin_message', '' );

    if ( empty( $value ) ) {
        return '<p>No message saved yet.</p>';
    }

    return '<p><strong>Saved Message:</strong> ' . esc_html( $value ) . '</p>';
}

//Add a top-level admin menu page
add_action( 'admin_menu', 'myplugin_wpo_register_menu_page' );

// Shortcode that outputs the saved option
add_shortcode( 'myplugin_message', 'myplugin_message_shortcode' );




// --------------------------------------------------------------------------
// * 6 * Create a custom table in the database and read/write to it
// --------------------------------------------------------------------------

// Create table on plugin activation
register_activation_hook( __FILE__, 'myplugin_create_table' );

function myplugin_create_table() {
    global $wpdb;

    // Use ONE consistent table name everywhere
    $table_name = $wpdb->prefix . 'myplugin_items';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        title varchar(255) NOT NULL,
        created datetime NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    // REQUIRED for dbDelta()
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}

// Insert a row
function myplugin_insert_item( $title ) {
    global $wpdb;

    $table_name = $wpdb->prefix . 'myplugin_items';

    $wpdb->insert(
        $table_name,
        [
            'title'   => $title,
            'created' => current_time( 'mysql' )
        ]
    );
}

// Read rows
function myplugin_get_items() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'myplugin_items';

    return $wpdb->get_results( "SELECT * FROM $table_name ORDER BY id DESC" );
}

// plugin to show db records and also add new ones
function myplugin_items_shortcode() {

    // Handle form submission
    if ( isset($_POST['myplugin_new_title']) && ! empty($_POST['myplugin_new_title']) ) {

        $title = sanitize_text_field( $_POST['myplugin_new_title'] );

        myplugin_insert_item( $title );

        wp_redirect( $_SERVER['REQUEST_URI'] );
        exit;
    }

    // Fetch items
    $items = myplugin_get_items();

    ob_start();
    ?>

    <div class="myplugin-wrapper">

        <h2>MyPlugin Items</h2>

        <table border="1" cellpadding="6" cellspacing="0">
            <tr>
                <th>ID</th>
                <th>Title</th>
                <th>Created</th>
            </tr>

            <?php if ( $items ) : ?>
                <?php foreach ( $items as $item ) : ?>
                    <tr>
                        <td><?php echo esc_html( $item->id ); ?></td>
                        <td><?php echo esc_html( $item->title ); ?></td>
                        <td><?php echo esc_html( $item->created ); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else : ?>
                <tr><td colspan="3">No records yet.</td></tr>
            <?php endif; ?>
        </table>

        <h3>Add New Item</h3>

        <form method="post">
            <input type="text" name="myplugin_new_title" placeholder="Enter title" required>
            <button type="submit">Add</button>
        </form>

    </div>

    <?php
    return ob_get_clean();
}

// Shortcode: display table + add form
add_shortcode( 'myplugin_items', 'myplugin_items_shortcode' );


// --------------------------------------------------------------------------
// * 7 * Calling an API (Shortcode: dad_joke API)
// --------------------------------------------------------------------------

// the dad joke api and get a joke
function dad_joke_shortcode() {

    // If refresh button was pressed, redirect to clear POST
    if ( isset($_POST['dad_joke_refresh']) ) {
        wp_redirect( $_SERVER['REQUEST_URI'] );
        exit;
    }

    // Fetch joke from API
    $response = wp_remote_get(
        'https://icanhazdadjoke.com/',
        [
            'headers' => [
                'Accept' => 'application/json',
                'User-Agent' => 'WordPress Dad Joke Plugin'
            ]
        ]
    );

    if ( is_wp_error( $response ) ) {
        $joke = 'Could not fetch a joke right now.';
    } else {
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body );
        $joke = ! empty( $data->joke ) ? $data->joke : 'No joke found.';
    }

    ob_start();
    ?>

    <div class="dad-joke-box" style="padding:15px;border:1px solid #ccc;margin:10px 0;">
        <p><strong>Dad Joke:</strong> <?php echo esc_html( $joke ); ?></p>

        <form method="post">
            <button type="submit" name="dad_joke_refresh">Get Another Joke</button>
        </form>
    </div>

    <?php
    return ob_get_clean();
}

// register the shortcode
add_shortcode( 'dad_joke', 'dad_joke_shortcode' );


