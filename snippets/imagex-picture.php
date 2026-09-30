<?php

declare(strict_types = 1);

use TimNarr\Imagex;

$imagex = new Imagex([
	'artDirection' => $artDirection ?? [],
	'attributes' => $attributes ?? [],
	'compareFormats' => $compareFormats ?? false,
	'focus' => $focus ?? false,
	'image' => $image ?? null,
	'loading' => $loading ?? 'lazy',
	'nonce' => $nonce ?? null,
	'ratio' => $ratio ?? 'intrinsic',
	'srcset' => $srcset ?? 'default',
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
