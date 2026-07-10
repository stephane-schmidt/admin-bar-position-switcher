<?php
/**
 * Automatic detection of the site's dominant colors — fully local.
 *
 * Combines structured sources (site logo, Elementor kit, theme.json, theme
 * mods, popular theme options) with an optional frequency analysis of the
 * home page and its same-origin stylesheets. Whites, blacks and grays are
 * filtered out so only brand-ish colors remain. No third-party service is
 * ever contacted.
 *
 * Adapted from the FreeCookie color detector by the same author (GPL).
 *
 * @package AdminBarPositionSwitcher
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class Switchmybar_Color_Detector.
 */
class Switchmybar_Color_Detector {

	/**
	 * Option caching the detected palette.
	 */
	const OPTION = 'switchmybar_colors_detected';

	/**
	 * Factory colors (Elementor / themes) that sites rarely change — never brand colors.
	 */
	const FACTORY_DEFAULTS = array( '#6ec1e4', '#61ce70', '#54595f', '#7a7a7a', '#69727d', '#f5f5f5', '#e8e8e8', '#0654a1' );

	/**
	 * Detected palette (up to 5 colors, first = primary), cached per plugin version.
	 * Without a valid cache, computes from structured sources only (no HTTP).
	 *
	 * @return string[] Hex list.
	 */
	public static function palette() {
		$cached = get_option( self::OPTION, null );
		// An empty palette is a valid (cached) answer — requiring non-empty
		// colors here would re-run detection on every page for such sites.
		if ( is_array( $cached ) && isset( $cached['colors'] ) && is_array( $cached['colors'] ) && ( $cached['ver'] ?? '' ) === SWITCHMYBAR_VERSION ) {
			return $cached['colors'];
		}
		$colors = self::rank( self::structured(), array() );
		update_option( self::OPTION, array( 'time' => time(), 'deep' => false, 'ver' => SWITCHMYBAR_VERSION, 'colors' => $colors ), false );
		return $colors;
	}

	/**
	 * Whether the deep (frequency) pass already ran for this plugin version.
	 *
	 * @return bool
	 */
	public static function has_deep() {
		$cached = get_option( self::OPTION, null );
		return is_array( $cached ) && ! empty( $cached['deep'] ) && ( $cached['ver'] ?? '' ) === SWITCHMYBAR_VERSION;
	}

	/**
	 * Full detection (structured + frequency), cached.
	 *
	 * @param bool $deep Include the frequency analysis (local HTTP).
	 * @return string[]
	 */
	public static function detect( $deep = true ) {
		$colors = self::rank( self::structured(), $deep ? self::frequency() : array() );
		update_option(
			self::OPTION,
			array(
				'time'   => time(),
				'deep'   => (bool) $deep,
				'ver'    => SWITCHMYBAR_VERSION,
				'colors' => $colors,
			),
			false
		);
		return $colors;
	}

	/* ------------------------------------------------------------------ */
	/* Structured sources (no HTTP request)                                */
	/* ------------------------------------------------------------------ */

