<?php 
/**
 * Plugin Name: Example Plugin
 * Plugin URI: https://www.youtube.com/watch?v=xvFZjo5PgG0
 * Description: WordPress example plugin
 * Version: 1.1
 * Author: Matt Petersen
 * Author URI: nesretep.net
 * Text Domain: example-plugin
 */

function generate_plugin_html() {
    $plugin_text = 'Matt Petersen is a developer and solution architect';

    return '
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

add_action( 'wp_body_open', 'generate_plugin_html' );