<?php

declare(strict_types = 1);

namespace TimNarr;

use Kirby\Exception\InvalidArgumentException;

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
 *
 * @internal Not part of the public API — may change in any release.
 */
function addRatioBasedHeightToSrcsetPreset(array $srcsetPreset, int $ratioX, int $ratioY): array
{
	$ratio = $ratioY / $ratioX;

	foreach ($srcsetPreset as $format => $srcset) {
		foreach ($srcset as $key => $src) {
			$width = (int)$src['width'];
			$srcsetPreset[$format][$key]['height'] = (int)(round($width * $ratio));
			// 'crop' option must be enabled when height is ratio calculated; explicit false is invalid in this context
			if (empty($srcsetPreset[$format][$key]['crop'])) {
				$srcsetPreset[$format][$key]['crop'] = true;
			}
		}
	}

	return $srcsetPreset;
}

/**
 * Normalizes a srcset preset from Kirby's `thumbs.srcsets` config to the
 * long form (`'400w' => ['width' => 400, ...]`).
 *
 * Accepts the same shorthand forms as Kirby's `File::srcset()`:
 * - `[400, 800]`                  → `['400w' => ['width' => 400], '800w' => ['width' => 800]]`
 * - `[400 => '1x', 800 => '2x']`  → `['1x' => ['width' => 400], '2x' => ['width' => 800]]`
 * - `['400w' => ['width' => 400]]` (returned as-is)
 *
 * @param array $preset The srcset preset as configured.
 * @param string $presetName The preset name, used in error messages.
 * @return array The normalized srcset preset.
 * @throws InvalidArgumentException If the preset is empty or an entry has no positive width.
 *
 * @internal Not part of the public API — may change in any release.
 */
function normalizeSrcsetPreset(array $preset, string $presetName): array
{
	if (empty($preset)) {
		throw new InvalidArgumentException("[kirby-imagex] Srcset preset '{$presetName}' is empty.");
	}

	$normalized = [];

	foreach ($preset as $key => $value) {
		[$condition, $options] = match (true) {
			is_array($value) => [$key, $value],
			is_string($value) => [$value, ['width' => $key]],
			default => [$value . 'w', ['width' => $value]],
		};

		if (!is_numeric($options['width'] ?? null) || $options['width'] <= 0) {
			throw new InvalidArgumentException("[kirby-imagex] Srcset preset '{$presetName}' entry '{$key}' needs a positive 'width'. (heights are derived from the ratio).");
		}

		$normalized[$condition] = $options;
	}

	return $normalized;
}
