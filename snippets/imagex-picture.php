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

$pictureAttributes = $imagex->getPictureAttributes();
$pictureSources = $imagex->getPictureSources();
$imgAttributes = $imagex->getImgAttributes();
?>

<picture <?= attr($pictureAttributes) ?>>
<?php foreach ($pictureSources as $source): ?>
	<source <?= attr($source) ?> />
<?php endforeach; ?>

	<img <?= attr($imgAttributes) ?>>
</picture>
