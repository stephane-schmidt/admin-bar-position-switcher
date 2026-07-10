<?php
/**
 * Plugin Name:       Admin Bar Position Switcher
 * Plugin URI:        https://wordpress.org/plugins/admin-bar-position-switcher/
 * Description:       Moves the WordPress toolbar to the bottom of the screen on the front end, with a one-click button to flip it between top and bottom. The choice is remembered per browser.
 * Version:           1.6.2
 * Requires at least: 5.9
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

define( 'SWITCHMYBAR_VERSION', '1.6.2' );
define( 'SWITCHMYBAR_FILE', __FILE__ );
define( 'SWITCHMYBAR_DIR', plugin_dir_path( __FILE__ ) );
define( 'SWITCHMYBAR_URL', plugin_dir_url( __FILE__ ) );

require_once SWITCHMYBAR_DIR . 'includes/class-switchmybar-settings.php';
require_once SWITCHMYBAR_DIR . 'includes/class-switchmybar-color-detector.php';
require_once SWITCHMYBAR_DIR . 'includes/class-switchmybar-admin-menu.php';
require_once SWITCHMYBAR_DIR . 'includes/class-switchmybar-plugin.php';

/**
 * Boot the plugin once all plugins are loaded.
 *
 * Translations are loaded automatically by WordPress (since 4.6) from the
 * plugin's Domain Path and from WordPress.org language packs, so there is no
 * call to load_plugin_textdomain() here.
 */
function switchmybar_init() {
	switchmybar_maybe_migrate_options();

	new Switchmybar_Plugin();

	if ( is_admin() ) {
		new Switchmybar_Settings();
		new Switchmybar_Admin_Menu();
	}
}
add_action( 'plugins_loaded', 'switchmybar_init' );

/**
 * One-time migration from the pre-1.6.0 option names (abps_*).
 */
function switchmybar_maybe_migrate_options() {
	if ( false === get_option( 'switchmybar_options', false ) ) {
		$legacy = get_option( 'abps_options', false );
		if ( false !== $legacy ) {
			update_option( 'switchmybar_options', $legacy );
			delete_option( 'abps_options' );
		}
	}
	if ( false !== get_option( 'abps_colors_detected', false ) ) {
		delete_option( 'abps_colors_detected' ); // cache: simply rebuilt under the new name.
	}
}

/**
 * Store default options on activation without clobbering existing ones.
 */
function switchmybar_activate() {
	$existing = get_option( Switchmybar_Settings::OPTION, array() );
	if ( ! is_array( $existing ) ) {
		$existing = array();
	}
	update_option( Switchmybar_Settings::OPTION, wp_parse_args( $existing, Switchmybar_Settings::get_defaults() ) );
}
register_activation_hook( __FILE__, 'switchmybar_activate' );
