<?php

declare(strict_types = 1);

use TimNarr\Imagex;

use function TimNarr\transformForJson;

$imagex = new Imagex([
	'artDirection' => $artDirection ?? [],
	'attributes' => $attributes ?? [],
	'compareFormats' => $compareFormats ?? false,
	'focus' => $focus ?? false,
	'image' => $image ?? null,
	'loading' => $loading ?? 'lazy',
	'ratio' => $ratio ?? 'intrinsic',
	'srcset' => $srcset ?? 'default',
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
