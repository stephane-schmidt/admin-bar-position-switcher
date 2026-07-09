<?php
/**
 * Settings screen (Settings → Admin Bar Position).
 *
 * @package AdminBarPositionSwitcher
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class ABPS_Settings.
 */
class ABPS_Settings {

	/**
	 * Option key holding the settings array.
	 */
	const OPTION = 'abps_options';

	/**
	 * Settings group / page slug.
	 */
	const SLUG = 'admin-bar-position-switcher';

	/**
	 * Register admin hooks.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_init', array( $this, 'register' ) );
		add_filter(
			'plugin_action_links_' . plugin_basename( ABPS_FILE ),
			array( $this, 'action_links' )
		);
	}

	/**
	 * Default option values.
	 *
	 * @return array
	 */
	public static function get_defaults() {
		return array(
			'default_position' => 'bottom',
			'show_toggle'      => 1,
			'remember_choice'  => 1,
			'auto_color'       => 1,
			'elementor_compat' => 1,
			'button_label'     => '',
			'bar_bg_enabled'   => 0,
			'bar_bg_color'     => '#1d2327',
			'hidden_items'     => array(),
		);
	}

	/**
	 * Friendly labels for the standard toolbar items.
	 *
	 * @return array id => label
	 */
	public static function known_items() {
		return array(
			'wp-logo'     => __( 'WordPress logo', 'admin-bar-position-switcher' ),
			'site-name'   => __( 'Site name', 'admin-bar-position-switcher' ),
			'customize'   => __( 'Customize', 'admin-bar-position-switcher' ),
			'updates'     => __( 'Updates', 'admin-bar-position-switcher' ),
			'comments'    => __( 'Comments', 'admin-bar-position-switcher' ),
			'new-content' => __( 'New', 'admin-bar-position-switcher' ),
			'search'      => __( 'Search', 'admin-bar-position-switcher' ),
			'my-account'  => __( 'User menu (My account)', 'admin-bar-position-switcher' ),
		);
	}

	/**
	 * The toolbar's current top-level items, as id => label.
	 *
	 * Enumerated from the live admin bar when available (so plugin-added items
	 * show up too), with a curated fallback.
	 *
	 * @return array
	 */
	public static function get_toolbar_items() {
		$items = array();
		$known = self::known_items();

		$bar = isset( $GLOBALS['wp_admin_bar'] ) ? $GLOBALS['wp_admin_bar'] : null;
		if ( $bar && method_exists( $bar, 'get_nodes' ) ) {
			$nodes = $bar->get_nodes();
			if ( is_array( $nodes ) ) {
				foreach ( $nodes as $id => $node ) {
					$parent = isset( $node->parent ) ? $node->parent : false;
					// Top-level items sit directly under a root group (whose own parent is false).
					if ( $parent && isset( $nodes[ $parent ] ) && false === $nodes[ $parent ]->parent ) {
						if ( isset( $known[ $id ] ) ) {
							$label = $known[ $id ];
						} else {
							$title = isset( $node->title ) && is_string( $node->title ) ? trim( wp_strip_all_tags( $node->title ) ) : '';
							$label = '' !== $title ? $title : $id;
						}
						$items[ $id ] = $label;
					}
				}
			}
		}

		if ( empty( $items ) ) {
			$items = $known;
		}

		return $items;
	}

	/**
	 * Resolved options merged over defaults.
	 *
	 * @return array
	 */
	public static function get_options() {
		$opts = get_option( self::OPTION, array() );
		if ( ! is_array( $opts ) ) {
			$opts = array();
		}
		return wp_parse_args( $opts, self::get_defaults() );
	}

