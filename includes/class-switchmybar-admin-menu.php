<?php
/**
 * Back-office menu behavior: left/right side switcher and macOS-Dock-style
 * auto-hide for the wp-admin sidebar, mirroring what the plugin does for the
 * front-end toolbar. When hidden, the menu keeps a 20px peek at the screen
 * edge so it never has to be guessed. Desktop only (WordPress switches to its
 * own responsive menu below 783px).
 *
 * @package AdminBarPositionSwitcher
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Switchmybar_Admin_Menu.
 */
class Switchmybar_Admin_Menu {

	/**
	 * Resolved plugin options.
	 *
	 * @var array
	 */
	private $options;

	/**
	 * Hook the admin-side behavior.
	 */
	public function __construct() {
		$this->options = Switchmybar_Settings::get_options();
		if ( empty( $this->options['menu_side_toggle'] ) && 'right' !== $this->options['menu_side_default'] && empty( $this->options['menu_auto_hide'] ) ) {
			return;
		}
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_footer', array( $this, 'print_button' ) );
	}

	/**
	 * Register the behavior assets through the enqueue APIs: an early inline
	 * head snippet (side classes before first paint), the behavior styles,
	 * and the footer behavior script with its configuration.
	 */
	public function enqueue_assets() {
		// Early side/auto-hide classes on <html>, in the head.
		wp_register_script( 'switchmybar-admin-head', false, array(), SWITCHMYBAR_VERSION, false );
		wp_enqueue_script( 'switchmybar-admin-head' );
		wp_add_inline_script( 'switchmybar-admin-head', $this->early_side_script() );

		// Behavior styles on a src-less handle.
		wp_register_style( 'switchmybar-admin-menu', false, array(), SWITCHMYBAR_VERSION );
		wp_enqueue_style( 'switchmybar-admin-menu' );
		wp_add_inline_style( 'switchmybar-admin-menu', $this->behavior_css() );

		// Behavior script (footer) + its configuration.
		wp_enqueue_script(
			'switchmybar-admin-menu',
			SWITCHMYBAR_URL . 'assets/js/switchmybar-admin-menu.js',
			array(),
			SWITCHMYBAR_VERSION,
			true
		);
		$config = array(
			'autoHide'       => ! empty( $this->options['menu_auto_hide'] ),
			'revealDistance' => 150,
		);
		wp_add_inline_script(
			'switchmybar-admin-menu',
			'window.SWITCHMYBAR_MENU = ' . wp_json_encode( $config ) . ';',
			'before'
		);
	}

	/**
	 * The inline snippet putting the side / auto-hide classes on <html>
	 * before first paint.
	 *
	 * @return string
	 */
	private function early_side_script() {
		$default = ( 'right' === $this->options['menu_side_default'] ) ? 'right' : 'left';
		$script  = "(function(){try{var d='" . esc_js( $default ) . "';var s=d;";
		$script .= "try{var v=localStorage.getItem('abpsMenuSide');if(v==='left'||v==='right'){s=v;}}catch(e){}";
		$script .= "if(s==='right'){document.documentElement.classList.add('abps-menu-right');}";
		if ( ! empty( $this->options['menu_auto_hide'] ) ) {
			$script .= "document.documentElement.classList.add('abps-menu-autohide');";
		}
		$script .= '}catch(e){}})();';
		return $script;
	}

