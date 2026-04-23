![Kirby Imagex Banner](/.github/imagex-banner.png)

# Kirby Imagex
**Modern Images for Kirby CMS** – Imagex helps you orchestrate modern, responsive and performant images in Kirby.


## Features
- 🌠 Dynamic generation of image srcsets for art-directed or media-condition-based images.
- 📐 Helps you easily change the aspect ratio without the need to define new srcset presets.
- 😴 Supports native lazy loading and is customizable for JavaScript lazy loading libraries.
- 🚀 Improve the performance of your critical LCP (Largest Contentful Paint) images, utilizing `Priority Hints`.
- ⚡️ Supports multiple modern image formats, like AVIF and WebP.
- 🔀 Supports server-side content negotiation. Generates AVIF/WebP variants while outputting less HTML with extension-less URLs.
- 🧩 Can easily be integrated as a snippet into existing blocks/projects.
- 🪄 Uses only Kirby's core capabilities for image handling.

## Getting Started
Four steps to get Imagex running:

1. [Install via Composer](#installation-via-composer)
2. [Set up Imagex global plugin config](#global-configuration)
3. [Adjust Kirby's thumbs config and add srcset presets](#adjust-kirbys-thumbs-config-and-add-srcset-presets)
4. [Configure and pass options to the Imagex snippet](#snippet-configuration-and-usage)

### Examples for Different Configurations and HTML Output
You'll find all examples in the [examples.md](/docs/examples/README.md).

## Installation via Composer
```
composer require timnarr/kirby-imagex
```

## Global Configuration
Configure global settings in your `config.php` file:
```php
return [
  'timnarr.imagex' => [
    'cache' => true,
    'compareFormatsWeights' => 'mobile',
    'contentNegotiation' => false,
    'customLazyloading' => false,
    'formats' => ['avif', 'webp'],
    'addOriginalFormatAsSource' => false,
    'noSrcsetInImg' => false,
    'relativeUrls' => false,
  ],
];
```

### Global Options

| Option | Default | Type | Description |
| ------ | ------- | ---- | ----------- |
| `cache` | `true` | Boolean | Imagex will cache some calculations. Read more about it here: "[Cache](#cache)" |
| `compareFormatsWeights` | `'mobile'` | String or Array | Controls the weighting used when comparing format sizes via `compareFormats`. Preset strings: `'mobile'` (50/30/20), `'desktop'` (20/30/50), `'balanced'` (34/33/33). For custom weights pass an array: `['small' => 0.4, 'medium' => 0.4, 'large' => 0.2]` — values must sum to `1.0`. Read more: "[Dynamic Format Size Handling](#dynamic-format-size-handling)". |
| `contentNegotiation` | `false` | Boolean | Delegates format selection to the web server instead of the browser. When enabled, all format variants (AVIF, WebP) are generated on disk but the HTML output contains only the original format — no `<source type="...">` elements. The server inspects the browser's `Accept` header and serves the best available file transparently. Art direction remains fully supported. Cannot be combined with `compareFormats`. **Requires web server configuration** (Apache, Nginx, or Caddy rewrite rules). Read more: "[Content Negotiation](#content-negotiation)". |
| `customLazyloading` | `false` | Boolean | Imagex will initially use native lazy loading with the `loading` attribute. Enable this option if you want to use a custom lazy loading library like lazysizes or any other JS-based solution. Imagex will then automatically use `data-src` and `data-srcset`. If you need something like `data-sizes="auto"` please use the snippet options to add it as a lazy HTML attribute. |
| `formats` | `['avif', 'webp']` | Array with Strings | Define the modern image formats you want to use. ⚠️ Order matters here! You should go from the most to less modern format. The order in this array also affects the `compareFormats` snippet-option. [Read more about why the correct order is important](#why-order-matters). You **shouldn't add the original image format here** like PNG or JPEG. |
| `addOriginalFormatAsSource` | `false` | Boolean | Adds a `<source>` element for the image's original format (e.g. JPEG, PNG). Useful when modern formats like AVIF or WebP can't be used, but you still need art-directed picture sources at different breakpoints / media conditions. |
| `noSrcsetInImg` | `false` | Boolean | If active this will only output the `src` attribute in the `<img>` tag. The smallest size from the given srcset-preset is used and the `srcset` attribute is omitted. |
| `relativeUrls` | `false` | Boolean | Output relative image URLs everywhere when active. |

## Adjust Kirby's Thumbs Config and Add Srcset Presets
The srcset configuration is still set up as you know it from Kirby, [like described here](https://getkirby.com/docs/reference/objects/cms/file/srcset#define-presets__extended-example).

Set your srcset preset, like `my-srcset`, and define only the `width` — no `height` needed. Imagex calculates the height from the `ratio` you pass to the snippet, so one preset works for any aspect ratio. Switching from `16/9` to `1/1` is a one-line change, no new presets needed.

If you use AVIF and/or WebP, add a separate preset per format by copying the base preset, appending `-{format}` to the name, and setting the `format` option. This gives you full control over quality per format.

The quality settings for the modern formats shouldn't simply be taken from the default preset. To really benefit from smaller image files due to modern formats, you should slightly adjust your quality settings. My rule of thumb is to take the quality value from JPEG/PNG and reduce it by 5 for WebP and 15 for AVIF. Here is a good [read](https://www.industrialempathy.com/posts/avif-webp-quality-settings/) with more detail, but I've also noticed that this isn't completely applicable to images in Kirby.

```php
// config.php
'thumbs' => [
  'srcsets' => [
    'my-srcset' => [ // preset for JPEG and PNG
      '400w'  => ['width' =>  400, 'crop' => true, 'quality' => 80],
      '800w'  => ['width' =>  800, 'crop' => true, 'quality' => 80],
      '1200w' => ['width' => 1200, 'crop' => true, 'quality' => 80],
    ],
    'my-srcset-webp' => [ // preset for webp
      '400w'  => ['width' =>  400, 'crop' => true, 'quality' => 75, 'format' => 'webp', 'sharpen' => 10],
      '800w'  => ['width' =>  800, 'crop' => true, 'quality' => 75, 'format' => 'webp', 'sharpen' => 10],
      '1200w' => ['width' => 1200, 'crop' => true, 'quality' => 75, 'format' => 'webp', 'sharpen' => 10],
    ],
    'my-srcset-avif' => [ // preset for avif
      '400w'  => ['width' =>  400, 'crop' => true, 'quality' => 65, 'format' => 'avif', 'sharpen' => 25],
      '800w'  => ['width' =>  800, 'crop' => true, 'quality' => 65, 'format' => 'avif', 'sharpen' => 25],
      '1200w' => ['width' => 1200, 'crop' => true, 'quality' => 65, 'format' => 'avif', 'sharpen' => 25],
    ],
    // other srcset definitions
```

## Snippet Configuration and Usage
Pass the file object of your image and other options to the Imagex snippet as follows:

```php
// Define your options and pass them to the `imagex-picture` snippet
<?php
$options = [
  'image' => $image->toFile(),
  'attributes' => [
    'img' => [
      'class' => ['my-image'],
      'style' => ['background: red;'],
      'sizes' => '100vw',
    ],
  ],
  'ratio' => '16/9',
  'srcset' => 'my-srcset',
  'loading' => 'lazy',
];
?>

<?php snippet('imagex-picture', $options) ?>
```

Imagex outputs a `<picture>` element with `<source>` elements and one `<img>`. If you need extra HTML you can wrap the Imagex snippet accordingly. Handle `svg` or `gif` files differently as needed!

```php
<?php

$options = [
  'image' => $image->toFile(),
  // ... other imagex options
];

?>

<figure>
  <?php if ($image->extension() === 'svg' || $image->extension() === 'gif'): ?>
    <?php snippet('svg-gif-image') ?> // handle svg and gif files differently
  <?php else: ?>
    <?php snippet('imagex-picture', $options) ?>
  <?php endif; ?>
  <figcaption>Lorem ipsum</figcaption>
</figure>
```

### JSON Output
If you need JSON output instead of HTML markup (for headless CMS setups, API endpoints, or JavaScript-driven rendering), use the `imagex-picture-json.php` snippet:

```php
<?php
$options = [
  'image' => $image->toFile(),
  'ratio' => '16/9',
  'srcset' => 'my-srcset',
  // ... all other options work the same
];

$json = snippet('imagex-picture-json', $options, true);
echo $json;
?>
```

The JSON output contains structured data with `picture` (including nested `sources`) and `img` attributes. See the [JSON output example](/docs/examples/json-output.md) for more details.

### Snippet Options
Only `image` is required — everything else has sane defaults.

- **Flat attribute syntax:** Attributes passed directly to `picture`/`img`/`sources` are auto-treated as `shared`. Use the full `shared`/`eager`/`lazy` structure when you need loading-mode-specific values.
- **Override anything:** Developer-defined attributes always win. `class` and `style` are merged; all others are replaced. Defaults: `loading: 'eager'` → `fetchpriority: 'high'`; `decoding: 'async'`.
- **`class` and `style` accept strings or arrays** — strings are auto-converted (`'foo bar'` → `['foo', 'bar']`). `null`/`false` values inside arrays are filtered out automatically, useful for conditional classes: `$condition ? 'my-class' : null`.
- ⚠️ When overriding `width`, `height`, `src`, or `srcset`, you are responsible for keeping dimensions consistent with your ratio. [See details here](#overriding-default-attributes).

| Option | Default | Type | Description |
| ------ | ------- | ---- | ----------- |
| `image` | – | File-Object | Required. Your initial image. Be sure to return a file object here: `$imageField->toFile()`. |
| `loading` | `'lazy'` | String | Set to `'eager'` or `'lazy'`. If an image is placed "above-the-fold" it should use `'eager'`. When `'eager'`, Imagex disables lazy loading and sets `fetchpriority="high"` automatically. You can add your logic here to determine if an image is critical, for example: Let the editor choose in the panel by adding a toggle field or query the index of your image blocks. |
| `srcset` | `'default'` | String | Name of the srcset preset (e.g. `'my-srcset'`, without format suffix), configured in [Kirby's config](#adjust-kirbys-thumbs-config-and-add-srcset-presets). Imagex automatically resolves the format-specific variants (`my-srcset-webp`, `my-srcset-avif`) — you only pass the base name. |
| `ratio` | `'intrinsic'` | String | Set the desired aspect ratio here. Can be omitted, default is `intrinsic`, which means the ratio of the provided image is used. Pass your ratio in this format: `x/y`. |
| `attributes` | `[]` | Array | HTML attributes grouped by element: `picture`, `img`, `sources`. Each can be flat (auto-converted to `shared`) or use the full `shared`/`eager`/`lazy` structure for loading-mode-specific attributes. |
| `artDirection` | `[]` | Array | Art-directed sources with `media`, `ratio`, `image`, and `attributes` options. Order matters! Browsers use the first `<source>` with a matching media condition. Order length-based media queries from large to small. Per entry: `image` is optional — omit it to reuse the main `image` at a different ratio without needing a second file. `ratio` is optional — falls back to `'intrinsic'` (not the base `ratio`). |
| `compareFormats` | `false` | Boolean | In some cases AVIF files can be larger than WebP. If this option is set to true, it enables a dynamic size comparison between the specified image formats. ⚠️ The `formats` order in `config.php` matters here! The comparison weighting can be configured globally via `compareFormatsWeights`. [Read more about it here](#dynamic-format-size-handling). |

```php
<?php
$options = [
  'image' => $image->toFile(),
  'loading' => $isCritical ? 'eager' : 'lazy',
  'srcset' => 'my-srcset',
  'ratio' => '1/1',

  'attributes' => [
    // Simple flat syntax (auto-converted to 'shared')
    'picture' => [
      'class' => ['my-picture-class'],
      'data-attr' => 'my-picture-attribute',
    ],

    // Full syntax with loading-mode-specific attributes
    'img' => [
      'shared' => [
        'class' => [
          'my-image-class',
          $setThisClassWhenTrue ? 'optional-class' : null
        ],
        'alt' => $image->alt(),
        'style' => ['background-color: red;', 'object-fit: cover;', 'object-position: ' . $image->focus() . ';'],
        'data-attr' => 'my-img-attribute',
        'sizes' => '760px',
      ],
      'eager' => [
        // extend `shared` attributes in eager loading mode
      ],
      'lazy' => [
        // extend `shared` attributes in lazy loading mode
        'class' => ['js-lazyload'],
      ],
    ],
  ],

  // Art direction with media query in each item
  'artDirection' => [
    [
      'media' => '(min-width: 1200px)',
      'ratio' => '21/9',
    ],
    [
      'media' => '(min-width: 820px)',
      'image' => $artDirectedImage,
    ],
    [
      'media' => '(prefers-color-scheme: dark)',
      'ratio' => '16/9',
      'image' => $darkModeImage,
    ],
    [
      'media' => '(orientation: landscape)',
      'ratio' => '21/9',
      'attributes' => ['shared' => ['data-landscape' => 'true']],
    ],
  ],
];

// Pass your options to the `imagex-picture` snippet
<?php snippet('imagex-picture', $options) ?>
```

### Overriding Default Attributes
Common reasons to override: provide a larger `src` for crawlers/SEO (they only read `<img src>`), set a LQIP placeholder for custom lazy loading, or change `loading`/`fetchpriority` for a specific image.

**`src` as fallback:** Modern browsers always prefer images from `srcset`/`<source>`. You can safely override `src` with a larger or differently-cropped image for SEO without affecting browser rendering.

```php
<?php
// Provide a larger image for crawlers while keeping optimized srcset for browsers
$options = [
  'image' => $image->toFile(),
  'attributes' => [
    'img' => [
      'src' => $image->thumb(['width' => 1200, 'quality' => 85])->url(), // large image for crawlers
      'alt' => $image->alt(),
    ],
  ],
  'srcset' => 'my-srcset',
];

snippet('imagex-picture', $options);
```

**Ratio consistency:** When you override `width`, `height`, `src`, or `srcset`, ensure your custom values match each other and the image ratio to avoid layout shifts. Use `null` or `false` to remove an attribute entirely. In most cases you should let Imagex handle dimensions automatically.

See [overriding-attributes.md](/docs/examples/overriding-attributes.md) for more examples including LQIP placeholders and dimension overrides.

## File Methods

Imagex registers additional methods on Kirby's `File` object.

### `thumbRatio(string $ratio, array $options = [])`

Generates a thumb whose dimensions are derived from an aspect ratio string instead of having to calculate the height manually.

| Parameter | Type | Description |
| --------- | ---- | ----------- |
| `$ratio` | String | Aspect ratio in `'x/y'` format (e.g. `'16/9'`), or `'intrinsic'` to use the image's natural ratio. |
| `$options` | Array | Standard Kirby thumb options (`width`, `quality`, `format`, …). `crop` defaults to `true`. |

**Returns** the thumb `File` object, so you can chain `->url()`, `->width()`, etc.

The primary use case is overriding `src` inside Imagex while keeping the ratio consistent — e.g. for SEO fallback images. Pass the same `$ratio` variable to both Imagex and `thumbRatio`, so the fallback `src` always matches the srcset aspect ratio:

```php
<?php
$ratio = '16/9';

$options = [
  'image' => $image->toFile(),
  'ratio' => $ratio,
  'srcset' => 'my-srcset',
  'attributes' => [
    'img' => [
      'shared' => [
        'src' => $image->toFile()->thumbRatio($ratio, ['width' => 1200])->url(),
      ],
    ],
  ],
];
?>

<?php snippet('imagex-picture', $options) ?>
```

See the [thumbRatio example](/docs/examples/thumb-ratio.md) for more details.

## Cache
Imagex caches two types of expensive calculations:

- **Srcset config**: The calculated heights per srcset entry (based on width and ratio) are cached so repeated use of the same preset/ratio combination avoids redundant work.
- **Format comparison**: When `compareFormats` is enabled, the result of the weighted format size comparison is cached per image. The cache key includes the image ID, its last-modified timestamp, the ratio, the srcset preset, and the active formats — so the cache automatically invalidates whenever the image is replaced or updated.

## Performance Improvements for Critical Images
Imagex provides features like Priority Hints for improving the loading times of critical images.

### Automatic Width & Height (CLS Prevention)
Imagex automatically sets `width` and `height` attributes on every `<img>` and `<source>` element. The values are derived from the srcset preset and the `ratio` you pass to the snippet — no manual configuration needed. This lets the browser reserve the correct amount of space before the image loads, preventing [Cumulative Layout Shift (CLS)](https://web.dev/articles/cls).

### Async Decoding
Imagex sets `decoding="async"` on every `<img>` by default. This allows the browser to decode the image off the main thread, keeping interactions smooth. You can override this via `attributes.img` if needed.

### Priority Hints
Imagex will set the priority hint `fetchpriority="high"` to critical images to get the browser to load it sooner. Imagex sets this automatically when you use `'loading' => 'eager'`. You can override this behavior via `attributes.img`. Read more about [fetchpriority here](https://web.dev/articles/fetch-priority#the_fetchpriority_attribute).

## Why Order Matters?
### Format Order and Media Attribute
The order of `<source>` elements in a `<picture>` element is essential, as browsers select the first matching source based on supported formats and media conditions. Modern formats like AVIF should be listed first, falling back to formats like WebP or JPEG. Imagex will follow the order of the formats defined in the config and you should go from the most to less modern format: `'formats' => ['avif', 'webp']`.

The `media` attribute is also important for responsive designs or art-directed images. With the media attribute you can specify the conditions under which each source should be used. This is important if you want to switch the ratio or the complete image at a specific media condition. You have to take care about the ordering of your `sources` array in the plugin options that you pass to the Imagex snippet. Imagex will create for each format all defined sources.

## Dynamic Format Size Handling
In some cases AVIF files can be larger than WebP and you end up sending larger files and more HTML to the user. If `compareFormats` is set to true, this option enables a dynamic size comparison between the specified image formats. The comparison is based on the order of the formats listed in the format array of your configuration. With the default `formats` array, this option checks whether AVIF is smaller than WebP and only outputs or creates files for AVIF if it's smaller. So again the order of your formats array matters for this feature.

### How Format Comparison Works

Imagex uses a **weighted multi-sample approach** to determine the smallest format:

1. **Multiple Samples**: Instead of checking just one size, Imagex samples three srcset widths:
   - First (smallest width)
   - Middle
   - Last (largest width)

   ⚠️ **This requires Kirby to generate up to three thumbs per format on the first request.** Kirby needs to create the thumb files on disk before their file sizes can be measured, and the comparison result is then stored in the Imagex cache. Subsequent requests are served from cache, but the initial render can be slow — especially with many formats or art-directed images using different source files. Make sure caching is enabled.

   ⚠️ **At least three srcset entries are recommended** for meaningful weighting. With only two entries, the middle and last sample are identical, so the `medium` and `large` weights effectively collapse into one. With a single entry all three samples are the same and weighting has no effect at all.

2. **Configurable Weighting**: The samples are weighted to reflect your audience's typical screen sizes. Configure this globally via `compareFormatsWeights` in your `config.php`. Available presets:
   - `'mobile'` (default) — 50% smallest, 30% middle, 20% largest
   - `'desktop'` — 20% smallest, 30% middle, 50% largest
   - `'balanced'` — roughly equal weight across all three
   - Custom array — `['small' => 0.4, 'medium' => 0.4, 'large' => 0.2]`

3. **Per-Image Comparison for Art Direction**: When using `artDirection` with different source images, each image is compared individually. This means one art-directed image might use AVIF while another uses WebP, depending on which format is smaller for each specific image.

4. **Combining with `addOriginalFormatAsSource`**: When `addOriginalFormatAsSource` is enabled, the original format (e.g. JPEG or PNG) is included in the comparison alongside the modern formats. This can be useful when your source images are already well-optimised and a modern format isn't guaranteed to be smaller. Note that the original format always uses the base srcset preset — make sure its quality settings are comparable to the modern format presets, otherwise the comparison may be skewed.

## Content Negotiation
Content negotiation is an alternative to `<picture>`/`<source>` format switching. Instead of the browser choosing from a list of `<source type="image/avif">` and `<source type="image/webp">` elements, the web server reads the browser's `Accept` header and transparently serves the best available file — AVIF, WebP, or JPEG — for every image URL.

Enable it globally in `config.php`:
```php
'timnarr.imagex' => [
  'contentNegotiation' => true,
  'formats' => ['avif', 'webp'], // still generated on disk, not output in HTML
],
```

When active:
- All format variants are still generated on disk as a side-effect (Kirby's thumb pipeline runs for every configured format).
- The HTML output contains no format-based `<source>` elements and no `type` attributes.
- Art direction (`artDirection`) continues to work — one `<source media="...">` per breakpoint is output instead of one per format per breakpoint.
- Cannot be combined with `compareFormats: true` — format selection is the server's responsibility.

**⚠️ Requires web server configuration.** Imagex outputs extension-less URLs (e.g. `/media/image-400x267`). Your server resolves these to the best available format. Example rules for Apache `.htaccess`:

```apache
<IfModule mod_rewrite.c>
  RewriteEngine On

  RewriteCond %{REQUEST_URI} ^/media/
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteCond %{HTTP_ACCEPT} image/avif
  RewriteCond %{DOCUMENT_ROOT}%{REQUEST_URI}.avif -f
  RewriteRule ^(.+)$ $1.avif [T=image/avif,L,E=img_neg:1]

  RewriteCond %{REQUEST_URI} ^/media/
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteCond %{HTTP_ACCEPT} image/webp
  RewriteCond %{DOCUMENT_ROOT}%{REQUEST_URI}.webp -f
  RewriteRule ^(.+)$ $1.webp [T=image/webp,L,E=img_neg:1]

  RewriteCond %{REQUEST_URI} ^/media/
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteCond %{DOCUMENT_ROOT}%{REQUEST_URI}.jpg -f
  RewriteRule ^(.+)$ $1.jpg [T=image/jpeg,L]

  RewriteCond %{REQUEST_URI} ^/media/
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteCond %{DOCUMENT_ROOT}%{REQUEST_URI}.png -f
  RewriteRule ^(.+)$ $1.png [T=image/png,L]
</IfModule>

<IfModule mod_headers.c>
  Header append Vary Accept env=img_neg
</IfModule>
```

Always set `Vary: Accept` so CDNs and shared caches store separate responses per accepted format.

See the [Content Negotiation example](/docs/examples/content-negotiation.md) for full examples including Nginx and Caddy configurations.

## Roadmap / Ideas
- [ ] Add tests for Imagex class
- [ ] Use Preload Resource Hints?! See [feature-branch](https://github.com/timnarr/kirby-imagex/tree/feature/preload-links)

## License
[MIT License](./LICENSE) Copyright © 2024-present Tim Narr
