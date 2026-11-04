# touch-apparel

## Touch Apparel Header plugin

The approved centered-logo WordPress header with an interactive product directory, using your existing Main Menu.

**[Download the installable plugin ZIP](https://github.com/mahin-ahmed1/touch-apparel/raw/refs/heads/main/touch-apparel-header.zip)**

### Install

1. Download `touch-apparel-header.zip` from this repository. Keep it zipped.
2. In WordPress, open **Plugins → Add New Plugin → Upload Plugin**.
3. Choose the ZIP, click **Install Now**, then **Activate**.
4. Open **Settings → Touch Apparel Header**, select your existing **Main Menu**, and save.
5. Keep Knitwear, Woven, Sweater, Suits, Work Wear, School Wear, and Other nested beneath **Products** in your menu. Assign their real destination URLs.
6. Replace the previous Elementor HTML header with a **Shortcode widget** containing `[touch_apparel_header]`.

Use a full-width parent container with zero padding, zero gap, and visible overflow. With Elementor Pro, place the shortcode in a Theme Builder Header template and configure its display conditions. The plugin does not automatically replace the theme's header.

### Files

- [`touch-apparel-header.zip`](touch-apparel-header.zip): install this file in WordPress.
- [`touch-apparel-header/`](touch-apparel-header/): editable plugin source.
- [Detailed setup guide](touch-apparel-header/README.md).

Use the dedicated plugin ZIP above, not GitHub's **Code → Download ZIP**, which also packages repository files.

PHP rendering tests and Chromium checks at six desktop/mobile widths passed in the development environment. The plugin has not been installed or tested on your live WordPress/Elementor site.
