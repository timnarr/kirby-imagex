# Thumbs Config

I use this thumbs config in all the examples. I limit this to have only two widths defined here to keep the examples simple. You propably have more widths defined in a real project. `crop` should be `true` everywhere, because we switch ratios and want to use Kirby's focus functionality but you can of course also use `'crop' => 'center'` and so on.

```php
// config.php
'thumbs' => [
  'srcsets' => [
    'imagex-demo' => [
      '400w' => ['width' => 400, 'crop' => true, 'quality' => 80, 'sharpen' => 10],
      '800w' => ['width' => 800, 'crop' => true, 'quality' => 80, 'sharpen' => 10],
    ],
    'imagex-demo-webp' => [
      '400w' => ['width' => 400, 'crop' => true, 'quality' => 75, 'sharpen' => 10, 'format' => 'webp'],
      '800w' => ['width' => 800, 'crop' => true, 'quality' => 75, 'sharpen' => 10, 'format' => 'webp'],
    ],
    'imagex-demo-avif' => [
      '400w' => ['width' => 400, 'crop' => true, 'quality' => 65, 'sharpen' => 25, 'format' => 'avif'],
      '800w' => ['width' => 800, 'crop' => true, 'quality' => 65, 'sharpen' => 25, 'format' => 'avif'],
    ],
    // ... other srcset presets
```
