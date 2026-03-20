![Kirby Imagex Banner](/.github/imagex-banner.png)

# Kirby Imagex
**Modern Images for Kirby CMS** – Imagex helps you orchestrate modern, responsive and performant images in Kirby.


## Features
- 🌠 Dynamic generation of image srcsets for art-directed or media-condition-based images.
- 📐 Helps you easily change the aspect ratio without the need to define new srcset presets.
- 😴 Supports native lazy loading and is customizable for JavaScript lazy loading libraries.
- 🚀 Improve the performance of your critical LCP (Largest Contentful Paint) images, utilizing `Priority Hints`.
- ⚡️ Supports multiple modern image formats, like `avif` and `webp`.
- 🧩 Can easily be integrated into existing blocks/projects.
- 🪄 Uses only Kirby's core capabilities for image handling.

## Getting Started
Four steps to get Imagex running:

1. [Install via Composer](#installation-via-composer)
2. [Set up Imagex global plugin config](#global-configuration)
3. [Adjust Kirby's thumbs config and add srcset presets](#adjust-kirbys-thumbs-config-and-add-srcset-presets)
4. [Add a snippet where you configure and pass options to the Imagex snippet](#snippet-configuration-and-usage)

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
    'customLazyloading' => false,
    'formats' => ['avif', 'webp'],
    'includeInitialFormat' => false,
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
| `customLazyloading` | `false` | Boolean | Imagex will initially use native lazy loading with the `loading` attribute. Enable this option if you want to use a custom lazy loading library like lazysizes or any other JS-based solution. Imagex will then automatically use `data-src` and `data-srcset`. If you need something like `data-sizes="auto"` please use the snippet config to add it as a lazy HTML attribute. |
| `formats` | `['avif', 'webp']` | Array with Strings | Define the modern file formats you want to use. ⚠️ Order matters here! You should go from the most to less modern format. The order in this array also affects the `compareFormats` **snippet-option**. [Read more about why the correct order is important](#why-order-matters). You shouldn't add the initial image format like `png` or `jpeg` here. |
| `includeInitialFormat` | `false` | Boolean | If active the format of the uploaded image (normally jpeg or png) will be treated as a modern format, which means Imagex will create `<source>` tags for it. This is especially useful when you can't use modern formats, but want to use art directed images. |
| `noSrcsetInImg` | `false` | Boolean | If active this will only output the `src` attribute in the `<img>` tag. The smallest size from the given srcset-preset is used and the `srcset` attribute is omitted. |
| `relativeUrls` | `false` | Boolean | Output relative image URLs everywhere when active. |

## Adjust Kirby's Thumbs Config and Add Srcset Presets
The srcset configuration is still set up as you know it from Kirby, [like described here](https://getkirby.com/docs/reference/objects/cms/file/srcset#define-presets__extended-example).

Set your srcset preset, like `my-srcset`, and define only the `width` — no `height` needed. Imagex calculates the height from the `ratio` you pass to the snippet, so one preset works for any aspect ratio. Switching from `16/9` to `1/1` is a one-line change, no new presets needed. This first srcset preset is used for `jpeg` or `png` images.

```php
// config.php
'thumbs' => [
  'srcsets' => [
    'my-srcset' => [
      '400w'  => ['width' =>  400, 'crop' => true, 'quality' => 80],
      '800w'  => ['width' =>  800, 'crop' => true, 'quality' => 80],
      '1200w' => ['width' => 1200, 'crop' => true, 'quality' => 80],
    ],
    // other srcset definitions
```

If you use `avif` and/or `webp`, you must have a separate srcset preset for these formats. Copy the preset and append `-{format}` to the name and then set the `format` and other options for this preset. This is necessary to have full control and to define different quality values or other options for each modern format.

The quality settings for the modern formats shouldn't simply be taken from the initial preset. To really benefit from smaller image files due to modern formats, you should slightly adjust your quality settings. My rule of thumb is to subtract 5 from the jpeg/png quality for webp and to subtract 15 for avif. Here is a good [read](https://www.industrialempathy.com/posts/avif-webp-quality-settings/) with more detail, but I've also noticed that this isn't completely applicable to images in Kirby.

```php
// config.php
'thumbs' => [
  'srcsets' => [
    'my-srcset' => [ // preset for jpeg and png
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
// Define your options and pass them to the `imagex` snippet
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

Imagex outputs a `<picture>` element with multiple `<source>` elements and one `<img>`. If you need extra HTML you can wrap the Imagex snippet accordingly. Handle `svg` or `gif` files differently as needed!

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
You can choose from many options to customize your images and pass them to the Imagex snippet. At first it might look heavy, but it's just very flexible and actually only `image` is required and everything else can be omitted, while Imagex is providing some sane defaults.

**Simplified Attribute Syntax:** You can now pass attributes directly without the `shared`/`eager`/`lazy` structure. Flat attributes are automatically treated as `shared` attributes. If you need different attributes for eager vs lazy loading, you can still use the full structure.

**All attributes can be overridden:** User-defined attributes always take precedence over default attributes generated by Imagex. This means you can override any attribute, including `src`, `srcset`, `loading`, `width`, `height`, `fetchpriority`, `decoding` and others. If you don't specify an attribute, Imagex will use its default values as fallback. For `class` and `style` attributes, user values are merged with defaults rather than replaced.

**Default behaviors:**
- `loading: 'eager'` automatically sets `fetchpriority: 'high'`
- `loading: 'lazy'` does not set `fetchpriority`
- `decoding` defaults to `'async'`
- All these defaults can be overridden via `attributes.img`

⚠️ **Note:** When overriding dimension-related attributes (`width`, `height`, `src`, `srcset`), be aware that Imagex calculates dimensions based on the `ratio` parameter. Overriding these attributes makes you responsible for maintaining consistency between dimensions and image sources. [See detailed explanation below](#overriding-default-attributes).

| Option | Default | Type | Description |
| ------ | ------- | ---- | ----------- |
| `image` | – | File-Object | Required. Your initial image. Be sure to return a file object here: `$field->toFile()`. |
| `loading` | `'lazy'` | String | Set to `'eager'` or `'lazy'`. If an image is placed "above-the-fold" it should use `'eager'`. When `'eager'`, Imagex disables lazy loading and sets `fetchpriority="high"` automatically. You can add your logic here to determine if an image is critical, for example: Let the editor choose in the panel by adding a toggle field or query the index of your image blocks. |
| `srcset` | `'default'` | String | Name of the srcset preset, configured in [Kirby's config](#adjust-kirbys-thumbs-config-and-add-srcset-presets). |
| `ratio` | `'intrinsic'` | String | Set the desired aspect ratio here for non-art-directed images. Can be omitted, default is `intrinsic`, which means the ratio of the source-image is used. |
| `attributes` | `[]` | Array | HTML attributes grouped by element: `picture`, `img`, `sources`. Each can be flat (auto-converted to `shared`) or use the full `shared`/`eager`/`lazy` structure for loading-mode-specific attributes. |
| `artDirection` | `[]` | Array | Art-directed sources with `media`, `ratio`, `image`, and `attributes` options. Order matters! Browsers use the first matching `<source>`. Order length-based media queries from large to small. You can change the ratio or use a different image for each media condition. |
| `compareFormats` | `false` | Boolean | In some cases `avif` files can be larger than `webp`. If this option is set to true, it enables a dynamic size comparison between the specified image formats. ⚠️ The `formats` order in `config.php` matters here! The comparison weighting can be configured globally via `compareFormatsWeights`. [Read more about it here](#dynamic-format-size-handling). |


```php
<?php
$options = [
  'image' => $image,
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

// Pass your options to the Imagex snippet
<?php snippet('imagex-picture', $options) ?>
```

### Overriding Default Attributes
You can override any attribute that Imagex generates by default. User-defined attributes always take precedence.

#### Why Override Attributes?

While Imagex handles most scenarios automatically, there are practical reasons to override certain attributes:

**1. Improved SEO and Social Media Sharing**
Many crawlers (Google, Facebook, Twitter) don't fully understand `<picture>` elements or modern formats and only read the `src` attribute of the `<img>` tag. By default, Imagex uses the smallest image from your srcset as `src` (e.g., 400px width). Overriding `src` with a larger, high-quality image ensures better:
- Google Image Search results
- Social media previews (Open Graph, Twitter Cards)
- SEO rankings with higher-quality images

**2. Custom Lazy Loading Strategies**
Override `src` with a low-quality image placeholder (LQIP) or blur-up effect for custom lazy loading implementations.

**3. Special Loading Behavior**
Override `loading` or other attributes for specific images that need different behavior than the global settings.

#### Basic Example

```php
<?php
$options = [
  'image' => $image,
  'attributes' => [
    'img' => [
      'shared' => [
        'width' => 500,  // Override default width
        'height' => 300, // Override default height
      ],
      'lazy' => [
        'data-src' => null|false, // Use null or false to remove default attributes
        'src' => 'custom-placeholder.jpg', // Override default src
        'loading' => 'custom-lazy', // Override lazy loading behavior
      ],
    ],
  ],
  'srcset' => 'my-srcset',
];

snippet('imagex-picture', $options);
```

In the basic example above:
- `width` and `height` will be set to your custom values instead of the calculated defaults
- `src` will use your custom placeholder image
- `loading` attribute will be set to `eager` even though Imagex would normally set it to `lazy`
- Attributes not specified by you (like `decoding`, `fetchpriority`, etc.) will still use Imagex defaults

#### SEO-Optimized Example

```php
<?php
// Provide a larger image for crawlers while keeping optimized srcset for browsers
$options = [
  'image' => $image,
  'attributes' => [
    'img' => [
      'src' => $image->thumb(['width' => 1200, 'quality' => 85])->url(), // Large image for crawlers
      'alt' => $image->alt(),
    ],
  ],
  'srcset' => 'my-srcset', // Srcset will still use optimized sizes (400w, 800w, etc.)
];

snippet('imagex-picture', $options);
```

In this SEO example:
- Modern browsers use the optimized `srcset` with modern formats (avif, webp) and appropriate sizes
- Crawlers and social media bots get a high-quality 1200px image from the `src` attribute (which serves as a fallback)
- Best of both worlds: Fast loading for users, quality images for SEO

**Note on src as fallback:** The `src` attribute primarily serves as a fallback for very old browsers and is what crawlers read. Modern browsers will always prefer images from `srcset` or `<source>` elements. This means you can safely override `src` with a different aspect ratio or larger size for SEO purposes without affecting the user experience, as browsers will load the correctly-sized images from your `srcset`.

**Example - Different ratio for SEO:**
```php
<?php
// Use 16:9 for browsers, but 1:1 for SEO/social media
$options = [
  'image' => $image,
  'ratio' => '16/9', // Browser gets 16:9 images via srcset
  'attributes' => [
    'img' => [
      // Crawlers get a square image
      'src' => $image->thumb(['width' => 1200, 'height' => 1200, 'crop' => true])->url(),
    ],
  ],
  'srcset' => 'my-srcset',
];
```
Result: Browsers display 16:9 images (from srcset), crawlers and social media get 1:1 image (from src).

**Important Note on Ratio Handling:**
Imagex automatically calculates image dimensions based on the specified `ratio` parameter. When you override dimension-related attributes (`width`, `height`, `src`, or `srcset`), you become responsible for maintaining consistency:

- **Overriding `width`/`height`**: The `srcset` will still use thumbnails generated with the original ratio, which may not match your custom dimensions.
- **Overriding `src`/`srcset`**: The `width` and `height` attributes will still reflect the calculated ratio, which may not match your custom images.

If you override these attributes, ensure that your custom values are consistent with each other and with the aspect ratio of your images to avoid layout shifts or distorted images.

**Example - Correct way to override dimensions:**
```php
<?php
// If you need custom dimensions, override all related attributes together
$options = [
  'image' => $image,
  'attributes' => [
    'img' => [
      'shared' => [
        'width' => 600,
        'height' => 400, // Make sure this matches your desired ratio (3:2 in this case)
      ],
      'lazy' => [
        // If you also override srcset, make sure your images have the same 3:2 ratio
        'srcset' => 'custom-300.jpg 300w, custom-600.jpg 600w, custom-900.jpg 900w',
      ],
    ],
  ],
  'srcset' => 'my-srcset',
  'ratio' => '3/2', // This ratio is now only used for generating the default thumbnails
];
```

Note: In most cases, you should **not** need to override `width`, `height`, or `srcset`. Let Imagex handle these automatically based on your `ratio` parameter for best results.

## File Methods

Imagex registers additional methods on Kirby's `File` object.

### `thumbRatio(string $ratio, array $options = [])`

Generates a thumb whose dimensions are derived from an aspect ratio string instead of having to calculate the height manually.

| Parameter | Type | Description |
| --------- | ---- | ----------- |
| `$ratio` | String | Aspect ratio in `'x/y'` format (e.g. `'16/9'`), or `'intrinsic'` to use the image's natural ratio. |
| `$options` | Array | Standard Kirby thumb options (`width`, `quality`, `format`, …). `crop` defaults to `true`. |

**Returns** the thumb `File` object, so you can chain `->url()`, `->width()`, etc.

#### Use Case: Consistent `src` Override

The primary use case is overriding the `src` attribute inside Imagex while keeping the ratio consistent with the rest of the picture element — for example for SEO-optimised fallback images or custom lazy-loading placeholders:

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
        'class' => $classImage ?? [],
        // Override src with a ratio-consistent, high-quality thumb for crawlers
        'src' => $image->toFile()->thumbRatio($ratio, ['width' => 1200])->url(),
      ],
    ],
  ],
];
?>

<?php snippet('imagex-picture', $options) ?>
```

Because `thumbRatio` accepts the same `$ratio` variable you already pass to Imagex, the fallback `src` and the srcset images always have the same aspect ratio — no manual height calculation needed.

#### Further Examples

```php
// 16:9 thumb at 800px width (crop: true by default)
$image->toFile()->thumbRatio('16/9', ['width' => 800])->url()

// 1:1 thumb with custom quality and webp format
$image->toFile()->thumbRatio('1/1', ['width' => 600, 'quality' => 85, 'format' => 'webp'])->url()
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
The order of `<source>` elements in a `<picture>` element is essential, as browsers select the first matching source based on supported formats and media conditions. Modern formats like `avif` should be listed first, falling back to formats like `webp` or `jpeg`. Imagex will follow the order of the formats defined in the config and you should go from the most to less modern format: `'formats' => ['avif', 'webp']`.

The `media` attribute is also important for responsive designs or art directed images. With the media attribute you can specify the conditions under which each source should be used. This is important if you want to switch the ratio or the complete image at a specific media condition. You have to take care about the ordering of your `sources` array in the plugin options that you pass to the Imagex snippet. Imagex will create for each format all defined sources.

## Dynamic Format Size Handling
In some cases `avif` files can be larger than `webp` and you end up sending more HTML and also larger files to the user. If `compareFormats` is set to true, this option enables a dynamic size comparison between the specified image formats. The comparison is based on the order of the formats listed in the format array of your configuration. With the default `formats` array, this option checks whether `avif` is smaller than `webp` and only outputs or creates files for `avif` if it's smaller. So again the order of your formats array matters for this feature.

### How Format Comparison Works

Imagex uses a **weighted multi-sample approach** to determine the smallest format:

1. **Multiple Samples**: Instead of checking just one size, Imagex samples three srcset widths:
   - First (smallest width)
   - Middle
   - Last (largest width)

2. **Configurable Weighting**: The samples are weighted to reflect your audience's typical screen sizes. Configure this globally via `compareFormatsWeights` in your `config.php`. Available presets:
   - `'mobile'` (default) — 50% smallest, 30% middle, 20% largest
   - `'desktop'` — 20% smallest, 30% middle, 50% largest
   - `'balanced'` — roughly equal weight across all three
   - Custom array — `['small' => 0.4, 'medium' => 0.4, 'large' => 0.2]`

3. **Per-Image Comparison for Art Direction**: When using `artDirection` with different source images, each image is compared individually. This means one art-directed image might use `avif` while another uses `webp`, depending on which format is smaller for each specific image.

## Roadmap / Ideas
- [ ] Add tests for Imagex class
- [ ] Use Preload Resource Hints?! See [feature-branch](https://github.com/timnarr/kirby-imagex/tree/feature/preload-links)

## License
[MIT License](./LICENSE) Copyright © 2024-present Tim Narr
