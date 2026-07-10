=== Admin Bar Position Switcher ===
Contributors: stephaneschmidt
Tags: admin bar, toolbar, admin bar position, bottom toolbar, front end
Requires at least: 5.9
Tested up to: 7.0
Requires PHP: 7.2
Stable tag: 1.8.2
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
* Optional auto-hide: the switch button drifts away like a falling leaf after a few seconds of inactivity, and floats back the moment your pointer moves anywhere over the toolbar (off by default; enable it in the settings).
* Remembers each browser's choice (can be turned off).
* Optionally tints the button to match the main color of the current page, with automatic black/white text for contrast.
* Optionally colorize the toolbar background, with automatically readable text.
* **Auto-hide the toolbar, macOS Dock style** (optional): the bar glides off-screen and slides back when your pointer comes within 150 pixels of its edge, staying at least 10 seconds once revealed.
* **Recolor the toolbar right from the toolbar**: a small "Bar" item shows the site's five dominant colors — auto-detected from your logo and theme — and one click applies and saves the color for YOUR account only (administrators; the settings screen holds the site-wide default).
* Removes WordPress's reserved top spacer when the bar is at the bottom, so there is no empty gap.
* Opens the toolbar sub-menus upward when the bar is at the bottom, so they stay on screen.
* Optional Elementor compatibility so sticky headers line up with the toolbar.
* Fully translatable, and ships with translations for many languages.
* No effect for logged-out visitors; nothing is enqueued for them.

**Go further with SwitchMyBar Pro**