	/**
	 * The behavior styles (desktop only).
	 *
	 * @return string
	 */
	private function behavior_css() {
		$css = '@media screen and (min-width:783px){';

		// Mirrored menu (right side). The wrap goes fixed (out of flow) so the
		// content column never wraps under it; long menus scroll internally.
		$css .= 'html.abps-menu-right #adminmenuwrap{position:fixed;float:none;left:auto;right:0;top:32px;bottom:0;overflow-y:auto;overflow-x:hidden;margin-top:0;}';
		$css .= 'html.abps-menu-right #adminmenuback{left:auto;right:0;}';
		$css .= 'html.abps-menu-right #wpcontent,html.abps-menu-right #wpfooter{margin-left:20px !important;margin-right:160px;}';
		$css .= 'html.abps-menu-right body.folded #wpcontent,html.abps-menu-right body.folded #wpfooter{margin-right:36px;}';
		$css .= 'html.abps-menu-right #adminmenu .wp-submenu{left:auto;right:160px;}';
		// The CURRENT item's submenu renders inline (position:relative) — core
		// resets left/right to auto there; our mirror must do the same or the
		// inline submenu gets shoved 160px sideways.
		$css .= 'html.abps-menu-right body:not(.folded) #adminmenu .wp-has-current-submenu .wp-submenu{left:auto;right:auto;}';
		$css .= 'html.abps-menu-right body.folded #adminmenu .wp-submenu{left:auto;right:36px;}';
		$css .= 'html.abps-menu-right #screen-meta-links{margin-right:20px;}';

		// Auto-hide (Dock style): the menu overlays the page, the space is
		// released, and a 20px peek stays at the edge (50% opacity).
		$css .= 'html.abps-menu-autohide #adminmenuwrap,html.abps-menu-autohide #adminmenuback{transition:transform .32s cubic-bezier(.4,0,.2,1),opacity .25s ease;}';
		$css .= 'html.abps-menu-autohide #adminmenuwrap{z-index:9990;}';
		$css .= 'html.abps-menu-autohide #wpcontent,html.abps-menu-autohide #wpfooter{transition:margin .32s cubic-bezier(.4,0,.2,1);}';
		// The page only reclaims the width once the menu has actually tucked
		// away (docked class, set at first hide and kept): reappearing on
		// proximity overlays the wide content instead of squeezing it back.
		$css .= 'html.abps-menu-docked #wpcontent,html.abps-menu-docked #wpfooter{margin-left:20px !important;}';
		$css .= 'html.abps-menu-docked.abps-menu-right #wpcontent,html.abps-menu-docked.abps-menu-right #wpfooter{margin-left:20px !important;margin-right:20px;}';
		$css .= 'html.abps-menu-autohide.abps-menu-hidden #adminmenuwrap,html.abps-menu-autohide.abps-menu-hidden #adminmenuback{transform:translateX(calc(-100% + 20px));opacity:.5;}';
		$css .= 'html.abps-menu-autohide.abps-menu-hidden.abps-menu-right #adminmenuwrap,html.abps-menu-autohide.abps-menu-hidden.abps-menu-right #adminmenuback{transform:translateX(calc(100% - 20px));}';

		// The side-switch button: a vertical tab glued to the menu's outer
		// edge; it follows the menu when it docks (down to the 20px peek).
		$css .= '#abps-menu-switch{position:fixed;left:160px;top:50%;transform:translateY(-50%);z-index:100000;margin:0;background:#1d2327;color:#fff;border:0;border-radius:0 4px 4px 0;padding:11px 7px;font:600 12px/1 -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Arial,sans-serif;letter-spacing:.02em;cursor:pointer;opacity:.82;box-shadow:2px 0 8px rgba(0,0,0,.22);writing-mode:vertical-rl;transition:opacity .25s ease,left .32s cubic-bezier(.4,0,.2,1),right .32s cubic-bezier(.4,0,.2,1);}';
		$css .= '#abps-menu-switch .abps-menu-switch-arrow{text-orientation:upright;margin-bottom:5px;}';
		$css .= '#abps-menu-switch:hover,#abps-menu-switch:focus{opacity:1;}';
		$css .= 'body.folded #abps-menu-switch{left:36px;}';
		$css .= 'html.abps-menu-right #abps-menu-switch{left:auto;right:160px;border-radius:4px 0 0 4px;box-shadow:-2px 0 8px rgba(0,0,0,.22);}';
		$css .= 'html.abps-menu-right body.folded #abps-menu-switch{left:auto;right:36px;}';
		$css .= 'html.abps-menu-autohide.abps-menu-hidden #abps-menu-switch{left:20px;opacity:.5;}';
		$css .= 'html.abps-menu-autohide.abps-menu-hidden.abps-menu-right #abps-menu-switch{left:auto;right:20px;}';
		$css .= 'html.abps-menu-autohide.abps-menu-hidden #abps-menu-switch:hover,html.abps-menu-autohide.abps-menu-hidden #abps-menu-switch:focus{opacity:1;}';

		$css .= '}';
		$css .= '@media screen and (max-width:782px){#abps-menu-switch{display:none;}}';
		$css .= '@media (prefers-reduced-motion:reduce){html.abps-menu-autohide #adminmenuwrap,html.abps-menu-autohide #adminmenuback,#abps-menu-switch{transition:none;}}';

		return $css;
	}

	/**
	 * The ↔ button (plain HTML in the footer; its behavior lives in the
	 * enqueued script).
	 */
	public function print_button() {
		if ( empty( $this->options['menu_side_toggle'] ) ) {
			return;
		}
		printf(
			'<button id="abps-menu-switch" type="button" title="%1$s"><span class="abps-menu-switch-arrow" aria-hidden="true">&#8596;</span>%2$s</button>',
			esc_attr__( 'Move the menu to the left or right', 'admin-bar-position-switcher' ),
			esc_html__( 'Menu', 'admin-bar-position-switcher' )
		);
	}
}
