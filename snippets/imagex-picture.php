<?php

declare(strict_types = 1);

use TimNarr\Imagex;

// Picks the known options from the snippet's variables; Imagex applies the defaults
$imagex = Imagex::fromSnippetData(get_defined_vars());

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
