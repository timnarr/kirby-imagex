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
