=== Admin Bar Position Switcher ===
Contributors: stephaneschmidt
Tags: admin bar, toolbar, admin bar position, bottom toolbar, front end
Requires at least: 5.0
Tested up to: 7.0
Requires PHP: 7.2
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Move the WordPress toolbar to the bottom of the screen on the front end, with a one-click button to flip it top or bottom.

== Description ==

By default WordPress pins its toolbar to the **top** of every front-end page for logged-in users. That top bar pushes the whole layout down, hides the first pixels of the design, and often collides with sticky headers, hero sections, and full-screen builders like Elementor.

**Admin Bar Position Switcher** flips that around: it moves the toolbar to the **bottom** of the screen, out of the way of your header and your content — and closes the empty gap WordPress normally reserves at the top. A small floating **"↕" button** lets each user move the bar back to the top or down to the bottom at any time, and the choice is remembered in that browser for next time.

Because the bar is a personal, per-user thing, everything happens on the front end and only for people who are logged in. **Visitors who are not logged in never see the toolbar or the button, and the plugin loads nothing at all on their pages** — no CSS, no JavaScript, no slow-down.

The switch button can also **blend into your site**: turn on "Match the page color" and the button automatically picks up the main color of the page it is on (from the site's theme color, its color palette, or the header background) and chooses black or white text for readable contrast. If no confident color is found, it quietly falls back to a neutral dark button.

**What it does**

* Places the WordPress toolbar at the bottom of the screen on the front end (default position is configurable).
* Adds a floating "↕" button to switch the toolbar between top and bottom, on the fly.
* Remembers each browser's choice (can be turned off).
* Optionally tints the button to match the main color of the current page, with automatic black/white text for contrast.
* Removes WordPress's reserved top spacer when the bar is at the bottom, so there is no empty gap.
* Opens the toolbar sub-menus upward when the bar is at the bottom, so they stay on screen.
* Optional Elementor compatibility so sticky headers line up with the toolbar.
* Fully translatable, and ships with translations for many languages.
* No effect for logged-out visitors; nothing is enqueued for them.

**Privacy**

The plugin stores the top/bottom preference in the browser's `localStorage` only. It sets no cookies, makes no external requests, and collects no personal data.

== Installation ==

1. Upload the `admin-bar-position-switcher` folder to `/wp-content/plugins/`, or install the plugin through the **Plugins → Add New** screen.
2. Activate the plugin through the **Plugins** screen in WordPress.
3. (Optional) Visit **Settings → Admin Bar Position** to choose the default position and button options.
4. Browse the front end while logged in: the toolbar now sits at the bottom, with a "↕ Bar" button to flip it.

== Frequently Asked Questions ==

= Does this change the toolbar inside wp-admin? =

No. The plugin only affects the front end of the site. The toolbar inside the dashboard is untouched.

= Do logged-out visitors see anything? =

No. Nothing is loaded or shown for visitors who are not logged in, because they never see the toolbar in the first place.

= Where is my top/bottom choice stored? =

In your browser's local storage, under the key `abpsPosition`. It is per-browser and never leaves your device. You can disable this in the settings.

= I use Elementor and my sticky header overlaps the toolbar. =

Keep the "Elementor compatibility" option enabled (it is on by default). It nudges Elementor sticky headers so they line up with the toolbar in both positions.

= How do I hide the floating button but keep the bar at the bottom? =

In **Settings → Admin Bar Position**, turn off "Switch button". The toolbar stays at the bottom; the button is removed.

== Screenshots ==

1. The toolbar moved to the bottom of the front end, with the "↕ Bar" switch button.
2. The settings screen under Settings → Admin Bar Position.

== About the author ==

Admin Bar Position Switcher is built and maintained by **Stéphane Schmidt**, a web designer and developer based in Switzerland.

Stéphane crafts clean, human-friendly WordPress sites — often for cultural and non-profit projects — with a soft spot for the little interface details that make a site pleasant to live in day to day. This plugin actually began life as one of those details, scratched into a client project until it deserved to stand on its own.

He builds with a lot of curiosity and enthusiasm, and these days he develops hand in hand with **Claude Code**, Anthropic's AI coding agent — something he's genuinely excited about. It's a field that never sits still: the tools evolve almost week to week, and Stéphane loves riding that wave, experimenting, learning, and shipping faster and better than ever.

He works as a freelancer and is also part of the studio **[alveo.design](https://alveo.design)**. **He's open for freelance work** — if you have a project in mind, say hello at stephane@alveo.design or find him on [Facebook](https://www.facebook.com/free.stephane). You're also welcome to write to report a bug or simply to tell him which side of the screen you like your toolbar on.

== Changelog ==

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.0 =
Initial release.
