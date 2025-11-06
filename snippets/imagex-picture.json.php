<?php

use TimNarr\Imagex;

$imagex = new Imagex([
	'critical' => $critical ?? false,
	'image' => $image,
	'imgAttributes' => $imgAttributes ?? ['shared' =>  [], 'eager' =>  [], 'lazy' => []],
	'pictureAttributes' => $pictureAttributes ?? ['shared' =>  [], 'eager' =>  [], 'lazy' => []],
	'ratio' => $ratio ?? 'intrinsic',
	'sourcesAttributes' => $sourcesAttributes ?? ['shared' =>  [], 'eager' =>  [], 'lazy' => []],
	'sourcesArtDirected' => $sourcesArtDirected ?? [],
	'srcsetName' => $srcsetName ?? 'default',
	'formatSizeHandling' => $formatSizeHandling ?? false,
]);

$data = [
	'pictureAttributes' => $imagex->getPictureAttributes(),
	'sources' => $imagex->getPictureSources(),
	'imgAttributes' => $imagex->getImgAttributes(),
];

return json_encode($data, JSON_PRETTY_PRINT);
