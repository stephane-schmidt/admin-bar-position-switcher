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
	var autoHideOn = ( cfg.autoHide === true || cfg.autoHide === 1 || cfg.autoHide === '1' );

	// Long menus: the fixed wrap keeps overflow visible so flyout submenus
	// work; only when the menu really is taller than the window do we trade
	// them for an internal scrollbar.
	function updateScrollGuard() {
		var m = document.getElementById( 'adminmenu' );
		if ( ! m ) {
			return;
		}
		root.classList.toggle( 'abps-menu-scroll', ( m.offsetHeight + 40 ) > window.innerHeight );
	}
	updateScrollGuard();
	window.addEventListener( 'resize', updateScrollGuard );

	function updateHideArrow() {
		var b = document.getElementById( 'abps-menu-hide' );
		if ( ! b ) {
			return;
		}
		var hidden = root.classList.contains( 'abps-menu-hidden' );
		var right = root.classList.contains( 'abps-menu-right' );
		// Arrow points where a click will send the menu.
		b.textContent = ( hidden !== right ) ? '\u276F' : '\u276E';
	}

	var btn = document.getElementById( 'abps-menu-switch' );
	if ( btn ) {
		btn.addEventListener( 'click', function () {
			var right = ! root.classList.contains( 'abps-menu-right' );
			root.classList.toggle( 'abps-menu-right', right );
			try {
				localStorage.setItem( 'abpsMenuSide', right ? 'right' : 'left' );
			} catch ( e ) {}
			updateHideArrow();
		} );
	}

	// The auto-hide block (below) swaps this in so a manual reveal restarts
	// its min-visible window instead of being re-hidden by a stale timer.
	var onManualReveal = function () {};

	var hideBtn = document.getElementById( 'abps-menu-hide' );
	if ( hideBtn ) {
		hideBtn.addEventListener( 'click', function () {
			var hidden = root.classList.contains( 'abps-menu-hidden' );
			if ( hidden ) {
				root.classList.remove( 'abps-menu-hidden' );
				root.classList.remove( 'abps-menu-manual' );
				root.classList.remove( 'abps-menu-docked' );
				if ( ! autoHideOn ) {
					root.classList.remove( 'abps-menu-autohide' );
				}
				try { localStorage.setItem( 'abpsMenuDock', '0' ); } catch ( e ) {}
				onManualReveal();
			} else {
				root.classList.add( 'abps-menu-autohide' );
				root.classList.add( 'abps-menu-docked' );
				root.classList.add( 'abps-menu-hidden' );
				root.classList.add( 'abps-menu-manual' );
				try { localStorage.setItem( 'abpsMenuDock', '1' ); } catch ( e ) {}
			}
			updateHideArrow();
		} );
	}
	updateHideArrow();

	if ( ! autoHideOn ) {
		return;
	}

	var HIDDEN = 'abps-menu-hidden';
	var REVEAL = typeof cfg.revealDistance === 'number' ? cfg.revealDistance : 150;
	var MIN_VISIBLE = typeof cfg.minVisible === 'number' ? cfg.minVisible : 10000;
	var menu = document.getElementById( 'adminmenumain' );
	if ( ! menu ) {
		return;
	}
	var pinned = false;
	var timer = null;
	// Once revealed, the menu stays for at least MIN_VISIBLE ms so a pointer
	// brushing past the edge never makes it bounce out and straight back in.
	var shownAt = Date.now();

	onManualReveal = function () {
		shownAt = Date.now();
		armHide( 2000 );
	};

	function hide() {
		// A manual hide/reveal (the tab) owns the state until toggled back.
		if ( pinned || root.classList.contains( 'abps-menu-manual' ) ) {
			return;
		}
		var left = shownAt + MIN_VISIBLE - Date.now();
		if ( left > 0 ) {
			armHide( left + 20 );
			return;
		}
		// "docked" sticks for the whole page view: the content expands
		// once, and later reveals overlay it instead of reflowing it.
		root.classList.add( 'abps-menu-docked' );
		root.classList.add( HIDDEN );
		if ( window.requestAnimationFrame ) {
			window.requestAnimationFrame( updateHideArrow );
		}
	}
	function show() {
		// Never let a proximity reveal undo a manual hide: only the tab does.
		if ( root.classList.contains( 'abps-menu-manual' ) ) {
			return;
		}
		if ( root.classList.contains( HIDDEN ) ) {
			shownAt = Date.now();
		}
		root.classList.remove( HIDDEN );
		if ( window.requestAnimationFrame ) {
			window.requestAnimationFrame( updateHideArrow );
		}
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
