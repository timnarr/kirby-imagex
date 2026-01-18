<?php

use TimNarr\Imagex;

$imagex = new Imagex([
	'loading' => $loading ?? 'lazy',
	'image' => $image,
	'attributes' => $attributes ?? [],
	'ratio' => $ratio ?? 'intrinsic',
	'artDirection' => $artDirection ?? [],
	'srcset' => $srcset ?? 'default',
	'compareFormats' => $compareFormats ?? false,
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