	/**
	 * @return array<string,int> hex => score
	 */
	protected static function structured() {
		$scores = array();

		// 0) Site logo — the most trustworthy brand signal.
		$i = 0;
		foreach ( self::logo_colors() as $hex ) {
			self::add_score( $scores, $hex, 1200 - $i * 250 );
			$i++;
		}

		// 1) Elementor kit.
		$kit_id = (int) get_option( 'elementor_active_kit' );
		if ( $kit_id ) {
			$s = get_post_meta( $kit_id, '_elementor_page_settings', true );
			if ( is_array( $s ) ) {
				$weight = array( 'primary' => 1000, 'secondary' => 700, 'accent' => 600, 'text' => 0 );
				if ( ! empty( $s['system_colors'] ) && is_array( $s['system_colors'] ) ) {
					foreach ( $s['system_colors'] as $c ) {
						if ( empty( $c['color'] ) || self::is_factory_default( $c['color'] ) ) {
							continue;
						}
						$id = $c['_id'] ?? '';
						$w  = $weight[ $id ] ?? 500;
						self::add( $scores, $c['color'], $w );
					}
				}
				if ( ! empty( $s['custom_colors'] ) && is_array( $s['custom_colors'] ) ) {
					$i = 0;
					foreach ( $s['custom_colors'] as $c ) {
						if ( ! empty( $c['color'] ) ) {
							self::add( $scores, $c['color'], max( 250, 520 - $i * 40 ) );
							$i++;
						}
					}
				}
			}
		}

		// 2) theme.json palette (block themes).
		if ( function_exists( 'wp_get_global_settings' ) ) {
			$palette = wp_get_global_settings( array( 'color', 'palette' ) );
			$flat    = array();
			if ( is_array( $palette ) ) {
				foreach ( array( 'theme', 'custom' ) as $origin ) {
					if ( ! empty( $palette[ $origin ] ) && is_array( $palette[ $origin ] ) ) {
						$flat = array_merge( $flat, $palette[ $origin ] );
					}
				}
			}
			$weight = array( 'primary' => 800, 'accent' => 700, 'secondary' => 600 );
			foreach ( $flat as $entry ) {
				if ( empty( $entry['color'] ) ) {
					continue;
				}
				$w = $weight[ $entry['slug'] ?? '' ] ?? 300;
				self::add( $scores, $entry['color'], $w );
			}
		}

		// 3) Customizer theme mods, scanned recursively for colors.
		$mods = get_theme_mods();
		if ( is_array( $mods ) ) {
			self::walk_values( $mods, $scores );
		}

		// 4) Popular theme/builder options that hold brand colors.
		foreach ( array( 'astra-settings', 'kadence_global_palette', 'generate_settings', 'oceanwp_customizer', 'blocksy_options', 'fl_builder_settings' ) as $opt ) {
			$val = get_option( $opt );
			if ( $val ) {
				self::walk_values( $val, $scores );
			}
		}

		return $scores;
	}

	/**
	 * Recursively collect colors from an options structure.
	 *
	 * @param mixed             $value  Value.
	 * @param array<string,int> $scores Accumulator (by reference).
	 */
	protected static function walk_values( $value, array &$scores ) {
		if ( is_array( $value ) ) {
			foreach ( $value as $v ) {
				self::walk_values( $v, $scores );
			}
		} elseif ( is_string( $value ) ) {
			foreach ( self::extract_colors( $value ) as $hex ) {
				self::add_score( $scores, $hex, 250 );
			}
		}
	}

	/* ------------------------------------------------------------------ */
	/* Site logo                                                           */
	/* ------------------------------------------------------------------ */

	/**
	 * Dominant colors of the logo (custom_logo, else site icon).
	 *
	 * @return string[]
	 */
	protected static function logo_colors() {
		$id = (int) get_theme_mod( 'custom_logo' );
		if ( ! $id ) {
			$id = (int) get_option( 'site_icon' );
		}
		if ( ! $id ) {
			return array();
		}
		$path = get_attached_file( $id );
		if ( ! $path || ! is_readable( $path ) ) {
			return array();
		}
		if ( preg_match( '/\.svg$/i', $path ) ) {
			return self::svg_colors( $path );
		}
		return self::dominant_from_file( $path, 3 );
	}

