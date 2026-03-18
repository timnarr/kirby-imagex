<?php

use TimNarr\Imagex;

$imagex = new Imagex([
	'artDirection' => $artDirection ?? [],
	'attributes' => $attributes ?? [],
	'compareFormats' => $compareFormats ?? false,
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
];

$data = transformForJson($data);

echo json_encode($data, JSON_UNESCAPED_SLASHES);
