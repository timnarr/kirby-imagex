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

$pictureAttributes = $imagex->getPictureAttributes();
$pictureSources = $imagex->getPictureSources();
$imgAttributes = $imagex->getImgAttributes();
$artDirectionStyles = $imagex->getArtDirectionStyles();
?>

<?php if ($artDirectionStyles !== ''): ?>
	<style><?= $artDirectionStyles ?></style>
<?php endif; ?>

<picture <?= attr($pictureAttributes) ?>>
	<?php foreach ($pictureSources as $source): ?>
		<source <?= attr($source) ?> />
	<?php endforeach; ?>

	<img <?= attr($imgAttributes) ?>>
</picture>
