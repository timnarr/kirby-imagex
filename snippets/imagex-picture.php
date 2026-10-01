<?php

declare(strict_types = 1);

use TimNarr\Imagex;

// Missing options are passed as null; Imagex applies the defaults
$imagex = new Imagex([
	'artDirection' => $artDirection ?? null,
	'attributes' => $attributes ?? null,
	'compareFormats' => $compareFormats ?? null,
	'focus' => $focus ?? null,
	'image' => $image ?? null,
	'loading' => $loading ?? null,
	'nonce' => $nonce ?? null,
	'ratio' => $ratio ?? null,
	'srcset' => $srcset ?? null,
]);

$pictureAttributes = $imagex->getPictureAttributes();
$pictureSources = $imagex->getPictureSources();
$imgAttributes = $imagex->getImgAttributes();
$artDirectionStyles = $imagex->getArtDirectionStyles();
?>

<?php if ($artDirectionStyles !== ''): ?>
	<style<?= attr(['nonce' => $imagex->getNonce()], ' ') ?>><?= $artDirectionStyles ?></style>
<?php endif; ?>

<picture <?= attr($pictureAttributes) ?>>
	<?php foreach ($pictureSources as $source): ?>
		<source <?= attr($source) ?> />
	<?php endforeach; ?>

	<img <?= attr($imgAttributes) ?>>
</picture>
