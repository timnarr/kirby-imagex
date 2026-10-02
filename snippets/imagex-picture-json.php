<?php

declare(strict_types = 1);

use TimNarr\Imagex;

use function TimNarr\transformForJson;

// Picks the known options from the snippet's variables; Imagex applies the defaults
$imagex = Imagex::fromSnippetData(get_defined_vars());

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
