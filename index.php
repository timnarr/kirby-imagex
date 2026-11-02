<?php

declare(strict_types = 1);

@include_once __DIR__ . '/vendor/autoload.php';

Kirby::plugin('timnarr/imagex', [
	'options' => [
		'cache' => true,
		'compareFormatsWeights' => 'mobile',
		'customLazyloading' => false,
		'formats' => ['avif', 'webp'],
		'addOriginalFormatAsSource' => false,
		'noSrcsetInImg' => false,
		'relativeUrls' => false,
		'stack' => null,
	],
	'snippets' => [
		'imagex-picture' => __DIR__ . '/snippets/imagex-picture.php',
		'imagex-picture-json' => __DIR__ . '/snippets/imagex-picture-json.php',
	],
	'fileMethods' => [
		/**
		 * Generate a thumb with dimensions derived from an aspect ratio.
		 *
		 * @param string $ratio Aspect ratio string in "x/y" format, or 'intrinsic' to use the image's natural ratio.
		 * @param array $options Additional thumb options (e.g. width, quality, format). 'crop' defaults to true and is never false.
		 * @return \Kirby\Cms\FileVersion|\Kirby\Cms\File|\Kirby\Filesystem\Asset The thumb, as returned by Kirby's thumb().
		 */
		'thumbRatio' => function (string $ratio, array $options = []): Kirby\Cms\FileVersion|Kirby\Cms\File|Kirby\Filesystem\Asset {
			['x' => $ratioX, 'y' => $ratioY] = \TimNarr\getAspectRatio($ratio, $this);
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
