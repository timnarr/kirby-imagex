# Examples: Art-Directed Images

1. [Ratio Change at Media Condition](#example-1-ratio-change-at-media-condition)
2. [Image and Ratio Change at Media Condition](#example-2-image-and-ratio-change-at-media-condition)
3. [Mixed: Some Sources Reuse the Main Image, Others Use a Different One](#example-3-mixed-some-sources-reuse-the-main-image-others-use-a-different-one)

## Example 1. Ratio Change at Media Condition

The `image` key is optional in each `artDirection` entry. When omitted, Imagex falls back to the
main `image`. This example changes only the crop ratio at a breakpoint — no second image file needed.

### Global Plugin Config
These are all default values, so you don't need to set them explicitly and I've only added them here for the demo.

```php
// config.php
return [
  'timnarr.imagex' => [
    'cache' => true,
    'customLazyloading' => false,
    'formats' => ['avif', 'webp'],
    'addOriginalFormatAsSource' => false,
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
  'srcset' => 'imagex-demo',
  'ratio' => '3/2', // and we set it to 3/2
  'artDirection' => [
    [
      // change ratio to 21/9 at `(min-width: 800px)` — same image, no `image` key needed
      'media' => '(min-width: 800px)',
      'ratio' => '21/9'
      // no `image` → Imagex reuses the main image above
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
  'srcset' => 'imagex-demo',
  'ratio' => '3/2', // and we set it to 3/2
  'artDirection' => [
    [
      // but change it to 21/9 at `(min-width: 800px)` with a different image
      'media' => '(min-width: 800px)',
      'ratio' => '21/9',
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
## Example 3. Mixed: Some Sources Reuse the Main Image, Others Use a Different One

This example combines both approaches: one breakpoint changes only the ratio (no `image` key,
falls back to main image), while another breakpoint uses a completely different image.

### Global Plugin Config
```php
// same as above
```

### Snippet Options
```php
// Define your options and pass them to the `imagex` snippet
<?php
$options = [
  'image' => $image->toFile(), // let's assume: `image.jpg`, portrait photo
  'srcset' => 'imagex-demo',
  'ratio' => '3/4', // portrait on mobile
  'artDirection' => [
    [
      // at large screens: wide crop of the same image — no second file needed
      'media' => '(min-width: 1200px)',
      'ratio' => '21/9',
      // no `image` → falls back to main image, just cropped differently
    ],
    [
      // at medium screens: a different image entirely (e.g. a landscape version)
      'media' => '(min-width: 600px)',
      'ratio' => '16/9',
      'image' => $landscapeImage->toFile(), // let's assume: `landscape.jpg`
    ],
  ]
];
?>

<?php snippet('imagex-picture', $options) ?>
```
