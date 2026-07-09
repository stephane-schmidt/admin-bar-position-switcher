<?php
/**
 * Front-end behavior: relocate the toolbar and print the switch button.
 *
 * @package AdminBarPositionSwitcher
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class ABPS_Plugin.
 */
class ABPS_Plugin {

	/**
	 * Resolved plugin options.
	 *
	 * @var array
	 */
	private $options;

	/**
	 * Hook front-end setup after the main query is available.
	 */
	public function __construct() {
		add_action( 'template_redirect', array( $this, 'maybe_boot' ) );
	}

	/**
	 * Only run when the toolbar is actually shown on the front end.
	 */
	public function maybe_boot() {
		if ( ! is_admin_bar_showing() ) {
			return;
		}

		$this->options = ABPS_Settings::get_options();

		// Disable WordPress's built-in top "bump"; we manage the offset ourselves.
		remove_action( 'wp_enqueue_scripts', 'wp_enqueue_admin_bar_bump_styles' );
		remove_action( 'wp_head', '_admin_bar_bump_cb' );

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_head', array( $this, 'print_early_script' ), 1 );
		add_action( 'wp_head', array( $this, 'print_noscript_fallback' ), 2 );
		add_action( 'wp_footer', array( $this, 'print_switch_button' ) );

		if ( ! empty( $this->options['hidden_items'] ) ) {
			add_action( 'wp_before_admin_bar_render', array( $this, 'remove_hidden_nodes' ), 1000 );
		}
	}

	/**
	 * Remove the toolbar items the user chose to hide.
	 */
	public function remove_hidden_nodes() {
		$bar = isset( $GLOBALS['wp_admin_bar'] ) ? $GLOBALS['wp_admin_bar'] : null;
		if ( ! $bar || ! method_exists( $bar, 'remove_node' ) ) {
			return;
		}
		foreach ( (array) $this->options['hidden_items'] as $id ) {
			$bar->remove_node( $id );
		}
	}

	/**
	 * Enqueue the stylesheet and the toggle script.
	 */
	public function enqueue_assets() {
		wp_enqueue_style(
			'admin-bar-position-switcher',
			ABPS_URL . 'assets/css/admin-bar-position-switcher.css',
			array(),
			ABPS_VERSION
		);

		$dynamic = $this->dynamic_css();
		if ( '' !== $dynamic ) {
			wp_add_inline_style( 'admin-bar-position-switcher', $dynamic );
		}

		wp_enqueue_script(
			'admin-bar-position-switcher',
			ABPS_URL . 'assets/js/admin-bar-position-switcher.js',
			array(),
			ABPS_VERSION,
			true
		);

		wp_localize_script(
			'admin-bar-position-switcher',
			'ABPS',
			array(
				'defaultPosition' => ( 'top' === $this->options['default_position'] ) ? 'top' : 'bottom',
				'remember'        => ! empty( $this->options['remember_choice'] ),
				'autoColor'       => ! empty( $this->options['auto_color'] ),
				'autoHide'        => ! empty( $this->options['auto_hide'] ),
				'storageKey'      => 'abpsPosition',
			)
		);
	}

