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
    'addOriginalFormatAsSource' => false,
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
      'sizes' => '(width >= 800px) 400px, 100vw',
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

In PHP, you don't need the JSON detour: `$file->imagex()` returns the same data as an array, and `json_encode()` gives the same JSON as the snippet.

```php
$data = $image->toFile()->imagex(['srcset' => 'imagex-demo', 'ratio' => '16/9'])->toArray();
```

### JSON Output
The JSON always has the same five keys. `picture`, `img` and every entry of `sources` are attribute objects you can spread onto the element as is. `artDirectionStyles` is a CSS string (empty without art direction), `preloadLinks` a list of `<link>` attribute objects (empty unless `preload` is enabled).

Note that `class` and `style` arrays are converted to strings in the JSON output. Attributes are left out exactly like in the HTML output: `null` and `false` remove an attribute, while empty strings like `alt=""` (decorative images) are kept. Kirby fields like `$image->alt()` are output as strings.

```json
{
    "picture": {
        "class": "my-picture",
        "data-component": "responsive-image"
    },
    "sources": [
        {
            "type": "image/avif",
            "width": 400,
            "height": 225,
            "sizes": "(width >= 800px) 400px, 100vw",
            "srcset": "https://example.com/image-400x225-crop-52-65-q65-sharpen25.avif 400w, https://example.com/image-800x450-crop-52-65-q65-sharpen25.avif 800w"
        },
        {
            "type": "image/webp",
            "width": 400,
            "height": 225,
            "sizes": "(width >= 800px) 400px, 100vw",
            "srcset": "https://example.com/image-400x225-crop-52-65-q80-sharpen10.webp 400w, https://example.com/image-800x450-crop-52-65-q80-sharpen10.webp 800w"
        }
    ],
    "img": {
        "src": "https://example.com/image-400x225-crop-52-65-q80-sharpen10.jpg",
        "width": 400,
        "height": 225,
        "decoding": "async",
        "loading": "lazy",
        "srcset": "https://example.com/image-400x225-crop-52-65-q80-sharpen10.jpg 400w, https://example.com/image-800x450-crop-52-65-q80-sharpen10.jpg 800w",
        "class": "my-image",
        "sizes": "(width >= 800px) 400px, 100vw",
        "alt": "A cat sits in the sun in front of yellow flowers."
    },
    "artDirectionStyles": "",
    "preloadLinks": []
}
```

Rendering it, e.g. in React:

```jsx
<picture {...data.picture}>
  {data.sources.map((source) => <source key={source.srcset} {...source} />)}
  <img {...data.img} />
</picture>
```

### JSON Output with Art Direction
When using `artDirection`, `sources` lists each art-directed entry in all formats first, with its `media` attribute, then the main image. Since the entry changes the ratio, `artDirectionStyles` contains the CSS that keeps the `<img>` in sync:

```php
$options = [
  'image' => $image->toFile(),
  'srcset' => 'imagex-demo',
  'ratio' => '3/2',
  'artDirection' => [
    [
      'media' => '(width >= 800px)',
      'ratio' => '21/9',
    ]
  ]
];
```

```json
{
    "picture": {},
    "sources": [
        {
            "type": "image/avif",
            "width": 400,
            "height": 171,
            "media": "(width >= 800px)",
            "srcset": "https://example.com/image-400x171-crop-52-65-q65-sharpen25.avif 400w, ..."
        },
        {
            "type": "image/webp",
            "width": 400,
            "height": 171,
            "media": "(width >= 800px)",
            "srcset": "https://example.com/image-400x171-crop-52-65-q80-sharpen10.webp 400w, ..."
        },
        {
            "type": "image/avif",
            "width": 400,
            "height": 267,
            "srcset": "https://example.com/image-400x267-crop-52-65-q65-sharpen25.avif 400w, ..."
        },
        {
            "type": "image/webp",
            "width": 400,
            "height": 267,
            "srcset": "https://example.com/image-400x267-crop-52-65-q80-sharpen10.webp 400w, ..."
        }
    ],
    "img": {
        "src": "https://example.com/image-400x267-crop-52-65-q80-sharpen10.jpg",
        "width": 400,
        "height": 267,
        "decoding": "async",
        "id": "imagex-a1b2c3d4",
        "loading": "lazy",
        "srcset": "..."
    },
    "artDirectionStyles": "@media (width >= 800px) { #imagex-a1b2c3d4 { aspect-ratio: 21 / 9 !important; } }",
    "preloadLinks": []
}
```

## Notes

- All options from `imagex-picture` work with `imagex-picture-json`
- The third parameter (`true`) in `snippet('imagex-picture-json', $options, true)` is required to return the output instead of echoing it
- The JSON includes all calculated attributes, srcsets, and URLs
- Art-directed sources are also included in the `sources` array with their media queries
- In PHP, `Imagex::toArray()` returns the same data as an array, e.g. to combine it with other data in a Kirby route
