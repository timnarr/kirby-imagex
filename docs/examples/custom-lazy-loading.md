# Examples: Custom Lazy Loading using `lazysizes`

Let's have a look how to configure Imagex to work with JS lazy loading libraries like [`lazysizes`](https://github.com/aFarkas/lazysizes). A image can be in `eager` or `lazy` loading mode. You can set attributes for these two modes and they will then extend `shared` attributes, if defined.

1. [Custom Lazy Loading for Non-Art-Directed Images](#example-1-custom-lazy-loading-for-non-art-directed-images)
2. [Custom Lazy Loading for Art-Directed Images](#example-2-custom-lazy-loading-for-art-directed-images)


## Example 1. Custom Lazy Loading for Non-Art-Directed Images
### Global Plugin Config
```php
// config.php
return [
  'timnarr.imagex' => [
    'formats' => ['avif', 'webp'],
    'customLazyloading' => true,
  ],
];
```

### Snippet Options
```php
// Define your options and pass them to the `imagex` snippet
<?php
$options = [
  'image' => $image,
  'critical' => false, // false will set the `lazy` loading mode, true will set it to `eager`
  'srcsetName' => 'imagex-demo',
  'ratio' => '3/2',
  'imgAttributes' => [
    'shared' => [
      'class' => 'my-img-component',
    ],
    'eager' => [
      'class' => 'my-img-component--eager',
      'sizes' => '400px'
    ],
    'lazy' => [
      'class' => 'lazyload',
      'data-sizes' => 'auto', // add lazysizes attributes to `lazy`
      'data-optimumx' => 'auto' // add lazysizes attributes to `lazy`
    ]
  ],
  'sourcesAttributes' => [
    'eager' => [
      'sizes' => '400px'
    ],
    'lazy' => [
      'data-sizes' => 'auto', // add lazysizes attributes to `lazy`
      'data-aspectratio' => '3/2' // add lazysizes attributes to `lazy`
    ]
  ],
  'sourcesArtDirected' => [
    [
      'ratio' => '21/9',
      'media' => '(min-width: 800px)',
      'image' => $block->imagetwo()->toFile(), // let's assume: `different-image.png`
    ]
  ],
];
?>

<?php snippet('imagex-picture', $options) ?>
```

### Final HTML Output
```html
<!-- And then JS kicks in of course and swap data-srcset to srcset and so on... -->
<picture>
  <source
    width="400" height="267" data-sizes="auto" type="image/avif"
    data-srcset="
      https://example.com/image-400x267-crop-52-65-q65-sharpen25.avif 400w,
      https://example.com/image-800x533-crop-52-65-q65-sharpen25.avif 800w">
  <source
    width="400" height="267" data-sizes="auto" type="image/webp"
    data-srcset="
      https://example.com/image-400x267-crop-52-65-q75-sharpen10.webp 400w,
      https://example.com/image-800x533-crop-52-65-q75-sharpen10.webp 800w">
    <img
      width="400" height="267" class="my-img-component lazyload" data-sizes="auto" decoding="async"
      data-src="https://example.com/image-400x267-crop-52-65-q80-sharpen10.jpg"
      data-srcset="
        https://example.com/image-400x267-crop-52-65-q80-sharpen10.jpg 400w,
        https://example.com/image-800x533-crop-52-65-q80-sharpen10.jpg 800w">
</picture>
```

If we now use the same image and config and switch this image to a critical (`'critical' => true`) one, because it is displayed above the fold or a editor decided to do so, then the image will switch from `lazy` to `eager` and prdouces this HTML:

```html
<picture>
  <source
    width="400" height="267" sizes="400px" type="image/avif"
    srcset="
      https://example.com/image-400x267-crop-52-65-q65-sharpen25.avif 400w,
      https://example.com/image-800x533-crop-52-65-q65-sharpen25.avif 800w">
  <source
    width="400" height="267" sizes="400px" type="image/webp"
    srcset="
      https://example.com/image-400x267-crop-52-65-q75-sharpen10.webp 400w,
      https://example.com/image-800x533-crop-52-65-q75-sharpen10.webp 800w">
    <!-- fetchpriority is added to `critical` images by default -->
    <!-- You can disable it by setting `'fetchpriority' => null` to imgAttributes['eager'] -->
    <img
      width="400" height="267" class="my-img-component my-img-component--eager" sizes="400px" decoding="async"
      fetchpriority="high"
      src="https://example.com/image-400x267-crop-52-65-q80-sharpen10.jpg"
      srcset="
        https://example.com/image-400x267-crop-52-65-q80-sharpen10.jpg 400w,
        https://example.com/image-800x533-crop-52-65-q80-sharpen10.jpg 800w">
</picture>
```


## Example 2. Custom Lazy Loading for Art-Directed Images
Lazysizes suggests to use the `data-aspectratio` attribute for `<sources>` that have a different aspect ratio then the initial image. Read more about the [aspectratio extension here](https://github.com/aFarkas/lazysizes/tree/gh-pages/plugins/aspectratio).

### Global Plugin Config
```php
// same as above
```

### Snippet Options
```php
// Define your options and pass them to the `imagex` snippet
<?php
$options = [
  // ... like above
  'sourcesAttributes' => [
    'eager' => [
      'sizes' => '400px'
    ],
    'lazy' => [
      'data-sizes' => 'auto', // add lazysizes attributes to `lazy`
      'data-aspectratio' => '3/2' // add lazysizes attributes to `lazy`
    ]
  ],
  'sourcesArtDirected' => [
    [
      'ratio' => '21/9',
      'media' => '(min-width: 800px)',
      'image' => $block->imagetwo()->toFile(), // let's assume: `different-image.png`
      'attributes' => [
        'lazy' => [
          'data-aspectratio' => '21/9' // add lazysizes attributes to `lazy`
        ]
      ]
    ]
  ],
];
?>

<?php snippet('imagex-picture', $options) ?>
```

### Final HTML Output
```html
<!-- And then JS kicks in of course and swap data-srcset to srcset and so on... -->
<picture>
  <source
    width="400" height="171" data-aspectratio="21/9" data-sizes="auto" media="(min-width: 800px)" type="image/avif"
    data-srcset="
      https://example.com/different-image-400x171-crop-51-52-q65-sharpen25.avif 400w,
      https://example.com/different-image-800x343-crop-51-52-q65-sharpen25.avif 800w">
  <source
    width="400" height="267" data-aspectratio="3/2" data-sizes="auto" type="image/avif"
    data-srcset="
      https://example.com/image-400x267-crop-52-65-q65-sharpen25.avif 400w,
      https://example.com/image-800x533-crop-52-65-q65-sharpen25.avif 800w">
  <source
    width="400" height="171" data-aspectratio="21/9" data-sizes="auto" media="(min-width: 800px)" type="image/webp"
    data-srcset="
      https://example.com/different-image-400x171-crop-51-52-q75-sharpen10.webp 400w,
      https://example.com/different-image-800x343-crop-51-52-q75-sharpen10.webp 800w">
  <source
    width="400" height="267" data-sizes="auto" data-aspectratio="3/2" type="image/webp"
    data-srcset="
      https://example.com/image-400x267-crop-52-65-q75-sharpen10.webp 400w,
      https://example.com/image-800x533-crop-52-65-q75-sharpen10.webp 800w">
  <img
    width="400" height="267" class="my-img-component lazyload" data-sizes="auto" decoding="async"
    data-src="https://example.com/image-400x267-crop-52-65-q80-sharpen10.jpg"
    data-srcset="
      https://example.com/image-400x267-crop-52-65-q80-sharpen10.jpg 400w,
      https://example.com/image-800x533-crop-52-65-q80-sharpen10.jpg 800w">
</picture>
```
