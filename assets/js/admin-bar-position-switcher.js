/**
 * Admin Bar Position Switcher — front-end toggle.
 *
 * The <html> class is already set by the inline head script (no flash). This
 * file keeps it in sync, wires the floating button, optionally tints the button
 * to match the main color of the page, and — when enabled in the settings —
 * lets the button drift away when idle and float back near the toolbar.
 */
( function () {
	'use strict';

	var cfg = window.ABPS || {};

	// Tolerate both real booleans (wp_json_encode) and the "1"/"" strings
	// that wp_localize_script would produce.
	function flag( v ) {
		return v === true || v === 1 || v === '1';
	}

	var KEY = cfg.storageKey || 'abpsPosition';
	var DEFAULT = cfg.defaultPosition === 'top' ? 'top' : 'bottom';
	var REMEMBER = ! ( cfg.remember === false || cfg.remember === 0 || cfg.remember === '' || cfg.remember === '0' );
	var AUTO_COLOR = flag( cfg.autoColor );
	var AUTO_HIDE = flag( cfg.autoHide ); // opt-in; the button stays visible by default
	var HIDE_DELAY = typeof cfg.hideDelay === 'number' ? cfg.hideDelay : 10000;
	var REVEAL_AT = typeof cfg.revealDistance === 'number' ? cfg.revealDistance : 50;
	var BAR_AUTO_HIDE = flag( cfg.barAutoHide ); // opt-in; the toolbar stays visible by default
	var BAR_REVEAL_AT = typeof cfg.barRevealDistance === 'number' ? cfg.barRevealDistance : 150;
	var BAR_MIN_VISIBLE = typeof cfg.barMinVisible === 'number' ? cfg.barMinVisible : 10000;
	var root = document.documentElement;

	/* ------------------------------------------------------------------ *
	 * Position handling
	 * ------------------------------------------------------------------ */
	function current() {
		if ( root.classList.contains( 'abps-top' ) ) {
			return 'top';
		}
		if ( root.classList.contains( 'abps-bottom' ) ) {
			return 'bottom';
		}
		return DEFAULT;
	}

	function apply( pos ) {
		root.classList.remove( 'abps-top', 'abps-bottom' );
		root.classList.add( pos === 'top' ? 'abps-top' : 'abps-bottom' );
	}

	// Make sure a class is present even if the early inline script was blocked.
	var pos = DEFAULT;
	if ( REMEMBER ) {
		try {
			var stored = window.localStorage.getItem( KEY );
			if ( stored === 'top' || stored === 'bottom' ) {
				pos = stored;
			}
		} catch ( e ) {}
	} else {
		pos = current();
	}
	apply( pos );

	var button = document.getElementById( 'abps-switch' );
	if ( button ) {
		button.addEventListener( 'click', function () {
			var next = current() === 'top' ? 'bottom' : 'top';
			apply( next );
			if ( REMEMBER ) {
				try {
					window.localStorage.setItem( KEY, next );
				} catch ( e ) {}
			}
		} );
	}

	/* ------------------------------------------------------------------ *
	 * Auto-hide (opt-in): after a spell of inactivity the button drifts
	 * away like a falling leaf; it floats back the moment the pointer moves
	 * over the toolbar (its full width) or within REVEAL_AT pixels of it, or
	 * the button gains keyboard focus. Touch: a tap brings it back.
	 * ------------------------------------------------------------------ */
	function setupAutoHide( btn ) {
		var HIDDEN = 'abps-switch--hidden';
		var RISING = 'abps-switch--rising';
		var bar = document.getElementById( 'wpadminbar' );
		var timer = null;
		var riseTimer = null;

		function stop() {
			if ( timer ) {
				window.clearTimeout( timer );
				timer = null;
			}
		}
		function doHide() {
			if ( riseTimer ) {
				window.clearTimeout( riseTimer );
				riseTimer = null;
			}
			btn.classList.remove( RISING );
			btn.classList.add( HIDDEN );
		}
		function show() {
			if ( ! btn.classList.contains( HIDDEN ) ) {
				return;
			}
			btn.classList.remove( HIDDEN );
			btn.classList.add( RISING );
			if ( riseTimer ) {
				window.clearTimeout( riseTimer );
			}
			riseTimer = window.setTimeout( function () {
				btn.classList.remove( RISING );
				riseTimer = null;
			}, 640 );
		}
		function arm() {
			stop();
			timer = window.setTimeout( doHide, HIDE_DELAY );
		}
		function reveal() { // show, then restart the countdown
			show();
			arm();
		}
		function pin() {    // show and hold (hovering / focused)
			show();
			stop();
		}

		// Reveal zone: the whole admin bar (full width) grown by REVEAL_AT px.
		function inZone( x, y ) {
			var r = bar ? bar.getBoundingClientRect() : null;
			if ( ! r || ( 0 === r.width && 0 === r.height ) ) {
				return false;
			}
			var dx = Math.max( r.left - x, 0, x - r.right );
			var dy = Math.max( r.top - y, 0, y - r.bottom );
			return ( dx * dx + dy * dy ) <= ( REVEAL_AT * REVEAL_AT );
		}

		var queued = false;
		var mx = 0;
		var my = 0;
		function onMove( e ) {
			mx = e.clientX;
			my = e.clientY;
			if ( queued ) {
				return;
			}
			queued = true;
			window.requestAnimationFrame( function () {
				queued = false;
				if ( inZone( mx, my ) ) {
					reveal();
				}
			} );
		}

		arm(); // visible on load, drifts away once the delay elapses

		document.addEventListener( 'mousemove', onMove, { passive: true } );
		document.addEventListener( 'touchstart', reveal, { passive: true } );

		btn.addEventListener( 'mouseenter', pin );
		btn.addEventListener( 'mouseleave', arm );
		btn.addEventListener( 'focus', pin );
		btn.addEventListener( 'blur', arm );
		btn.addEventListener( 'click', pin );

		if ( bar ) {
			bar.addEventListener( 'mouseenter', pin );
			bar.addEventListener( 'mouseleave', arm );
		}
	}

	if ( button && AUTO_HIDE ) {
		setupAutoHide( button );
	}

	/* ------------------------------------------------------------------ *
	 * Auto-hiding toolbar (macOS Dock style): the bar slides off-screen
	 * and glides back when the pointer comes within BAR_REVEAL_AT pixels
	 * of its edge, while it is hovered, or when it has keyboard focus.
	 * ------------------------------------------------------------------ */
	function setupBarAutoHide() {
		var HIDDEN = 'abps-bar-hidden';
		var bar = document.getElementById( 'wpadminbar' );
		if ( ! bar ) {
			return;
		}
		var pinned = false;
		var timer = null;
		// Once revealed, the bar stays for at least BAR_MIN_VISIBLE ms so a
		// pointer brushing past the edge never makes it bounce in and out.
		var shownAt = Date.now();

		function hide() {
			if ( pinned ) {
				return;
			}
			var left = shownAt + BAR_MIN_VISIBLE - Date.now();
			if ( left > 0 ) {
				armHide( left + 20 );
				return;
			}
			root.classList.add( HIDDEN );
		}
		function show() {
			if ( root.classList.contains( HIDDEN ) ) {
				shownAt = Date.now();
			}
			root.classList.remove( HIDDEN );
		}
		function armHide( delay ) {
			if ( timer ) {
				window.clearTimeout( timer );
			}
			timer = window.setTimeout( hide, delay );
		}

		function nearEdge( y ) {
			if ( current() === 'top' ) {
				return y <= BAR_REVEAL_AT;
			}
			return ( window.innerHeight - y ) <= BAR_REVEAL_AT;
		}

		var queued = false;
		var my = 0;
		function onMove( e ) {
			my = e.clientY;
			if ( queued ) {
				return;
			}
			queued = true;
			window.requestAnimationFrame( function () {
				queued = false;
				if ( nearEdge( my ) ) {
					show();
				} else if ( ! pinned ) {
					armHide( 280 );
				}
			} );
		}

		document.addEventListener( 'mousemove', onMove, { passive: true } );
		document.addEventListener( 'touchstart', function ( e ) {
			var t = e.touches && e.touches[ 0 ];
			if ( t && nearEdge( t.clientY ) ) {
				show();
				armHide( 4000 );
			}
		}, { passive: true } );

		bar.addEventListener( 'mouseenter', function () {
			pinned = true;
			show();
		} );
		bar.addEventListener( 'mouseleave', function () {
			pinned = false;
			armHide( 280 );
		} );
		bar.addEventListener( 'focusin', function () {
			pinned = true;
			show();
		} );
		bar.addEventListener( 'focusout', function () {
			pinned = false;
			armHide( 280 );
		} );

		// Visible on load so the bar is discoverable, then it glides away.
		armHide( 1200 );
	}

	if ( BAR_AUTO_HIDE ) {
		setupBarAutoHide();
	}

	/* ------------------------------------------------------------------ *
	 * Toolbar color picker: the "Bar" item in the admin bar shows the
	 * site's dominant colors; clicking a swatch saves the choice (AJAX)
	 * and recolors the toolbar immediately.
	 * ------------------------------------------------------------------ */
	function setupColorPicker() {
		var liveCss = null;

		function applyBar( color, text ) {
			if ( ! liveCss ) {
				liveCss = document.getElementById( 'abps-live-bar' );
			}
			if ( ! liveCss ) {
				liveCss = document.createElement( 'style' );
				liveCss.id = 'abps-live-bar';
				document.head.appendChild( liveCss );
			}
			if ( ! color ) {
				liveCss.textContent = '#wpadminbar{background:#1d2327 !important;}' +
					'#wpadminbar #wp-admin-bar-root-default>li>.ab-item,#wpadminbar #wp-admin-bar-top-secondary>li>.ab-item{color:#fff !important;}' +
					'#wpadminbar #wp-admin-bar-root-default>li>.ab-item .ab-icon:before,#wpadminbar #wp-admin-bar-top-secondary>li>.ab-item .ab-icon:before,#wpadminbar #wp-admin-bar-root-default>li>.ab-item:before,#wpadminbar #wp-admin-bar-top-secondary>li>.ab-item:before{color:#fff !important;}';
			} else {
				liveCss.textContent = '#wpadminbar{background:' + color + ' !important;}' +
					'#wpadminbar #wp-admin-bar-root-default>li>.ab-item,#wpadminbar #wp-admin-bar-top-secondary>li>.ab-item{color:' + text + ' !important;}' +
					'#wpadminbar #wp-admin-bar-root-default>li>.ab-item .ab-icon:before,#wpadminbar #wp-admin-bar-top-secondary>li>.ab-item .ab-icon:before,#wpadminbar #wp-admin-bar-root-default>li>.ab-item:before,#wpadminbar #wp-admin-bar-top-secondary>li>.ab-item:before{color:' + text + ' !important;}';
			}
			var dot = document.querySelector( '#wp-admin-bar-abps-colors .abps-dot' );
			if ( dot ) {
				dot.style.background = color || '#1d2327';
			}
		}

		function post( data ) {
			var body = new window.FormData();
			var k;
			for ( k in data ) {
				body.append( k, data[ k ] );
			}
			body.append( 'nonce', cfg.nonce );
			return window.fetch( cfg.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: body } )
				.then( function ( r ) { return r.json(); } );
		}

		document.addEventListener( 'click', function ( e ) {
			var swatch = e.target && e.target.closest ? e.target.closest( '.abps-swatch' ) : null;
			if ( ! swatch ) {
				return;
			}
			e.preventDefault();
			post( { action: 'switchmybar_bar_color', color: swatch.getAttribute( 'data-abps-color' ) } )
				.then( function ( res ) {
					if ( res && res.success ) {
						applyBar( res.data.color, res.data.text );
					}
				} )
				.catch( function () {} );
		} );

		// First run for this plugin version: refine the palette in the
		// background (logo + theme + a frequency scan of the home page).
		if ( cfg.needDeep && window.fetch ) {
			post( { action: 'switchmybar_detect_colors' } )
				.then( function ( res ) {
					if ( ! ( res && res.success && res.data.colors && res.data.colors.length ) ) {
						return;
					}
					var wrap = document.querySelector( '#wp-admin-bar-abps-colors-swatches .abps-swatches' );
					if ( ! wrap ) {
						return;
					}
					var defaultBtn = wrap.querySelector( '.abps-swatch--default' );
					wrap.querySelectorAll( '.abps-swatch:not(.abps-swatch--default)' ).forEach( function ( b ) {
						b.remove();
					} );
					res.data.colors.forEach( function ( hex ) {
						var b = document.createElement( 'button' );
						b.type = 'button';
						b.className = 'abps-swatch';
						b.setAttribute( 'data-abps-color', hex );
						b.style.background = hex;
						b.title = hex;
						b.setAttribute( 'aria-label', hex );
						wrap.insertBefore( b, defaultBtn );
					} );
				} )
				.catch( function () {} );
		}
	}

	if ( flag( cfg.canPick ) && cfg.ajaxUrl && cfg.nonce ) {
		setupColorPicker();
	}

	/* ------------------------------------------------------------------ *
	 * Auto color: tint the button with the page's main color
	 * ------------------------------------------------------------------ */
	function parseRgb( str ) {
		if ( ! str ) {
			return null;
		}
		var m = str.match( /rgba?\(([^)]+)\)/i );
		if ( ! m ) {
			return null;
		}
		var parts = m[ 1 ].split( ',' ).map( function ( x ) {
			return parseFloat( x );
		} );
		if ( parts.length < 3 ) {
			return null;
		}
		var alpha = parts.length >= 4 ? parts[ 3 ] : 1;
		return [ parts[ 0 ], parts[ 1 ], parts[ 2 ], alpha ];
	}

	// Resolve any CSS color string (hex, name, hsl…) to an rgb array via the browser.
	function resolveColor( str ) {
		if ( ! str ) {
			return null;
		}
		var probe = document.createElement( 'span' );
		probe.style.color = '';
		probe.style.color = str.trim();
		if ( probe.style.color === '' ) {
			return null; // invalid value
		}
		probe.style.display = 'none';
		document.body.appendChild( probe );
		var resolved = window.getComputedStyle( probe ).color;
		document.body.removeChild( probe );
		return parseRgb( resolved );
	}

	function relativeLuminance( r, g, b ) {
		var channels = [ r, g, b ].map( function ( v ) {
			v /= 255;
			return v <= 0.03928 ? v / 12.92 : Math.pow( ( v + 0.055 ) / 1.055, 2.4 );
		} );
		return 0.2126 * channels[ 0 ] + 0.7152 * channels[ 1 ] + 0.0722 * channels[ 2 ];
	}

	function saturation( r, g, b ) {
		var mx = Math.max( r, g, b );
		var mn = Math.min( r, g, b );
		return mx === 0 ? 0 : ( mx - mn ) / mx;
	}

	function isUsable( c, minSaturation ) {
		if ( ! c || c[ 3 ] < 0.5 ) {
			return false; // missing or mostly transparent
		}
		var r = c[ 0 ], g = c[ 1 ], b = c[ 2 ];
		if ( r > 240 && g > 240 && b > 240 ) {
			return false; // near white
		}
		if ( r < 18 && g < 18 && b < 18 ) {
			return false; // near black
		}
		if ( minSaturation && saturation( r, g, b ) < minSaturation ) {
			return false; // too gray to read as "the page color"
		}
		return true;
	}

	function pickPageColor() {
		// 1) An explicit theme color declared by the site.
		var meta = document.querySelector( 'meta[name="theme-color"]' );
		if ( meta ) {
			var mc = resolveColor( meta.getAttribute( 'content' ) );
			if ( isUsable( mc, 0 ) ) {
				return mc;
			}
		}

		// 2) A primary/accent color exposed as a CSS custom property (block themes).
		var rootStyle = window.getComputedStyle( root );
		var vars = [
			'--wp--preset--color--primary',
			'--wp--preset--color--accent',
			'--wp--preset--color--secondary',
		];
		for ( var i = 0; i < vars.length; i++ ) {
			var vc = resolveColor( rootStyle.getPropertyValue( vars[ i ] ) );
			if ( isUsable( vc, 0.15 ) ) {
				return vc;
			}
		}

		// 3) The background color of the site header / banner.
		var selectors = [
			'header#masthead',
			'.site-header',
			'[role="banner"]',
			'header.wp-block-template-part',
			'header',
		];
		for ( var j = 0; j < selectors.length; j++ ) {
			var el = document.querySelector( selectors[ j ] );
			if ( el ) {
				var bg = parseRgb( window.getComputedStyle( el ).backgroundColor );
				if ( isUsable( bg, 0.12 ) ) {
					return bg;
				}
			}
		}

		return null;
	}

	function applyAutoColor() {
		if ( ! button ) {
			return;
		}
		var c = pickPageColor();
		if ( ! c ) {
			return; // no confident color — keep the default dark button
		}
		var textColor = relativeLuminance( c[ 0 ], c[ 1 ], c[ 2 ] ) > 0.5 ? '#1d2327' : '#ffffff';
		button.style.backgroundColor = 'rgb(' + Math.round( c[ 0 ] ) + ',' + Math.round( c[ 1 ] ) + ',' + Math.round( c[ 2 ] ) + ')';
		button.style.color = textColor;
	}

	if ( AUTO_COLOR && button ) {
		applyAutoColor();
	}
}() );
