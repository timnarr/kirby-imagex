# Examples: Modern Configuration

1. [Modern Config](#example-1-modern-config)
2. [Modern Config plus `noSrcsetInImg`](#example-2-modern-config-plus-nosrcsetinimg)
3. [Modern Config plus `formatSizeHandling`](#example-3-modern-config-plus-formatsizehandling)
4. [Modern Config plus `relativeUrls`](#example-4-modern-config-plus-relativeurls)

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
'srcsetName' => 'imagex-demo',
'ratio' => '3/2',
'imgAttributes' => [
  'shared' => [
    'sizes' => '400px'
  ],
],
'sourcesAttributes' => [
  'shared' => [
    'sizes' => '400px'
  ],
],
'sourcesArtDirected' => [
  [
    'ratio' => '21/9',
    'media' => '(min-width: 800px)',
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

## Example 3. Modern Config plus `formatSizeHandling`
`formatSizeHandling` will compare file sizes per image and will test if the most modern format is also the smallest. In this demo setup `avif` is checked if it's smaller as `webp` and if it's not smaller Imagex will omit it.

🚧 **This feature is currently pretty basic.** It only generates the middle item / width from the given srcset preset and check if the file size is smaller than the next less modern format. And currently this is only done for the initial image and not for the images of art directed sources.

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
  'formatSizeHandling' => true, // skip most modern format if larger
  'sourcesArtDirected' => [], // `'formatSizeHandling' => true` should only be used without art directed images at the moment
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
