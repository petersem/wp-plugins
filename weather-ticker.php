<?php 
/**
 * Plugin Name: weather ticker
 * Plugin URI: nesretep.net
 * Description: WordPress automated weather ticker plugin with Admin Custom Latitude & Longitude controls.
 * Version: 1.0.1
 * Author: Matt
 * Author URI: nesretep.net
 * Text Domain: test-plugin
 * GitHub Plugin URI: https://github.com/petersem/wp-weather-plugin
 * GitHub Branch: main
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/* ---------------------------------------------------------
   ADMIN MENU
--------------------------------------------------------- */
add_action( 'admin_menu', 'weather_ticker_menu' );
function weather_ticker_menu() {
    add_options_page(
        'Weather Ticker Settings',
        'Weather Ticker',
        'manage_options',
        'weather-ticker',
        'weather_ticker_settings_page'
    );
}

/* ---------------------------------------------------------
   SETTINGS
--------------------------------------------------------- */
add_action( 'admin_init', 'weather_ticker_settings_init' );
function weather_ticker_settings_init() {

    register_setting( 'weather_ticker_group', 'weather_ticker_enabled' );

    register_setting( 'weather_ticker_group', 'weather_ticker_lat', array(
        'sanitize_callback' => 'sanitize_coordinate_change',
        'default'           => '-27.470125'
    ) );

    register_setting( 'weather_ticker_group', 'weather_ticker_lon', array(
        'sanitize_callback' => 'sanitize_coordinate_change',
        'default'           => '153.021072'
    ) );

    register_setting( 'weather_ticker_group', 'weather_ticker_api_key', array(
        'sanitize_callback' => 'sanitize_coordinate_change',
        'default'           => '*************'
    ) );
}

function sanitize_coordinate_change( $new_value ) {
    delete_transient( 'custom_live_ticker_text_v25' );
    return sanitize_text_field( $new_value );
}

// Admin page
function weather_ticker_settings_page() {

    if ( isset( $_POST['clear_weather_cache'] ) && check_admin_referer( 'clear_weather_nonce' ) ) {
        delete_transient( 'custom_live_ticker_text_v25' );
        echo '<div class="notice notice-success is-dismissible"><p>Weather transient cache successfully cleared!</p></div>';
    }

    $enabled     = get_option( 'weather_ticker_enabled', '1' );
    $saved_lat   = get_option( 'weather_ticker_lat', '-27.470125' );
    $saved_lon   = get_option( 'weather_ticker_lon', '153.021072' );
    $saved_API_key = get_option( 'weather_ticker_api_key', 'enter an OpenWeatherMap API key' );
    $cached_text = get_transient( 'custom_live_ticker_text_v25' );
    ?>
    <div class="wrap">
        <h1>🌤️ Weather Ticker Configuration Control</h1>

        <form method="post" action="options.php">
            <?php settings_fields( 'weather_ticker_group' ); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Toggle Visibility</th>
                    <td>
                        <label>
                            <input type="checkbox" name="weather_ticker_enabled" value="1" <?php checked( $enabled, '1' ); ?> />
                            Display the scrolling weather ticker under the theme header.
                        </label>
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row"><label for="weather_ticker_api_key">OpenWeatherMap API Key</label></th>
                    <td>
                        <input name="weather_ticker_api_key" id="weather_ticker_api_key" type="password" value="<?php echo esc_attr( $saved_API_key ); ?>" class="regular-text" />
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row"><label for="weather_ticker_lat">Target Latitude (lat)</label></th>
                    <td>
                        <input type="text" name="weather_ticker_lat" id="weather_ticker_lat" value="<?php echo esc_attr( $saved_lat ); ?>" class="regular-text" />
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row"><label for="weather_ticker_lon">Target Longitude (lon)</label></th>
                    <td>
                        <input type="text" name="weather_ticker_lon" id="weather_ticker_lon" value="<?php echo esc_attr( $saved_lon ); ?>" class="regular-text" />
                    </td>
                </tr>
            </table>

            <?php submit_button( 'Save Configuration Parameters' ); ?>
        </form>

        <hr />

        <h2>⚙️ System Diagnostics & Cache Status</h2>

        <table class="wp-list-table widefat fixed striped" style="max-width: 800px;">
            <tbody>
                <tr>
                    <td><strong>Active Target Coordinates:</strong></td>
                    <td><code>Lat: <?php echo esc_html( $saved_lat ); ?> | Lon: <?php echo esc_html( $saved_lon ); ?></code></td>
                </tr>
                <tr>
                    <td><strong>Cache Engine Output Status:</strong></td>
                    <td>
                        <?php if ( $cached_text ) : ?>
                            <span style="color: #46b450; font-weight: bold;">🟢 Operational & Active</span>
                        <?php else : ?>
                            <span style="color: #dc3232; font-weight: bold;">🔴 Expired / Empty</span>
                        <?php endif; ?>
                    </td>
                </tr>
            </tbody>
        </table>

        <form method="post" action="">
            <?php wp_nonce_field( 'clear_weather_nonce', 'clear_weather_nonce' ); ?>
            <input type="submit" name="clear_weather_cache" class="button button-secondary" value="Flush Active Weather Cache" />
        </form>
    </div>
    <?php
}

