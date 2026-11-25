<?php

declare(strict_types = 1);

@include_once __DIR__ . '/vendor/autoload.php';

Kirby::plugin('timnarr/imagex', [
	'options' => [
		'cache' => true,
		'compareFormatsWeights' => 'mobile',
		'customLazyLoading' => false,
		'formats' => ['avif', 'webp'],
		'addOriginalFormatAsSource' => false,
		'srcsetInImg' => true,
		'relativeUrls' => false,
		'stack' => null,
	],
	'snippets' => [
		'imagex-picture' => __DIR__ . '/snippets/imagex-picture.php',
		'imagex-picture-json' => __DIR__ . '/snippets/imagex-picture-json.php',
	],
	'fileMethods' => [
		/**
		 * Create an Imagex instance for this image, e.g. `<?= $image->imagex(['ratio' => '16/9']) ?>`.
		 *
		 * Unlike the snippets, it only gets the options you pass: unknown options throw,
		 * and controller variables with the same name as an option are never picked up.
		 *
		 * @param array $options The snippet options, without 'image'.
		 * @return \TimNarr\Imagex Renders the `imagex-picture` snippet when echoed; toArray() returns the JSON data.
		 * @throws \Kirby\Exception\InvalidArgumentException If an option is invalid or 'image' is passed.
		 */
		'imagex' => function (array $options = []): TimNarr\Imagex {
			if (array_key_exists('image', $options)) {
				throw new Kirby\Exception\InvalidArgumentException("[kirby-imagex] \$file->imagex() uses the file itself as 'image'. Remove 'image' from the options.");
			}

			return new TimNarr\Imagex([...$options, 'image' => $this]);
		},
		/**
		 * Generate a thumb with dimensions derived from an aspect ratio.
		 *
		 * @param string $ratio Aspect ratio string in "x/y" format, or 'intrinsic' to use the image's natural ratio.
		 * @param array $options Additional thumb options (e.g. width, quality, format). 'crop' defaults to true and is never false.
		 * @return \Kirby\Cms\FileVersion|\Kirby\Cms\File|\Kirby\Filesystem\Asset The thumb, as returned by Kirby's thumb().
		 * @throws \Kirby\Exception\InvalidArgumentException If the ratio is invalid or a 'height' is passed.
		 */
		'thumbRatio' => function (string $ratio, array $options = []): Kirby\Cms\FileVersion|Kirby\Cms\File|Kirby\Filesystem\Asset {
			if (isset($options['height'])) {
				throw new Kirby\Exception\InvalidArgumentException("[kirby-imagex] thumbRatio() derives the height from the ratio. Remove 'height' from the options and pass a 'width' instead.");
			}

			['x' => $ratioX, 'y' => $ratioY] = \TimNarr\Imagex\getAspectRatio($ratio, $this);
			$width = $options['width'] ?? $this->width();
			$height = (int)round($width * $ratioY / $ratioX);

			return $this->thumb([
				...$options,
				'width'  => $width,
				'height' => $height,
				// Never false, like in srcset presets: without cropping the thumb loses the ratio
				'crop'   => empty($options['crop']) ? true : $options['crop'],
			]);
		},
	],
]);
