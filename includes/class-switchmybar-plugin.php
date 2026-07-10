<?php
/**
 * Front-end behavior: relocate the toolbar and print the switch button.
 *
 * @package AdminBarPositionSwitcher
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Switchmybar_Plugin.
 */
class Switchmybar_Plugin {

	/**
	 * Resolved plugin options.
	 *
	 * @var array
	 */
	private $options;

	/**
	 * Hook front-end setup after the main query is available.
	 *
	 * The AJAX handlers are registered here (not in maybe_boot) because
	 * admin-ajax.php requests never reach template_redirect.
	 */
	public function __construct() {
		add_action( 'template_redirect', array( $this, 'maybe_boot' ) );
		add_action( 'wp_ajax_switchmybar_bar_color', array( $this, 'ajax_bar_color' ) );
		add_action( 'wp_ajax_switchmybar_detect_colors', array( $this, 'ajax_detect_colors' ) );
	}

	/**
	 * Only run when the toolbar is actually shown on the front end.
	 */
	public function maybe_boot() {
		if ( ! is_admin_bar_showing() ) {
			return;
		}

		$this->options = Switchmybar_Settings::get_options();

		// Disable WordPress's built-in top "bump"; we manage the offset ourselves.
		remove_action( 'wp_enqueue_scripts', 'wp_enqueue_admin_bar_bump_styles' );
		remove_action( 'wp_head', '_admin_bar_bump_cb' );

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_footer', array( $this, 'print_switch_button' ) );

		if ( ! empty( $this->options['bar_picker'] ) && current_user_can( 'manage_options' ) ) {
			add_action( 'admin_bar_menu', array( $this, 'add_color_picker_node' ), 500 );
		}
	}


	/**
	 * Add the "Bar" color picker to the toolbar: hovering it reveals the
	 * site's dominant colors (detected from the logo and theme); clicking a
	 * swatch recolors the toolbar and saves the choice.
	 *
	 * @param WP_Admin_Bar $bar The admin bar.
	 */
	public function add_color_picker_node( $bar ) {
		$label = isset( $this->options['button_label'] ) ? trim( (string) $this->options['button_label'] ) : '';
		if ( '' === $label ) {
			$label = __( 'Bar', 'admin-bar-position-switcher' );
		}
		$current = ! empty( $this->options['bar_bg_enabled'] ) ? $this->options['bar_bg_color'] : '#1d2327';

		$bar->add_node(
			array(
				'id'     => 'abps-colors',
				'parent' => 'top-secondary',
				'title'  => '<span class="abps-dot" style="background:' . esc_attr( $current ) . '"></span>' . esc_html( $label ),
				'href'   => false,
				'meta'   => array( 'title' => __( 'Toolbar color', 'admin-bar-position-switcher' ) ),
			)
		);

		$swatches = '';
		foreach ( Switchmybar_Color_Detector::palette() as $hex ) {
			$swatches .= '<button type="button" class="abps-swatch" data-abps-color="' . esc_attr( $hex ) . '" style="background:' . esc_attr( $hex ) . '" title="' . esc_attr( $hex ) . '" aria-label="' . esc_attr( $hex ) . '"></button>';
		}
		$swatches .= '<button type="button" class="abps-swatch abps-swatch--default" data-abps-color="default" title="' . esc_attr__( 'Default', 'admin-bar-position-switcher' ) . '" aria-label="' . esc_attr__( 'Default', 'admin-bar-position-switcher' ) . '"></button>';

		$bar->add_node(
			array(
				'id'     => 'abps-colors-swatches',
				'parent' => 'abps-colors',
				'title'  => '<span class="abps-swatches">' . $swatches . '</span>',
				'href'   => false,
			)
		);
	}

	/**
	 * AJAX: save a toolbar color picked from the swatches.
	 */
	public function ajax_bar_color() {
		check_ajax_referer( 'switchmybar_bar_color', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
		}
		$raw  = isset( $_POST['color'] ) ? sanitize_text_field( wp_unslash( $_POST['color'] ) ) : '';
		$opts = Switchmybar_Settings::get_options();
		if ( 'default' === $raw ) {
			$opts['bar_bg_enabled'] = 0;
			update_option( Switchmybar_Settings::OPTION, $opts );
			wp_send_json_success( array( 'color' => '', 'text' => '' ) );
		}
		$hex = sanitize_hex_color( $raw );
		if ( ! $hex ) {
			wp_send_json_error( array( 'message' => 'invalid color' ), 400 );
		}
		$opts['bar_bg_enabled'] = 1;
		$opts['bar_bg_color']   = $hex;
		update_option( Switchmybar_Settings::OPTION, $opts );
		wp_send_json_success(
			array(
				'color' => $hex,
				'text'  => self::readable_text_color( $hex ),
			)
		);
	}