/* ---------------------------------------------------------
   TICKER GENERATION (shared by both theme types)
--------------------------------------------------------- */
function generate_ticker_html() {

    $ticker_text = get_transient( 'custom_live_ticker_text_v25' );

    if ( false === $ticker_text ) {

        $saved_lat = get_option( 'weather_ticker_lat', '-27.470125' );
        $saved_lon = get_option( 'weather_ticker_lon', '153.021072' );
        $saved_api_key = get_option( 'weather_ticker_api_key', '' );

        $api_url = "https://api.openweathermap.org/data/2.5/forecast?lat={$saved_lat}&lon={$saved_lon}&units=metric&appid={$saved_api_key}";
        $response = wp_remote_get( $api_url, array( 'timeout' => 5 ) );

        if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {

            $data = json_decode( wp_remote_retrieve_body( $response ), true );

            if ( ! empty( $data['list'] ) ) {

                $current  = $data['list'][0];
                $temp     = number_format( (float) $current['main']['temp'], 1 );
                $desc     = ucfirst( $current['weather'][0]['description'] );
                $humid    = $current['main']['humidity'];
                $location = $data['city']['name'] ?? 'Selected Location';

                $ticker_text = "📍 {$location} Now: {$temp}°C, {$desc} | 💧 Humidity: {$humid}% | ";

                $forecast_indices = array( 8, 16, 24, 32 );

                foreach ( $forecast_indices as $index ) {
                    if ( isset( $data['list'][$index] ) ) {
                        $item = $data['list'][$index];
                        $f_temp = number_format( (float) $item['main']['temp'], 1 );
                        $f_desc = ucfirst( $item['weather'][0]['description'] );
                        $day    = wp_date( 'D', $item['dt'] );
                        $ticker_text .= "☀️ {$day}: {$f_temp}°C ({$f_desc}) | ";
                    }
                }

                set_transient( 'custom_live_ticker_text_v25', $ticker_text, 3600 );
            }
        }
    }

    if ( ! $ticker_text ) {
        $ticker_text = "📍 Weather service unavailable | Please check settings | ";
    }

    return '
    <div class="custom-weather-ticker">
        <div class="ticker-track">' . esc_html( $ticker_text ) . '</div>
    </div>

    <style>
        .custom-weather-ticker {
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
            animation: brisbaneTickerEffect 35s linear infinite;
        }
        @keyframes brisbaneTickerEffect {
            0% { transform: translate3d(0, 0, 0); }
            100% { transform: translate3d(-100%, 0, 0); }
        }
        .ticker-track:hover {
            animation-play-state: paused;
            cursor: pointer;
        }
    </style>';
}

/* ---------------------------------------------------------
   BLOCK THEMES (Gutenberg)
--------------------------------------------------------- */
add_filter( 'render_block', 'automated_weather_ticker', 10, 2 );

function automated_weather_ticker( $block_content, $block ) {

    if ( '1' !== get_option( 'weather_ticker_enabled', '1' ) ) {
        return $block_content;
    }

    if ( ! empty( $block['attrs']['slug'] ) && 'header' === $block['attrs']['slug'] ) {
        return $block_content . generate_ticker_html();
    }

    return $block_content;
}

// Astra theme
add_action( 'get_header', 'output_weather_ticker' );

function output_weather_ticker() {

    if ( '1' !== get_option( 'weather_ticker_enabled', '1' ) ) {
        return;
    }

    echo generate_ticker_html();
}
