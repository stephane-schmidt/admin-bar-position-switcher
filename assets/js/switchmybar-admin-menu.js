/**
 * Admin Bar Position Switcher — back-office menu behavior.
 *
 * Side switcher (left/right, remembered per browser) and macOS-Dock-style
 * auto-hide: the menu keeps a 10px peek at the screen edge and glides back
 * when the pointer comes near, hovers it, or focuses into it.
 */
( function () {
	'use strict';

	var cfg = window.SWITCHMYBAR_MENU || {};
	var root = document.documentElement;

	var btn = document.getElementById( 'abps-menu-switch' );
	if ( btn ) {
		btn.addEventListener( 'click', function () {
			var right = ! root.classList.contains( 'abps-menu-right' );
			root.classList.toggle( 'abps-menu-right', right );
			try {
				localStorage.setItem( 'abpsMenuSide', right ? 'right' : 'left' );
			} catch ( e ) {}
		} );
	}

	if ( ! ( cfg.autoHide === true || cfg.autoHide === 1 || cfg.autoHide === '1' ) ) {
		return;
	}

	var HIDDEN = 'abps-menu-hidden';
	var REVEAL = typeof cfg.revealDistance === 'number' ? cfg.revealDistance : 150;
	var menu = document.getElementById( 'adminmenumain' );
	if ( ! menu ) {
		return;
	}
	var pinned = false;
	var timer = null;

	function hide() {
		if ( ! pinned ) {
			// "docked" sticks for the whole page view: the content expands
			// once, and later reveals overlay it instead of reflowing it.
			root.classList.add( 'abps-menu-docked' );
			root.classList.add( HIDDEN );
		}
	}
	function show() {
		root.classList.remove( HIDDEN );
	}
	function armHide( delay ) {
		if ( timer ) {
			window.clearTimeout( timer );
		}
		timer = window.setTimeout( hide, delay );
	}
	function nearEdge( x ) {
		if ( root.classList.contains( 'abps-menu-right' ) ) {
			return ( window.innerWidth - x ) <= REVEAL;
		}
		return x <= REVEAL;
	}

	var queued = false;
	var mx = 0;
	document.addEventListener( 'mousemove', function ( e ) {
		mx = e.clientX;
		if ( queued ) {
			return;
		}
		queued = true;
		window.requestAnimationFrame( function () {
			queued = false;
			if ( nearEdge( mx ) ) {
				show();
			} else if ( ! pinned ) {
				armHide( 2000 );
			}
		} );
	}, { passive: true } );
	document.addEventListener( 'touchstart', function ( e ) {
		var t = e.touches && e.touches[ 0 ];
		if ( t && nearEdge( t.clientX ) ) {
			show();
			armHide( 4000 );
		}
	}, { passive: true } );

	menu.addEventListener( 'mouseenter', function () {
		pinned = true;
		show();
	} );
	menu.addEventListener( 'mouseleave', function () {
		pinned = false;
		armHide( 2000 );
	} );
	menu.addEventListener( 'focusin', function () {
		pinned = true;
		show();
	} );
	menu.addEventListener( 'focusout', function () {
		pinned = false;
		armHide( 2000 );
	} );

	// Visible for a moment on load so the menu is discoverable, then it
	// tucks away, leaving a clearly visible 20px peek at the edge.
	armHide( 5000 );
}() );
