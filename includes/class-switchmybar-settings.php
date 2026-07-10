<?php
/**
 * Settings screen (Settings → Admin Bar Position).
 *
 * @package AdminBarPositionSwitcher
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Switchmybar_Settings.
 */
class Switchmybar_Settings {

	/**
	 * Option key holding the settings array.
	 */
	const OPTION = 'switchmybar_options';

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
			'plugin_action_links_' . plugin_basename( SWITCHMYBAR_FILE ),
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
			'auto_hide'        => 0,
			'bar_auto_hide'    => 0,
			'remember_choice'  => 1,
			'auto_color'       => 1,
			'elementor_compat' => 1,
			'button_label'     => '',
			'bar_bg_enabled'   => 0,
			'bar_bg_color'     => '#1d2327',
			'bar_picker'       => 1,
		);
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
			'switchmybar_group',
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize' ),
				'default'           => self::get_defaults(),
			)
		);

		add_settings_section(
			'switchmybar_main',
			'',
			'__return_false',
			self::SLUG
		);

		add_settings_field(
			'default_position',
			__( 'Default position', 'admin-bar-position-switcher' ),
			array( $this, 'field_default_position' ),
			self::SLUG,
			'switchmybar_main'
		);

		add_settings_field(
			'show_toggle',
			__( 'Switch button', 'admin-bar-position-switcher' ),
			array( $this, 'field_show_toggle' ),
			self::SLUG,
			'switchmybar_main'
		);

		add_settings_field(
			'button_label',
			__( 'Button label', 'admin-bar-position-switcher' ),
			array( $this, 'field_button_label' ),
			self::SLUG,
			'switchmybar_main'
		);

		add_settings_field(
			'auto_hide',
			__( 'Auto-hide the button', 'admin-bar-position-switcher' ),
			array( $this, 'field_auto_hide' ),
			self::SLUG,
			'switchmybar_main'
		);


		add_settings_field(
			'bar_auto_hide',
			__( 'Auto-hide the toolbar', 'admin-bar-position-switcher' ),
			array( $this, 'field_bar_auto_hide' ),
			self::SLUG,
			'switchmybar_main'
		);

		add_settings_field(
			'remember_choice',
			__( 'Remember the choice', 'admin-bar-position-switcher' ),
			array( $this, 'field_remember_choice' ),
			self::SLUG,
			'switchmybar_main'
		);

		add_settings_field(
			'auto_color',
			__( 'Match the page color', 'admin-bar-position-switcher' ),
			array( $this, 'field_auto_color' ),
			self::SLUG,
			'switchmybar_main'
		);

		add_settings_field(
			'elementor_compat',
			__( 'Elementor compatibility', 'admin-bar-position-switcher' ),
			array( $this, 'field_elementor_compat' ),
			self::SLUG,
			'switchmybar_main'
		);

		add_settings_section(
			'switchmybar_appearance',
			__( 'Appearance & items', 'admin-bar-position-switcher' ),
			array( $this, 'section_appearance_intro' ),
			self::SLUG
		);

		add_settings_field(
			'bar_bg',
			__( 'Toolbar background', 'admin-bar-position-switcher' ),
			array( $this, 'field_bar_bg' ),
			self::SLUG,
			'switchmybar_appearance'
		);

		add_settings_field(
			'bar_picker',
			__( 'Color picker in the toolbar', 'admin-bar-position-switcher' ),
			array( $this, 'field_bar_picker' ),
			self::SLUG,
			'switchmybar_appearance'
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
				settings_fields( 'switchmybar_group' );
				do_settings_sections( self::SLUG );
				submit_button();
				?>
			</form>
			<?php $this->render_pro_teaser(); ?>
			<?php $this->render_support(); ?>
		</div>
		<?php
	}

	/**
	 * A small SwitchMyBar Pro card, shown only while the add-on is absent.
	 */
	public function render_pro_teaser() {
		if ( class_exists( 'Switchmybar_Pro_Options' ) ) {
			return;
		}
		?>
		<div class="card" style="max-width:520px;margin-top:28px;border-left:4px solid #2271b1;">
			<h2 class="title" style="margin-bottom:6px;">SwitchMyBar Pro</h2>
			<p style="color:#50575e;">
				<?php esc_html_e( 'Color and reorder the back-office menu, dock it away like the macOS Dock, and hide the toolbar items you never use. One payment of $15, yours for life.', 'admin-bar-position-switcher' ); ?>
			</p>
			<p style="margin:0;">
				<a class="button button-primary" href="https://switchmybar.com/" target="_blank" rel="noopener nofollow"><?php esc_html_e( 'Discover SwitchMyBar Pro', 'admin-bar-position-switcher' ); ?></a>
			</p>
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
		<p class="description"><?php esc_html_e( 'The text color adjusts automatically for readability. This is the site-wide default; administrators can override it for themselves with the "Bar" item.', 'admin-bar-position-switcher' ); ?></p>
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
			<?php esc_html_e( 'Add a "Bar" item to the toolbar so each administrator can recolor it with one of the site\'s dominant colors, detected from your logo and theme. The pick is personal: it only applies to their own account.', 'admin-bar-position-switcher' ); ?>
		</label>
		<?php
	}

}
