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
		add_action( 'admin_head', array( $this, 'print_menu_styling' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_sortable' ) );
		add_filter( 'custom_menu_order', array( $this, 'maybe_custom_order' ) );
		add_filter( 'menu_order', array( $this, 'apply_menu_order' ), 99 );
		add_filter(
			'plugin_action_links_' . plugin_basename( ABPS_FILE ),
			array( $this, 'action_links' )
		);
	}

	/**
	 * Load the sortable helper on the plugin's settings page only.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_sortable( $hook ) {
		if ( 'toplevel_page_' . self::SLUG !== $hook ) {
			return;
		}
		wp_enqueue_script( 'jquery-ui-sortable' );
		wp_add_inline_script(
			'jquery-ui-sortable',
			'jQuery(function($){$(".abps-sortable").sortable({axis:"y",cursor:"grabbing",containment:"parent"});});'
		);
	}

	/**
	 * Enable WordPress's custom menu ordering when our order is active.
	 *
	 * @param bool $enabled Current value.
	 * @return bool
	 */
	public function maybe_custom_order( $enabled ) {
		$opts = self::get_options();
		return $enabled || ( ! empty( $opts['menu_order_on'] ) && ! empty( $opts['menu_order_custom'] ) );
	}

	/**
	 * Apply the saved back-office menu order: dragged items first (in their
	 * saved order), everything else keeps its current relative order.
	 *
	 * @param array $order Menu slugs in their current order.
	 * @return array
	 */
	public function apply_menu_order( $order ) {
		if ( ! is_array( $order ) ) {
			return $order;
		}
		$opts = self::get_options();
		if ( empty( $opts['menu_order_on'] ) || empty( $opts['menu_order_custom'] ) ) {
			return $order;
		}
		$saved = array_values( array_intersect( (array) $opts['menu_order_custom'], $order ) );
		$rest  = array_values( array_diff( $order, $saved ) );
		return array_merge( $saved, $rest );
	}

	/**
	 * Colorize the left admin menu and add spacers, per the settings.
	 *
	 * Printed in admin_head so it wins over the admin color scheme.
	 */
	public function print_menu_styling() {
		$opts    = self::get_options();
		$colors  = (array) $opts['menu_colors'];
		$spacers = (array) $opts['menu_spacers'];
		$dim     = ! empty( $opts['menu_dim'] );
		if ( empty( $colors ) && empty( $spacers ) && ! $dim ) {
			return;
		}

		$css = '';
		foreach ( $colors as $id => $hex ) {
			$id  = sanitize_key( $id );
			$hex = sanitize_hex_color( $hex );
			if ( ! $id || ! $hex ) {
				continue;
			}
			$fg   = ABPS_Plugin::readable_text_color( $hex );
			$li   = '#adminmenu li#' . $id;
			$css .= $li . ' > a.menu-top{background:' . $hex . ' !important;color:' . $fg . ' !important;}';
			$css .= $li . ' div.wp-menu-image:before{color:' . $fg . ' !important;}';
			$css .= $li . ' > a.menu-top:hover,' . $li . '.opensub > a.menu-top,' . $li . ' > a.menu-top:focus{filter:brightness(1.12);}';
			$css .= $li . '.wp-has-current-submenu > a.wp-has-current-submenu,' . $li . '.current > a.current{background:' . $hex . ' !important;color:' . $fg . ' !important;}';
		}
		foreach ( $spacers as $id ) {
			$id = sanitize_key( $id );
			if ( $id ) {
				$css .= '#adminmenu li#' . $id . '{margin-top:16px;}';
			}
		}

		// Dim every item without a custom color; hover/current restore it.
		if ( $dim ) {
			$not = '';
			foreach ( array_keys( $colors ) as $id ) {
				$id = sanitize_key( $id );
				if ( $id ) {
					$not .= ':not(#' . $id . ')';
				}
			}
			$base = '#adminmenu li.menu-top' . $not;
			$css .= $base . ' > a .wp-menu-name,' . $base . ' > a .wp-menu-image{opacity:.5;filter:grayscale(.6);transition:opacity .15s ease,filter .15s ease;}';
			$css .= '#adminmenu li.menu-top:hover > a .wp-menu-name,#adminmenu li.menu-top:hover > a .wp-menu-image,'
				. '#adminmenu li.menu-top.wp-has-current-submenu > a .wp-menu-name,#adminmenu li.menu-top.wp-has-current-submenu > a .wp-menu-image,'
				. '#adminmenu li.menu-top.current > a .wp-menu-name,#adminmenu li.menu-top.current > a .wp-menu-image{opacity:1 !important;filter:none !important;}';
		}

		if ( '' !== $css ) {
			echo '<style id="abps-admin-menu">' . $css . '</style>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from sanitized keys and hex colors only.
		}
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
			'auto_hide'        => 0,
			'bar_auto_hide'    => 0,
			'remember_choice'  => 1,
			'auto_color'       => 1,
			'elementor_compat' => 1,
			'button_label'     => '',
			'bar_bg_enabled'   => 0,
			'bar_bg_color'     => '#1d2327',
			'bar_picker'       => 1,
			'hidden_items'     => array(),
			'menu_colors'      => array(),
			'menu_spacers'     => array(),
			'menu_dim'         => 0,
			'menu_order_on'    => 0,
			'menu_order_custom' => array(),
			'bar_order_on'     => 0,
			'bar_order_custom' => array(),
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
	 * Add the settings page as a top-level entry in the left admin menu.
	 */
	public function add_menu() {
		add_menu_page(
			__( 'Admin Bar Position', 'admin-bar-position-switcher' ),
			__( 'Admin Bar', 'admin-bar-position-switcher' ),
			'manage_options',
			self::SLUG,
			array( $this, 'render_page' ),
			'dashicons-sort'
		);
	}

	/**
	 * Add a "Settings" link on the Plugins screen.
	 *
	 * @param array $links Existing action links.
	 * @return array
	 */
	public function action_links( $links ) {
		$url  = admin_url( 'admin.php?page=' . self::SLUG );
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
			'auto_hide',
			__( 'Auto-hide the button', 'admin-bar-position-switcher' ),
			array( $this, 'field_auto_hide' ),
			self::SLUG,
			'abps_main'
		);

		add_settings_field(
			'bar_auto_hide',
			__( 'Auto-hide the toolbar', 'admin-bar-position-switcher' ),
			array( $this, 'field_bar_auto_hide' ),
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
			'bar_picker',
			__( 'Color picker in the toolbar', 'admin-bar-position-switcher' ),
			array( $this, 'field_bar_picker' ),
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

		add_settings_section(
			'abps_admin_menu',
			__( 'Back-office menu', 'admin-bar-position-switcher' ),
			array( $this, 'section_admin_menu_intro' ),
			self::SLUG
		);

		add_settings_field(
			'menu_styling',
			__( 'Menu items', 'admin-bar-position-switcher' ),
			array( $this, 'field_menu_styling' ),
			self::SLUG,
			'abps_admin_menu'
		);

		add_settings_field(
			'menu_dim',
			__( 'Dim the other items', 'admin-bar-position-switcher' ),
			array( $this, 'field_menu_dim' ),
			self::SLUG,
			'abps_admin_menu'
		);

		add_settings_field(
			'menu_order',
			__( 'Menu order', 'admin-bar-position-switcher' ),
			array( $this, 'field_menu_order' ),
			self::SLUG,
			'abps_admin_menu'
		);

		add_settings_field(
			'bar_order',
			__( 'Toolbar order', 'admin-bar-position-switcher' ),
			array( $this, 'field_bar_order' ),
			self::SLUG,
			'abps_appearance'
		);
	}

	/**
	 * The left admin menu's top-level entries with their order slugs.
	 *
	 * @return array[] Each entry: array( 'id' => li id, 'slug' => order slug, 'label' => label ).
	 */
	public static function get_admin_menu_entries() {
		$entries = array();

		global $menu;
		if ( is_array( $menu ) && ! empty( $menu ) ) {
			foreach ( $menu as $entry ) {
				$classes = isset( $entry[4] ) ? (string) $entry[4] : '';
				$slug    = isset( $entry[2] ) ? (string) $entry[2] : '';
				$id      = isset( $entry[5] ) ? (string) $entry[5] : '';
				if ( '' === $slug || false !== strpos( $classes, 'wp-menu-separator' ) ) {
					continue;
				}
				$label = isset( $entry[0] ) ? trim( wp_strip_all_tags( preg_replace( '/<span[^>]*>.*?<\/span>/s', '', (string) $entry[0] ) ) ) : '';
				$entries[] = array(
					'id'    => $id,
					'slug'  => $slug,
					'label' => '' !== $label ? $label : $slug,
				);
			}
		}

		if ( empty( $entries ) ) {
			foreach ( array(
				'index.php'           => 'menu-dashboard',
				'edit.php'            => 'menu-posts',
				'upload.php'          => 'menu-media',
				'edit.php?post_type=page' => 'menu-pages',
				'edit-comments.php'   => 'menu-comments',
				'themes.php'          => 'menu-appearance',
				'plugins.php'         => 'menu-plugins',
				'users.php'           => 'menu-users',
				'tools.php'           => 'menu-tools',
				'options-general.php' => 'menu-settings',
			) as $slug => $id ) {
				$entries[] = array( 'id' => $id, 'slug' => $slug, 'label' => $slug );
			}
		}

		return $entries;
	}

	/**
	 * Render a sortable list (shared by the two order fields).
	 *
	 * @param string $field   Option key holding the order.
	 * @param string $on_key  Option key of the enable checkbox.
	 * @param array  $items   value => label, in default order.
	 * @param array  $saved   Saved order (values).
	 * @param int    $enabled Whether the order is applied.
	 */
	protected function render_sortable( $field, $on_key, array $items, array $saved, $enabled ) {
		// Saved order first, then any new items after.
		$ordered = array();
		foreach ( $saved as $value ) {
			if ( isset( $items[ $value ] ) ) {
				$ordered[ $value ] = $items[ $value ];
				unset( $items[ $value ] );
			}
		}
		$ordered += $items;
		?>
		<label style="display:block;margin-bottom:8px;">
			<input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[<?php echo esc_attr( $on_key ); ?>]" value="1" <?php checked( $enabled, 1 ); ?> />
			<?php esc_html_e( 'Apply this custom order', 'admin-bar-position-switcher' ); ?>
		</label>
		<ul class="abps-sortable" style="max-width:380px;margin:0;">
			<?php foreach ( $ordered as $value => $label ) : ?>
				<li style="display:flex;align-items:center;gap:8px;background:#fff;border:1px solid #c3c4c7;border-radius:4px;padding:6px 10px;margin:0 0 4px;cursor:grab;">
					<span class="dashicons dashicons-menu" aria-hidden="true" style="color:#8c8f94;"></span>
					<?php echo esc_html( $label ); ?>
					<input type="hidden" name="<?php echo esc_attr( self::OPTION ); ?>[<?php echo esc_attr( $field ); ?>][]" value="<?php echo esc_attr( $value ); ?>" />
				</li>
			<?php endforeach; ?>
		</ul>
		<p class="description"><?php esc_html_e( 'Drag the items into the order you want.', 'admin-bar-position-switcher' ); ?></p>
		<?php
	}

	/**
	 * Field: drag-and-drop order of the back-office menu.
	 */
	public function field_menu_order() {
		$opts  = self::get_options();
		$items = array();
		foreach ( self::get_admin_menu_entries() as $entry ) {
			$items[ $entry['slug'] ] = $entry['label'];
		}
		$this->render_sortable( 'menu_order_custom', 'menu_order_on', $items, (array) $opts['menu_order_custom'], (int) $opts['menu_order_on'] );
	}

	/**
	 * Field: drag-and-drop order of the front-end toolbar.
	 */
	public function field_bar_order() {
		$opts = self::get_options();
		$this->render_sortable( 'bar_order_custom', 'bar_order_on', self::get_toolbar_items(), (array) $opts['bar_order_custom'], (int) $opts['bar_order_on'] );
	}

	/**
	 * Field: dim the menu items that have no custom color.
	 */
	public function field_menu_dim() {
		$value = self::get_options()['menu_dim'];
		?>
		<label>
			<input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[menu_dim]" value="1" <?php checked( $value, 1 ); ?> />
			<?php esc_html_e( 'Fade the menu items that have no custom color; they light up again on hover or when active.', 'admin-bar-position-switcher' ); ?>
		</label>
		<?php
	}

	/**
	 * Intro line for the back-office menu section.
	 */
	public function section_admin_menu_intro() {
		echo '<p>' . esc_html__( 'Give the left admin menu your own colors: pick a background per item (the text stays readable automatically) and add spacers between groups.', 'admin-bar-position-switcher' ) . '</p>';
	}

	/**
	 * The left admin menu's top-level items, as li-id => label.
	 *
	 * Enumerated from the live $menu global when available (so plugin-added
	 * entries show up too), with a curated core fallback.
	 *
	 * @return array
	 */
	public static function get_admin_menu_items() {
		$items = array();

		global $menu;
		if ( is_array( $menu ) && ! empty( $menu ) ) {
			foreach ( $menu as $entry ) {
				$classes = isset( $entry[4] ) ? (string) $entry[4] : '';
				$id      = isset( $entry[5] ) ? (string) $entry[5] : '';
				if ( '' === $id || false !== strpos( $classes, 'wp-menu-separator' ) ) {
					continue;
				}
				$label = isset( $entry[0] ) ? trim( wp_strip_all_tags( preg_replace( '/<span[^>]*>.*?<\/span>/s', '', (string) $entry[0] ) ) ) : '';
				if ( '' === $label ) {
					$label = $id;
				}
				$items[ $id ] = $label;
			}
		}

		if ( empty( $items ) ) {
			$items = array(
				'menu-dashboard'  => __( 'Dashboard' ),   // phpcs:ignore WordPress.WP.I18n.MissingArgDomain -- core string on purpose.
				'menu-posts'      => __( 'Posts' ),       // phpcs:ignore WordPress.WP.I18n.MissingArgDomain
				'menu-media'      => __( 'Media' ),       // phpcs:ignore WordPress.WP.I18n.MissingArgDomain
				'menu-pages'      => __( 'Pages' ),       // phpcs:ignore WordPress.WP.I18n.MissingArgDomain
				'menu-comments'   => __( 'Comments' ),    // phpcs:ignore WordPress.WP.I18n.MissingArgDomain
				'menu-appearance' => __( 'Appearance' ),  // phpcs:ignore WordPress.WP.I18n.MissingArgDomain
				'menu-plugins'    => __( 'Plugins' ),     // phpcs:ignore WordPress.WP.I18n.MissingArgDomain
				'menu-users'      => __( 'Users' ),       // phpcs:ignore WordPress.WP.I18n.MissingArgDomain
				'menu-tools'      => __( 'Tools' ),       // phpcs:ignore WordPress.WP.I18n.MissingArgDomain
				'menu-settings'   => __( 'Settings' ),    // phpcs:ignore WordPress.WP.I18n.MissingArgDomain
			);
		}

		return $items;
	}

	/**
	 * Field: per-item color + spacer for the left admin menu.
	 */
	public function field_menu_styling() {
		$opts    = self::get_options();
		$colors  = (array) $opts['menu_colors'];
		$spacers = (array) $opts['menu_spacers'];
		$items   = self::get_admin_menu_items();

		// Keep already-styled items visible even if their plugin is gone.
		foreach ( array_merge( array_keys( $colors ), $spacers ) as $id ) {
			if ( ! isset( $items[ $id ] ) ) {
				$items[ $id ] = $id;
			}
		}
		?>
		<fieldset>
			<?php foreach ( $items as $id => $label ) : ?>
				<?php $color = isset( $colors[ $id ] ) ? $colors[ $id ] : ''; ?>
				<div style="display:flex;align-items:center;gap:14px;margin:4px 0;">
					<input type="color" name="<?php echo esc_attr( self::OPTION ); ?>[menu_colors][<?php echo esc_attr( $id ); ?>]" value="<?php echo esc_attr( $color ? $color : '#1d2327' ); ?>" />
					<label style="min-width:110px;">
						<input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[menu_colors_on][<?php echo esc_attr( $id ); ?>]" value="1" <?php checked( '' !== $color ); ?> />
						<?php esc_html_e( 'Color', 'admin-bar-position-switcher' ); ?>
					</label>
					<label style="min-width:130px;">
						<input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[menu_spacers][]" value="<?php echo esc_attr( $id ); ?>" <?php checked( in_array( $id, $spacers, true ) ); ?> />
						<?php esc_html_e( 'Spacer before', 'admin-bar-position-switcher' ); ?>
					</label>
					<span><?php echo esc_html( $label ); ?> <code style="opacity:.55;"><?php echo esc_html( $id ); ?></code></span>
				</div>
			<?php endforeach; ?>
		</fieldset>
		<?php
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
		$out['auto_hide']        = empty( $input['auto_hide'] ) ? 0 : 1;
		$out['bar_auto_hide']    = empty( $input['bar_auto_hide'] ) ? 0 : 1;
		$out['remember_choice']  = empty( $input['remember_choice'] ) ? 0 : 1;
		$out['auto_color']       = empty( $input['auto_color'] ) ? 0 : 1;
		$out['elementor_compat'] = empty( $input['elementor_compat'] ) ? 0 : 1;
		$out['button_label']     = isset( $input['button_label'] ) ? sanitize_text_field( $input['button_label'] ) : '';
		$out['bar_bg_enabled']   = empty( $input['bar_bg_enabled'] ) ? 0 : 1;
		$out['bar_picker']       = empty( $input['bar_picker'] ) ? 0 : 1;

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

		// Back-office menu: keep a color only for items whose "Color" box is ticked.
		$colors = array();
		if ( isset( $input['menu_colors'] ) && is_array( $input['menu_colors'] ) ) {
			$enabled = ( isset( $input['menu_colors_on'] ) && is_array( $input['menu_colors_on'] ) ) ? $input['menu_colors_on'] : array();
			foreach ( $input['menu_colors'] as $id => $hex ) {
				$id = sanitize_key( $id );
				if ( '' === $id || empty( $enabled[ $id ] ) ) {
					continue;
				}
				$hex = sanitize_hex_color( $hex );
				if ( $hex ) {
					$colors[ $id ] = $hex;
				}
			}
		}
		$out['menu_colors'] = $colors;

		$spacers = array();
		if ( isset( $input['menu_spacers'] ) && is_array( $input['menu_spacers'] ) ) {
			foreach ( $input['menu_spacers'] as $id ) {
				$id = sanitize_key( $id );
				if ( '' !== $id ) {
					$spacers[] = $id;
				}
			}
		}
		$out['menu_spacers'] = array_values( array_unique( $spacers ) );
		$out['menu_dim']     = empty( $input['menu_dim'] ) ? 0 : 1;

		// Custom orders (menu slugs may contain "?" and "=", so no sanitize_key).
		$out['menu_order_on'] = empty( $input['menu_order_on'] ) ? 0 : 1;
		$morder = array();
		if ( isset( $input['menu_order_custom'] ) && is_array( $input['menu_order_custom'] ) ) {
			foreach ( $input['menu_order_custom'] as $slug ) {
				$slug = sanitize_text_field( (string) $slug );
				if ( '' !== $slug ) {
					$morder[] = $slug;
				}
			}
		}
		$out['menu_order_custom'] = array_values( array_unique( $morder ) );

		$out['bar_order_on'] = empty( $input['bar_order_on'] ) ? 0 : 1;
		$border = array();
		if ( isset( $input['bar_order_custom'] ) && is_array( $input['bar_order_custom'] ) ) {
			foreach ( $input['bar_order_custom'] as $id ) {
				$id = sanitize_key( $id );
				if ( '' !== $id ) {
					$border[] = $id;
				}
			}
		}
		$out['bar_order_custom'] = array_values( array_unique( $border ) );

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
			<?php settings_errors(); ?>
			<p><?php esc_html_e( 'Choose where the WordPress toolbar sits on the front end. Visitors who are not logged in never see it.', 'admin-bar-position-switcher' ); ?></p>
			<form action="options.php" method="post">
				<?php
				settings_fields( 'abps_group' );
				do_settings_sections( self::SLUG );
				submit_button();
				?>
			</form>
			<?php $this->render_support(); ?>
		</div>
		<?php
	}

	/**
	 * A small "Support the author" card below the settings form.
	 *
	 * Informational only: the author's links and a donation button. There are
	 * no form fields, so it lives outside the settings form.
	 */
	public function render_support() {
		$links = array(
			'Facebook'     => 'https://www.facebook.com/free.stephane',
			'Instagram'    => 'https://www.instagram.com/free.stephane/',
			'TikTok'       => 'https://www.tiktok.com/@freestephane',
			'GitHub'       => 'https://github.com/stephane-schmidt',
			'alveo.design' => 'https://alveo.design',
		);
		$donate = 'https://revolut.me/stphanjt11';
		?>
		<div class="card" style="max-width:520px;margin-top:28px;">
			<h2 class="title" style="margin-bottom:6px;"><?php esc_html_e( 'Support the author', 'admin-bar-position-switcher' ); ?></h2>
			<p style="color:#50575e;">
				<?php esc_html_e( 'This little plugin is free and open source. If it earned a spot on your screen, you can support its development — or simply say hello.', 'admin-bar-position-switcher' ); ?>
			</p>
			<p style="margin:0 0 10px;">
				<strong>Stéphane Schmidt</strong> &middot; <?php esc_html_e( 'Available for freelance work', 'admin-bar-position-switcher' ); ?>
			</p>
			<p style="display:flex;flex-wrap:wrap;gap:6px 14px;margin:0 0 16px;">
				<?php foreach ( $links as $label => $url ) : ?>
					<a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener nofollow"><?php echo esc_html( $label ); ?></a>
				<?php endforeach; ?>
			</p>
			<p style="margin:0;">
				<a class="button button-primary" href="<?php echo esc_url( $donate ); ?>" target="_blank" rel="noopener nofollow"><?php esc_html_e( 'Buy me a coffee', 'admin-bar-position-switcher' ); ?></a>
			</p>
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
	 * Field: auto-hide the button when idle.
	 */
	public function field_auto_hide() {
		$value = self::get_options()['auto_hide'];
		?>
		<label>
			<input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[auto_hide]" value="1" <?php checked( $value, 1 ); ?> />
			<?php esc_html_e( 'Let the button drift away after a few seconds without use, and bring it back when the pointer moves over the toolbar.', 'admin-bar-position-switcher' ); ?>
		</label>
		<p class="description"><?php esc_html_e( 'Off by default: the button stays visible.', 'admin-bar-position-switcher' ); ?></p>
		<?php
	}

	/**
	 * Field: auto-hide the whole toolbar (macOS Dock style).
	 */
	public function field_bar_auto_hide() {
		$value = self::get_options()['bar_auto_hide'];
		?>
		<label>
			<input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[bar_auto_hide]" value="1" <?php checked( $value, 1 ); ?> />
			<?php esc_html_e( 'Hide the toolbar off-screen like the macOS Dock: it glides back when the pointer comes within 150 pixels of its edge, or when it receives keyboard focus.', 'admin-bar-position-switcher' ); ?>
		</label>
		<p class="description"><?php esc_html_e( 'Off by default: the toolbar stays visible at all times.', 'admin-bar-position-switcher' ); ?></p>
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
	 * Field: color picker in the toolbar.
	 */
	public function field_bar_picker() {
		$value = self::get_options()['bar_picker'];
		?>
		<label>
			<input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[bar_picker]" value="1" <?php checked( $value, 1 ); ?> />
			<?php esc_html_e( 'Add a "Bar" item to the toolbar so administrators can recolor it with one of the site\'s dominant colors, detected from your logo and theme.', 'admin-bar-position-switcher' ); ?>
		</label>
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
