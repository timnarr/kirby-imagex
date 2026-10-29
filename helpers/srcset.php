<?php

declare(strict_types = 1);

namespace TimNarr;

use Kirby\Exception\InvalidArgumentException;

/**
 * Adds a height based on the aspect ratio to every entry of the given srcset presets.
 *
 * @param array $srcsetPresets Normalized srcset presets keyed by preset name, each entry with a 'width'
 *                             (e.g. ['my-srcset' => ['400w' => ['width' => 400]]]).
 * @param int $ratioX The width part of the aspect ratio.
 * @param int $ratioY The height part of the aspect ratio.
 * @return array The srcset presets with 'height' (and 'crop') set on every entry.
 *
 * @internal Not part of the public API — may change in any release.
 */
function addRatioBasedHeightToSrcsetPreset(array $srcsetPresets, int $ratioX, int $ratioY): array
{
	$ratio = $ratioY / $ratioX;

	foreach ($srcsetPresets as $presetName => $preset) {
		foreach ($preset as $condition => $options) {
			$srcsetPresets[$presetName][$condition]['height'] = (int)round((int)$options['width'] * $ratio);

			// A ratio-based height only works with cropping; an explicit false is invalid here
			if (empty($options['crop'])) {
				$srcsetPresets[$presetName][$condition]['crop'] = true;
			}
		}
	}

	return $srcsetPresets;
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
