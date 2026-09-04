<?php

use TimNarr\Imagex;

$imagex = new Imagex([
	'artDirection' => $artDirection ?? [],
	'attributes' => $attributes ?? [],
	'compareFormats' => $compareFormats ?? false,
	'focus' => $focus ?? false,
	'image' => $image,
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
