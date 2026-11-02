# Example: Preload Links via Template Stacks

A hero image that is the page's LCP element: Imagex pushes preload links and its art-direction styles into a Kirby 6 template stack in the `<head>`.

### Global Plugin Config
```php
// config.php
return [
  'timnarr.imagex' => [
    'formats' => ['avif', 'webp'],
    'stack' => 'imagex-head', // name of the stack output in your <head>
  ],
];
```

### Header Snippet
```php
// site/snippets/header.php
<!DOCTYPE html>
<html>
<head>
  <title><?= $page->title() ?></title>
  <?php stack('imagex-head') ?>
</head>
<body>
```

`stack()` outputs a placeholder that Kirby fills once the whole page is rendered, so snippets further down the page can still push into it.

### Snippet Options
```php
<?php
$options = [
  'image' => $image->toFile(),
  'srcset' => 'imagex-demo',
  'ratio' => '3/2',
  'loading' => 'eager', // required for preload
  'preload' => true,
  'attributes' => [
    // a <source> doesn't inherit the <img>'s sizes, so set it for the sources
    'sources' => ['sizes' => '100vw'],
    'img' => ['sizes' => '100vw', 'alt' => $image->alt()],
  ],
  'artDirection' => [
    [
      'media' => '(width >= 800px)',
      'ratio' => '21/9',
    ],
  ],
];
?>

<?php snippet('imagex-picture', $options) ?>
```

### Final HTML Output
```html
<head>
  <title>Home</title>
  <link as="image" fetchpriority="high" imagesizes="100vw"
    imagesrcset="
      https://example.com/image-400x171-crop-52-65-q65-sharpen25.avif 400w,
      https://example.com/image-800x343-crop-52-65-q65-sharpen25.avif 800w"
    media="(width >= 800px)" rel="preload" type="image/avif">
  <link as="image" fetchpriority="high" imagesizes="100vw"
    imagesrcset="
      https://example.com/image-400x267-crop-52-65-q65-sharpen25.avif 400w,
      https://example.com/image-800x533-crop-52-65-q65-sharpen25.avif 800w"
    media="(width < 800px)" rel="preload" type="image/avif">
  <style>@media (width >= 800px) { #imagex-a1b2c3d4 { aspect-ratio: 21 / 9 !important; } }</style>
</head>
<body>
  <picture>
    <source
      width="400" height="171" media="(width >= 800px)" sizes="100vw" type="image/avif"
      srcset="
        https://example.com/image-400x171-crop-52-65-q65-sharpen25.avif 400w,
        https://example.com/image-800x343-crop-52-65-q65-sharpen25.avif 800w">
    <source
      width="400" height="171" media="(width >= 800px)" sizes="100vw" type="image/webp"
      srcset="
        https://example.com/image-400x171-crop-52-65-q80-sharpen10.webp 400w,
        https://example.com/image-800x343-crop-52-65-q80-sharpen10.webp 800w">
    <source
      width="400" height="267" sizes="100vw" type="image/avif"
      srcset="
        https://example.com/image-400x267-crop-52-65-q65-sharpen25.avif 400w,
        https://example.com/image-800x533-crop-52-65-q65-sharpen25.avif 800w">
    <source
      width="400" height="267" sizes="100vw" type="image/webp"
      srcset="
        https://example.com/image-400x267-crop-52-65-q80-sharpen10.webp 400w,
        https://example.com/image-800x533-crop-52-65-q80-sharpen10.webp 800w">
    <img
      id="imagex-a1b2c3d4" width="400" height="267" alt="…" sizes="100vw"
      decoding="async" fetchpriority="high"
      src="https://example.com/image-400x267-crop-52-65-q80-sharpen10.jpg"
      srcset="
        https://example.com/image-400x267-crop-52-65-q80-sharpen10.jpg 400w,
        https://example.com/image-800x533-crop-52-65-q80-sharpen10.jpg 800w">
  </picture>
```

### What Happens Here
- Only AVIF is preloaded. A browser fetches every preload link whose `type` it supports, so preloading WebP as well would download the image twice in browsers supporting both. Browsers without AVIF support skip the links and load the image from the `<picture>` as usual. With `compareFormats: true`, Imagex preloads the format it picked as the smallest.
- The second link's `media` excludes the first one: at 800px and up, only the 21/9 crop is fetched — exactly the one `<picture>` uses.
- The `<style>` element moved into the `<head>` as well. Without the `stack` option, it would be rendered right before the `<picture>`, and `preload: true` would throw an error.
