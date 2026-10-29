<?php

declare(strict_types = 1);

use Kirby\Exception\InvalidArgumentException;
use TimNarr\Imagex;

// Picks the known options from the snippet's variables; Imagex applies the defaults
$imagex = Imagex::fromSnippetData(get_defined_vars());

$pictureAttributes = $imagex->getPictureAttributes();
$pictureSources = $imagex->getPictureSources();
$imgAttributes = $imagex->getImgAttributes();
$artDirectionStyles = $imagex->getArtDirectionStyles();
$preloadLinks = $imagex->getPreloadLinks();
$stack = $imagex->getStack();

$styleElement = $artDirectionStyles !== ''
	? '<style' . attr(['nonce' => $imagex->getNonce()], ' ') . '>' . $artDirectionStyles . '</style>'
	: null;

if ($stack === null && $preloadLinks !== []) {
	throw new InvalidArgumentException("[kirby-imagex] Option 'preload' needs a template stack in the <head>: set 'timnarr.imagex.stack' (e.g. 'imagex-head') in your config and output it with <?php stack('imagex-head') ?>.");
}

// Head elements go into the configured template stack, which Kirby renders in the <head>
if ($stack !== null) {
	foreach ($preloadLinks as $link) {
		push($stack, '<link ' . attr($link) . '>', unique: true);
	}

	if ($styleElement !== null) {
		push($stack, $styleElement);
	}
}
?>

<?php if ($stack === null && $styleElement !== null): ?>
	<?= $styleElement ?>
<?php endif; ?>

<picture <?= attr($pictureAttributes) ?>>
	<?php foreach ($pictureSources as $source): ?>
		<source <?= attr($source) ?> />
	<?php endforeach; ?>

	<img <?= attr($imgAttributes) ?>>
</picture>
