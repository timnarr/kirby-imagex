# Examples: Content Negotiation

Content negotiation is an alternative to `<picture>`/`<source>` format switching. Instead of listing every format as a separate `<source>` element, Imagex generates all format variants on the server (AVIF, WebP, JPEG) but outputs only the original format in the HTML. Your web server then inspects the browser's `Accept` header and transparently serves the best available format.

**Requires server-side configuration.** See [Server Configuration](#server-configuration) below.

1. [Basic Content Negotiation](#example-1-basic-content-negotiation)
2. [Content Negotiation with Art Direction](#example-2-content-negotiation-with-art-direction)
3. [Server Configuration](#server-configuration)

---

## Example 1. Basic Content Negotiation

No art direction — Imagex generates all format thumbs and outputs a plain `<img>` without any `<source>` elements. The server decides which file to serve.

### Global Plugin Config
```php
// config.php
return [
  'timnarr.imagex' => [
    'contentNegotiation' => true,
    'formats' => ['avif', 'webp'], // still generated on disk, not output in HTML
  ],
];
```

### Snippet Options
```php
$options = [
  'image' => $image->toFile(), // let's assume: `image.jpg`
  'srcset' => 'my-srcset',
  'ratio' => '3/2',
];
```

### Final HTML Output
```html
<picture>
  <img
    width="400" height="267" decoding="async" loading="lazy"
    src="https://example.com/media/image-400x267-crop-q80"
    srcset="
      https://example.com/media/image-400x267-crop-q80 400w,
      https://example.com/media/image-800x533-crop-q80 800w">
</picture>
```

The browser requests `/media/image-400x267-crop-q80` (no extension). The server checks the `Accept` header, finds `image-400x267-crop-q80.avif` on disk, and responds with that file.

---

## Example 2. Content Negotiation with Art Direction

Art direction is fully supported. Each `<source>` now carries only a `media` attribute — no `type`. The server handles format selection for every URL in the srcset, including art-directed ones.

### Global Plugin Config
```php
// config.php
return [
  'timnarr.imagex' => [
    'contentNegotiation' => true,
    'formats' => ['avif', 'webp'],
  ],
];
```

### Snippet Options
```php
$options = [
  'image' => $image->toFile(), // let's assume: `portrait.jpg`
  'srcset' => 'my-srcset',
  'ratio' => '3/4',
  'artDirection' => [
    [
      'media' => '(min-width: 800px)',
      'ratio' => '21/9',
      'image' => $landscapeImage->toFile(), // let's assume: `landscape.jpg`
    ],
  ],
];
```

### Final HTML Output
```html
<picture>
  <source
    width="400" height="171" media="(min-width: 800px)"
    srcset="
      https://example.com/media/landscape-400x171-crop-q80 400w,
      https://example.com/media/landscape-800x343-crop-q80 800w">
  <img
    width="400" height="533" decoding="async" loading="lazy"
    src="https://example.com/media/portrait-400x533-crop-q80"
    srcset="
      https://example.com/media/portrait-400x533-crop-q80 400w,
      https://example.com/media/portrait-800x1067-crop-q80 800w">
</picture>
```

Compare this to the same config without content negotiation — you'd get 4 `<source>` elements (2 formats × 2 sources). With content negotiation: 1 `<source>` per breakpoint, same visual result.

All AVIF and WebP variants for both `landscape.jpg` and `portrait.jpg` are still generated on disk so the server can serve them.

---

## Server Configuration

The generated format variants sit on disk alongside the original thumbs. Your server needs a rule that intercepts requests for JPEG/PNG files, checks whether a better format variant exists, and serves it transparently — keeping the original URL in the browser.

> ⚠️ Always set the `Vary: Accept` response header when doing content negotiation. Without it, a CDN or shared proxy cache may store the AVIF response and serve it to a browser that only sent `Accept: image/jpeg`, causing broken images.

### Apache (.htaccess)

Requires `mod_rewrite` and `mod_headers`. Kirby stores all generated thumbs under `media/`, so the rules are scoped to that path. Place this in your `.htaccess` at the document root:

```apache
<IfModule mod_rewrite.c>
  RewriteEngine On

  # Extension-less URLs under /media/ — serve best available format
  # %{REQUEST_FILENAME} !-f ensures only extension-less requests are processed
  RewriteCond %{REQUEST_URI} ^/media/
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteCond %{HTTP_ACCEPT} image/avif
  RewriteCond %{DOCUMENT_ROOT}%{REQUEST_URI}.avif -f
  RewriteRule ^(.+)$ $1.avif [T=image/avif,L,E=img_neg:1]

  RewriteCond %{REQUEST_URI} ^/media/
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteCond %{HTTP_ACCEPT} image/webp
  RewriteCond %{DOCUMENT_ROOT}%{REQUEST_URI}.webp -f
  RewriteRule ^(.+)$ $1.webp [T=image/webp,L,E=img_neg:1]

  # Fallback to original format if no modern variant is accepted or available
  RewriteCond %{REQUEST_URI} ^/media/
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteCond %{DOCUMENT_ROOT}%{REQUEST_URI}.jpg -f
  RewriteRule ^(.+)$ $1.jpg [T=image/jpeg,L]

  RewriteCond %{REQUEST_URI} ^/media/
  RewriteCond %{REQUEST_FILENAME} !-f
  RewriteCond %{DOCUMENT_ROOT}%{REQUEST_URI}.png -f
  RewriteRule ^(.+)$ $1.png [T=image/png,L]
</IfModule>

<IfModule mod_headers.c>
  # Vary: Accept is set only when a format rewrite actually happened
  Header append Vary Accept env=img_neg
</IfModule>
```

### Nginx

Add the following inside your `server` block:

```nginx
location ~* ^/media/ {
    # Prefer AVIF, fall back to WebP, then JPEG, then PNG
    # Last matching `if` wins in Nginx, so put the most preferred format last
    set $ext "";
    if ($http_accept ~* "image/webp") {
        set $ext ".webp";
    }
    if ($http_accept ~* "image/avif") {
        set $ext ".avif";
    }

    # try_files: attempt negotiated format, fall back to original extensions
    # Files with extensions are served directly ($uri matches); extension-less
    # requests fall through to $uri.jpg / $uri.png as a final fallback
    try_files $uri$ext $uri.jpg $uri.png $uri =404;

    add_header Vary Accept always;
}
```

### Caddy

```caddy
@media {
    path_regexp ^/media/
}

handle @media {
    @accepts_avif header Accept *image/avif*
    @accepts_webp header Accept *image/webp*

    handle @accepts_avif {
        try_files {path}.avif {path}.jpg {path}.png
    }
    handle @accepts_webp {
        try_files {path}.webp {path}.jpg {path}.png
    }
    handle {
        try_files {path}.jpg {path}.png {path}
    }

    header Vary Accept
}
```

### When are the format files available?

Kirby generates thumb files **on the first request** that renders the snippet. The AVIF and WebP variants for a given image are written to disk when `contentNegotiation` is enabled and the page is first loaded. On subsequent requests, the server-side rules find the files and serve them.

If you want all variants pre-generated before going live, render all relevant pages once (e.g. via a warmup script or by visiting them in a browser) so the thumb files exist before real traffic hits.
