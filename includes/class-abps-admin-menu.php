<?php
/**
 * Back-office menu behavior: left/right side switcher and macOS-Dock-style
 * auto-hide for the wp-admin sidebar, mirroring what the plugin does for the
 * front-end toolbar. Desktop only (WordPress switches to its own responsive
 * menu below 783px).
 *
 * @package AdminBarPositionSwitcher
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class ABPS_Admin_Menu.
 */
class ABPS_Admin_Menu {

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
		$this->options = ABPS_Settings::get_options();
		if ( empty( $this->options['menu_side_toggle'] ) && 'right' !== $this->options['menu_side_default'] && empty( $this->options['menu_auto_hide'] ) ) {
			return;
		}
		add_action( 'admin_head', array( $this, 'print_early_script' ), 0 );
		add_action( 'admin_head', array( $this, 'print_css' ), 5 );
		add_action( 'admin_footer', array( $this, 'print_button_and_script' ) );
	}

	/**
	 * Put the side / auto-hide classes on <html> before first paint.
	 */
	public function print_early_script() {
		$default = ( 'right' === $this->options['menu_side_default'] ) ? 'right' : 'left';
		$script  = "(function(){try{var d='" . esc_js( $default ) . "';var s=d;";
		$script .= "try{var v=localStorage.getItem('abpsMenuSide');if(v==='left'||v==='right'){s=v;}}catch(e){}";
		$script .= "if(s==='right'){document.documentElement.classList.add('abps-menu-right');}";
		if ( ! empty( $this->options['menu_auto_hide'] ) ) {
			$script .= "document.documentElement.classList.add('abps-menu-autohide');";
		}
		$script .= '}catch(e){}})();';
		echo '<script>' . $script . '</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- values escaped with esc_js above.
	}

	/**
	 * The behavior styles (desktop only).
	 */
	public function print_css() {
		$css = '@media screen and (min-width:783px){';

		// Mirrored menu (right side). The wrap goes fixed (out of flow) so the
		// content column never wraps under it; long menus scroll internally.
		$css .= 'html.abps-menu-right #adminmenuwrap{position:fixed;float:none;left:auto;right:0;top:32px;bottom:0;overflow-y:auto;overflow-x:hidden;margin-top:0;}';
		$css .= 'html.abps-menu-right #adminmenuback{left:auto;right:0;}';
		$css .= 'html.abps-menu-right #wpcontent,html.abps-menu-right #wpfooter{margin-left:20px !important;margin-right:160px;}';
		$css .= 'html.abps-menu-right body.folded #wpcontent,html.abps-menu-right body.folded #wpfooter{margin-right:36px;}';
		$css .= 'html.abps-menu-right #adminmenu .wp-submenu{left:auto;right:160px;}';
		$css .= 'html.abps-menu-right body.folded #adminmenu .wp-submenu{left:auto;right:36px;}';
		$css .= 'html.abps-menu-right #screen-meta-links{margin-right:20px;}';

		// Auto-hide (Dock style): the menu overlays the page, space is released.
		$css .= 'html.abps-menu-autohide #adminmenuwrap,html.abps-menu-autohide #adminmenuback{transition:transform .32s cubic-bezier(.4,0,.2,1);will-change:transform;}';
		$css .= 'html.abps-menu-autohide #adminmenuwrap{z-index:9990;}';
		$css .= 'html.abps-menu-autohide #wpcontent,html.abps-menu-autohide #wpfooter{margin-left:20px !important;}';
		$css .= 'html.abps-menu-autohide.abps-menu-right #wpcontent,html.abps-menu-autohide.abps-menu-right #wpfooter{margin-left:20px !important;margin-right:20px;}';
		$css .= 'html.abps-menu-autohide.abps-menu-hidden #adminmenuwrap,html.abps-menu-autohide.abps-menu-hidden #adminmenuback{transform:translateX(-110%);}';
		$css .= 'html.abps-menu-autohide.abps-menu-hidden.abps-menu-right #adminmenuwrap,html.abps-menu-autohide.abps-menu-hidden.abps-menu-right #adminmenuback{transform:translateX(110%);}';

		// The floating side-switch button, twin of the front #abps-switch.
		$css .= '#abps-menu-switch{position:fixed;left:12px;bottom:12px;z-index:100000;margin:0;background:#1d2327;color:#fff;border:0;border-radius:4px;padding:7px 11px;font:600 12px/1 -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Arial,sans-serif;letter-spacing:.02em;cursor:pointer;opacity:.82;box-shadow:0 2px 8px rgba(0,0,0,.28);transition:opacity .25s ease,left .32s cubic-bezier(.4,0,.2,1),right .32s cubic-bezier(.4,0,.2,1);}';
		$css .= '#abps-menu-switch:hover,#abps-menu-switch:focus{opacity:1;}';
		$css .= 'html.abps-menu-right #abps-menu-switch{left:auto;right:12px;}';
		$css .= 'html.abps-menu-autohide.abps-menu-hidden #abps-menu-switch{opacity:.5;}';
		$css .= 'html.abps-menu-autohide.abps-menu-hidden #abps-menu-switch:hover,html.abps-menu-autohide.abps-menu-hidden #abps-menu-switch:focus{opacity:1;}';

		$css .= '}';
		$css .= '@media screen and (max-width:782px){#abps-menu-switch{display:none;}}';
		$css .= '@media (prefers-reduced-motion:reduce){html.abps-menu-autohide #adminmenuwrap,html.abps-menu-autohide #adminmenuback,#abps-menu-switch{transition:none;}}';

		echo '<style id="abps-admin-menu-behavior">' . $css . '</style>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static CSS built above.
	}

	/**
	 * The ↔ button and its behavior script.
	 */
	public function print_button_and_script() {
		if ( ! empty( $this->options['menu_side_toggle'] ) ) {
			printf(
				'<button id="abps-menu-switch" type="button" title="%1$s">&#8596; %2$s</button>',
				esc_attr__( 'Move the menu to the left or right', 'admin-bar-position-switcher' ),
				esc_html__( 'Menu', 'admin-bar-position-switcher' )
			);
		}

		$auto = ! empty( $this->options['menu_auto_hide'] ) ? 'true' : 'false';
		?>
		<script>
		( function () {
			'use strict';
			var root = document.documentElement;

			var btn = document.getElementById( 'abps-menu-switch' );
			if ( btn ) {
				btn.addEventListener( 'click', function () {
					var right = ! root.classList.contains( 'abps-menu-right' );
					root.classList.toggle( 'abps-menu-right', right );
					try { localStorage.setItem( 'abpsMenuSide', right ? 'right' : 'left' ); } catch ( e ) {}
				} );
			}

			if ( ! <?php echo $auto; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- literal true/false. ?> ) {
				return;
			}
			var HIDDEN = 'abps-menu-hidden';
			var REVEAL = 150;
			var menu = document.getElementById( 'adminmenumain' );
			if ( ! menu ) {
				return;
			}
			var pinned = false;
			var timer = null;

			function hide() { if ( ! pinned ) { root.classList.add( HIDDEN ); } }
			function show() { root.classList.remove( HIDDEN ); }
			function armHide( delay ) {
				if ( timer ) { window.clearTimeout( timer ); }
				timer = window.setTimeout( hide, delay );
			}
			function nearEdge( x ) {
				if ( root.classList.contains( 'abps-menu-right' ) ) {
					return ( window.innerWidth - x ) <= REVEAL;
				}
				return x <= REVEAL;
			}

			var queued = false, mx = 0;
			document.addEventListener( 'mousemove', function ( e ) {
				mx = e.clientX;
				if ( queued ) { return; }
				queued = true;
				window.requestAnimationFrame( function () {
					queued = false;
					if ( nearEdge( mx ) ) { show(); } else if ( ! pinned ) { armHide( 280 ); }
				} );
			}, { passive: true } );
			document.addEventListener( 'touchstart', function ( e ) {
				var t = e.touches && e.touches[ 0 ];
				if ( t && nearEdge( t.clientX ) ) { show(); armHide( 4000 ); }
			}, { passive: true } );

			menu.addEventListener( 'mouseenter', function () { pinned = true; show(); } );
			menu.addEventListener( 'mouseleave', function () { pinned = false; armHide( 280 ); } );
			menu.addEventListener( 'focusin', function () { pinned = true; show(); } );
			menu.addEventListener( 'focusout', function () { pinned = false; armHide( 280 ); } );

			armHide( 1200 );
		}() );
		</script>
		<?php
	}
}
