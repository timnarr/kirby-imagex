<?php

@include_once __DIR__ . '/vendor/autoload.php';

Kirby::plugin('timnarr/imagex', [
	'options' => [
		'cache' => true,
		'customLazyloading' => false,
		'formats' => ['avif', 'webp'],
		'includeInitialFormat' => false,
		'noSrcsetInImg' => false,
		'relativeUrls' => false,
	],
	'snippets' => [
		'imagex-picture' => __DIR__ . '/snippets/imagex-picture.php',
	],
]);