	/**
	 * Brand colors from an SVG logo (vector file = XML text).
	 *
	 * @param string $path Path to the .svg file.
	 * @return string[]
	 */
	public static function svg_colors( $path ) {
		$svg = @file_get_contents( $path, false, null, 0, 300000 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		if ( ! $svg ) {
			return array();
		}
		$cols = self::extract_colors( $svg );

		// Common named colors (incl. deep reds studios like to use).
		$named = array(
			'maroon'    => '#800000',
			'darkred'   => '#8b0000',
			'firebrick' => '#b22222',
			'crimson'   => '#dc143c',
			'brown'     => '#a52a2a',
			'indigo'    => '#4b0082',
			'purple'    => '#800080',
			'teal'      => '#008080',
			'navy'      => '#000080',
			'olive'     => '#808000',
		);
		foreach ( $named as $name => $hex ) {
			if ( preg_match( '/[:"\'\s=]' . $name . '[;"\'\s<]/i', $svg ) ) {
				$cols[] = $hex;
			}
		}

		$tally = array();
		foreach ( $cols as $hex ) {
			if ( self::is_brandish( $hex ) ) {
				$tally[ $hex ] = ( $tally[ $hex ] ?? 0 ) + 1;
			}
		}
		if ( empty( $tally ) ) {
			return array();
		}
		arsort( $tally );
		return array_slice( array_keys( $tally ), 0, 3 );
	}

	/**
	 * Dominant brand colors of a raster image (via GD), most frequent first.
	 * Skips transparency; returns nothing for non-raster files (e.g. SVG).
	 *
	 * @param string $path File path.
	 * @param int    $max  Max colors.
	 * @return string[]
	 */
	public static function dominant_from_file( $path, $max = 3 ) {
		if ( ! $path || ! is_readable( $path ) || ! extension_loaded( 'gd' ) ) {
			return array();
		}
		$info = @getimagesize( $path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		if ( ! $info ) {
			return array();
		}
		switch ( $info[2] ) {
			case IMAGETYPE_JPEG:
				$img = @imagecreatefromjpeg( $path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
				break;
			case IMAGETYPE_PNG:
				$img = @imagecreatefrompng( $path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
				break;
			case IMAGETYPE_GIF:
				$img = @imagecreatefromgif( $path ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
				break;
			case IMAGETYPE_WEBP:
				$img = function_exists( 'imagecreatefromwebp' ) ? @imagecreatefromwebp( $path ) : false; // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
				break;
			default:
				return array();
		}
		if ( ! $img ) {
			return array();
		}

		$w  = imagesx( $img );
		$h  = imagesy( $img );
		$mx = max( $w, $h );
		$sc = $mx > 56 ? 56 / $mx : 1;
		$sw = max( 1, (int) ( $w * $sc ) );
		$sh = max( 1, (int) ( $h * $sc ) );

		$small = imagecreatetruecolor( $sw, $sh );
		imagealphablending( $small, false );
		imagesavealpha( $small, true );
		imagecopyresampled( $small, $img, 0, 0, 0, 0, $sw, $sh, $w, $h );

		$tally = array();
		for ( $y = 0; $y < $sh; $y++ ) {
			for ( $x = 0; $x < $sw; $x++ ) {
				$rgba  = imagecolorat( $small, $x, $y );
				$alpha = ( $rgba >> 24 ) & 0x7F; // 0 = opaque, 127 = transparent.
				if ( $alpha > 64 ) {
					continue;
				}
				$r   = ( $rgba >> 16 ) & 0xFF;
				$g   = ( $rgba >> 8 ) & 0xFF;
				$b   = $rgba & 0xFF;
				$hex = self::rgb_hex( $r, $g, $b );
				if ( ! self::is_brandish( $hex ) ) {
					continue;
				}
				// Quantize to merge close shades.
				$q           = self::rgb_hex( (int) ( round( $r / 24 ) * 24 ), (int) ( round( $g / 24 ) * 24 ), (int) ( round( $b / 24 ) * 24 ) );
				$tally[ $q ] = ( $tally[ $q ] ?? 0 ) + 1;
			}
		}
		if ( empty( $tally ) ) {
			return array();
		}
		arsort( $tally );
		return array_slice( array_keys( $tally ), 0, $max );
	}

	/* ------------------------------------------------------------------ */
	/* Frequency analysis (local HTTP)                                     */
	/* ------------------------------------------------------------------ */

	/**
	 * @return array<string,int> hex => occurrences
	 */
	protected static function frequency() {
		$counts = array();

		$home = wp_safe_remote_get(
			home_url( '/' ),
			array(
				'timeout'             => 5,
				'redirection'         => 2,
				'sslverify'           => apply_filters( 'https_local_ssl_verify', false ), // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- core filter for loopback requests.
				'limit_response_size' => 500000,
				'user-agent'          => 'ABPS-Colors/' . SWITCHMYBAR_VERSION,
			)
		);
		if ( is_wp_error( $home ) ) {
			return $counts;
		}
		$html = (string) wp_remote_retrieve_body( $home );

		// Colors in the HTML itself (inline styles, <style> blocks).
		foreach ( self::extract_colors( $html ) as $hex ) {
			self::add_score( $counts, $hex, 1 );
		}

		// Same-origin stylesheets (excluding our own), bounded.
		$host = wp_parse_url( home_url(), PHP_URL_HOST );
		if ( preg_match_all( '#<link[^>]+rel=["\']stylesheet["\'][^>]*href=["\']([^"\']+)["\']#i', $html, $m ) ) {
			$done = 0;
			foreach ( $m[1] as $href ) {
				if ( $done >= 6 ) {
					break;
				}
				if ( false !== stripos( $href, 'admin-bar-position-switcher' ) ) {
					continue;
				}
				$url = self::abs_url( $href );
				if ( wp_parse_url( $url, PHP_URL_HOST ) !== $host ) {
					continue;
				}
				$resp = wp_safe_remote_get(
					$url,
					array(
						'timeout'             => 5,
						'redirection'         => 2,
						'sslverify'           => apply_filters( 'https_local_ssl_verify', false ), // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- core filter for loopback requests.
						'limit_response_size' => 500000,
					)
				);
				if ( is_wp_error( $resp ) ) {
					continue;
				}
				$css = substr( (string) wp_remote_retrieve_body( $resp ), 0, 500000 );
				foreach ( self::extract_colors( $css ) as $hex ) {
					self::add_score( $counts, $hex, 1 );
				}
				$done++;
			}
		}

		// Frequency is a strong brand signal: amplify but cap it.
		foreach ( $counts as $hex => $n ) {
			$counts[ $hex ] = min( $n, 150 ) * 6;
		}
		return $counts;
	}

	/* ------------------------------------------------------------------ */
	/* Merge / rank / filter                                               */
	/* ------------------------------------------------------------------ */

	/**
	 * Merge two score sets, drop neutrals, merge close hues, return ranked hex (max 5).
	 *
	 * @param array<string,int> $a Scores A.
	 * @param array<string,int> $b Scores B.
	 * @return string[]
	 */
	protected static function rank( array $a, array $b ) {
		$scores = $a;
		foreach ( $b as $hex => $n ) {
			self::add_score( $scores, $hex, $n );
		}

		$scores = array_filter(
			$scores,
			function ( $score, $hex ) {
				return self::is_brandish( $hex );
			},
			ARRAY_FILTER_USE_BOTH
		);

		arsort( $scores );

		$kept = array();
		foreach ( $scores as $hex => $score ) {
			$merged = false;
			foreach ( $kept as $k => $ks ) {
				if ( self::distance( $hex, $k ) < 26 ) {
					$kept[ $k ] += $score;
					$merged      = true;
					break;
				}
			}
			if ( ! $merged ) {
				$kept[ $hex ] = $score;
			}
		}
		arsort( $kept );

		return array_slice( array_keys( $kept ), 0, 5 );
	}

	/* ------------------------------------------------------------------ */
	/* Color utilities                                                     */
	/* ------------------------------------------------------------------ */

	/**
	 * Extract every hex (#rgb/#rrggbb) and rgb()/rgba() color from text, normalized.
	 *
	 * @param string $text Text.
	 * @return string[]
	 */
	public static function extract_colors( $text ) {
		$out = array();

		if ( preg_match_all( '/#([0-9a-fA-F]{6}|[0-9a-fA-F]{3})\b/', $text, $m ) ) {
			foreach ( $m[1] as $hex ) {
				$out[] = self::norm_hex( '#' . $hex );
			}
		}
		if ( preg_match_all( '/rgba?\(\s*(\d{1,3})\s*,\s*(\d{1,3})\s*,\s*(\d{1,3})/i', $text, $m, PREG_SET_ORDER ) ) {
			foreach ( $m as $set ) {
				$out[] = self::rgb_hex( (int) $set[1], (int) $set[2], (int) $set[3] );
			}
		}
		return $out;
	}

	/**
	 * Normalize a hex to lowercase #rrggbb.
	 *
	 * @param string $hex Color.
	 * @return string
	 */
	protected static function norm_hex( $hex ) {
		$hex = strtolower( ltrim( $hex, '#' ) );
		if ( 3 === strlen( $hex ) ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}
		return '#' . $hex;
	}

	/**
	 * @param int $r Red.
	 * @param int $g Green.
	 * @param int $b Blue.
	 * @return string
	 */
	protected static function rgb_hex( $r, $g, $b ) {
		$c = function ( $x ) {
			return str_pad( dechex( max( 0, min( 255, $x ) ) ), 2, '0', STR_PAD_LEFT );
		};
		return '#' . $c( $r ) . $c( $g ) . $c( $b );
	}

	/**
	 * [r,g,b] from a normalized hex.
	 *
	 * @param string $hex Color.
	 * @return int[]
	 */
	protected static function rgb( $hex ) {
		$hex = ltrim( $hex, '#' );
		return array( hexdec( substr( $hex, 0, 2 ) ), hexdec( substr( $hex, 2, 2 ) ), hexdec( substr( $hex, 4, 2 ) ) );
	}

	/**
	 * Is this a brand-ish color? (not white/black, not gray).
	 *
	 * @param string $hex Color.
	 * @return bool
	 */
	public static function is_brandish( $hex ) {
		list( $r, $g, $b ) = self::rgb( $hex );
		$mx = max( $r, $g, $b );
		$mn = min( $r, $g, $b );
		if ( 0 === $mx ) {
			return false;
		}
		$l = ( $mx + $mn ) / 2;
		$s = ( $mx - $mn ) / $mx;
		if ( $l > 232 || $l < 18 ) {
			return false;
		}
		if ( $s < 0.22 ) {
			return false;
		}
		return true;
	}

	/**
	 * Does the raw color match a known factory default (Elementor, etc.)?
	 *
	 * @param string $raw Raw color.
	 * @return bool
	 */
	protected static function is_factory_default( $raw ) {
		$c = self::extract_colors( (string) $raw );
		return ! empty( $c ) && in_array( $c[0], self::FACTORY_DEFAULTS, true );
	}

	/**
	 * Euclidean RGB distance between two hex colors.
	 *
	 * @param string $a Color A.
	 * @param string $b Color B.
	 * @return float
	 */
	protected static function distance( $a, $b ) {
		list( $r1, $g1, $b1 ) = self::rgb( $a );
		list( $r2, $g2, $b2 ) = self::rgb( $b );
		return sqrt( ( $r1 - $r2 ) ** 2 + ( $g1 - $g2 ) ** 2 + ( $b1 - $b2 ) ** 2 );
	}

	/**
	 * Score a raw color (validated + normalized).
	 *
	 * @param array<string,int> $scores Accumulator.
	 * @param string            $raw    Raw color.
	 * @param int               $weight Weight.
	 */
	protected static function add( array &$scores, $raw, $weight ) {
		$colors = self::extract_colors( (string) $raw );
		if ( empty( $colors ) ) {
			return;
		}
		self::add_score( $scores, $colors[0], $weight );
	}

	/**
	 * @param array<string,int> $scores Accumulator.
	 * @param string            $hex    Normalized hex.
	 * @param int               $weight Weight.
	 */
	protected static function add_score( array &$scores, $hex, $weight ) {
		$hex            = self::norm_hex( $hex );
		$scores[ $hex ] = ( $scores[ $hex ] ?? 0 ) + $weight;
	}

	/**
	 * Resolve a possibly relative / protocol-relative URL.
	 *
	 * @param string $href Href.
	 * @return string
	 */
	protected static function abs_url( $href ) {
		if ( 0 === strpos( $href, '//' ) ) {
			return ( is_ssl() ? 'https:' : 'http:' ) . $href;
		}
		if ( 0 === strpos( $href, 'http' ) ) {
			return $href;
		}
		return home_url( $href );
	}
}
