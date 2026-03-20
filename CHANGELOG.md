# Changelog

All notable changes to this project will be documented in this file.

## [0.2.0] - unreleased
[0.2.0]: https://github.com/timnarr/kirby-imagex/compare/0.1.4...HEAD

### Added
- New `loading` option with values `'eager'` or `'lazy'` (replaces `critical`)
- Automatic `fetchpriority="high"` when `loading: 'eager'` (overridable via `attributes.img`)
- Default `decoding="async"` attribute (overridable via `attributes.img`)
- Simplified flat attribute syntax — attributes are auto-converted to `shared` structure
- New `normalizeAttributesStructure()` helper function
- Cache invalidation when `srcset` config changes
- Improved `compareFormats` with weighted multi-sample approach (samples first, middle, last srcset widths)
- Per-image format comparison for art-directed images (each image is compared individually when using different source files)
- New helper functions: `getSampleElements()` and `calculateWeightedFormatSize()`
- New method `getSmallestFormatForImage()` with optional image and ratio parameters
- New `compareFormatsWeights` global config option — controls how sampled file sizes are weighted when `compareFormats` is active. Presets: `'mobile'` (default, 50/30/20), `'desktop'` (20/30/50), `'balanced'` (34/33/33). Custom weights can be passed as an array (must sum to `1.0`)
- New `resolveCompareFormatsWeights()` helper for preset resolution and validation
- Format comparison result is now cached per image — cache key is derived from file ID, last-modified timestamp, ratio, srcset preset, and active formats; auto-invalidates when a file is replaced or updated
- `crop` is automatically set to `true` in srcset entries where the height is ratio-calculated and `crop` is not explicitly configured
- Exception is now thrown when `thumbs.srcsets` is not configured in `config.php`
- New `imagex-picture-json` snippet that returns a JSON structure instead of HTML — useful for headless CMS setups, API endpoints, and JavaScript-driven rendering (accepts the same options as `imagex-picture`)
- New unit tests: 3 tests for `normalizeAttributesStructure()`, 7 tests for `resolveCompareFormatsWeights()`, additional edge case tests for helper functions

### Changed
- **BREAKING:** `critical` option renamed to `loading` with string values `'eager'`/`'lazy'` instead of boolean
- **BREAKING:** `srcsetName` option renamed to `srcset`
- **BREAKING:** `formatSizeHandling` option renamed to `compareFormats`
- **BREAKING:** `imgAttributes`, `pictureAttributes`, `sourcesAttributes` merged into single `attributes` option with `img`, `picture`, `sources` keys
- **BREAKING:** `sourcesArtDirected` renamed to `artDirection`
- **BREAKING:** `class` attribute must now be an array, no longer accepts strings
- Improved error messages with available options when invalid values are passed
- User-defined attributes always take precedence over Imagex-generated defaults for all attributes — including `src`, `srcset`, `width`, `height`, `loading`, `fetchpriority`, and `decoding`
- `relativeUrls` now also processes user-defined URL attributes (previously only applied to Imagex-generated URLs)

### Fixed
- PHP 8.4 compatibility

### Migration Guide

#### From 0.1.x to 0.2.0

**1. Replace `critical` with `loading`**
```php
// Before
'critical' => true,
'critical' => false,

// After
'loading' => 'eager',
'loading' => 'lazy',
```

**2. Replace `srcsetName` with `srcset`**
```php
// Before
'srcsetName' => 'my-preset',

// After
'srcset' => 'my-preset',
```

**3. Replace `formatSizeHandling` with `compareFormats`**
```php
// Before
'formatSizeHandling' => true,

// After
'compareFormats' => true,
```

**4. Merge attribute options into `attributes`**
```php
// Before
'imgAttributes' => [
  'shared' => ['alt' => 'text', 'sizes' => '100vw'],
  'lazy' => ['class' => ['lazyload']],
],
'pictureAttributes' => [
  'shared' => ['class' => ['my-picture']],
],
'sourcesAttributes' => [
  'shared' => ['sizes' => '100vw'],
],

// After
'attributes' => [
  'img' => [
    'shared' => ['alt' => 'text', 'sizes' => '100vw'],
    'lazy' => ['class' => ['lazyload']],
  ],
  'picture' => [
    'shared' => ['class' => ['my-picture']],
  ],
  'sources' => [
    'shared' => ['sizes' => '100vw'],
  ],
],

// Or use simplified flat syntax (auto-converted to 'shared')
'attributes' => [
  'img' => ['alt' => 'text', 'sizes' => '100vw'],
  'picture' => ['class' => ['my-picture']],
  'sources' => ['sizes' => '100vw'],
],
```

**5. Rename `sourcesArtDirected` to `artDirection`**
```php
// Before
'sourcesArtDirected' => [
  [
    'media' => '(min-width: 800px)',
    'ratio' => '21/9',
    'image' => $wideImage,
  ],
],

// After
'artDirection' => [
  [
    'media' => '(min-width: 800px)',
    'ratio' => '21/9',
    'image' => $wideImage,
  ],
],
```

**6. Remove manual `fetchpriority` for eager images**

`fetchpriority="high"` is now automatically set when `loading: 'eager'`. You can still override it:
```php
'loading' => 'eager',
'attributes' => [
  'img' => [
    'fetchpriority' => 'low', // Override automatic 'high'
  ],
],
```

**7. Remove manual `decoding` if using default**

`decoding="async"` is now set by default. You can still override it:
```php
'attributes' => [
  'img' => [
    'decoding' => 'sync', // Override automatic 'async'
  ],
],
```

**8. Convert `class` attribute from string to array**

The `class` attribute must now be an array. This allows for easier conditional classes and merging.
```php
// Before
'class' => 'my-image another-class',

// After
'class' => ['my-image', 'another-class'],

// Conditional classes are now cleaner
'class' => [
  'my-image',
  $isActive ? 'is-active' : null,  // null values are filtered out
],
```

## [0.1.4] - May 28, 2025
[0.1.4]: https://github.com/timnarr/kirby-imagex/releases/tag/0.1.4

Previous stable release.