	/**
	 * AJAX: run the deep color detection once (logo + theme + frequency scan).
	 */
	public function ajax_detect_colors() {
		check_ajax_referer( 'switchmybar_bar_color', 'nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'forbidden' ), 403 );
		}
		wp_send_json_success( array( 'colors' => Switchmybar_Color_Detector::detect( true ) ) );
	}


	/**
	 * Enqueue the stylesheet and the toggle script.
	 */
	public function enqueue_assets() {
		// Position class before first paint (head, before the stylesheet
		// applies) so the layout never flashes: a src-less registered handle
		// carries the inline snippet through the enqueue API.
		wp_register_script( 'switchmybar-head', false, array(), SWITCHMYBAR_VERSION, false );
		wp_enqueue_script( 'switchmybar-head' );
		wp_add_inline_script( 'switchmybar-head', $this->early_position_script() );

		wp_enqueue_style(
			'admin-bar-position-switcher',
			SWITCHMYBAR_URL . 'assets/css/admin-bar-position-switcher.css',
			array(),
			SWITCHMYBAR_VERSION
		);

		$dynamic = $this->dynamic_css();
		if ( '' !== $dynamic ) {
			wp_add_inline_style( 'admin-bar-position-switcher', $dynamic );
		}

		wp_enqueue_script(
			'admin-bar-position-switcher',
			SWITCHMYBAR_URL . 'assets/js/admin-bar-position-switcher.js',
			array(),
			SWITCHMYBAR_VERSION,
			true
		);

		$config = array(
			'defaultPosition' => ( 'top' === $this->options['default_position'] ) ? 'top' : 'bottom',
			'remember'        => ! empty( $this->options['remember_choice'] ),
			'autoColor'       => ! empty( $this->options['auto_color'] ),
			'autoHide'        => ! empty( $this->options['auto_hide'] ),
			'storageKey'      => 'abpsPosition',
		);

		if ( ! empty( $this->options['bar_picker'] ) && current_user_can( 'manage_options' ) ) {
			$config['canPick']  = true;
			$config['ajaxUrl']  = admin_url( 'admin-ajax.php' );
			$config['nonce']    = wp_create_nonce( 'switchmybar_bar_color' );
			$config['needDeep'] = ! Switchmybar_Color_Detector::has_deep();
		}

		// wp_json_encode preserves booleans; wp_localize_script would cast
		// every scalar to a string ("1"/"") and silently break the JS flags.
		wp_add_inline_script(
			'admin-bar-position-switcher',
			'window.ABPS = ' . wp_json_encode( $config ) . ';',
			'before'
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
			// When the toolbar auto-hides it overlays the page, so nothing is offset.
			$css .= 'html.abps-bar-autohide .elementor-location-header .elementor-sticky--active{top:0 !important;}';
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
		return (string) apply_filters( 'switchmybar_dynamic_css', $css, $this->options );
	}

	/**
	 * The inline snippet applying the saved/default position before first
	 * paint (attached to the src-less "switchmybar-head" handle). When
	 * JavaScript never runs, the stylesheet's html:not(.abps-top):not(.abps-bottom)
	 * fallback rules keep the native top toolbar usable.
	 *
	 * @return string
	 */
	private function early_position_script() {
		$default  = ( 'top' === $this->options['default_position'] ) ? 'top' : 'bottom';
		$remember = ! empty( $this->options['remember_choice'] );

		$script  = '(function(){try{';
		$script .= "var d='" . esc_js( $default ) . "';var p=d;";
		if ( $remember ) {
			$script .= "var s=localStorage.getItem('abpsPosition');if(s==='top'||s==='bottom'){p=s;}";
		}
		$script .= "document.documentElement.classList.add(p==='top'?'abps-top':'abps-bottom');";
		$script .= "}catch(e){document.documentElement.classList.add('abps-" . esc_js( $default ) . "');}})();";

		return $script;
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
