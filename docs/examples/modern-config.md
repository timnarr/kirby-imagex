# Examples: Modern Configuration

1. [Modern Config](#example-1-modern-config)
2. [Modern Config plus `noSrcsetInImg`](#example-2-modern-config-plus-nosrcsetinimg)
3. [Modern Config plus `compareFormats`](#example-3-modern-config-plus-compareformats)
4. [Modern Config plus `relativeUrls`](#example-4-modern-config-plus-relativeurls)
5. [`compareFormats` with Art Direction](#example-5-compareformats-with-art-direction)
6. [`compareFormatsWeights` Presets](#example-6-compareformatsweights-presets)

## Example 1. Modern Config
### Global Plugin Config
```php
// config.php
return [
  'timnarr.imagex' => [
    'formats' => ['avif', 'webp'], // our modern formats
    'noSrcsetInImg' => false, // skip srcset in <img> with initial img-format -> less HTML
    'relativeUrls' => false, // relative URLs -> less HTML
  ],
];
```

### Snippet Options
```php
'image' => $image,
'srcset' => 'imagex-demo',
'ratio' => '3/2',
'attributes' => [
  'img' => [
    'sizes' => '400px'
  ],
  'sources' => [
    'sizes' => '400px'
  ],
],
'artDirection' => [
  [
    'media' => '(min-width: 800px)',
    'ratio' => '21/9',
    'image' => $block->imagetwo()->toFile(), // let's assume: `different-image.png`
  ]
],
```

### Final HTML Output
```html
<picture>
  <source
    width="400" height="171" media="(min-width: 800px)" sizes="400px" type="image/avif"
    srcset="
      https://example.com/different-image-400x171-crop-51-52-q65-sharpen25.avif 400w,
      https://example.com/different-image-800x343-crop-51-52-q65-sharpen25.avif 800w">
  <source
    width="400" height="267" sizes="400px" type="image/avif"
    srcset="
      https://example.com/image-400x267-crop-52-65-q65-sharpen25.avif 400w,
      https://example.com/image-800x533-crop-52-65-q65-sharpen25.avif 800w">
  <source
    width="400" height="171" media="(min-width: 800px)" sizes="400px" type="image/webp"
    srcset="
      https://example.com/different-image-400x171-crop-51-52-q75-sharpen10.webp 400w,
      https://example.com/different-image-800x343-crop-51-52-q75-sharpen10.webp 800w">
  <source
    width="400" height="267" sizes="400px" type="image/webp"
    srcset="
      https://example.com/image-400x267-crop-52-65-q75-sharpen10.webp 400w,
      https://example.com/image-800x533-crop-52-65-q75-sharpen10.webp 800w">
  <img
    width="400" height="267" sizes="400px" decoding="async" loading="lazy"
    src="https://example.com/image-400x267-crop-52-65-q80-sharpen10.jpg"
    srcset="
      https://example.com/image-400x267-crop-52-65-q80-sharpen10.jpg 400w,
      https://example.com/image-800x533-crop-52-65-q80-sharpen10.jpg 800w">
</picture>
```

## Example 2. Modern Config plus `noSrcsetInImg`
`webp` is pretty [well supported](https://caniuse.com/?search=webp) and if you don't need to support older browsers or be just fine with a minimal `jpeg` fallback you can activate `noSrcsetInImg` to skip the `srcset` attribute in `<img>` and output less HTML. This can save you some KB - imagine you have 8 or more widths in your srcset preset and like 10 images on a page which are using this preset. This can sum up to some KB. This will get you just a really small performance benefit, especially with gzip compression active you will not notice it this much, but you know... more such adjustments and saving some KB here and there will do it.


### Global Plugin Config
```php
// config.php
return [
  'timnarr.imagex' => [
    'formats' => ['avif', 'webp'], // our modern formats
    'noSrcsetInImg' => true, // skip srcset in <img> -> less HTML
    'relativeUrls' => false,
  ],
];
```

### Snippet Config
```php
// same as above
```

### Final HTML Output
```html
<picture>
  <source
    width="400" height="171" media="(min-width: 800px)" sizes="400px" type="image/avif"
    srcset="
      https://example.com/different-image-400x171-crop-51-52-q65-sharpen25.avif 400w,
      https://example.com/different-image-800x343-crop-51-52-q65-sharpen25.avif 800w">
  <source
    width="400" height="267" sizes="400px" type="image/avif"
    srcset="
      https://example.com/image-400x267-crop-52-65-q65-sharpen25.avif 400w,
      https://example.com/image-800x533-crop-52-65-q65-sharpen25.avif 800w">
  <source
    width="400" height="171" media="(min-width: 800px)" sizes="400px" type="image/webp"
    srcset="
      https://example.com/different-image-400x171-crop-51-52-q75-sharpen10.webp 400w,
      https://example.com/different-image-800x343-crop-51-52-q75-sharpen10.webp 800w">
  <source
    width="400" height="267" sizes="400px" type="image/webp"
    srcset="
      https://example.com/image-400x267-crop-52-65-q75-sharpen10.webp 400w,
      https://example.com/image-800x533-crop-52-65-q75-sharpen10.webp 800w">
  <img
    width="400" height="267" sizes="400px" decoding="async" loading="lazy"
    src="https://example.com/image-400x267-crop-52-65-q80-sharpen10.jpg">
</picture>
```

## Example 3. Modern Config plus `compareFormats`
`compareFormats` will compare file sizes per image and will test if the most modern format is also the smallest. In this demo setup `avif` is checked if it's smaller as `webp` and if it's not smaller Imagex will omit it.

### Global Plugin Config
```php
// config.php
return [
  'timnarr.imagex' => [
    'formats' => ['avif', 'webp'], // our modern formats
    'noSrcsetInImg' => true, // skip srcset in <img> -> less HTML
    'relativeUrls' => false,
  ],
];
```

### Snippet Config
```php
// same as above, except...
  'compareFormats' => true, // skip most modern format if larger
  'artDirection' => [], // `'compareFormats' => true` should only be used without art directed images at the moment
```

### Final HTML Output
```html
<picture>
  <source
    width="400" height="267" sizes="400px" type="image/webp"
    srcset="
      https://example.com/image-400x267-crop-52-65-q75-sharpen10.webp 400w,
      https://example.com/image-800x533-crop-52-65-q75-sharpen10.webp 800w">
  <img
    width="400" height="267" sizes="400px" decoding="async" loading="lazy"
    src="https://example.com/image-400x267-crop-52-65-q80-sharpen10.jpg">
</picture>
```

## Example 4. Modern Config plus `relativeUrls`
You can active `relativeUrls` to use relative URLs. Some people are in favor of absolute URLs. I have not had any negative experiences with relative image URLs. Please test it for your specific use case.

### Global Plugin Config
```php
// config.php
return [
  'timnarr.imagex' => [
    'formats' => ['avif', 'webp'], // our modern formats
    'noSrcsetInImg' => true, // skip srcset in <img> -> less HTML
    'relativeUrls' => true, // relative URLs -> less HTML
  ],
];
```

### Snippet Config
```php
// same as above
```

### Final HTML Output
```html
<picture>
  <source
    width="400" height="171" media="(min-width: 800px)" sizes="400px" type="image/webp"
    srcset="
      /different-image-400x171-crop-51-52-q75-sharpen10.webp 400w,
      /different-image-800x343-crop-51-52-q75-sharpen10.webp 800w">
  <source
    width="400" height="267" sizes="400px" type="image/webp"
    srcset="
      /image-400x267-crop-52-65-q75-sharpen10.webp 400w,
      /image-800x533-crop-52-65-q75-sharpen10.webp 800w">
  <img
    width="400" height="267" sizes="400px" decoding="async" loading="lazy"
    src="/image-400x267-crop-52-65-q80-sharpen10.jpg">
</picture>
```

## Example 5. compareFormats with Art Direction

When using `compareFormats` together with art-directed images that have different source files, Imagex compares formats **individually for each image**. This means one art-directed source might output `avif` while another outputs `webp`, depending on which format produces smaller files for each specific image.

### How it works

1. Imagex samples three srcset widths (first, middle, last) for each image
2. Applies mobile-first weighting: 50% smallest, 30% middle, 20% largest
3. Compares the weighted file sizes between formats
4. Outputs only the smallest format for each image

### Global Plugin Config
```php
// config.php
return [
  'timnarr.imagex' => [
    'formats' => ['avif', 'webp'],
    'noSrcsetInImg' => true,
    'relativeUrls' => true,
  ],
];
```

### Snippet Options
```php
$options = [
  'image' => $image->toFile(), // let's assume: `image.jpg` where avif is smaller than webp
  'srcset' => 'imagex-demo',
  'ratio' => '3/2',
  'compareFormats' => true,
  'artDirection' => [
    [
      'media' => '(min-width: 800px)',
      'ratio' => '21/9',
      'image' => $differentImage->toFile(), // let's assume: `illustration.png` where webp is smaller than avif
    ]
  ],
];
```

### Final HTML Output
In this example, the main image uses `avif` (smaller), while the art-directed illustration uses `webp` (smaller for that specific image):

```html
<picture>
  <!-- Art-directed source: webp is smaller for this illustration -->
  <source
    width="400" height="171" media="(min-width: 800px)" type="image/webp"
    srcset="
      /illustration-400x171-crop-51-52-q75-sharpen10.webp 400w,
      /illustration-800x343-crop-51-52-q75-sharpen10.webp 800w">
  <!-- Main image: avif is smaller for this photo -->
  <source
    width="400" height="267" type="image/avif"
    srcset="
      /image-400x267-crop-52-65-q65-sharpen25.avif 400w,
      /image-800x533-crop-52-65-q65-sharpen25.avif 800w">
  <img
    width="400" height="267" decoding="async" loading="lazy"
    src="/image-400x267-crop-52-65-q80-sharpen10.jpg">
</picture>
```

This per-image comparison ensures you always serve the smallest format for each specific image, rather than applying a one-size-fits-all decision.

## Example 6. `compareFormatsWeights` Presets

`compareFormatsWeights` controls how the three sampled file sizes are weighted when `compareFormats` is enabled. Use it to match your audience's typical device mix. The default `'mobile'` preset prioritises smaller widths; switch to `'desktop'` for predominantly large-screen audiences, or pass a custom array for full control.

Available presets:
- `'mobile'` *(default)* — 50% smallest width, 30% middle, 20% largest
- `'desktop'` — 20% smallest, 30% middle, 50% largest
- `'balanced'` — roughly equal weight (34/33/33)
- Custom array — define your own weights (values must sum to `1.0`)

### Desktop Audience

```php
// config.php
return [
  'timnarr.imagex' => [
    'formats' => ['avif', 'webp'],
    'compareFormatsWeights' => 'desktop', // prioritises larger widths
    'noSrcsetInImg' => true,
  ],
];
```

### Balanced Weighting

```php
// config.php
return [
  'timnarr.imagex' => [
    'formats' => ['avif', 'webp'],
    'compareFormatsWeights' => 'balanced',
    'noSrcsetInImg' => true,
  ],
];
```

### Custom Weights

```php
// config.php
return [
  'timnarr.imagex' => [
    'formats' => ['avif', 'webp'],
    'compareFormatsWeights' => ['small' => 0.6, 'medium' => 0.3, 'large' => 0.1],
    'noSrcsetInImg' => true,
  ],
];
```

The HTML output is identical to [Example 3](#example-3-modern-config-plus-compareformats) — `compareFormatsWeights` only affects the internal decision of which format wins, not the structure of the output.
