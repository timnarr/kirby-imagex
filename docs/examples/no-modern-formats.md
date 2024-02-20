# Examples: No Modern Formats

1. [No Modern Formats](#example-1-no-modern-formats)
2. [No Modern Formats with Art Direction](#example-2-no-modern-formats-with-art-direction)

## Example 1. No Modern Formats
### Global Plugin Config
```php
// config.php
return [
  'timnarr.imagex' => [
    'formats' => [],
    'includeInitialFormat' => true,
    'noSrcsetInImg' => true,
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
  'ratio' => '3/2'
];
?>

<?php snippet('imagex-picture', $options) ?>
```

### Final HTML Output
```html
<picture>
  <source
    width="400" height="267" type="image/jpeg"
    srcset="
      https://example.com/image-400x267-crop-52-65-q80-sharpen10.jpg 400w,
      https://example.com/image-800x533-crop-52-65-q80-sharpen10.jpg 800w">
  <img
    width="400" height="267" decoding="async" loading="lazy"
    src="https://example.com/image-400x267-crop-52-65-q80-sharpen10.jpg">
</picture>
```

## Example 2. No Modern Formats with Art Direction
### Global Plugin Config
```php
// like above
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
    width="400" height="171" media="(min-width: 800px)" type="image/png"
    srcset="
      https://example.com/different-image-400x171-crop-51-52-q80-sharpen10.png 400w,
      https://example.com/different-image-800x343-crop-51-52-q80-sharpen10.png 800w">
  <source
    width="400" height="267" type="image/jpeg"
    srcset="
      https://example.com/image-400x267-crop-52-65-q80-sharpen10.jpg 400w,
      https://example.com/image-800x533-crop-52-65-q80-sharpen10.jpg 800w">
  <img
    width="400" height="267" decoding="async" loading="lazy"
    src="https://example.com/image-400x267-crop-52-65-q80-sharpen10.jpg">
</picture>
```
