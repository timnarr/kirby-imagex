# Examples: Overriding Default Attributes

1. [Basic Override](#example-1-basic-override)
2. [SEO-Optimized `src`](#example-2-seo-optimized-src)
3. [Different Ratio for SEO](#example-3-different-ratio-for-seo)
4. [Overriding Dimensions](#example-4-overriding-dimensions)

---

## Example 1. Basic Override

Use `null` or `false` to remove an attribute. Unspecified attributes (like `decoding`, `fetchpriority`) still use Imagex defaults.

```php
<?php
$options = [
  'image' => $image,
  'attributes' => [
    'img' => [
      'shared' => [
        'width' => 500,  // Override calculated width
        'height' => 300, // Override calculated height
      ],
      'lazy' => [
        'data-src' => null,                    // Remove attribute
        'src' => 'custom-placeholder.jpg',     // Override src
        'loading' => 'custom-lazy',            // Override loading behavior
      ],
    ],
  ],
  'srcset' => 'my-srcset',
];

snippet('imagex-picture', $options);
```

---

## Example 2. SEO-Optimized `src`

Many crawlers (Google, Facebook, Twitter) don't fully understand `<picture>` elements and only read the `<img src>` attribute. By default Imagex uses the smallest srcset image as `src` (e.g. 400px). Overriding it with a larger image improves Google Image Search results, Open Graph/Twitter Card previews, and SEO rankings.

Modern browsers always prefer images from `srcset`/`<source>`, so overriding `src` does not affect what real users see.

```php
<?php
$options = [
  'image' => $image,
  'attributes' => [
    'img' => [
      'src' => $image->thumb(['width' => 1200, 'quality' => 85])->url(), // large image for crawlers
      'alt' => $image->alt(),
    ],
  ],
  'srcset' => 'my-srcset',
];

snippet('imagex-picture', $options);
```

Result: Browsers load optimised srcset images in avif/webp. Crawlers and social-media bots read the 1200px fallback from `src`.

---

## Example 3. Different Ratio for SEO

You can supply a different ratio for crawlers via `src` without affecting browsers.

```php
<?php
$options = [
  'image' => $image,
  'ratio' => '16/9', // browsers get 16:9 via srcset
  'attributes' => [
    'img' => [
      // crawlers/social-media get a 1:1 image from src
      'src' => $image->thumb(['width' => 1200, 'height' => 1200, 'crop' => true])->url(),
    ],
  ],
  'srcset' => 'my-srcset',
];
```

For a ratio-safe way to generate the `src` thumb, use the [`thumbRatio()` file method](/docs/examples/thumb-ratio.md):

```php
'src' => $image->toFile()->thumbRatio('1/1', ['width' => 1200])->url(),
```

---

## Example 4. Overriding Dimensions

When you override `width`/`height`, the srcset still uses thumbnails generated with the original `ratio`, which may not match your custom dimensions. If you override `src`/`srcset`, the `width` and `height` attributes still reflect the calculated ratio.

If you need to override dimensions, override all related attributes together:

```php
<?php
$options = [
  'image' => $image,
  'ratio' => '3/2',
  'attributes' => [
    'img' => [
      'shared' => [
        'width' => 600,
        'height' => 400, // matches 3:2 ratio
      ],
      'lazy' => [
        // custom srcset must also use 3:2 images
        'srcset' => 'custom-300.jpg 300w, custom-600.jpg 600w, custom-900.jpg 900w',
      ],
    ],
  ],
  'srcset' => 'my-srcset',
];
```

In most cases you should **not** need to override `width`, `height`, or `srcset` — let Imagex handle these automatically via the passed `ratio`.