[SwitchMyBar Pro](https://switchmybar.com/) is a small add-on for the back-office side: colorize, reorder (drag and drop) and space the left admin menu, dim the technical items, flip the menu to the right or dock it away macOS-style with floating tabs, and hide the toolbar items you never use. One payment of $15, yours for life.

**Privacy**

The plugin stores the top/bottom preference in the browser's `localStorage` only. It sets no cookies and collects no personal data. The color detection contacts no third-party service: it only reads your own site (logo file, theme settings, and a bounded request to your own home page).

== Installation ==

1. Upload the `admin-bar-position-switcher` folder to `/wp-content/plugins/`, or install the plugin through the **Plugins → Add New** screen.
2. Activate the plugin through the **Plugins** screen in WordPress.
3. (Optional) Open **Admin Bar Position** in the left admin menu to choose the default position and button options.
4. Browse the front end while logged in: the toolbar now sits at the bottom, with a "↕ Bar" button to flip it.

== Frequently Asked Questions ==

= Does this change the toolbar inside wp-admin? =

The toolbar inside the dashboard is untouched: the position switch, the floating button and the toolbar colors only affect the front end. Everything that touches wp-admin itself (menu colors, order, docking) lives in the SwitchMyBar Pro add-on.

= Do logged-out visitors see anything? =

No. Nothing is loaded or shown for visitors who are not logged in, because they never see the toolbar in the first place.

= Where is my top/bottom choice stored? =

In your browser's local storage, under the key `abpsPosition`. It is per-browser and never leaves your device. You can disable this in the settings.

= I use Elementor and my sticky header overlaps the toolbar. =

Keep the "Elementor compatibility" option enabled (it is on by default). It nudges Elementor sticky headers so they line up with the toolbar in both positions.

= How do I hide the floating button but keep the bar at the bottom? =

In **Admin Bar Position** (left admin menu), turn off "Switch button". The toolbar stays at the bottom; the button is removed.

== Screenshots ==

1. The WordPress toolbar moved to the bottom of the front end, with the "↕ Bar" switch button.
2. Colorize the toolbar background — the text color stays readable automatically.
3. Hide the individual toolbar items you don't need.
4. All options on one simple settings screen (Admin Bar Position, in the left admin menu).
5. The "Bar" item in the toolbar: pick one of your site's dominant colors, auto-detected from your logo and theme.

== About the author ==

Admin Bar Position Switcher is built and maintained by **Stéphane Schmidt**, a web designer and developer based in Switzerland.

Stéphane crafts clean, human-friendly WordPress sites — often for cultural and non-profit projects — with a soft spot for the little interface details that make a site pleasant to live in day to day. This plugin actually began life as one of those details, scratched into a client project until it deserved to stand on its own.

He builds with a lot of curiosity and enthusiasm, and these days he develops hand in hand with **Claude Code**, Anthropic's AI coding agent — something he's genuinely excited about. It's a field that never sits still: the tools evolve almost week to week, and Stéphane loves riding that wave, experimenting, learning, and shipping faster and better than ever.

He works as a freelancer and is also part of the studio **[alveo.design](https://alveo.design)**. **He's open for freelance work** — if you have a project in mind, say hello at stephane@alveo.design or find him on [Facebook](https://www.facebook.com/free.stephane), [Instagram](https://www.instagram.com/free.stephane/) and [TikTok](https://www.tiktok.com/@freestephane). You're also welcome to write to report a bug or simply to tell him which side of the screen you like your toolbar on. And if the plugin is useful to you, you can buy him a coffee at https://revolut.me/stphanjt11 — thank you!

== Changelog ==

= 1.8.2 =
* Changed: the "Bar" swatches are now personal — each administrator's pick recolors the toolbar for their own account only (stored in user meta), while the settings screen keeps the site-wide default for everyone else. Picking "default" returns to the site color.

= 1.8.1 =
* Changed: the macOS-Dock mode for the toolbar stays in the free plugin (it had briefly moved to the Pro add-on in 1.8.0). The Pro add-on keeps the back-office side: menu colors, order, spacing, dimming, side/Dock tabs, hidden toolbar items and toolbar order.

= 1.8.0 =
* Changed: the plugin refocuses on the front-end toolbar. The back-office features (menu colors, order, spacing, dimming, side/Dock, hidden toolbar items, toolbar order and toolbar Dock) move to the SwitchMyBar Pro add-on. Existing settings are not lost: installing the add-on picks them up automatically.
* Note: nothing else changes — position, floating button, color picker, toolbar background and Elementor compatibility are and stay free.

= 1.7.1 =
* Fixed: on busy sites (page builders, analytics and the like adding toolbar items), the toolbar's right group — user menu and the "Bar" color picker — wrapped onto a hidden second row; with the bar at the bottom that row fell below the screen. The bar now grows to keep every row visible.

= 1.7.0 =
* Added: a hide/show tab above the side-switch tab — one click tucks the back-office menu away (20px peek at the edge) and the page takes the full width; another click brings it back. Remembered per browser, and it works even when the auto-hide option is off.
* Changed: the floating control beside the menu is now a group of two vertical tabs (hide/show on top, left/right below); its arrow flips to show where the next click sends the menu.
* Changed: once revealed, the auto-hidden toolbar and back-office menu now stay visible for at least 10 seconds — no more instant bounce when the pointer brushes past the edge.
* Fixed: with the menu on the left in Dock mode, the page content could drop below the whole menu once it docked (the menu now leaves the layout flow on both sides).
* Fixed: the toolbar color picker had stopped working in 1.6.0 (the JavaScript still called the old AJAX action names after the prefix rename). Clicking a swatch works again.
* Fixed: programmatic saves (activation, migration, the color picker) went through the settings-form sanitizer and silently wiped the saved back-office menu colors. The sanitizer now recognizes both shapes.
* Changed: the back-office menu settings now merge order, color and spacing into a single drag-and-drop list — drag the handle to reorder, and "spacer before" becomes "space after" (existing setups are converted automatically; the visual gaps stay exactly where they were).
* Fixed: flyout submenus were clipped by the fixed menu container in Dock and right-side modes; the container now stays visible and only grows a scrollbar when the menu is taller than the window.
* Fixed: narrow screens that auto-fold the menu (783-960px) now get the same tab and submenu offsets as the manually collapsed menu.
* Changed: the side/Dock features step aside on RTL admin locales instead of mirroring incorrectly.

= 1.6.5 =
* Fixed: with the menu on the right, the current item's inline submenu was shoved sideways (a stray block between the menu groups). The mirror now resets left/right for inline submenus, exactly like core does.

= 1.6.4 =
* Changed: the back-office menu's side-switch button is now a vertical tab glued to the menu's outer edge (mid-height); when the menu docks away, the tab follows it down to the 20px peek.

= 1.6.3 =
* Fixed: on some machines, back-office pages only painted after scrolling. The "dim" effect no longer uses GPU filters (plain static opacity instead, with a lightweight hover highlight for colored items), and the will-change hints were removed everywhere.

= 1.6.2 =
* Improved: the page now waits for the menu to actually tuck away before reclaiming its width (with a smooth glide), and later reveals overlay the widened page instead of squeezing it back. The 20px peek settles at 50% opacity.

= 1.6.1 =
* Improved: the auto-hidden back-office menu is far less shy — it waits 5 seconds before tucking away (2 seconds after the pointer leaves) and keeps a clearly visible 20px colored peek at the screen edge instead of a faint 10px sliver.

= 1.6.0 =
* Changed: every script and style now goes through the WordPress enqueue APIs (src-less handles carry the early inline snippets; the noscript fallback became pure CSS) — requested by the WordPress.org plugin review.
* Changed: all PHP symbols (functions, classes, constants, options, AJAX actions, filters) now use the unique "switchmybar" prefix; existing settings are migrated automatically from the old names.
* Improved: when the back-office menu auto-hides, a 10px half-transparent peek now stays at the screen edge (no more guessing where it went) and the menu waits a little longer before tucking away.

= 1.5.0 =
* New: the back-office menu gets the toolbar treatment — a floating "↔ Menu" button flips it between left and right (remembered per browser, with a default side setting), and an optional macOS-Dock auto-hide slides it off-screen until the pointer comes within 150 pixels of its edge (or it receives keyboard focus).
* Improved: when the toolbar auto-hides, the floating switch button now settles near the screen edge and fades to 50%, returning to full strength on hover or focus.

= 1.4.0 =
* New: reorder the back-office menu and the toolbar by drag and drop — two sortable lists in the settings, with an "Apply this custom order" switch per zone. The order is global and reversible.

= 1.3.1 =
* Changed: the left admin menu entry is now simply called "Admin Bar" ("Admin Barre" in French) so it fits on one line; the settings page keeps its full title.

= 1.3.0 =
* New: "Dim the other items" option for the back-office menu — items without a custom color fade out and light up again on hover or when active, so the everyday menus stand out.
* New tutorial screenshots: the toolbar color picker, the colorized back-office menu, and the full settings page.

= 1.2.0 =
* New: colorize the left admin menu of the back office — pick a background color per menu item (the text color adjusts automatically for readability) and add spacers between groups, from the new "Back-office menu" section of the settings.

= 1.1.1 =
* Changed: the settings page now lives as its own entry in the left admin menu ("Admin Bar Position", with an up/down icon) instead of hiding under Settings.

= 1.1.0 =
* New: optional auto-hiding toolbar, macOS Dock style — the bar glides off-screen and slides back when the pointer comes within 150 pixels of its edge (or when it receives keyboard focus). Off by default: the toolbar stays visible unless you enable it.
* New: recolor the toolbar directly from the toolbar. A small "Bar" item (administrators only) reveals the site's five dominant colors on hover; one click recolors the bar, keeps the text readable, and saves the choice.
* New: automatic color detection, fully local — it samples your logo (PNG/JPEG/WebP via GD, SVG parsed as text), reads the Elementor kit (skipping factory defaults), theme.json, Customizer settings and popular theme options, then refines with a frequency scan of your home page. No external service.
* New: "Color picker in the toolbar" setting to turn the item off.

= 1.0.4 =
* Changed: auto-hiding the switch button is now an opt-in setting, off by default — the button stays visible unless you enable it under Settings → Admin Bar Position.
* New: when enabled, the button drifts away like a falling leaf and floats back as soon as the pointer moves anywhere over the toolbar (its full width), not just near the button. The new option is translated into every bundled language.

= 1.0.3 =
* New: a small "Support the author" card on the settings screen — the author's links and a one-tap way to buy him a coffee. Translated into every bundled language.

= 1.0.2 =
* New: the floating switch button now auto-hides after a short spell of inactivity and slides back in when the pointer comes within reach (or it receives keyboard focus). A tap brings it back on touch devices, and the motion respects the "reduce motion" accessibility preference.

= 1.0.1 =
* New: hide individual toolbar items from the front-end toolbar.
* New: colorize the toolbar background, with an automatically readable text color.

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.6.5 =
Fixes the current item's submenu when the back-office menu sits on the right.

= 1.6.4 =
The menu's side-switch button becomes a vertical tab on the menu's edge.

= 1.6.3 =
Fixes back-office pages that only painted after scrolling (no more GPU filters).

= 1.6.2 =
The page waits for the menu to hide before taking the full width; the peek settles at 50%.

= 1.6.1 =
Slower, friendlier auto-hide for the back-office menu, with a clearly visible 20px peek.

= 1.6.0 =
Enqueue-API compliance, unique "switchmybar" prefix (settings migrate automatically), and a visible 10px peek when the back-office menu hides.

= 1.5.0 =
Left/right switcher and Dock-style auto-hide for the back-office menu.

= 1.4.0 =
Reorder the back-office menu and the toolbar by drag and drop.

= 1.3.1 =
Shorter left-menu label: "Admin Bar".

= 1.3.0 =
Adds the "Dim the other items" option for the back-office menu.

= 1.2.0 =
Colorize the back-office menu: per-item colors with readable text, plus spacers between groups.

= 1.1.1 =
The settings page moved to its own entry in the left admin menu.

= 1.1.0 =
Recolor the toolbar right from the toolbar with your site's dominant colors, and optionally auto-hide the whole bar, macOS Dock style.

= 1.0.4 =
Auto-hide is now opt-in (off by default). When enabled, the button drifts away like a falling leaf and returns when you move over the toolbar.

= 1.0.3 =
Adds a small "Support the author" card to the settings screen.

= 1.0.2 =
The switch button now tucks itself away when idle and slides back as your pointer approaches.

= 1.0.1 =
Adds options to hide toolbar items and to colorize the toolbar background.

= 1.0.0 =
Initial release.
