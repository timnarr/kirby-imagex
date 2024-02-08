<?php

@include_once __DIR__ . '/vendor/autoload.php';

Kirby::plugin('timnarr/imagex', [
	'options' => [
		'cache' => true,
		'customLazyloading' => false,
		'formats' => ['avif', 'webp'],
		'includeInitialFormat' => false,
		'noSrcsetInImg' => false,
		'preloadLinks' => false,
		'relativeUrls' => false,
	],
	'snippets' => [
		'imagex-picture' => __DIR__ . '/snippets/imagex-picture.php',
		'imagex-preload' => __DIR__ . '/snippets/imagex-preload.php',
	],
	'hooks' => [
		'page.render:after' => function ($contentType, $data, $html, $page) {
			if ($contentType === 'html') {
				if (!kirby()->option('timnarr.imagex.preloadLinks')) {
					return $html;
				}

				// Init a new DOMDocument
				$dom = new DOMDocument();

				// Load HTML, suppressing warnings with '@'
				@$dom->loadHTML($html);

				$templateElements = $dom->getElementsByTagName('template');

				// Get and store HTML from 'imagex' templates and prepare them for removal
				$extractedPreloadLinks = '';
				$templateTagsToRemove = [];
				foreach ($templateElements as $template) {
					if ($template->getAttribute('class') === 'kirby-imagex') {
						foreach ($template->childNodes as $childNode) {
							$extractedPreloadLinks .= $dom->saveHTML($childNode);
						}
						$templateTagsToRemove[] = $template;
					}
				}

				// Remove 'imagex' templates from the DOM
				foreach ($templateTagsToRemove as $template) {
					$template->parentNode->removeChild($template);
				}

				// Insert the preload links before </head>
				$finalHTML = str_replace('</head>', $extractedPreloadLinks . '</head>', $dom->saveHTML());

				return $finalHTML;
			}
		},
	],
]);
