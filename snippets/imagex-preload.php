<?php

// This snippet is used to preload critical images to improve performance.
// It is used in the imagex-picture snippet and is not intended to be used directly.

if (kirby()->option('timnarr.imagex.preloadLinks')) {

	echo '<template class="kirby-imagex">';

	foreach ($pictureSources as $source) {

		// Skip sources that are not the smallest modern format
		if ($smallestModernFormat && !str_contains($source['type'], $smallestModernFormat)) {
			continue;
		}

		$attributes = [
			'rel' => 'preload',
			'as' => 'image',
			'imagesrcset' => $source['srcset'] ?? null,
			'imagesizes' => $source['sizes'] ?? null,
			'media' => $source['media'] ?? null,
			'type' => $source['type'] ?? null,
		];

		echo '<link ' . attr($attributes) . '>';
	}

	echo '</template>';
}
