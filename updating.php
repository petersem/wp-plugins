<?php 
/**
 * Plugin Name: updating
 * Plugin URI: https://www.youtube.com/watch?v=xvFZjo5PgG0
 * Description: WordPress example plugins. One file to rule them all. 😛😛😛
 * Version: 1.2.2
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



$username = 'petersem';
$repo = 'wp-plugins';
$pluginslug = 'updating';


add_filter('pre_set_site_transient_update_plugins', function($transient) {
    
    if (empty($transient->checked)) {
        return $transient;
    }

    $plugin_file = plugin_basename(__FILE__);
    $current_version = $transient->checked[$plugin_file];

    $remote = wp_remote_get('https://api.github.com/repos/' . $username . '/' . $repo . '/releases/latest', [
        'headers' => ['Accept' => 'application/vnd.github.v3+json']
    ]);

    if (is_wp_error($remote)) {
        return $transient;
    }

    $data = json_decode(wp_remote_retrieve_body($remote));
    if (!$data || empty($data->tag_name)) {
        return $transient;
    }

    $latest_version = ltrim($data->tag_name, 'v');

    if (version_compare($latest_version, $current_version, '>')) {
        $transient->response[$plugin_file] = (object)[
            'slug'        => $pluginslug,
            'new_version' => $latest_version,
            'package'     => $data->zipball_url,
            'url'         => $data->html_url,
        ];
    }

    return $transient;
});