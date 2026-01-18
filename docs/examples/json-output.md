# Example: JSON Output

The `imagex-picture-json` snippet provides JSON output instead of HTML markup. This is useful for:
- Headless CMS setups
- API endpoints
- JavaScript-driven rendering (SPA, React, Vue, etc.)
- Custom client-side image handling

## Usage

The `imagex-picture-json` snippet accepts the same options as `imagex-picture` but returns JSON data instead of HTML.

### Global Plugin Config
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
<?php
$options = [
  'image' => $image->toFile(), // let's assume: `image.jpg` with 16/9 aspect ratio
  'srcset' => 'imagex-demo',
  'ratio' => '16/9',
  'attributes' => [
    'img' => [
      'alt' => $image->toFile()->alt(),
      'sizes' => '(min-width: 800px) 400px, 100vw',
      'class' => ['my-image'],
    ],
    'picture' => [
      'class' => ['my-picture'],
      'data-component' => 'responsive-image'
    ]
  ]
];

// Important: Pass `true` as third parameter to return the snippet output
$json = snippet('imagex-picture-json', $options, true);

// Output JSON
header('Content-Type: application/json');
echo $json;

// Or decode and process the data
$data = json_decode($json, true);
?>
```

### JSON Output
The JSON structure groups `picture` attributes with `sources` nested inside, and `img` attributes separately. Note that `class` and `style` arrays are converted to strings in the JSON output.

```json
{
    "picture": {
        "class": "my-picture",
        "data-component": "responsive-image",
        "sources": [
            {
                "type": "image/avif",
                "width": 400,
                "height": 225,
                "sizes": "(min-width: 800px) 400px, 100vw",
                "srcset": "https://example.com/image-400x225-crop-52-65-q65-sharpen25.avif 400w, https://example.com/image-800x450-crop-52-65-q65-sharpen25.avif 800w"
            },
            {
                "type": "image/webp",
                "width": 400,
                "height": 225,
                "sizes": "(min-width: 800px) 400px, 100vw",
                "srcset": "https://example.com/image-400x225-crop-52-65-q75-sharpen10.webp 400w, https://example.com/image-800x450-crop-52-65-q75-sharpen10.webp 800w"
            }
        ]
    },
    "img": {
        "src": "https://example.com/image-400x225-crop-52-65-q80-sharpen10.jpg",
        "width": 400,
        "height": 225,
        "decoding": "async",
        "loading": "lazy",
        "srcset": "https://example.com/image-400x225-crop-52-65-q80-sharpen10.jpg 400w, https://example.com/image-800x450-crop-52-65-q80-sharpen10.jpg 800w",
        "class": "my-image",
        "sizes": "(min-width: 800px) 400px, 100vw",
        "alt": "A cat sits in the sun in front of yellow flowers."
    }
}
```

### JSON Output with Art Direction
When using `artDirection`, the sources array includes entries with `media` attributes:

```php
$options = [
  'image' => $image->toFile(),
  'srcset' => 'imagex-demo',
  'ratio' => '3/2',
  'artDirection' => [
    [
      'media' => '(min-width: 800px)',
      'ratio' => '21/9',
    ]
  ]
];
```

```json
{
    "picture": {
        "sources": [
            {
                "type": "image/avif",
                "width": 400,
                "height": 171,
                "media": "(min-width: 800px)",
                "srcset": "https://example.com/image-400x171-crop-52-65-q65-sharpen25.avif 400w, ..."
            },
            {
                "type": "image/avif",
                "width": 400,
                "height": 267,
                "srcset": "https://example.com/image-400x267-crop-52-65-q65-sharpen25.avif 400w, ..."
            }
        ]
    },
    "img": {
        "src": "https://example.com/image-400x267-crop-52-65-q80-sharpen10.jpg",
        "width": 400,
        "height": 267,
        "decoding": "async",
        "loading": "lazy",
        "srcset": "..."
    }
}
```

## Notes

- All options from `imagex-picture` work with `imagex-picture-json`
- The third parameter (`true`) in `snippet('imagex-picture-json', $options, true)` is required to return the output instead of echoing it
- The JSON includes all calculated attributes, srcsets, and URLs
- Art-directed sources are also included in the `sources` array with their media queries
- Combine with Kirby's routing system to create custom API endpoints
