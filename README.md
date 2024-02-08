# Kirby Imagex
**Modern Images for Kirby CMS** – This plugin helps you orchestrate modern, responsive and performant images in Kirby.

## Features
- 🌠 Dynamic generation of image srcsets for art-directed or media-condition-based images.
- 📐 Helps you easily change the aspect ratio without the need to define new srcset presets.
- 😴 Supports native lazy loading and is customizable for JavaScript lazy loading libraries.
- 🚀 Improve the performance of your critical LCP (Largest Contentful Paint) images, utilizing `Priority Hints` and `Preload Resource Hints`.
- ⚡️ Supports multiple modern image formats, like `avif` and `webp`.
- 🧩 Can easily be integrated into existing blocks/projects.
- 🪄 Uses only Kirby's core capabilities for image editing.

## Getting Started
Four steps to get Imagex running:

1. [Install via Composer](#installation-via-composer)
2. [Set up Imagex global plugin config](#global-configuration)
3. [Adjust Kirby's thumbs config and add srcset presets](#adjust-kirbys-thumbs-config-and-add-srcset-presets)
4. [Add a snippet where you configure and pass options to the Imagex snippet](#snippet-configuration-and-usage)

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
    'preloadLinks' => false,
    'relativeUrls' => false,
  ],
];
```

| Option | Default | Type | Description |
| ------ | ------- | ---- | ----------- |
| `cache` | `true` | Boolean | Imagex will cache some calculations. Read more about it here: "[Cache](#cache)" |
| `customLazyloading` | `false` | Boolean | Imagex will initially use native lazy loading with the `loading` attribute. You can enable this option if you want to use a custom lazy loading library like lazysizes or any other JS-based solution. Imagex automatically use `data-src` and `data-srcset` if this is option is active. If you need something like `data-sizes="auto"` please use the snippet config to add it as a lazy HTML attribute. |
| `formats` | `['avif', 'webp']` | Array with Strings | Define the modern file formats you want to use. ⚠️ Order matters here! You should go from the most to less modern format. The order in this array also affects the `formatSizeHandling` snippet-option. [Read more about why the correct order is important](#why-order-matters). You shouldn't add the initial image format like `png` or `jpeg` here. |
| `includeInitialFormat` | `false` | Boolean | If active the format of the uploaded image (normally jpeg or png) will be treated as a modern format, which means Imagex will create `<source>` tags and preloading links for it. This is especially useful when you can't use modern formats, but want to use art directed images and preloading links. |
| `noSrcsetInImg` | `false` | Boolean | If active this will only output `src` with the smallest size from the given srcset-preset and the `srcset` attribute is omitted. |
| `preloadLinks` | `false` | Boolean | If active Imagex will generate preloading links for the most modern format and add them to the `<head>` as a progressive enhancement. See "[Preload `<link>`s for critical images](#preload-links-for-critical-images)" |
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

The quality settings for the modern formats cannot simply be taken from the initial preset. To really benefit from smaller image files due to modern formats, you should slightly adjust your quality settings. My rule of thumb is to subtract 5 from the jpeg/png quality for webp and to substract 15 for avif. Here is a good [read](https://www.industrialempathy.com/posts/avif-webp-quality-settings/) with more detail, but I've also found that this isn't completely applicable to images in Kirby.

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
Pass the file object of your image and other options to the imagex snippet as follows:

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
    ],
  ],
  'ratio' => '16/9',
  'sizes' => '100vw',
  'srcsetName' => 'my-srcset',
  'critical' => false,
];
?>

<?php snippet('imagex', $options) ?>
```

Imagex outputs a `<picture>` element with multiple `<source>` elements and one `<img>`. If you need extra HTML you can wrap the imagex snippet accordingly. Handle `svg` or `gif` files differently as needed!

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
    <?php snippet('imagex', $options) ?>
  <?php endif; ?>
  <figcaption>Lorem ipsum</figcaption>
</figure>
```

### Snippet Options
You can choose from many options to customize your images and pass them to the imagex snippet. At first it might look heavy, but it's just very flexible and actually only `image` is required and everything else can be omitted, while Imagex is providing some sane defaults.

For each HTML element of the picture element you can add attributes, like CSS classes, inline-styles, data-attributes and so on. You only need to add your attributes to one of these three attribute categories, I call them loading modes: `shared`, `eager` and `lazy`. `shared` is for attributes that should exists always, no matter what loading mode the image is. If you have attributes that should only be used in `eager` or `lazy` loading mode you can add them to one of it. Imagex will merge the `shared` attributes with the attributes of the current loading mode automatically. The attributes of the non-applicable loading mode will have no effect then.

| Option | Default | Type | Description |
| ------ | ------- | ---- | ----------- |
| `image` | – | File-Object | Required. Your initial image. Be sure to return a file object here: `$field->toFile()`. |
| `pictureAttributes` | `['shared' => [], 'eager' => [], 'lazy' => []]` | Array | HTML attributes added to `<picture>`. Set `shared` attributes regardless of the loading mode. Extend `shared` attributes for a specific loading mode by setting `eager` and `lazy`. |
| `imgAttributes` | `['shared' => [], 'eager' => [], 'lazy' => []]` | Array | HTML attributes added to `<img>`. Set `shared` attributes regardless of the loading mode. Extend `shared` attributes for a specific loading mode by setting `eager` and `lazy`. |
| `srcsetName` | `'default'` | String | Name of the srcset preset, configured in [Kirby's config](#adjust-kirbys-thumbs-config-and-add-srcset-presets).  |
| `sizes` | `'auto'` | String | Value of the `sizes` attribute. |
| `critical` | `false` | Boolean | With this flag you can switch between `eager` and `lazy`. A critical image is placed "above-the-fold" and should be loaded in `eager` mode. If `critical` is `true` Imagex disables lazy loading and set `fetchpriority="high"` to the image. You can add your logic here to determine if an image is critical, for example: Let the editor choose in the panel by adding a toggle field or query the index of your image blocks and set `critical` to true if it's in your first two blocks. Or just set it to `true` if you know a specific image-block is only used above the fold. |
| `ratio` | `'intrinsic'` | String | Set the desired aspect ratio here for not art-directed-images which is used for the thumbs in your `<img>` src and srcset and for non art directed sources or let the editor choose from a set of predefined ratios from the panel. Can be omitted, default is `intrinsic`, which means the ratio of the source-image is used. |
| `sourcesAttributes` | `['shared' => [], 'eager' => [], 'lazy' => []]` | Array | HTML attributes added to non-art-directed `<source>` elements. Set `shared` attributes regardless of the loading mode. Extend `shared` attributes for a specific loading mode by setting `eager` and `lazy`. |
| `sourcesArtDirected` | `[]` | Array | Order matters here! Browsers will use the first matching `<source>` element they will find. Order your length-based media queries from large to small. You can change the ratio of the initial image if a media condition matches or use a complete different image and load it if a media condition matches. Again you can add a `attributes` array to each art-directed source with `shared`, `eager` and `lazy` attributes to change the HTML attributes for the different loading modes |
| `formatSizeHandling` | `false` | Boolean | In some cases `avif` files can be larger than `webp`. If this option is set to true, it enables a dynamic size comparison between the specified image formats. ⚠️ Again: The `formats` order in `config.php` matters here! [Read more about it here](#dynamic-format-size-handling). |


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
      'style' => ['background-color: red;', 'object-fit: cover;', 'object-postion: ' . $image->focus() . ';'],
      'data-attr' => 'my-img-attribute'
    ],
    'eager' => [
      // extend `shared` attributes in eager loading mode
    ],
    'lazy' => [
      // extend `shared` attributes in lazy loading mode
    ],
    // Do not add `src`, `srcset`, `sizes` or `loading` or their equivalents for lazy loading (like `data-src`) here.
    // These attributes are handled automatically by imagex and adding them here will throw an exception.
  ],
  'srcsetName' => 'my-srcset',
  'sizes' => '760px',
  'critical' => $isCritical ?? false,
  'ratio' => '1/1',
  'sources' => [
    ['ratio' => '21/9', 'media' => '(min-width: 1200px)']
    ['media' => '(min-width: 820px)', 'image' => $artDirectedImage]
    ['ratio' => '16/9', 'media' => '(prefers-color-scheme: dark)', 'image' => $darkModeImage]
    ['ratio' => '21/9', 'media' => '(orientation: landscape)', 'attributes' => ['shared' => ['attribute' => 'value'], 'eager' => [], 'lazy' => []]]
  ],
];

// Pass your options to the imagex snippet
<?php snippet('imagex', $options) ?>
```

## Cache
Imagex will do some simple calculations per image, like calculating the height by the given width and ratio. Basically imagex get the srcset definition from the config file, calculate and set the height and output the final config. The result will be cached to reduce unnecessary calculations when you use the same combination of srcset-preset and ratio for other images.

## Performance Improvements for Critical Images
Imagex provides features like Priority Hints and Preload Resource Hints for improving the loading times of critical images.

### With Priority Hints
Imagex will set the priority hint `fetchpriority="high"` to critical images to get the browser to load it sooner. Imagex set this by default if you pass `'critical' => true` to the imagex snippet. Read more about [fetchpriority here](https://web.dev/articles/fetch-priority#the_fetchpriority_attribute).

### With Preload Resource Hints
With Imagex it is possible to easily preload critical responsive images with resource hints, providing a preload link element and using the `imagesizes` and `imagesrcset` attributes. Imagex uses the `page.render:after` hook from Kirby, searches for images with preloading links and add them to the end of the `<head>`. You only have to opt-in in this feature by setting `'preloadLinks' => true` in the config.php and set `'critical' => true` for the images you want to preload.

Here is an example of a final preload link:

```html
<link
  rel="preload"
  as="image"
  imagesizes="100vw"
  imagesrcset="
    /image-560x280.avif 560w,
    /image-768x384.avif 768w,
    /image-960x480.avif 960w,
    /image-1080x540.avif 1080w,
    /image-1240x620.avif 1240w"
  media="(min-width: 650px)"
  type="image/avif"
>
```

Browser support is pretty good and it is supported in Chromium browsers and Firefox for quite a while. Safari is late, but supports it since v17.2., see "Can I use" for [imagesrcset](https://caniuse.com/mdn-html_elements_link_imagesrcset) and [imagesizes](https://caniuse.com/?search=imagesizes).

Imagex omits the `href` attribute of the `<link>` element, so older browsers that don't support `imagesizes` and `imagesrcset` don't load unecassary bytes and we can really use this as a progressive enhancement.

Preloading responsive images also works well for art directed images using the `media` attribute and defining an appropriate media condition. Imagex will also automatically generate links for art directed images when you use them.

🚧 **There are two things to note:**
1. Preloading links only work with one format. Even if we have multiple preload links for different formats and define a `type` attribute with the mime-type, like `type="image/avif"`, the browser will preload both formats if these formats are supported. This works different in the `<picture>` element where the browser use the first matching `<source>` and ignore the others. Thats why Imagex will only preload the most modern image format and will also consider if you have the `formatSizeHandling` option activated.
2. You should set the sizes attribute to a lenght value like `100vw` or a media condition like `(min-width: 1200px) 600px, 100vw`.

## Why Order Matters?
### Format Order and Media Attribute
The order of `<source>` elements in a `<picture>` element is essential, as browsers select the first matching source based on supported formats and media conditions. Modern formats like `avif` should be listed first, falling back to formats like `webp` or `jpeg`. Imagex will follow the order of the formats defined in the config and you should go from the most to less modern format: `'formats' => ['avif', 'webp']`.

The `media` attribute is also important for responsive designs or art directed images. With the media attribute you can specify the conditions under which each source should be used. This is important if you want to switch the ratio or the complete image at a specific media condition. You have to take care about the ordering of your `sources` array in the plugin options that you pass to the Imagex snippet. Imagex will create for each format all defined sources.

## Dynamic Format Size Handling
In some cases `avif` files can be larger than `webp` and you end up sending more HTML and also larger files to the user. If `formatSizeHandling` is set to true, this option enables a dynamic size comparison between the specified image formats. The comparison is based on the order of the formats listed in the format array of your configuration. With the default `formats` array, this option checks whether `avif` is smaller than `webp` and only outputs or creates files for `avif` if it's smaller. So again the order of your formats array matters for this feature. The size comparions is currently very basic and Imagex will only generate the middle item of your passed srcset preset and compare it's size with the next format. If the the more modern format will have a larger file the rest of the srcset preset is not generated and is also omitted in the HTML output.

🚧 **This feature is currently pretty basic.** It only generates the middle item / width from the given srcset preset and check if the file size is smaller than the next less modern format. And currently this is only done for the initial image and not for the images of art directed sources.

## Examples for Different Configurations and HTML Output
You'll find all examples in the [examples.md](/docs/examples/README.md).

## License
[MIT License](./LICENSE) Copyright © 2024-present Tim Narr
