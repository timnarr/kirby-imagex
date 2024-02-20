# Examples: Loading modes `eager` and `lazy`

Imagex will use native lazy loadig (`loading="lazy"`) by default. See [Custom Lazy Loading](https://github.com/timnarr/kirby-imagex/blob/main/docs/examples/custom-lazy-loading.md) when you use a JS lazy loading library.

A image can be in `eager` or `lazy` loading mode. You can set attributes for these two modes and they will then extend `shared` attributes, if defined.

## Global Plugin Config
These are all default values, so you don't need to set them explicitly and I've only added them here for the demo.

```php
// config.php
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

### Snippet Options
```php
// Define your options and pass them to the `imagex` snippet
<?php
$options = [
  'image' => $image,
  'critical' => false, // false is the deafult - will set the `lazy` loading mode, true will set it to `eager`
  'srcsetName' => 'imagex-demo',
  'ratio' => '3/2',
  'imgAttributes' => [
    'shared' => [
      'class' => 'my-img-component',
      'sizes' => '400px'
    ],
    'eager' => [
      'class' => 'my-img-component--eager',
    ],
    'lazy' => [
      'class' => 'my-img-component--lazy',
    ]
  ],
  'sourcesAttributes' => [
    'shared' => [
      'sizes' => '400px'
    ],
  ],
];
?>

<?php snippet('imagex-picture', $options) ?>
```

### Final HTML Output
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
  <img
    width="400" height="267" class="my-img-component my-img-component--lazy"
    decoding="async" loading="lazy" sizes="400px"
    src="https://example.com/image-400x267-crop-52-65-q80-sharpen10.jpg"
    srcset="
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
    width="400" height="267" class="my-img-component my-img-component--eager"
    decoding="async" fetchpriority="high" sizes="400px"
    src="https://example.com/image-400x267-crop-52-65-q80-sharpen10.jpg"
    srcset="
      https://example.com/image-400x267-crop-52-65-q80-sharpen10.jpg 400w,
      https://example.com/image-800x533-crop-52-65-q80-sharpen10.jpg 800w">
</picture>
```

You can also use `eager` and `lazy` attributes with art-directed images like this:

```php
'sourcesArtDirected' => [
  [
    'ratio' => '21/9',
    'media' => '(min-width: 800px)',
    'image' => $block->imagetwo()->toFile(),
    'attributes' => [
      'shared' => [
        'attribute' => 'value'
      ]
      'eager' => [
        'attribute' => 'value'
      ]
      'lazy' => [
        'attribute' => 'value'
      ]
    ]
  ]
],
```