	/**
	 * Add the options page under Settings.
	 */
	public function add_menu() {
		add_options_page(
			__( 'Admin Bar Position', 'admin-bar-position-switcher' ),
			__( 'Admin Bar Position', 'admin-bar-position-switcher' ),
			'manage_options',
			self::SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Add a "Settings" link on the Plugins screen.
	 *
	 * @param array $links Existing action links.
	 * @return array
	 */
	public function action_links( $links ) {
		$url  = admin_url( 'options-general.php?page=' . self::SLUG );
		$link = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'admin-bar-position-switcher' ) . '</a>';
		array_unshift( $links, $link );
		return $links;
	}

	/**
	 * Register the setting, section and fields.
	 */
	public function register() {
		register_setting(
			'abps_group',
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize' ),
				'default'           => self::get_defaults(),
			)
		);

		add_settings_section(
			'abps_main',
			'',
			'__return_false',
			self::SLUG
		);

		add_settings_field(
			'default_position',
			__( 'Default position', 'admin-bar-position-switcher' ),
			array( $this, 'field_default_position' ),
			self::SLUG,
			'abps_main'
		);

		add_settings_field(
			'show_toggle',
			__( 'Switch button', 'admin-bar-position-switcher' ),
			array( $this, 'field_show_toggle' ),
			self::SLUG,
			'abps_main'
		);

		add_settings_field(
			'button_label',
			__( 'Button label', 'admin-bar-position-switcher' ),
			array( $this, 'field_button_label' ),
			self::SLUG,
			'abps_main'
		);

		add_settings_field(
			'remember_choice',
			__( 'Remember the choice', 'admin-bar-position-switcher' ),
			array( $this, 'field_remember_choice' ),
			self::SLUG,
			'abps_main'
		);

		add_settings_field(
			'auto_color',
			__( 'Match the page color', 'admin-bar-position-switcher' ),
			array( $this, 'field_auto_color' ),
			self::SLUG,
			'abps_main'
		);

		add_settings_field(
			'elementor_compat',
			__( 'Elementor compatibility', 'admin-bar-position-switcher' ),
			array( $this, 'field_elementor_compat' ),
			self::SLUG,
			'abps_main'
		);

		add_settings_section(
			'abps_appearance',
			__( 'Appearance & items', 'admin-bar-position-switcher' ),
			array( $this, 'section_appearance_intro' ),
			self::SLUG
		);

		add_settings_field(
			'bar_bg',
			__( 'Toolbar background', 'admin-bar-position-switcher' ),
			array( $this, 'field_bar_bg' ),
			self::SLUG,
			'abps_appearance'
		);

		add_settings_field(
			'hidden_items',
			__( 'Hide toolbar items', 'admin-bar-position-switcher' ),
			array( $this, 'field_hidden_items' ),
			self::SLUG,
			'abps_appearance'
		);
	}

	/**
	 * Intro line for the appearance section.
	 */
	public function section_appearance_intro() {
		echo '<p>' . esc_html__( 'These options change how the toolbar looks on the front end.', 'admin-bar-position-switcher' ) . '</p>';
	}

	/**
	 * Sanitize submitted settings.
	 *
	 * @param mixed $input Raw submitted values.
	 * @return array
	 */
	public function sanitize( $input ) {
		if ( ! is_array( $input ) ) {
			$input = array();
		}

		$out                     = array();
		$out['default_position'] = ( isset( $input['default_position'] ) && 'top' === $input['default_position'] ) ? 'top' : 'bottom';
		$out['show_toggle']      = empty( $input['show_toggle'] ) ? 0 : 1;
		$out['remember_choice']  = empty( $input['remember_choice'] ) ? 0 : 1;
		$out['auto_color']       = empty( $input['auto_color'] ) ? 0 : 1;
		$out['elementor_compat'] = empty( $input['elementor_compat'] ) ? 0 : 1;
		$out['button_label']     = isset( $input['button_label'] ) ? sanitize_text_field( $input['button_label'] ) : '';
		$out['bar_bg_enabled']   = empty( $input['bar_bg_enabled'] ) ? 0 : 1;

		$color               = isset( $input['bar_bg_color'] ) ? sanitize_hex_color( $input['bar_bg_color'] ) : '';
		$out['bar_bg_color'] = $color ? $color : '#1d2327';

		$hidden = array();
		if ( isset( $input['hidden_items'] ) && is_array( $input['hidden_items'] ) ) {
			foreach ( $input['hidden_items'] as $id ) {
				$id = sanitize_key( $id );
				if ( '' !== $id ) {
					$hidden[] = $id;
				}
			}
		}
		$out['hidden_items'] = array_values( array_unique( $hidden ) );

		return $out;
	}

	/**
	 * Render the settings page.
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html_x( 'Admin Bar Position', 'settings page title', 'admin-bar-position-switcher' ); ?></h1>
			<p><?php esc_html_e( 'Choose where the WordPress toolbar sits on the front end. Visitors who are not logged in never see it.', 'admin-bar-position-switcher' ); ?></p>
			<form action="options.php" method="post">
				<?php
				settings_fields( 'abps_group' );
				do_settings_sections( self::SLUG );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Field: default position.
	 */
	public function field_default_position() {
		$value = self::get_options()['default_position'];
		?>
		<select name="<?php echo esc_attr( self::OPTION ); ?>[default_position]">
			<option value="bottom" <?php selected( $value, 'bottom' ); ?>><?php esc_html_e( 'Bottom', 'admin-bar-position-switcher' ); ?></option>
			<option value="top" <?php selected( $value, 'top' ); ?>><?php esc_html_e( 'Top (WordPress default)', 'admin-bar-position-switcher' ); ?></option>
		</select>
		<p class="description"><?php esc_html_e( 'Where the toolbar appears before the visitor flips it.', 'admin-bar-position-switcher' ); ?></p>
		<?php
	}

