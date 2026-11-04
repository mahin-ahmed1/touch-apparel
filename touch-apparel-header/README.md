# Touch Apparel Header

A small WordPress plugin for the approved centered-logo header and split product directory. It uses an existing WordPress navigation menu; it does not create menus, pages, products, or edit theme files. No Elementor dependency, external assets, font imports, or build step.

## Installation

1. Upload `touch-apparel-header.zip` under **Plugins → Add New Plugin → Upload Plugin** and activate it.
2. Open **Settings → Touch Apparel Header**. Select your existing **Main Menu** and save.
3. The top-level item named **Products** is detected automatically. If yours has a different name, select that item in the plugin settings and save again.
4. In your existing WordPress menu editor, arrange the menu as follows. Indent all seven categories one level beneath Products:

   ```text
   Home
   About
   Services
   Products
     Knitwear
     Woven
     Sweater
     Suits
     Work Wear
     School Wear
     Other
   Sustainability
   Contact us
   ```

5. Assign each category its real page, product-category archive, or custom URL. Products is an expandable control, so its own URL is not used. Empty URLs and `#` are rendered as inactive text rather than fake links. If Products has no children, it renders as a regular item until you add categories.
6. In Elementor, add a **Shortcode widget** containing:

   ```text
   [touch_apparel_header]
   ```

   To override the menu for one instance, use its exact name, slug, or ID:

   ```text
   [touch_apparel_header menu="Main Menu"]
   ```

7. Replace the old HTML header widget instead of leaving both active. Use a full-width Elementor parent container, zero padding and gap, and visible overflow. Keep ancestor containers' overflow visible too. A parent with `overflow: hidden` will clip the desktop mega menu. If other sections cover it, increase the header container's z-index in Elementor.
8. With Elementor Pro, place the shortcode in a Theme Builder **Header** template and configure its display conditions. The plugin also works in any existing shortcode-capable header area supplied by your theme or header builder. It does not replace the theme header automatically; disable duplicate theme headers using your theme/builder's settings.

## Editable content

- **Menu links, labels, order and categories:** your existing WordPress menu editor, commonly Appearance → Menus. The plugin settings select which existing menu to read; they do not register a theme menu location.
- **Products selection, location, brand line and fallback text logo:** Settings → Touch Apparel Header.
- **Logo:** the WordPress Site Logo, where supported by your theme. If no Site Logo is available, the approved typographic logo is used. Uncheck “Use the WordPress Site Logo” to retain the text treatment. Elementor's own logo setting may be separate from the WordPress Site Logo.
- **Fonts:** keep Inter Tight and Syne available through Elementor as already configured. The plugin references them and never imports fonts.
- **Design:** `assets/header.css`. Updating the plugin may replace edits to this file; keep deliberate overrides in your site's custom CSS, scoped to `.ta-index-header`.

The product spotlight and category numbers/count are generated from the selected menu. Page and category menu objects resolve through WordPress. Custom links use the URL you provide. Additional child levels remain nested and are included in the count.

## Interaction

- Click/tap Products to expand the directory; native `details` works without JavaScript.
- Hover or focus a linked category to update the decorative desktop spotlight.
- On screens up to 900px, the menu toggle opens the navigation and the directory becomes a simple list.
- Escape closes the nearest open dropdown, then the mobile menu on a subsequent press.
- Clicking outside or moving keyboard focus out closes navigation.
- Reduced-motion preferences are respected; essential menu content remains available without JavaScript.
- Multiple instances get separate navigation IDs. Use one visible header per page; separate desktop/mobile copies are unnecessary.

## Before publishing

Test on a staging page first. Check real links, dropdown positioning, desktop/mobile navigation, keyboard focus, your actual logo, and Elementor editor behavior. Test with the site's active theme and optimization plugins; clear their caches after installation or updates.

Requires WordPress 6.2+ and PHP 7.4+. No analytics, outbound HTTP requests, database tables, automatic updates, or frontend dependencies. Settings use WordPress's capability checks, nonces, sanitization and escaping. Menu permissions stay under WordPress control. Deactivation retains settings; menu content is never deleted.
