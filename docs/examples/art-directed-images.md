# Examples: Art-Directed Images

1. [Ratio Change at Media Condition](#example-1-ratio-change-at-media-condition)
2. [Image and Ratio Change at Media Condition](#example-2-image-and-ratio-change-at-media-condition)

## Example 1. Ratio Change at Media Condition

### Global Plugin Config
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
  'image' => $image->toFile(), // let's assume: `image.jpg` and aspect ratio of 1/1
  'srcsetName' => 'imagex-demo',
  'ratio' => '3/2' // and we set it to 3/2
  'sourcesArtDirected' => [
    [
      // but change it to 21/9 at `(min-width: 800px)`
      'ratio' => '21/9',
      'media' => '(min-width: 800px)'
    ]
  ]
];
?>

<?php snippet('imagex-picture', $options) ?>
```

### Final HTML Output
```html
<picture>
  <source
    width="400" height="171" media="(min-width: 800px)" type="image/avif"
    srcset="
      https://example.com/image-400x171-crop-52-65-q65-sharpen25.avif 400w,
      https://example.com/image-800x343-crop-52-65-q65-sharpen25.avif 800w">
  <source
    width="400" height="267" type="image/avif"
    srcset="
      https://example.com/image-400x267-crop-52-65-q65-sharpen25.avif 400w,
      https://example.com/image-800x533-crop-52-65-q65-sharpen25.avif 800w">
  <source
    width="400" height="171"  media="(min-width: 800px)" type="image/webp"
    srcset="
      https://example.com/image-400x171-crop-52-65-q75-sharpen10.webp 400w,
      https://example.com/image-800x343-crop-52-65-q75-sharpen10.webp 800w">
  <source
    width="400" height="267" type="image/webp"
    srcset="
      https://example.com/image-400x267-crop-52-65-q75-sharpen10.webp 400w,
      https://example.com/image-800x533-crop-52-65-q75-sharpen10.webp 800w">
  <img
    width="400" height="267" decoding="async" loading="lazy"
    src="https://example.com/image-400x267-crop-52-65-q80-sharpen10.jpg"
    srcset="
      https://example.com/image-400x267-crop-52-65-q80-sharpen10.jpg 400w,
      https://example.com/image-800x533-crop-52-65-q80-sharpen10.jpg 800w">
</picture>
```

## Example 2. Image and Ratio Change at Media Condition

### Global Plugin Config
```php
// same as above
```

### Snippet Options
```php
// Define your options and pass them to the `imagex` snippet
<?php
$options = [
  'image' => $image->toFile(), // let's assume: `image.jpg` and aspect ratio of 1/1
  'srcsetName' => 'imagex-demo',
  'ratio' => '3/2' // and we set it to 3/2
  'sourcesArtDirected' => [
    [
      // but change it to 21/9 at `(min-width: 800px)`
      'ratio' => '21/9',
      'media' => '(min-width: 800px)',
      'image' => $mySecondImage->toFile() // let's assume: `different-image.png`
    ]
  ]
];
?>

<?php snippet('imagex-picture', $options) ?>
```

### Final HTML Output
```html
<picture>
  <source
    width="400" height="171" media="(min-width: 800px)" type="image/avif"
    srcset="
      https://example.com/different-image-400x171-crop-52-65-q65-sharpen25.avif 400w,
      https://example.com/different-image-800x343-crop-52-65-q65-sharpen25.avif 800w">
  <source
    width="400" height="267" type="image/avif"
    srcset="
      https://example.com/image-400x267-crop-52-65-q65-sharpen25.avif 400w,
      https://example.com/image-800x533-crop-52-65-q65-sharpen25.avif 800w">
  <source
    width="400" height="171"  media="(min-width: 800px)" type="image/webp"
    srcset="
      https://example.com/different-image-400x171-crop-52-65-q75-sharpen10.webp 400w,
      https://example.com/different-image-800x343-crop-52-65-q75-sharpen10.webp 800w">
  <source
    width="400" height="267" type="image/webp"
    srcset="
      https://example.com/image-400x267-crop-52-65-q75-sharpen10.webp 400w,
      https://example.com/image-800x533-crop-52-65-q75-sharpen10.webp 800w">
  <img
    width="400" height="267" decoding="async" loading="lazy"
    src="https://example.com/image-400x267-crop-52-65-q80-sharpen10.jpg"
    srcset="
      https://example.com/image-400x267-crop-52-65-q80-sharpen10.jpg 400w,
      https://example.com/image-800x533-crop-52-65-q80-sharpen10.jpg 800w">
</picture>
```
