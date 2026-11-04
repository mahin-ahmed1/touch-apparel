<?php
/**
 * Plugin Name: Touch Apparel Header
 * Description: The Touch Apparel centered header and interactive product directory, powered by an existing WordPress menu. Shortcode: [touch_apparel_header]
 * Version: 1.0.0
 * Requires at least: 6.2
 * Requires PHP: 7.4
 * Author: Touch Apparel
 * License: GPL-2.0-or-later
 * Text Domain: touch-apparel-header
 */

namespace TouchApparel\Header;

defined('ABSPATH') || exit;

final class Plugin {
    const OPTION = 'taeh_settings';
    const VERSION = '1.0.0';

    public static function boot() {
        add_action('wp_enqueue_scripts', [__CLASS__, 'assets']);
        add_action('admin_menu', [__CLASS__, 'admin_menu']);
        add_action('admin_init', [__CLASS__, 'register_settings']);
        add_shortcode('touch_apparel_header', [__CLASS__, 'render']);
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), [__CLASS__, 'settings_link']);
    }

    public static function settings() {
        return wp_parse_args(get_option(self::OPTION, []), [
            'menu_id' => 0,
            'products_id' => 0,
            'location' => 'Bangladesh · Established 2015',
            'tagline' => "Stitched to Perfection,\nShipped with Precision.",
            'brand_name' => 'touch apparel',
            'caption' => 'Sourcing & Manufacturing',
            'use_site_logo' => 1,
        ]);
    }

    public static function assets() {
        // Small scoped assets load early so Elementor templates do not flash unstyled.
        $url = plugin_dir_url(__FILE__) . 'assets/';
        wp_enqueue_style('taeh-header', $url . 'header.css', [], self::VERSION);
        wp_enqueue_script('taeh-header', $url . 'header.js', [], self::VERSION, true);
    }

    private static function menu($settings, $override = '') {
        if ($override !== '') {
            return wp_get_nav_menu_object($override);
        }
        if ($settings['menu_id']) {
            return wp_get_nav_menu_object((int) $settings['menu_id']);
        }
        // Reuse Main Menu; never create or modify an existing menu.
        $menus = wp_get_nav_menus();
        foreach ($menus as $menu) {
            if (strcasecmp($menu->name, 'Main Menu') === 0) {
                return $menu;
            }
        }
        return count($menus) === 1 ? reset($menus) : false;
    }

    public static function render($attributes = []) {
        $settings = self::settings();
        $attributes = shortcode_atts(['menu' => ''], $attributes, 'touch_apparel_header');
        $menu = self::menu($settings, (string) $attributes['menu']);
        $items = $menu ? wp_get_nav_menu_items($menu->term_id) : [];

        if (!$items || is_wp_error($items)) {
            return current_user_can('manage_options')
                ? '<p class="taeh-setup-notice">Touch Apparel Header: select a populated menu in Settings → Touch Apparel Header.</p>'
                : '';
        }

        $children = [];
        foreach ($items as $item) {
            $children[(int) $item->menu_item_parent][] = $item;
        }

        $products_id = 0;
        foreach ($children[0] ?? [] as $item) {
            if ((int) $item->ID === (int) $settings['products_id']) {
                $products_id = (int) $item->ID;
                break;
            }
        }
        if (!$products_id) {
            foreach ($children[0] ?? [] as $item) {
                if (strcasecmp(trim(wp_strip_all_tags($item->title)), 'Products') === 0) {
                    $products_id = (int) $item->ID;
                    break;
                }
            }
        }

        $uid = wp_unique_id('taeh-');
        $logo_id = $settings['use_site_logo']
            ? (int) (get_theme_mod('custom_logo') ?: get_option('site_logo', 0)) : 0;
        $logo = $logo_id ? wp_get_attachment_image($logo_id, 'full', false, [
            'class' => 'ta-ih__logo-image', 'alt' => '', 'loading' => 'eager',
        ]) : '';

        ob_start();
        ?>
        <header class="ta-index-header" data-ta-index-header>
          <div class="ta-ih__shell">
            <div class="ta-ih__masthead">
              <p class="ta-ih__location"><?php echo esc_html($settings['location']); ?></p>
              <a class="ta-ih__brand" href="<?php echo esc_url(home_url('/')); ?>"
                 aria-label="<?php echo esc_attr($settings['brand_name'] . ' home'); ?>">
                <?php if ($logo) : ?>
                  <?php echo $logo; // Generated and escaped by WordPress. ?>
                <?php else : ?>
                  <span class="ta-ih__brand-name"><?php echo esc_html($settings['brand_name']); ?><span>.</span></span>
                  <span class="ta-ih__brand-caption"><?php echo esc_html($settings['caption']); ?></span>
                <?php endif; ?>
              </a>
              <p class="ta-ih__signature">
                <?php foreach (preg_split('/\r\n|\r|\n/', $settings['tagline']) as $line) : ?>
                  <span><?php echo esc_html($line); ?></span>
                <?php endforeach; ?>
              </p>
              <button class="ta-ih__toggle" type="button" aria-expanded="false"
                      aria-controls="<?php echo esc_attr($uid); ?>" aria-label="Open navigation">
                <span class="ta-ih__toggle-lines" aria-hidden="true"></span>
              </button>
            </div>
            <nav class="ta-ih__nav" id="<?php echo esc_attr($uid); ?>" aria-label="Main navigation">
              <ul class="ta-ih__nav-list">
                <?php foreach ($children[0] ?? [] as $item) : ?>
                  <li>
                    <?php if ((int) $item->ID === $products_id && !empty($children[$products_id])) : ?>
                      <?php self::products($item, $children); ?>
                    <?php elseif (!empty($children[(int) $item->ID])) : ?>
                      <?php self::submenu($item, $children); ?>
                    <?php else : ?>
                      <?php self::link($item, 'ta-ih__link'); ?>
                    <?php endif; ?>
                  </li>
                <?php endforeach; ?>
              </ul>
            </nav>
          </div>
        </header>
        <?php
        return ob_get_clean();
    }

    private static function products($parent, $children) {
        $categories = $children[(int) $parent->ID];
        $total = self::count_items($categories, $children);
        $first = reset($categories);
        ?>
        <details class="ta-ih__products">
          <summary class="ta-ih__products-trigger">
            <?php echo esc_html(wp_strip_all_tags($parent->title)); ?>
            <span class="ta-ih__plus" aria-hidden="true"></span>
          </summary>
          <div class="ta-ih__dropdown">
            <div class="ta-ih__dropdown-inner">
              <div class="ta-ih__spotlight" aria-hidden="true">
                <div class="ta-ih__spotlight-top">
                  <span class="ta-ih__spotlight-label">Touch Apparel<br>Product directory</span>
                  <?php echo self::arrow('ta-ih__spotlight-icon'); ?>
                </div>
                <div>
                  <span class="ta-ih__spotlight-name" data-ta-preview-name><?php echo esc_html(wp_strip_all_tags($first->title)); ?></span>
                  <div class="ta-ih__spotlight-bottom">
                    <span class="ta-ih__spotlight-line"></span>
                    <span><span data-ta-preview-number>01</span> / <?php echo esc_html(self::number($total)); ?></span>
                  </div>
                </div>
              </div>
              <div class="ta-ih__directory">
                <div class="ta-ih__directory-heading">
                  <p class="ta-ih__directory-title">Explore our products</p>
                  <span class="ta-ih__directory-count"><?php echo esc_html(self::number($total)); ?> categories</span>
                </div>
                <ul class="ta-ih__categories">
                  <?php $index = 0; self::product_rows($categories, $children, $index); ?>
                </ul>
              </div>
            </div>
          </div>
        </details>
        <?php
    }

    private static function number($number) {
        return str_pad((string) $number, 2, '0', STR_PAD_LEFT);
    }

    private static function count_items($items, $children) {
        $count = count($items);
        foreach ($items as $item) {
            if (!empty($children[(int) $item->ID])) {
                $count += self::count_items($children[(int) $item->ID], $children);
            }
        }
        return $count;
    }

    private static function product_rows($items, $children, &$index) {
        foreach ($items as $item) {
            ++$index;
            echo '<li class="ta-ih__category">';
            self::link($item, 'ta-ih__category-link', self::number($index));
            if (!empty($children[(int) $item->ID])) {
                echo '<ul class="ta-ih__nested-categories">';
                self::product_rows($children[(int) $item->ID], $children, $index);
                echo '</ul>';
            }
            echo '</li>';
        }
    }

    private static function submenu($item, $children) {
        ?>
        <details class="ta-ih__secondary">
          <summary class="ta-ih__products-trigger"><?php echo esc_html(wp_strip_all_tags($item->title)); ?><span class="ta-ih__plus" aria-hidden="true"></span></summary>
          <ul class="ta-ih__secondary-list">
            <?php foreach ($children[(int) $item->ID] as $child) : ?>
              <li><?php
                if (!empty($children[(int) $child->ID])) self::submenu($child, $children);
                else self::link($child, 'ta-ih__link');
              ?></li>
            <?php endforeach; ?>
          </ul>
        </details>
        <?php
    }

    private static function link($item, $class, $number = '') {
        $url = esc_url($item->url);
        $active = $url && $url !== '#';
        $tag = $active ? 'a' : 'span';
        echo '<' . $tag . ' class="' . esc_attr($class) . '"';
        if ($active) {
            echo ' href="' . $url . '"';
            if ($item->target === '_blank') {
                echo ' target="_blank" rel="' . esc_attr(trim($item->xfn . ' noopener noreferrer')) . '"';
            } elseif ($item->xfn) {
                echo ' rel="' . esc_attr($item->xfn) . '"';
            }
            if (self::is_current($item)) echo ' aria-current="page"';
            if ($item->attr_title) echo ' title="' . esc_attr($item->attr_title) . '"';
        } else {
            echo ' aria-disabled="true"';
        }
        echo '>';
        if ($number !== '') {
            echo '<span class="ta-ih__category-number" aria-hidden="true">' . esc_html($number) . '</span>';
            echo '<span class="ta-ih__category-name">' . esc_html(wp_strip_all_tags($item->title)) . '</span>';
            echo self::arrow('ta-ih__category-arrow');
        } else {
            echo esc_html(wp_strip_all_tags($item->title));
            if (in_array(strtolower(trim(wp_strip_all_tags($item->title))), ['contact', 'contact us'], true)) {
                echo self::arrow('ta-ih__contact-arrow');
            }
        }
        echo '</' . $tag . '>';
    }

    private static function arrow($class) {
        return '<svg class="' . esc_attr($class) . '" viewBox="0 0 24 24" fill="none" aria-hidden="true" focusable="false"><path d="M5 19L19 5M5 5H19V19" stroke="currentColor" stroke-width="1.5"/></svg>';
    }

    private static function is_current($item) {
        if ($item->type === 'post_type' && is_singular()) {
            return (int) $item->object_id === get_queried_object_id();
        }
        if ($item->type === 'taxonomy' && (is_tax() || is_category() || is_tag())) {
            return (int) $item->object_id === get_queried_object_id();
        }
        return $item->type === 'custom' && is_front_page()
            && untrailingslashit($item->url) === untrailingslashit(home_url('/'));
    }

    public static function settings_link($links) {
        array_unshift($links, '<a href="' . esc_url(admin_url('options-general.php?page=taeh-header')) . '">Settings</a>');
        return $links;
    }

    public static function admin_menu() {
        add_options_page('Touch Apparel Header', 'Touch Apparel Header', 'manage_options', 'taeh-header', [__CLASS__, 'admin_page']);
    }

    public static function register_settings() {
        register_setting('taeh_header', self::OPTION, [
            'type' => 'array', 'sanitize_callback' => [__CLASS__, 'sanitize'],
        ]);
    }

    public static function sanitize($input) {
        $input = is_array($input) ? $input : [];
        $saved = self::settings();
        $menu_id = absint($input['menu_id'] ?? 0);
        $products_id = absint($input['products_id'] ?? 0);
        if ($menu_id !== (int) $saved['menu_id']) $products_id = 0;
        return [
            'menu_id' => $menu_id,
            'products_id' => $products_id,
            'location' => sanitize_text_field($input['location'] ?? ''),
            'tagline' => sanitize_textarea_field($input['tagline'] ?? ''),
            'brand_name' => sanitize_text_field($input['brand_name'] ?? '') ?: 'touch apparel',
            'caption' => sanitize_text_field($input['caption'] ?? ''),
            'use_site_logo' => empty($input['use_site_logo']) ? 0 : 1,
        ];
    }

    public static function admin_page() {
        if (!current_user_can('manage_options')) return;
        $settings = self::settings();
        $menu = self::menu($settings);
        $items = $menu ? wp_get_nav_menu_items($menu->term_id) : [];
        ?>
        <div class="wrap">
          <h1>Touch Apparel Header</h1>
          <p>Use your existing menu. Add its product categories as children of Products; the plugin never changes your menu.</p>
          <p>In Elementor, insert a <strong>Shortcode widget</strong> containing <code>[touch_apparel_header]</code>.</p>
          <?php settings_errors(); ?>
          <form method="post" action="options.php">
            <?php settings_fields('taeh_header'); ?>
            <table class="form-table" role="presentation">
              <tr>
                <th scope="row"><label for="taeh-menu">Navigation menu</label></th>
                <td>
                  <select id="taeh-menu" name="taeh_settings[menu_id]">
                    <option value="0" <?php selected((int) $settings['menu_id'], 0); ?>>Automatic: Main Menu, or the only available menu</option>
                    <?php foreach (wp_get_nav_menus() as $available) : ?>
                      <option value="<?php echo esc_attr($available->term_id); ?>" <?php selected((int) $settings['menu_id'], (int) $available->term_id); ?>><?php echo esc_html($available->name); ?></option>
                    <?php endforeach; ?>
                  </select>
                  <p class="description">Select your existing Main Menu. After changing menus, save before choosing a specific Products item below.</p>
                </td>
              </tr>
              <tr>
                <th scope="row"><label for="taeh-products">Products menu item</label></th>
                <td>
                  <select id="taeh-products" name="taeh_settings[products_id]">
                    <option value="0" <?php selected((int) $settings['products_id'], 0); ?>>Automatic: top-level item named Products</option>
                    <?php foreach (is_array($items) ? $items : [] as $item) : ?>
                      <?php if ((int) $item->menu_item_parent !== 0) continue; ?>
                      <option value="<?php echo esc_attr($item->ID); ?>" <?php selected((int) $settings['products_id'], (int) $item->ID); ?>><?php echo esc_html(wp_strip_all_tags($item->title)); ?></option>
                    <?php endforeach; ?>
                  </select>
                  <p class="description">The selected item's children become the product directory. Its own URL is replaced by the expandable Products control.</p>
                </td>
              </tr>
              <?php foreach (['brand_name' => 'Text logo', 'caption' => 'Logo caption', 'location' => 'Location / establishment text'] as $key => $label) : ?>
                <tr>
                  <th scope="row"><label for="taeh-<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></label></th>
                  <td><input class="regular-text" id="taeh-<?php echo esc_attr($key); ?>" name="taeh_settings[<?php echo esc_attr($key); ?>]" value="<?php echo esc_attr($settings[$key]); ?>"></td>
                </tr>
              <?php endforeach; ?>
              <tr>
                <th scope="row"><label for="taeh-tagline">Brand line</label></th>
                <td><textarea class="large-text" rows="3" id="taeh-tagline" name="taeh_settings[tagline]"><?php echo esc_textarea($settings['tagline']); ?></textarea><p class="description">Line breaks are preserved on desktop.</p></td>
              </tr>
              <tr>
                <th scope="row">Site logo</th>
                <td><label><input type="checkbox" name="taeh_settings[use_site_logo]" value="1" <?php checked((int) $settings['use_site_logo'], 1); ?>> Use the WordPress Site Logo when available</label><p class="description">Otherwise the approved text logo is used. The Elementor Site Settings logo may be stored separately; use the WordPress Site Logo for this integration.</p></td>
              </tr>
            </table>
            <?php submit_button(); ?>
          </form>
        </div>
        <?php
    }
}

Plugin::boot();
