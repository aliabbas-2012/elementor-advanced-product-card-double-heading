# Advanced Product Card + Double Heading (Elementor)

Custom WordPress plugin that adds two Elementor widgets:

- `Product Card Advanced`
- `Double Heading`

This plugin is useful when you need a highly customizable product-style card and a heading where one part is styled independently using a `<span>`.

## Features

### 1) Product Card Advanced

- Image, title, caption, price, divider, and corner banner controls
- Card-wide link (`href`) with optional open in new tab
- Card style controls (background, hover, border, padding)
- Image box style controls (background, border, margin, padding, height)
- Image fit/alignment controls
- Shape clipping options:
  - None, Circle, Ellipse, Triangle, Diamond, Hexagon, Pentagon, Star
  - Parallelogram with:
    - Direction: `Left Top (-)` or `Right Top (+)`
    - Lean slider (0-50)
  - Custom clip-path input
- Banner position and full style controls
- Typography/color/margin controls for title, caption, and price
- Divider style controls

### 2) Double Heading

Outputs one heading with two parts:

`<h1>Heading Part 1 <span>Heading Part 2</span></h1>`

- Selectable heading tag (`h1`-`h6`, `div`, `span`, `p`)
- Separate text inputs for heading part 1 and span part 2
- Span direction:
  - Same line
  - Down (next line)
- Separate style controls for heading and span:
  - Typography
  - Color
  - Margin (span)

If span typography is not set, it naturally inherits from the parent heading.

## Installation

1. Copy `custom-elementor-product-card.php` into a plugin folder, for example:
   - `wp-content/plugins/advanced-product-card-elementor/`
2. Make sure Elementor is installed and active.
3. Activate the plugin from **WordPress Admin > Plugins**.
4. Open Elementor editor and search for:
   - `Product Card Advanced`
   - `Double Heading`

## Usage

### Product Card Advanced

1. Drag the widget into your page.
2. Fill content fields (image, title, caption, price, etc.).
3. Optional: set **Card Link** and enable/disable **Open In New Tab**.
4. Customize card/image/banner/title/divider/text styles in the **Style** tab.

### Double Heading

1. Drag the widget into your page.
2. Enter:
   - `Heading Part 1`
   - `Heading Part 2 (Span)`
3. Choose heading tag.
4. Set span direction (same line or down).
5. Customize heading and span styles.

## Notes

- Built for Elementor widget system (`\Elementor\Widget_Base`).
- Includes output escaping for URL and attribute contexts.
- Tested with `php -l` syntax validation.

## Suggested GitHub Description

`Custom Elementor widgets for WordPress: advanced product card with shape clipping + clickable card link, and a double heading widget with independent span styling.`

## License

Use your preferred license (MIT, GPL-2.0-or-later, etc.) before publishing publicly.
