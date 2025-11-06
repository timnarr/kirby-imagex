![Kirby Imagex Banner](/.github/imagex-banner.png)

# Kirby Imagex
**Modern Images for Kirby CMS** – This plugin helps you orchestrate modern, responsive and performant images in Kirby.

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
    'customLazyloading' => false,
    'formats' => ['avif', 'webp'],
    'includeInitialFormat' => false,
    'noSrcsetInImg' => false,
    'relativeUrls' => false,
  ],
];
```

| Option | Default | Type | Description |
| ------ | ------- | ---- | ----------- |
| `cache` | `true` | Boolean | Imagex will cache some calculations. Read more about it here: "[Cache](#cache)" |
| `customLazyloading` | `false` | Boolean | Imagex will initially use native lazy loading with the `loading` attribute. Enable this option if you want to use a custom lazy loading library like lazysizes or any other JS-based solution. Imagex will then automatically use `data-src` and `data-srcset`. If you need something like `data-sizes="auto"` please use the snippet config to add it as a lazy HTML attribute. |
| `formats` | `['avif', 'webp']` | Array with Strings | Define the modern file formats you want to use. ⚠️ Order matters here! You should go from the most to less modern format. The order in this array also affects the `formatSizeHandling` snippet-option. [Read more about why the correct order is important](#why-order-matters). You shouldn't add the initial image format like `png` or `jpeg` here. |
| `includeInitialFormat` | `false` | Boolean | If active the format of the uploaded image (normally jpeg or png) will be treated as a modern format, which means Imagex will create `<source>` tags for it. This is especially useful when you can't use modern formats, but want to use art directed images. |
| `noSrcsetInImg` | `false` | Boolean | If active this will only output the `src` attribute in the `<img>` tag. The smallest size from the given srcset-preset is used and the `srcset` attribute is omitted. |
| `relativeUrls` | `false` | Boolean | Output relative image URLs everywhere when active. |

## Adjust Kirby's Thumbs Config and Add Srcset Presets
The srcset configuration is still set up as you know it from Kirby, [like described here](https://getkirby.com/docs/reference/objects/cms/file/srcset#define-presets__extended-example).

Set your srcset preset, like `my-srcset` and just define the width and other configs here. I leave out the `height` in my settings and only define the `width`. This makes working with images easier and more predictable, because it allows you to quickly and easily change the ratio of a image component or add a new ratio without having to create a new srcset preset to your config. The `height` is later calulated by Imagex based on the ratio. This first srcset preset is used for `jpeg` or `png` images.

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

The quality settings for the modern formats shouldn't simply be taken from the initial preset. To really benefit from smaller image files due to modern formats, you should slightly adjust your quality settings. My rule of thumb is to subtract 5 from the jpeg/png quality for webp and to substract 15 for avif. Here is a good [read](https://www.industrialempathy.com/posts/avif-webp-quality-settings/) with more detail, but I've also noticed that this isn't completely applicable to images in Kirby.

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
  'imgAttributes' => [
    'shared' => [
      'class' => 'my-image',
      'decoding' => 'async',
      'style' => ['background: red;'],
      'sizes' => '100vw',
    ],
  ],
  'ratio' => '16/9',
  'srcsetName' => 'my-srcset',
  'critical' => false,
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
  'srcsetName' => 'my-srcset',
  // ... all other options work the same
];

$json = snippet('imagex-picture-json', $options, true);
echo $json;
?>
```

The JSON output contains structured data with `pictureAttributes`, `sources`, and `imgAttributes`. See the [JSON output example](/docs/examples/json-output.md) for more details.

### Snippet Options
You can choose from many options to customize your images and pass them to the Imagex snippet. At first it might look heavy, but it's just very flexible and actually only `image` is required and everything else can be omitted, while Imagex is providing some sane defaults.

For each HTML element of the picture element you can add attributes, CSS classes, inline-styles, data-attributes and so on. You only need to add your attributes to one of these three attribute categories, which I call "loading modes": `shared`, `eager` and `lazy`. The `shared` mode is for attributes that should exists always, no matter what loading mode the image is. If you have attributes that should only be used in `eager` or `lazy` loading mode you can add them to one of it. Imagex will merge the `shared` attributes with the attributes of the current loading mode automatically. The attributes of the non-applicable loading mode will have no effect then.

