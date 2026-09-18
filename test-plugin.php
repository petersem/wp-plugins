<?php 
/**
 * Plugin Name: test-mp-plugin
 * Plugin URI: nesretep.net
 * Description: WordPress automated weather ticker plugin with Admin Custom Latitude & Longitude controls.
 * Version: 4.0
 * Author: Matt
 * Author URI: nesretep.net
 * Text Domain: test-plugin
 */

// Exit immediately if accessed directly to guarantee security
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define core lookup values
$api_key  = '0ba35265604a6eeee7bd7c2f8615cdcc'; 


// Setup the Admin Settings Page Menu
add_action( 'admin_menu', 'ttf25_weather_ticker_menu' );
function ttf25_weather_ticker_menu() {
	add_options_page(
		'Weather Ticker Settings',
		'Weather Ticker',
		'manage_options',
		'ttf25-weather-ticker',
		'ttf25_weather_ticker_settings_page'
	);
}


 // Register Plugin Settings Configuration
 add_action( 'admin_init', 'ttf25_weather_ticker_settings_init' );
function ttf25_weather_ticker_settings_init() {
	register_setting( 'ttf25_weather_ticker_group', 'ttf25_weather_ticker_enabled' );
	
	// Register custom Latitude field with a cache clearing hook
	register_setting( 'ttf25_weather_ticker_group', 'ttf25_weather_ticker_lat', array(
		'sanitize_callback' => 'ttf25_sanitize_coordinate_change',
		'default'           => '-27.470125'
	) );

	// Register custom Lat/Long fields with a cache clearing hook
	register_setting( 'ttf25_weather_ticker_group', 'ttf25_weather_ticker_lon', array(
		'sanitize_callback' => 'ttf25_sanitize_coordinate_change',
		'default'           => '153.021072'
	) );

    // Register custom api key with a cache clearing hook
	register_setting( 'ttf25_weather_ticker_group', 'ttf25_weather_ticker_api_key', array(
		'sanitize_callback' => 'ttf25_sanitize_coordinate_change',
		'default'           => '*************'
	) );
}

// Force clear the cache database automatically whenever custom latitude or longitude points are saved
function ttf25_sanitize_coordinate_change( $new_value ) {
	delete_transient( 'custom_live_ticker_text_v25' );
	return sanitize_text_field( $new_value );
}

// Render the Admin Dashboard Settings UI & Diagnostics Layout
function ttf25_weather_ticker_settings_page() {
    
	if ( isset( $_POST['clear_weather_cache'] ) && check_admin_referer( 'clear_weather_nonce' ) ) {
		delete_transient( 'custom_live_ticker_text_v25' );
		echo '<div class="notice notice-success is-dismissible"><p>Weather transient cache successfully cleared!</p></div>';
	}

	$enabled     = get_option( 'ttf25_weather_ticker_enabled', '1' );
	$saved_lat   = get_option( 'ttf25_weather_ticker_lat', '-27.470125' );
	$saved_lon   = get_option( 'ttf25_weather_ticker_lon', '153.021072' );
    $saved_API_key = get_option( 'ttf25_weather_ticker_api_key', 'enter an OpenWeatherMap API key' );
	$cached_text = get_transient( 'custom_live_ticker_text_v25' );
	
	?>
	<div class="wrap">
		<h1>🌤️ Weather Ticker Configuration Control</h1>
		<form method="post" action="options.php">
			<?php settings_fields( 'ttf25_weather_ticker_group' ); ?>
			<table class="form-table">
				<tr valign="top">
					<th scope="row">Toggle Visibility</th>
					<td>
						<label>
							<input type="checkbox" name="ttf25_weather_ticker_enabled" value="1" <?php checked( $enabled, '1' ); ?> />
							Display the scrolling weather ticker under the theme header block asset templates.
						</label>
					</td>
				</tr>

				<!-- API input field box -->
				<tr valign="top">
					<th scope="row"><label for="ttf25_weather_ticker_api_key">OpenWeatherMap API Key</label></th>
					<td>
						<input name="ttf25_weather_ticker_api_key" id="ttf25_weather_ticker_api_key" type="password" value="<?php echo esc_attr( $saved_API_key ); ?>" class="regular-text" placeholder="e.g. -27.470125" />
						<p class="description">OpenWeatherMap API key</p>
					</td>
				</tr>
                
				<!-- Interactive Latitude input field box -->
				<tr valign="top">
					<th scope="row"><label for="ttf25_weather_ticker_lat">Target Latitude (lat)</label></th>
					<td>
						<input type="text" name="ttf25_weather_ticker_lat" id="ttf25_weather_ticker_lat" value="<?php echo esc_attr( $saved_lat ); ?>" class="regular-text" placeholder="e.g. -27.470125" />
						<p class="description">Use negative values for positions in the Southern Hemisphere. </p>
					</td>
				</tr>

				<!-- Interactive Longitude input field box -->
				<tr valign="top">
					<th scope="row"><label for="ttf25_weather_ticker_lon">Target Longitude (lon)</label></th>
					<td>
						<input type="text" name="ttf25_weather_ticker_lon" id="ttf25_weather_ticker_lon" value="<?php echo esc_attr( $saved_lon ); ?>" class="regular-text" placeholder="e.g. 153.021072" />
						<p class="description">Changing either coordinate parameter automatically clears the data cache layer immediately.</p>
					</td>
				</tr>
                <tr>
                    (Lat -27.4698 and Lon 153.0251 for Brisbane)
                </tr>
			</table>
			<?php submit_button( 'Save Configuration Parameters' ); ?>
		</form>

		<hr style="margin: 30px 0; border: none; border-top: 1px solid #ccc;" />

		<h2>⚙️ System Diagnostics & Cache Status</h2>
		<table class="wp-list-table widefat fixed striped" style="max-width: 800px;">
			<tbody>
				<tr>
					<td style="width: 200px;"><strong>Active Target Coordinates:</strong></td>
					<td><code>Lat: <?php echo esc_html( $saved_lat ); ?> | Lon: <?php echo esc_html( $saved_lon ); ?></code></td>
				</tr>
				<tr>
					<td><strong>Cache Engine Output Status:</strong></td>
					<td>
						<?php if ( $cached_text ) : ?>
							<span style="color: #46b450; font-weight: bold;">🟢 Operational & Active</span> (Loaded from local data cache storage)
						<?php else : ?>
							<span style="color: #dc3232; font-weight: bold;">🔴 Expired / Empty</span> (Will trigger external API lookup on next page view)
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<td><strong>Currently Cached String Layout:</strong></td>
					<td><code><?php echo $cached_text ? esc_html( $cached_text ) : 'No active text payload currently cached in database'; ?></code></td>
				</tr>
				<tr>
					<td><strong>System Query URL:</strong></td>
                    <?php 
                        $api_url = "https://api.openweathermap.org/data/2.5/forecast?lat={$saved_lat}&lon={$saved_lon}&units=metric&appid=************";
                    ?>
					<td><code style="word-break: break-all;"><?php echo esc_html( $api_url ); ?></code></td>
				</tr>
				</tr>
			</tbody>
		</table>

		<form method="post" action="" style="margin-top: 15px;">
			<?php wp_nonce_field( 'clear_weather_nonce', 'clear_weather_nonce' ); ?>
			<input type="submit" name="clear_weather_cache" class="button button-secondary" value="Flush Active Weather Cache Memory Storage Layer" />
		</form>
	</div>
	<?php
}

