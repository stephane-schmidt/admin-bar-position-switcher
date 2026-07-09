/**
 * Admin Bar Position Switcher — front-end toggle.
 *
 * The <html> class is already set by the inline head script (no flash). This
 * file keeps it in sync, wires the floating button, and (optionally) tints the
 * button to match the main color of the page being viewed.
 */
( function () {
	'use strict';

	var cfg = window.ABPS || {};
	var KEY = cfg.storageKey || 'abpsPosition';
	var DEFAULT = cfg.defaultPosition === 'top' ? 'top' : 'bottom';
	var REMEMBER = cfg.remember !== false;
	var AUTO_COLOR = cfg.autoColor === true;
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