	/**
	 * Field: show toggle.
	 */
	public function field_show_toggle() {
		$value = self::get_options()['show_toggle'];
		?>
		<label>
			<input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[show_toggle]" value="1" <?php checked( $value, 1 ); ?> />
			<?php esc_html_e( 'Show the floating button to move the toolbar top/bottom.', 'admin-bar-position-switcher' ); ?>
		</label>
		<?php
	}

	/**
	 * Field: button label.
	 */
	public function field_button_label() {
		$value = self::get_options()['button_label'];
		?>
		<input type="text" class="regular-text" name="<?php echo esc_attr( self::OPTION ); ?>[button_label]" value="<?php echo esc_attr( $value ); ?>" placeholder="<?php esc_attr_e( 'Bar', 'admin-bar-position-switcher' ); ?>" />
		<p class="description"><?php esc_html_e( 'Text shown next to the ↕ arrow on the button. Leave empty for the default.', 'admin-bar-position-switcher' ); ?></p>
		<?php
	}

	/**
	 * Field: remember choice.
	 */
	public function field_remember_choice() {
		$value = self::get_options()['remember_choice'];
		?>
		<label>
			<input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[remember_choice]" value="1" <?php checked( $value, 1 ); ?> />
			<?php esc_html_e( 'Remember each browser\'s choice (stored locally in the browser).', 'admin-bar-position-switcher' ); ?>
		</label>
		<?php
	}

	/**
	 * Field: match the page color.
	 */
	public function field_auto_color() {
		$value = self::get_options()['auto_color'];
		?>
		<label>
			<input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[auto_color]" value="1" <?php checked( $value, 1 ); ?> />
			<?php esc_html_e( 'Tint the button with the main color of each page (falls back to the default dark button when no color is found).', 'admin-bar-position-switcher' ); ?>
		</label>
		<?php
	}

	/**
	 * Field: Elementor compatibility.
	 */
	public function field_elementor_compat() {
		$value = self::get_options()['elementor_compat'];
		?>
		<label>
			<input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[elementor_compat]" value="1" <?php checked( $value, 1 ); ?> />
			<?php esc_html_e( 'Adjust Elementor sticky headers so they line up with the toolbar.', 'admin-bar-position-switcher' ); ?>
		</label>
		<?php
	}

	/**
	 * Field: toolbar background color.
	 */
	public function field_bar_bg() {
		$opts    = self::get_options();
		$enabled = $opts['bar_bg_enabled'];
		$color   = $opts['bar_bg_color'];
		?>
		<label>
			<input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[bar_bg_enabled]" value="1" <?php checked( $enabled, 1 ); ?> />
			<?php esc_html_e( 'Colorize the toolbar background', 'admin-bar-position-switcher' ); ?>
		</label>
		&nbsp;
		<input type="color" name="<?php echo esc_attr( self::OPTION ); ?>[bar_bg_color]" value="<?php echo esc_attr( $color ); ?>" />
		<p class="description"><?php esc_html_e( 'The text color adjusts automatically for readability.', 'admin-bar-position-switcher' ); ?></p>
		<?php
	}

	/**
	 * Field: hide individual toolbar items.
	 */
	public function field_hidden_items() {
		$hidden = (array) self::get_options()['hidden_items'];
		$items  = self::get_toolbar_items();

		// Keep any already-hidden item visible in the list so it can be toggled back.
		foreach ( $hidden as $id ) {
			if ( ! isset( $items[ $id ] ) ) {
				$items[ $id ] = $id;
			}
		}
		?>
		<fieldset>
			<p class="description"><?php esc_html_e( 'Tick the items you want to hide from the front-end toolbar.', 'admin-bar-position-switcher' ); ?></p>
			<?php foreach ( $items as $id => $label ) : ?>
				<label style="display:inline-block;min-width:230px;margin:3px 0;">
					<input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[hidden_items][]" value="<?php echo esc_attr( $id ); ?>" <?php checked( in_array( $id, $hidden, true ) ); ?> />
					<?php echo esc_html( $label ); ?>
					<code style="opacity:.55;"><?php echo esc_html( $id ); ?></code>
				</label>
			<?php endforeach; ?>
		</fieldset>
		<?php
	}
}