// Weather Ticker Core Filter Logic (Hooks directly inside block layout templates)
function ttf25_automated_weather_ticker( $block_content, $block ) {

    
	if ( '1' !== get_option( 'ttf25_weather_ticker_enabled', '1' ) ) {
		return $block_content;
	}

	if ( ! empty( $block['attrs']['slug'] ) && 'header' === $block['attrs']['slug'] ) {
		
		$ticker_text = get_transient( 'custom_live_ticker_text_v25' );
		
		if ( false === $ticker_text ) {
			$saved_lat = get_option( 'ttf25_weather_ticker_lat', '-27.470125' );
			$saved_lon = get_option( 'ttf25_weather_ticker_lon', '153.021072' );
            $saved_api_key = get_option( 'ttf25_weather_ticker_api_key', 'enter an OpenWeatherMap API key' );

            $api_url = "https://api.openweathermap.org/data/2.5/forecast?lat={$saved_lat}&lon={$saved_lon}&units=metric&appid=" . $saved_api_key;
			$response = wp_remote_get( $api_url, array( 'timeout' => 5 ) );

			if ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
				$data = json_decode( wp_remote_retrieve_body( $response ), true );

				if ( ! empty( $data['list'] ) ) {
					$current  = $data['list'][0];
					$raw_temp = (float) $current['main']['temp'];
					
					// Force calculation backup transformations safely if raw Kelvin surfaces
					if ( $raw_temp > 100 ) {
						$raw_temp = $raw_temp - 273.15;
					}
					
					$temp    = number_format( $raw_temp, 1 );
					$desc    = ucfirst( $current['weather'][0]['description'] );
					$humid   = $current['main']['humidity'];
					
					// Dynamic Location Label Header
					$location_label = (!empty($data['city']['name'])) ? $data['city']['name'] : 'Selected Location';
					$ticker_text = "📍 {$location_label} Now: {$temp}°C, {$desc} | 💧 Humidity: {$humid}% | ";
					
					$forecast_indices = array( 8, 16, 24, 32 );
					
					foreach ( $forecast_indices as $index ) {
						if ( isset( $data['list'][$index] ) ) {
							$forecast_item = $data['list'][$index];
							$raw_f_temp    = (float) $forecast_item['main']['temp'];
							
							if ( $raw_f_temp > 100 ) {
								$raw_f_temp = $raw_f_temp - 273.15;
							}
							
							$f_temp   = number_format( $raw_f_temp, 1 );
							$f_desc   = ucfirst( $forecast_item['weather'][0]['description'] );
							$day_name = wp_date( 'D', $forecast_item['dt'] );
							
							$ticker_text .= "☀️ {$day_name}: {$f_temp}°C ({$f_desc}) | ";
						}
					}
					
					set_transient( 'custom_live_ticker_text_v25', $ticker_text, 3600 );
				}
			}
		}

		if ( ! $ticker_text ) {
			$ticker_text = "📍 Weather service unavailable | Please check settings | ";
		}

		$ticker_html = '
		<div class="custom-weather-ticker">
			<div class="ticker-track">
				' . esc_html( $ticker_text ) . '
			</div>
		</div>
		
		<style>
			.custom-weather-ticker {
				width: 100%;
				background: #111111;
				color: #ffffff;
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
		
		return $block_content . $ticker_html;
	}

	return $block_content;
}
add_filter( 'render_block', 'ttf25_automated_weather_ticker', 10, 2 );