	/**
	 * Optional CSS that depends on the saved options.
	 *
	 * Kept out of the static stylesheet so integrations (e.g. Elementor sticky
	 * header compatibility) can be toggled from the settings screen.
	 *
	 * @return string
	 */
	private function dynamic_css() {
		$css = '';

		if ( ! empty( $this->options['elementor_compat'] ) ) {
			$css .= 'html.abps-bottom .elementor-location-header .elementor-sticky--active{top:0 !important;}';
			$css .= 'html.abps-top .elementor-location-header .elementor-sticky--active{top:32px !important;}';
			$css .= '@media screen and (max-width:782px){html.abps-top .elementor-location-header .elementor-sticky--active{top:46px !important;}}';
		}

		if ( ! empty( $this->options['bar_bg_enabled'] ) ) {
			$bg = $this->options['bar_bg_color'];
			$fg = self::readable_text_color( $bg );
			// Recolor the top bar and its top-level items only; sub-menus keep their own styling.
			$top = '#wpadminbar #wp-admin-bar-root-default>li>.ab-item,#wpadminbar #wp-admin-bar-top-secondary>li>.ab-item';
			$ico = '#wpadminbar #wp-admin-bar-root-default>li>.ab-item .ab-icon:before,#wpadminbar #wp-admin-bar-top-secondary>li>.ab-item .ab-icon:before,#wpadminbar #wp-admin-bar-root-default>li>.ab-item:before,#wpadminbar #wp-admin-bar-top-secondary>li>.ab-item:before';
			$css .= '#wpadminbar{background:' . $bg . ' !important;}';
			$css .= $top . '{color:' . $fg . ' !important;}';
			$css .= $ico . '{color:' . $fg . ' !important;}';
		}

		/**
		 * Filter the plugin's dynamically generated CSS.
		 *
		 * @param string $css     Extra CSS appended to the stylesheet.
		 * @param array  $options Resolved plugin options.
		 */
		return (string) apply_filters( 'abps_dynamic_css', $css, $this->options );
	}

	/**
	 * Apply the saved/default position before first paint to avoid a flash.
	 */
	public function print_early_script() {
		$default  = ( 'top' === $this->options['default_position'] ) ? 'top' : 'bottom';
		$remember = ! empty( $this->options['remember_choice'] );

		$script  = '(function(){try{';
		$script .= "var d='" . esc_js( $default ) . "';var p=d;";
		if ( $remember ) {
			$script .= "var s=localStorage.getItem('abpsPosition');if(s==='top'||s==='bottom'){p=s;}";
		}
		$script .= "document.documentElement.classList.add(p==='top'?'abps-top':'abps-bottom');";
		$script .= "}catch(e){document.documentElement.classList.add('abps-" . esc_js( $default ) . "');}})();";

		echo '<script>' . $script . '</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- values escaped with esc_js above.
	}

	/**
	 * Keep the native top toolbar usable when JavaScript is disabled.
	 */
	public function print_noscript_fallback() {
		echo '<noscript><style>'
			. 'html{margin-top:32px !important;}'
			. '@media screen and (max-width:782px){html{margin-top:46px !important;}}'
			. '#abps-switch{display:none !important;}'
			. '</style></noscript>' . "\n";
	}

	/**
	 * Output the floating toggle button in the footer.
	 */
	public function print_switch_button() {
		if ( empty( $this->options['show_toggle'] ) ) {
			return;
		}

		$label = isset( $this->options['button_label'] ) ? trim( (string) $this->options['button_label'] ) : '';
		if ( '' === $label ) {
			$label = __( 'Bar', 'admin-bar-position-switcher' );
		}

		printf(
			'<button id="abps-switch" type="button" title="%1$s">&#8597; %2$s</button>',
			esc_attr__( 'Move the toolbar to the top or bottom', 'admin-bar-position-switcher' ),
			esc_html( $label )
		);
	}

	/**
	 * Pick a readable text color (dark or light) for a given background hex.
	 *
	 * @param string $hex Background color, e.g. "#1d2327".
	 * @return string "#1d2327" or "#ffffff".
	 */
	public static function readable_text_color( $hex ) {
		$hex = ltrim( (string) $hex, '#' );
		if ( 3 === strlen( $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}
		if ( 6 !== strlen( $hex ) || ! ctype_xdigit( $hex ) ) {
			return '#ffffff';
		}
		$r   = hexdec( substr( $hex, 0, 2 ) );
		$g   = hexdec( substr( $hex, 2, 2 ) );
		$b   = hexdec( substr( $hex, 4, 2 ) );
		$lum = ( 0.2126 * $r + 0.7152 * $g + 0.0722 * $b ) / 255;
		return $lum > 0.6 ? '#1d2327' : '#ffffff';
	}
}
