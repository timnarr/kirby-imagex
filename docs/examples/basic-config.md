# Examples: Basic Configuration

1. [Minimal Configuration Based on Imagex Defaults](#example-1-minimal-configuration-based-on-imagex-defaults)
2. [Change Ratio to `16/9` and Add HTML Attributes to `<img>`](#example-2-change-ratio-to-169-and-add-html-attributes-to-img)
2. [Add HTML Attributes to `<picture>`](#example-3-add-html-attributes-to-picture)

## Example 1. Minimal Configuration Based on Imagex Defaults

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
];
?>

<?php snippet('imagex-picture', $options) ?>
```

### Final HTML Output
```html
<picture>
  <source
    width="400" height="400" type="image/avif"
    srcset="
      https://example.com/image-400x400-crop-52-65-q65-sharpen25.avif 400w,
      https://example.com/image-800x800-crop-52-65-q65-sharpen25.avif 800w">
  <source
    width="400" height="400" type="image/webp"
    srcset="
      https://example.com/image-400x400-crop-52-65-q75-sharpen10.webp 400w,
      https://example.com/image-800x800-crop-52-65-q75-sharpen10.webp 800w">
  <img
    width="400" height="400" decoding="async" loading="lazy"
    src="https://example.com/image-400x400-crop-52-65-q80-sharpen10.jpg"
    srcset="
      https://example.com/image-400x400-crop-52-65-q80-sharpen10.jpg 400w,
      https://example.com/image-800x800-crop-52-65-q80-sharpen10.jpg 800w">
</picture>
```

## Example 2. Change Ratio to `16/9` and Add HTML Attributes to `<img>`

### Global Plugin Config
```php
// same as above
```

### Snippet Options
```php
<?php
$options = [
  'image' => $image->toFile(), // let's assume: `image.jpg` and aspect ratio of 1/1
  'srcsetName' => 'imagex-demo',
  'ratio' => '16/9',
  'imgAttributes' => [
    'shared' => [
      'alt' => $image->toFile()->alt(),
      'sizes' => '(min-width: 800px) 400px, 100vw',
      'class' => [
        'my-image',
        $conditionalClass ? 'my-image--variant' : null // let's ssume $conditionalClass is `true`
      ],
      'style' => [
        'object-fit: cover;',
        'object-position: ' . $image->toFile()->focus(); . ';'
      ]
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
    width="400" height="225" type="image/avif"
    srcset="
      https://example.com/image-400x225-crop-52-65-q65-sharpen25.avif 400w,
      https://example.com/image-800x450-crop-52-65-q65-sharpen25.avif 800w">
  <source
    width="400" height="225" type="image/webp"
    srcset="
      https://example.com/image-400x225-crop-52-65-q75-sharpen10.webp 400w,
      https://example.com/image-800x450-crop-52-65-q75-sharpen10.webp 800w">
  <img
    class="my-image my-image--variant" sizes="(min-width: 800px) 400px, 100vw"
    style="object-fit: cover; object-position: 52% 65%;"
    width="400" height="225" decoding="async" loading="lazy"
    alt="A cat sits in the sun in front of yellow flowers."
    src="https://example.com/image-400x225-crop-52-65-q80-sharpen10.jpg"
    srcset="
      https://example.com/image-400x225-crop-52-65-q80-sharpen10.jpg 400w,
      https://example.com/image-800x450-crop-52-65-q80-sharpen10.jpg 800w">
</picture>
```

## Example 3. Add HTML Attributes to `<picture>`

### Global Plugin Config
```php
// same as above
```

### Snippet Options
```php
<?php
$options = [
  'image' => $image->toFile(), // let's assume: `image.jpg` and aspect ratio of 1/1
  'srcsetName' => 'imagex-demo',
  'ratio' => '16/9',
  'imgAttributes' => [
    'shared' => [
      'alt' => $image->toFile()->alt(),
      'sizes' => '(min-width: 800px) 400px, 100vw',
      'class' => ['my-image'],
      'style' => [
        'object-fit: cover;',
        'object-position: ' . $image->toFile()->focus(); . ';'
      ]
    ],
  ],
  'pictureAttributes' => [
    'shared' => [
      'class' => ['my-picture'],
      'data-attr' => 'some-data-attribute'
    ]
  ]
];
?>

<?php snippet('imagex-picture', $options) ?>
```

### Final HTML Output
```html
<picture class="my-picture" data-attr="some-data-attribute">
  <source
    width="400" height="225" type="image/avif"
    srcset="
      https://example.com/image-400x225-crop-52-65-q65-sharpen25.avif 400w,
      https://example.com/image-800x450-crop-52-65-q65-sharpen25.avif 800w">
  <source
    width="400" height="225" type="image/webp"
    srcset="
      https://example.com/image-400x225-crop-52-65-q75-sharpen10.webp 400w,
      https://example.com/image-800x450-crop-52-65-q75-sharpen10.webp 800w">
  <img
    class="my-image my-image--variant" sizes="(min-width: 800px) 400px, 100vw"
    style="object-fit: cover; object-position: 52% 65%;"
    width="400" height="225" decoding="async" loading="lazy"
    alt="A cat sits in the sun in front of yellow flowers."
    src="https://example.com/image-400x225-crop-52-65-q80-sharpen10.jpg"
    srcset="
      https://example.com/image-400x225-crop-52-65-q80-sharpen10.jpg 400w,
      https://example.com/image-800x450-crop-52-65-q80-sharpen10.jpg 800w">
</picture>
```
