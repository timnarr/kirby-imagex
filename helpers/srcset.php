<?php

namespace TimNarr;

/**
 * Adds height property to each source in a srcset preset based on a specified aspect ratio.
 *
 * This function iterates over a srcset preset array, calculating and adding a height for each source
 * based on the given aspect ratio and the width specified in the srcset.
 *
 * @param array $srcsetPreset An array of srcset preset configuration, each containing a 'width' key.
 * @param int $ratioX The width part of the aspect ratio.
 * @param int $ratioY The height part of the aspect ratio.
 * @return array The modified srcset preset array with 'height' added to each source configuration.
 */
function addRatioBasedHeightToSrcsetPreset(array $srcsetPreset, int $ratioX, int $ratioY): array
{
	$ratio = $ratioY / $ratioX;

	foreach ($srcsetPreset as $format => $srcset) {
		foreach ($srcset as $key => $src) {
			$width = (int)$src['width'];
			$srcsetPreset[$format][$key]['height'] = (int)(round($width * $ratio));
		}
	}

	return $srcsetPreset;
}