**All attributes can be overridden:** User-defined attributes always take precedence over default attributes generated by Imagex. This means you can override any attribute, including `src`, `srcset`, `loading`, `width`, `height`, and others. If you don't specify an attribute, Imagex will use its default values as fallback. For `class` and `style` attributes, user values are merged with defaults rather than replaced.

⚠️ **Note:** When overriding dimension-related attributes (`width`, `height`, `src`, `srcset`), be aware that Imagex calculates dimensions based on the `ratio` parameter. Overriding these attributes makes you responsible for maintaining consistency between dimensions and image sources. [See detailed explanation below](#overriding-default-attributes).

| Option | Default | Type | Description |
| ------ | ------- | ---- | ----------- |
| `image` | – | File-Object | Required. Your initial image. Be sure to return a file object here: `$field->toFile()`. |
| `pictureAttributes` | `['shared' => [], 'eager' => [], 'lazy' => []]` | Array | HTML attributes added to `<picture>`. Set `shared` attributes regardless of the loading mode. Extend `shared` attributes for a specific loading mode by setting `eager` and `lazy`. |
| `imgAttributes` | `['shared' => [], 'eager' => [], 'lazy' => []]` | Array | HTML attributes added to `<img>`. Set `shared` attributes regardless of the loading mode. Extend `shared` attributes for a specific loading mode by setting `eager` and `lazy`. |
| `srcsetName` | `'default'` | String | Name of the srcset preset, configured in [Kirby's config](#adjust-kirbys-thumbs-config-and-add-srcset-presets).  |
| `critical` | `false` | Boolean | With this flag you can switch between `eager` and `lazy`. If a critical image is placed "above-the-fold" then it should be loaded in `eager` mode. If `critical` is `true` Imagex disables lazy loading and set `fetchpriority="high"` to the image. You can add your logic here to determine if an image is critical, for example: Let the editor choose in the panel by adding a toggle field or query the index of your image blocks and set `critical` to true if it's in your first two blocks. Or just set it to `true` if you know a specific image-block is only used above the fold. |
| `ratio` | `'intrinsic'` | String | Set the desired aspect ratio here for not art-directed-images which is used for the thumbs in your `<img>` src and srcset and for non art directed sources or let the editor choose from a set of predefined ratios from the panel. Can be omitted, default is `intrinsic`, which means the ratio of the source-image is used. |
| `sourcesAttributes` | `['shared' => [], 'eager' => [], 'lazy' => []]` | Array | HTML attributes added to non-art-directed `<source>` elements. Set `shared` attributes regardless of the loading mode. Extend `shared` attributes for a specific loading mode by setting `eager` and `lazy`. |
| `sourcesArtDirected` | `[]` | Array | Order matters here! Browsers will use the first matching `<source>` element they will find. Order your length-based media queries from large to small. You can change the ratio of the initial image if a media condition matches or use a complete different image and load it if a media condition matches. Again you can add a `attributes` array to each art-directed source with `shared`, `eager` and `lazy` attributes to change the HTML attributes for the different loading modes |
| `formatSizeHandling` | `false` | Boolean | In some cases `avif` files can be larger than `webp`. If this option is set to true, it enables a dynamic size comparison between the specified image formats. ⚠️ Again: The `formats` order in `config.php` matters here! 🚧 This feature is currently pretty basic - [Read more about it here](#dynamic-format-size-handling). |


```php
<?php
$options = [
  'image' => $image,
  'pictureAttributes' => [
    'shared' => [
      'class' => 'my-picture-class',
      'data-attr' => 'my-picture-attribute'
    ],
    'eager' => [
      // extend `shared` attributes in eager loading mode
      'class' => 'my-picture-class--eager'
    ],
    'lazy' => [
      // extend `shared` attributes in lazy loading mode
      'class' => 'my-picture-class--lazy js-image'
    ],
  ],
  'imgAttributes' => [
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
    ],
    // You can override any attribute generated by Imagex, including `src`, `srcset`, `loading`, etc.
    // User-defined attributes always take precedence over Imagex defaults.
  ],
  'srcsetName' => 'my-srcset',
  'critical' => $isCritical ?? false,
  'ratio' => '1/1',
  'sourcesArtDirected' => [
    ['ratio' => '21/9', 'media' => '(min-width: 1200px)']
    ['media' => '(min-width: 820px)', 'image' => $artDirectedImage]
    ['ratio' => '16/9', 'media' => '(prefers-color-scheme: dark)', 'image' => $darkModeImage]
    ['ratio' => '21/9', 'media' => '(orientation: landscape)', 'attributes' => ['shared' => ['attribute' => 'value'], 'eager' => [], 'lazy' => []]]
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
  'imgAttributes' => [
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
  'srcsetName' => 'my-srcset',
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
  'imgAttributes' => [
    'shared' => [
      'src' => $image->thumb(['width' => 1200, 'quality' => 85])->url(), // Large image for crawlers
      'alt' => $image->alt(),
    ],
  ],
  'srcsetName' => 'my-srcset', // Srcset will still use optimized sizes (400w, 800w, etc.)
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
  'imgAttributes' => [
    'shared' => [
      // Crawlers get a square image
      'src' => $image->thumb(['width' => 1200, 'height' => 1200, 'crop' => true])->url(),
    ],
  ],
  'srcsetName' => 'my-srcset',
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
  'imgAttributes' => [
    'shared' => [
      'width' => 600,
      'height' => 400, // Make sure this matches your desired ratio (3:2 in this case)
    ],
    'lazy' => [
      // If you also override srcset, make sure your images have the same 3:2 ratio
      'srcset' => 'custom-300.jpg 300w, custom-600.jpg 600w, custom-900.jpg 900w',
    ],
  ],
  'srcsetName' => 'my-srcset',
  'ratio' => '3/2', // This ratio is now only used for generating the default thumbnails
];
```

Note: In most cases, you should **not** need to override `width`, `height`, or `srcset`. Let Imagex handle these automatically based on your `ratio` parameter for best results.

## Cache
Imagex will do some simple calculations per image, like calculating the height by the given width and ratio. Basically Imagex get the srcset definition from the config file, calculate and set the height and output the final config. The result will be cached to reduce unnecessary calculations when you use the same combination of srcset-preset and ratio for other images.

## Performance Improvements for Critical Images
Imagex provides features like Priority Hints for improving the loading times of critical images.

### Priority Hints
Imagex will set the priority hint `fetchpriority="high"` to critical images to get the browser to load it sooner. Imagex set this by default if you pass `'critical' => true` to the Imagex snippet. Read more about [fetchpriority here](https://web.dev/articles/fetch-priority#the_fetchpriority_attribute).

## Why Order Matters?
### Format Order and Media Attribute
The order of `<source>` elements in a `<picture>` element is essential, as browsers select the first matching source based on supported formats and media conditions. Modern formats like `avif` should be listed first, falling back to formats like `webp` or `jpeg`. Imagex will follow the order of the formats defined in the config and you should go from the most to less modern format: `'formats' => ['avif', 'webp']`.

The `media` attribute is also important for responsive designs or art directed images. With the media attribute you can specify the conditions under which each source should be used. This is important if you want to switch the ratio or the complete image at a specific media condition. You have to take care about the ordering of your `sources` array in the plugin options that you pass to the Imagex snippet. Imagex will create for each format all defined sources.

## Dynamic Format Size Handling
In some cases `avif` files can be larger than `webp` and you end up sending more HTML and also larger files to the user. If `formatSizeHandling` is set to true, this option enables a dynamic size comparison between the specified image formats. The comparison is based on the order of the formats listed in the format array of your configuration. With the default `formats` array, this option checks whether `avif` is smaller than `webp` and only outputs or creates files for `avif` if it's smaller. So again the order of your formats array matters for this feature. The size comparions is currently very basic and Imagex will only generate the middle item of your passed srcset preset and compare it's size with the next format. If the the more modern format will have a larger file the rest of the srcset preset is not generated and is also omitted in the HTML output.

🚧 **This feature is currently pretty basic.** It only generates the middle item / width from the given srcset preset and check if the file size is smaller than the next less modern format. And currently this is only done for the initial image and not for the images of art directed sources.

## Roadmap / Ideas
- [ ] Add tests for Imagex class
- [ ] Use Preload Resource Hints?! See [feature-branch](https://github.com/timnarr/kirby-imagex/tree/feature/preload-links)
- [ ] Improved determination of the smallest format, when using `formatSizeHandling`
- [ ] Improve `formatSizeHandling` in combination with art-directed images

## License
[MIT License](./LICENSE) Copyright © 2024-present Tim Narr
