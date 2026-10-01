<?php

declare(strict_types = 1);

use TimNarr\Imagex;

use function TimNarr\transformForJson;

// Missing options are passed as null; Imagex applies the defaults
$imagex = new Imagex([
	'artDirection' => $artDirection ?? null,
	'attributes' => $attributes ?? null,
	'compareFormats' => $compareFormats ?? null,
	'focus' => $focus ?? null,
	'image' => $image ?? null,
	'loading' => $loading ?? null,
	'nonce' => $nonce ?? null,
	'ratio' => $ratio ?? null,
	'srcset' => $srcset ?? null,
]);

$data = [
	'picture' => [
		...$imagex->getPictureAttributes(),
		'sources' => $imagex->getPictureSources(),
	],
	'img' => $imagex->getImgAttributes(),
	'artDirectionStyles' => $imagex->getArtDirectionStyles(),
];

$data = transformForJson($data);

echo json_encode($data, JSON_UNESCAPED_SLASHES);
