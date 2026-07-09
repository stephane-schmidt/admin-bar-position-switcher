<?php
/**
 * Plugin Name:       Admin Bar Position Switcher
 * Plugin URI:        https://wordpress.org/plugins/admin-bar-position-switcher/
 * Description:       Moves the WordPress toolbar to the bottom of the screen on the front end, with a one-click button to flip it between top and bottom. The choice is remembered per browser.
 * Version:           1.0.4
 * Requires at least: 5.0
 * Requires PHP:      7.2
 * Author:            Stéphane Schmidt
 * Author URI:        https://alveo.design
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       admin-bar-position-switcher
 * Domain Path:       /languages
 *
 * @package AdminBarPositionSwitcher
 */

defined( 'ABSPATH' ) || exit;

define( 'ABPS_VERSION', '1.0.4' );
define( 'ABPS_FILE', __FILE__ );
define( 'ABPS_DIR', plugin_dir_path( __FILE__ ) );
define( 'ABPS_URL', plugin_dir_url( __FILE__ ) );

require_once ABPS_DIR . 'includes/class-abps-settings.php';
require_once ABPS_DIR . 'includes/class-abps-plugin.php';

/**
 * Boot the plugin once all plugins are loaded.
 *
 * Translations are loaded automatically by WordPress (since 4.6) from the
 * plugin's Domain Path and from WordPress.org language packs, so there is no
 * call to load_plugin_textdomain() here.
 */
function abps_init() {
	new ABPS_Plugin();

	if ( is_admin() ) {
		new ABPS_Settings();
	}
}
add_action( 'plugins_loaded', 'abps_init' );

/**
 * Store default options on activation without clobbering existing ones.
 */
function abps_activate() {
	$existing = get_option( ABPS_Settings::OPTION, array() );
	if ( ! is_array( $existing ) ) {
		$existing = array();
	}
	update_option( ABPS_Settings::OPTION, wp_parse_args( $existing, ABPS_Settings::get_defaults() ) );
}
register_activation_hook( __FILE__, 'abps_activate' );
