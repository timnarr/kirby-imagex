<?php

namespace TimNarr;

$imagex = new Imagex([
	'loading' => $loading ?? 'lazy',
	'image' => $image,
	'attributes' => $attributes ?? [],
	'ratio' => $ratio ?? 'intrinsic',
	'artDirection' => $artDirection ?? [],
	'srcset' => $srcset ?? 'default',
	'compareFormats' => $compareFormats ?? false,
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
