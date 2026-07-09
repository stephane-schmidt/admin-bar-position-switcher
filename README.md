# Admin Bar Position Switcher

> Move the WordPress toolbar to the **bottom** of the screen on the front end, with a one‑click button to flip it top/bottom. The choice is remembered per browser.

A small, focused WordPress plugin. **GPL‑2.0‑or‑later.**

---

## Screenshots

![The WordPress toolbar at the bottom, with the switch button](docs/img/screenshot-1.png)
<p align="center"><em>The WordPress toolbar moved to the bottom, with the “↕ Bar” switch button.</em></p>

![Colorize the toolbar background](docs/img/screenshot-2.png)
<p align="center"><em>Colorize the toolbar — the text color stays readable automatically.</em></p>

![Hide toolbar items](docs/img/screenshot-3.png)
<p align="center"><em>Hide the individual toolbar items you don’t need.</em></p>

![Settings screen](docs/img/screenshot-4.png)
<p align="center"><em>All options on one simple settings screen.</em></p>

---

## Why

By default WordPress pins its toolbar to the **top** of every front‑end page for logged‑in users. That top bar pushes the whole layout down, hides the first pixels of the design, and often collides with sticky headers, hero sections, and full‑screen builders like Elementor.

**Admin Bar Position Switcher** moves the toolbar to the **bottom** instead, closes the empty gap WordPress reserves at the top, and adds a floating **"↕" button** so each user can send the bar back to the top or down to the bottom whenever they like.

Everything happens on the front end and **only for logged‑in users**. Visitors who are not logged in never see the toolbar or the button, and the plugin loads **nothing at all** on their pages — no CSS, no JavaScript.

## Features

- Places the WordPress toolbar at the bottom of the screen on the front end (default position is configurable).
- Floating "↕" button to switch the toolbar between top and bottom, on the fly.
- Remembers each browser's choice (can be turned off).
- **Match the page color** — optionally tints the button with the main color of the current page (theme color, palette, or header background), with automatic black/white text for contrast. Falls back to a neutral dark button when no color is found.
- **Hide toolbar items** — remove individual items you don't want (WordPress logo, Comments, + New, Updates, …).
- **Colorize the toolbar** — set a custom background color for the bar, with automatically readable text.
- Removes WordPress's reserved top spacer when the bar is at the bottom, so there is no empty gap.
- Opens the toolbar sub‑menus upward when the bar is at the bottom.
- Optional Elementor compatibility so sticky headers line up with the toolbar.
- Clean, no external requests, no tracking, no cookies (the preference lives in `localStorage`).

## Settings

**Settings → Admin Bar Position**

| Setting | What it does |
| --- | --- |
| Default position | Where the toolbar starts (bottom or top). |
| Switch button | Show or hide the floating "↕" button. |
| Button label | Text shown next to the arrow (default: "Bar"). |
| Remember the choice | Store each browser's top/bottom preference. |
| Match the page color | Tint the button with the page's main color. |
| Elementor compatibility | Align Elementor sticky headers with the toolbar. |
| Toolbar background | Set a custom background color for the bar (text stays readable). |
| Hide toolbar items | Hide individual items from the front-end toolbar. |

## Installation

1. Install from the WordPress Plugin Directory (once published), or upload the `admin-bar-position-switcher` folder to `/wp-content/plugins/`.
2. Activate the plugin through the **Plugins** screen.
3. (Optional) Visit **Settings → Admin Bar Position** to tune the defaults.

## Translations

Ships translated into **26 languages** (full UI): French, Spanish, German, Italian, Portuguese (Portugal & Brazil), Dutch, Russian, Japanese, Chinese (Simplified & Traditional), Korean, Arabic, Polish, Swedish, Danish, Norwegian, Finnish, Czech, Turkish, Greek, Hebrew, Hungarian, Romanian, Ukrainian and Indonesian. Contributions of new or improved translations are welcome.

## Author

Built and maintained by **Stéphane Schmidt** — a web designer and developer based in Switzerland.

Stéphane crafts clean, human‑friendly WordPress sites, often for cultural and non‑profit projects, and develops hand in hand with **Claude Code** with a lot of enthusiasm. **Available for freelance work** — reach him at **stephane@alveo.design**, on [alveo.design](https://alveo.design), on [Facebook](https://www.facebook.com/free.stephane), on [Instagram](https://www.instagram.com/free.stephane/), or on [TikTok](https://www.tiktok.com/@freestephane).

## License

This plugin is free software, released under the **GNU General Public License v2.0 or later**. See [LICENSE](LICENSE).
