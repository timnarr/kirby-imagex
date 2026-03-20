# Examples: `thumbRatio()` File Method

1. [Custom `src` with Matching Ratio](#example-1-custom-src-with-matching-ratio)
2. [Custom `src` for SEO with a Different Ratio](#example-2-custom-src-for-seo-with-a-different-ratio)

---

## Example 1. Custom `src` with Matching Ratio

The typical use case: you want to override the `src` attribute on the `<img>` element (e.g. for crawlers or as a custom lazy-loading placeholder) while keeping the ratio consistent with the rest of the picture element.

Without `thumbRatio` you would have to calculate the height manually:

```php
// Manual – error-prone if the ratio changes
'src' => $image->toFile()->thumb(['width' => 1200, 'height' => 675, 'crop' => true])->url()
```

With `thumbRatio` you reuse the same `$ratio` variable:

```php
// Automatic – height is always consistent with the ratio
'src' => $image->toFile()->thumbRatio($ratio, ['width' => 1200])->url()
```

### Snippet Options

```php
<?php
$ratio = '16/9';
$image = $page->image()->toFile();

$options = [
  'image' => $image,
  'srcset' => 'imagex-demo',
  'ratio' => $ratio,
  'attributes' => [
    'img' => [
      'shared' => [
        'class' => ['my-image'],
        // thumbRatio uses the same $ratio — height is calculated automatically
        'src' => $image->thumbRatio($ratio, ['width' => 1200])->url(),
      ],
    ],
  ],
];
?>

<?php snippet('imagex-picture', $options) ?>
```

### Result

The `src` attribute points to a `1200×675` crop (16:9), while the `srcset` and `<source>` elements deliver optimised sizes in modern formats. No manual height calculation needed.

---

## Example 2. Custom `src` for SEO with a Different Ratio

You can also use `thumbRatio` to supply a different ratio for crawlers only. Browsers always prefer `srcset` / `<source>` over `src`, so this does not affect the visual result for real users.

```php
<?php
$ratio = '16/9';
$image = $page->image()->toFile();

$options = [
  'image' => $image,
  'srcset' => 'imagex-demo',
  'ratio' => $ratio, // browsers see 16:9
  'attributes' => [
    'img' => [
      'shared' => [
        // crawlers and social-media bots get a square image from src
        'src' => $image->thumbRatio('1/1', ['width' => 1200])->url(),
      ],
    ],
  ],
];
?>

<?php snippet('imagex-picture', $options) ?>
```

### Further Options

```php
// AVIF format, custom quality
$image->thumbRatio('3/2', ['width' => 800, 'quality' => 65, 'format' => 'avif'])->url()

// Chain any Kirby File method after thumbRatio
$image->thumbRatio('16/9', ['width' => 800])->width()  // → 800
$image->thumbRatio('16/9', ['width' => 800])->height() // → 450
```
