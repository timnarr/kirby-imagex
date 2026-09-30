<?php

declare(strict_types = 1);

namespace TimNarr;

use Kirby\Cms\File;
use Kirby\Exception\InvalidArgumentException;
use Kirby\Filesystem\F;
use Kirby\Toolkit\A;
use Kirby\Toolkit\Str;

class Imagex
{
	protected string $loading;
	protected bool $customLazyloading;
	protected array $formats;
	protected File $image;
	protected array $imgAttributes;
	protected array $pictureAttributes;
	protected string $ratio;
	protected bool $focus;
	protected string|null $nonce;
	protected array $artDirection;
	private string|null $artDirectionId = null;
	private string|null $artDirectionStylesCache = null;
	protected array $sourcesAttributes;
	protected string $srcset;
	protected bool $compareFormats;
	protected array $compareFormatsWeights;
	protected bool $addOriginalFormatAsSource;
	protected bool $noSrcsetInImg;
	protected $kirby;

	/** Normalized srcset presets from the config, keyed by format. */
	private array $srcsetPresets;

	/** Srcset presets with ratio-based heights, keyed by resolved ratio ('x/y'). */
	private array $dynamicSrcsetPresets = [];

	/** Smallest format per image and ratio, keyed by 'imageId|ratio'. */
	private array $smallestFormats = [];

	/**
	 * Constructor to initialize Imagex with its options.
	 *
	 * @param array $options
	 * @throws InvalidArgumentException If required options are missing or have invalid types.
	 */
	public function __construct(array $options)
	{
		// Validate required option: image
		if (!isset($options['image'])) {
			throw new InvalidArgumentException('[kirby-imagex] Missing required option: image');
		}

		// Type validation for image
		if (!($options['image'] instanceof File)) {
			throw new InvalidArgumentException("[kirby-imagex] Option 'image' must be an instance of Kirby\Cms\File");
		}

		// Validate loading option
		$loading = $options['loading'] ?? 'lazy';
		if (!in_array($loading, ['eager', 'lazy'])) {
			throw new InvalidArgumentException("[kirby-imagex] Option 'loading' must be 'eager' or 'lazy'. Got: '{$loading}'");
		}

		// Validate required option: ratio
		if (!isset($options['ratio']) || !is_string($options['ratio'])) {
			throw new InvalidArgumentException('[kirby-imagex] Missing or invalid required option: ratio. Must be a string (e.g. "16/9" or "intrinsic").');
		}

		// Validate required option: srcset
		if (!isset($options['srcset']) || !is_string($options['srcset'])) {
			throw new InvalidArgumentException('[kirby-imagex] Missing or invalid required option: srcset. Must be a string matching a "thumbs.srcsets" preset name.');
		}

		// Validate required option: compareFormats
		if (!isset($options['compareFormats']) || !is_bool($options['compareFormats'])) {
			throw new InvalidArgumentException('[kirby-imagex] Missing or invalid required option: compareFormats. Must be a boolean.');
		}

		// Validate optional option: focus
		if (isset($options['focus']) && !is_bool($options['focus'])) {
			throw new InvalidArgumentException('[kirby-imagex] Invalid option: focus. Must be a boolean.');
		}

		// Validate optional option: nonce
		if (isset($options['nonce']) && (!is_string($options['nonce']) || $options['nonce'] === '')) {
			throw new InvalidArgumentException('[kirby-imagex] Invalid option: nonce. Must be a non-empty string (e.g. kirby()->nonce()) or null.');
		}

		// Assign options to properties
		$this->loading = $loading;
		$this->image = $options['image'];
		$this->ratio = $options['ratio'];
		$this->srcset = $options['srcset'];
		$this->compareFormats = $options['compareFormats'];
		$this->focus = $options['focus'] ?? false;
		$this->nonce = $options['nonce'] ?? null;

		// Normalize and assign attributes
		$attributes = $options['attributes'] ?? [];
		$this->imgAttributes = normalizeAttributesStructure($attributes['img'] ?? []);
		$this->pictureAttributes = normalizeAttributesStructure($attributes['picture'] ?? []);
		$this->sourcesAttributes = normalizeAttributesStructure($attributes['sources'] ?? []);

		// Resolving the ratio validates its format (and dimensions for 'intrinsic') up front
		getAspectRatio($this->ratio, $this->image);

		$this->artDirection = $this->validateArtDirection($options['artDirection'] ?? []);

		// Cache kirby instance and assign options
		$this->kirby = kirby();
		$this->customLazyloading = $this->getBoolOption('customLazyloading');
		$this->compareFormatsWeights = resolveCompareFormatsWeights($this->kirby->option('timnarr.imagex.compareFormatsWeights'));
		$this->addOriginalFormatAsSource = $this->getBoolOption('addOriginalFormatAsSource');
		$this->noSrcsetInImg = $this->getBoolOption('noSrcsetInImg');

		$formats = $this->kirby->option('timnarr.imagex.formats');
		$invalidFormats = is_array($formats) ? array_filter($formats, fn ($format) => !is_string($format) || $format === '') : null;

		if ($invalidFormats !== []) {
			throw new InvalidArgumentException("[kirby-imagex] Option 'timnarr.imagex.formats' must be an array of format names (e.g. ['avif', 'webp']).");
		}

		$this->formats = $this->resolveFormats($formats);

		if ($this->compareFormats && count($this->formats) <= 1) {
			throw new InvalidArgumentException('[kirby-imagex] Not enough formats to determine the smallest. Please set "compareFormats" to false or add at least two formats in the configuration.');
		}

		$this->srcsetPresets = $this->resolveSrcsetPresets();
	}

	/**
	 * Reads a boolean plugin option, throwing a descriptive error for other types.
	 *
	 * @param string $name Option name without the 'timnarr.imagex.' prefix.
	 * @return bool The option value.
	 * @throws InvalidArgumentException If the option is not a boolean.
	 */
	private function getBoolOption(string $name): bool
	{
		$value = $this->kirby->option('timnarr.imagex.' . $name);

		if (!is_bool($value)) {
			throw new InvalidArgumentException("[kirby-imagex] Option 'timnarr.imagex.{$name}' must be a boolean. Got: " . get_debug_type($value));
		}

		return $value;
	}

	/**
	 * Validates the shape of every artDirection entry.
	 *
	 * Each entry needs a `media` condition: a <source> without one always matches,
	 * so the default image (and every later source) would never be used.
	 *
	 * @param mixed $artDirection The artDirection option as passed in.
	 * @return array The validated artDirection sources.
	 * @throws InvalidArgumentException If an entry is malformed.
	 */
	private function validateArtDirection(mixed $artDirection): array
	{
		if (!is_array($artDirection)) {
			throw new InvalidArgumentException("[kirby-imagex] Option 'artDirection' must be an array of sources.");
		}

		$allowedKeys = ['media', 'ratio', 'image', 'attributes'];

		foreach ($artDirection as $index => $source) {
			$prefix = "[kirby-imagex] artDirection[{$index}]";

			if (!is_array($source)) {
				throw new InvalidArgumentException("{$prefix} must be an array with the keys: " . implode(', ', $allowedKeys));
			}

			$unknownKeys = array_diff(array_keys($source), $allowedKeys);

			if (!empty($unknownKeys)) {
				throw new InvalidArgumentException("{$prefix} has unknown key(s): " . implode(', ', $unknownKeys) . '. Allowed: ' . implode(', ', $allowedKeys));
			}

			if (!is_string($source['media'] ?? null) || trim($source['media']) === '') {
				throw new InvalidArgumentException("{$prefix} is missing 'media' (e.g. '(min-width: 800px)'). Without it the source always matches and the default image is never used.");
			}

			if (isset($source['ratio']) && !is_string($source['ratio'])) {
				throw new InvalidArgumentException("{$prefix}: 'ratio' must be a string (e.g. \"16/9\" or \"intrinsic\").");
			}

			// null is allowed and falls back to the main image, e.g. for an optional content field
			if (isset($source['image']) && !($source['image'] instanceof File)) {
				throw new InvalidArgumentException("{$prefix}: 'image' must be an instance of Kirby\\Cms\\File or null.");
			}

			if (isset($source['attributes']) && !is_array($source['attributes'])) {
				throw new InvalidArgumentException("{$prefix}: 'attributes' must be an array.");
			}

			getAspectRatio($source['ratio'] ?? 'intrinsic', $source['image'] ?? $this->image);
		}

		return $artDirection;
	}

	/**
	 * Resolves the formats to render <source> elements for: normalized, unique,
	 * in configured order, plus 'originalformat' if addOriginalFormatAsSource is set.
	 *
	 * @param array $configFormats Formats from the plugin config.
	 * @return array List of format names.
	 */
	private function resolveFormats(array $configFormats): array
	{
		$formats = $this->addOriginalFormatAsSource ? [...$configFormats, 'originalformat'] : $configFormats;

		return array_values(array_unique(array_map(fn ($format) => normalizeFormat($format), $formats)));
	}

	/**
	 * Resolves and validates the srcset presets for the image format and every
	 * active format, e.g. 'my-srcset', 'my-srcset-webp', 'my-srcset-avif'.
	 *
	 * Runs once in the constructor, so misconfiguration is caught immediately
	 * with a helpful error message rather than during rendering.
	 *
	 * @return array Normalized srcset presets keyed by format.
	 * @throws InvalidArgumentException If presets are missing or malformed.
	 */
	private function resolveSrcsetPresets(): array
	{
		$allPresets = $this->kirby->option('thumbs.srcsets');

		if (!is_array($allPresets) || empty($allPresets)) {
			throw new InvalidArgumentException('[kirby-imagex] No srcset presets found. Please configure "thumbs.srcsets" in your config.');
		}

		$available = implode(', ', array_keys($allPresets));

		if (!isset($allPresets[$this->srcset])) {
			throw new InvalidArgumentException("[kirby-imagex] Srcset preset '{$this->srcset}' not found in 'thumbs.srcsets'. Available: {$available}");
		}

		$presetNames = [];

		foreach ($this->formats as $format) {
			$presetNames[$format] = $format === 'originalformat' ? $this->srcset : $this->srcset . '-' . $format;
		}

		$missing = array_filter($presetNames, fn ($name) => !isset($allPresets[$name]));

		if (!empty($missing)) {
			$missingList = implode(', ', array_map(fn ($name) => "'{$name}'", $missing));

			throw new InvalidArgumentException("[kirby-imagex] Missing srcset preset(s) for active formats: {$missingList}. Add them to 'thumbs.srcsets' in config.php, or remove the corresponding format from 'timnarr.imagex.formats'. Available presets: {$available}");
		}

		$normalize = fn (string $name) => is_array($allPresets[$name])
			? normalizeSrcsetPreset($allPresets[$name], $name)
			: throw new InvalidArgumentException("[kirby-imagex] Srcset preset '{$name}' must be an array.");

		$presets = [$this->getImageFormat() => $normalize($this->srcset)];

		foreach ($presetNames as $format => $name) {
			$presets[$format] = $normalize($name);
		}

		return $presets;
	}

	/**
	 * Get the file format of a image
	 *
	 * @param File|null $image Optional file object to determine format; defaults to main image.
	 * @return string The image file format.
	 */
	private function getImageFormat(File|null $image = null): string
	{
		$image = $image ?? $this->image;

		return normalizeFormat($image->extension());
	}

	/**
	 * Get srcset preset with dynamic heights based on aspect ratio.
	 *
	 * Memoized per instance: it's called for the <img>, every format and every
	 * art-directed source, but only depends on the resolved ratio. It's plain
	 * arithmetic, so a persistent cache lookup would cost more than it saves.
	 *
	 * @param string|null $ratio Optional aspect ratio; defaults to object's ratio.
	 * @param File|null $image Optional file object (for 'intrinsic'); defaults to main image.
	 * @return array Srcset preset with dynamic heights.
	 */
	private function getDynamicSrcsetPreset(string|null $ratio = null, File|null $image = null): array
	{
		['x' => $ratioX, 'y' => $ratioY] = getAspectRatio($ratio ?? $this->ratio, $image ?? $this->image);

		return $this->dynamicSrcsetPresets["{$ratioX}/{$ratioY}"] ??= addRatioBasedHeightToSrcsetPreset($this->srcsetPresets, $ratioX, $ratioY);
	}

	/**
	 * Get the srcset value for a given srcset preset.
	 *
	 * @param array $srcsetPreset Srcset preset array.
	 * @param File|null $image Optional file object; defaults to main image.
	 * @return string Srcset value string.
	 */
	private function getSrcsetValue(array $srcsetPreset, File|null $image = null): string
	{
		$image = $image ?? $this->image;

		return $image->srcset($srcsetPreset);
	}

	/**
	 * Get the smallest image format based on weighted file size comparison.
	 * Uses mobile-first weighting across multiple srcset samples.
	 *
	 * @param File|null $image Optional file object; defaults to main image.
	 * @param string|null $ratio Optional aspect ratio; defaults to object's ratio.
	 * @return string|null Format of the smallest format or null if unable to determine.
	 */
	public function getSmallestFormatForImage(File|null $image = null, string|null $ratio = null): string|null
	{
		$image = $image ?? $this->image;
		$ratio = $ratio ?? $this->ratio;
		$formats = $this->formats;

		// Check for the specific condition where only the 'originalformat' is present and addOriginalFormatAsSource is true.
		if (!$this->compareFormats && count($formats) === 1 && A::has($formats, 'originalformat') && $this->addOriginalFormatAsSource) {
			return null;
		}

		// Return the first format if there is only one format, regardless of compareFormats's state.
		if (!$this->compareFormats || count($formats) === 1) {
			return A::first($formats);
		}

		// Called per format for every art-directed source — memoize on top of the persistent cache
		return $this->smallestFormats[$image->id() . '|' . $ratio] ??= $this->compareFormatSizes($image, $ratio);
	}

	/**
	 * Generates sample thumbs in every format and returns the format with the
	 * smallest weighted file size. The result is cached persistently, keyed by
	 * everything it depends on (including the file's modification time).
	 *
	 * @param File $image The image to compare formats for.
	 * @param string $ratio Aspect ratio of the generated thumbs.
	 * @return string The smallest format.
	 */
	private function compareFormatSizes(File $image, string $ratio): string
	{
		$cacheKey = implode('-', [
			$this->kirby->plugin('timnarr/imagex')->version(),
			$image->id(),
			(string)$image->modified(),
			$ratio,
			json_encode($this->srcsetPresets),
			implode(',', $this->formats),
			json_encode($this->compareFormatsWeights),
		]);
		$cacheId = 'compare-formats-' . Str::slug($image->id()) . '-' . hash('xxh3', $cacheKey);

		return $this->kirby->cache('timnarr.imagex')->getOrSet($cacheId, function () use ($image, $ratio) {
			$srcsets = $this->getDynamicSrcsetPreset($ratio, $image);
			$formatSizes = [];

			foreach ($this->formats as $format) {
				$formatSizes[$format] = calculateWeightedFormatSize($image, $srcsets[$format], $this->compareFormatsWeights);
			}

			return findSmallestValueAndKey($formatSizes);
		});
	}

	/**
	 * Get the smallest image format based on file size (wrapper for backwards compatibility).
	 *
	 * @return string|null Format of the smallest format or null if unable to determine.
	 */
	public function getSmallestFormat(): string|null
	{
		return $this->getSmallestFormatForImage();
	}

	/**
	 * Get the <img> tag attributes based on srcset preset and user defined + default attributes.
	 *
	 * @return array Attributes for the <img> tag.
	 */
	public function getImgAttributes(): array
	{
		$format = $this->getImageFormat();
		$srcsetPreset = $this->getDynamicSrcsetPreset();
		$srcsetValue = $this->getSrcsetValue($srcsetPreset[$format]);

		$image = $this->image;
		$isEager = $this->loading === 'eager';
		$userAttributes = $this->imgAttributes;
		$customLazyloading = $this->customLazyloading;
		$useNoSrcsetInImg = $this->noSrcsetInImg;

		$firstItemInSrcsetConfig = A::first($srcsetPreset[$format]);
		$src = $image->thumb($firstItemInSrcsetConfig)->url();
		['width' => $width, 'height' => $height] = $firstItemInSrcsetConfig;

		// An id is only needed (and generated) when there are actual art-direction
		// style overrides to scope to this <img> — see getArtDirectionStyles().
		$artDirectionId = $this->getArtDirectionStyles() !== '' ? $this->resolveArtDirectionId() : null;

		$defaultAttributes = [
			'shared' => [
				'src' => $src,
				'width' => $width,
				'height' => $height,
				'decoding' => 'async',
				'fetchpriority' => $isEager ? 'high' : null,
				'id' => $artDirectionId,
				// With a CSP nonce, focus styles go into getArtDirectionStyles() instead: strict
				// CSPs block style attributes, and nonces only apply to <style> elements
				'style' => $this->focus && $this->nonce === null ? ['object-fit: cover;', 'object-position: ' . resolveFocusValue($image) . ';'] : [],
			],
			'eager' => [
				'srcset' => $useNoSrcsetInImg ? null : $srcsetValue,
			],
			'lazy' => [
				'loading' => $customLazyloading ? null : 'lazy',
				'data-src' => $customLazyloading ? $src : null,
				// custom lazy loading libraries swap data-src into src; a placeholder can be set via attributes
				'src' => $customLazyloading ? null : $src,
				'data-srcset' => $useNoSrcsetInImg ? null : ($customLazyloading ? $srcsetValue : null),
				'srcset' => $useNoSrcsetInImg ? null : (!$customLazyloading ? $srcsetValue : null),
			],
		];

		$mergedAttributes = mergeHTMLAttributes($userAttributes, $this->loading, $defaultAttributes);

		// Apply urlHandler to all URL-based attributes (handles user-overridden attributes)
		return applyUrlHandlerToAttributes($mergedAttributes);
	}

	/**
	 * Resolves the id used to scope generated art-direction CSS to this <img>.
	 * Reuses a user-supplied 'id' attribute if present — checked in the same
	 * shared/loading-mode priority getImgAttributes() itself resolves with, so
	 * an id set only under 'eager'/'lazy' is still picked up — otherwise lazily
	 * generates and caches one for the lifetime of this instance.
	 *
	 * @return string The <img> element's id.
	 */
	private function resolveArtDirectionId(): string
	{
		$userId = $this->imgAttributes[$this->loading]['id'] ?? $this->imgAttributes['shared']['id'] ?? null;

		if ($userId) {
			return (string)$userId;
		}

		return $this->artDirectionId ??= 'imagex-' . substr(hash('xxh3', uniqid('', true)), 0, 8);
	}

	/**
	 * Generates scoped CSS for art-directed sources that change the aspect ratio
	 * and/or (when the `focus` option is enabled) the focus point of the image.
	 *
	 * A <source>'s `media`/`ratio`/`image` only ever influences which thumbnail
	 * is picked by the browser — the rendered <img> keeps the default ratio and
	 * focus point regardless of which source matched. This produces `@media`-scoped
	 * `!important` rules (targeting this instance's <img> id) so the <img>'s
	 * `aspect-ratio` and `object-position` follow whichever art-directed source
	 * is currently active. A property is only emitted if at least one source
	 * differs from the default; if none does, no CSS (and no id) is generated.
	 *
	 * `media` is developer-supplied CSS syntax (e.g. '(min-width: 800px)'), passed
	 * through as-is like `ratio`/`srcset` elsewhere in Imagex — it can't be escaped
	 * without breaking valid media-query syntax, so it must not be built from
	 * untrusted/content-field input. `focus` values, which do come from a
	 * Panel-editable content field, are validated in resolveFocusValue().
	 *
	 * With a `nonce` set (for a strict Content Security Policy), the `focus`
	 * styles are emitted here as a base `#id` rule instead of an inline `style`
	 * attribute on the <img>: CSP nonces only apply to <style> elements.
	 *
	 * @return string CSS rules (possibly empty), meant to be wrapped in a <style> tag.
	 */
	public function getArtDirectionStyles(): string
	{
		if ($this->artDirectionStylesCache !== null) {
			return $this->artDirectionStylesCache;
		}

		$rules = $this->getArtDirectionRules();

		if ($this->focus && $this->nonce !== null) {
			array_unshift($rules, $this->getImgSelector() . ' { object-fit: cover; object-position: ' . resolveFocusValue($this->image) . '; }');
		}

		return $this->artDirectionStylesCache = implode(' ', $rules);
	}

	/**
	 * Get the CSP nonce for the generated <style> element.
	 *
	 * @return string|null The nonce, or null if none was set.
	 */
	public function getNonce(): string|null
	{
		return $this->nonce;
	}

	/**
	 * Builds the `@media`-scoped rules that keep the <img> in sync with the
	 * active art-directed source. See getArtDirectionStyles().
	 *
	 * @return array CSS rules, possibly empty.
	 */
	private function getArtDirectionRules(): array
	{
		if (empty($this->artDirection)) {
			return [];
		}

		$defaults = [
			'aspect-ratio' => $this->getRatioCss($this->ratio, $this->image),
			'object-position' => $this->focus ? resolveFocusValue($this->image) : null,
		];

		$sources = [];

		foreach ($this->artDirection as $source) {
			$sourceImage = $source['image'] ?? $this->image;
			$sources[] = [
				'media' => $source['media'],
				'aspect-ratio' => $this->getRatioCss($source['ratio'] ?? 'intrinsic', $sourceImage),
				'object-position' => $this->focus ? resolveFocusValue($sourceImage) : null,
			];
		}

		// A property only needs rules if at least one source changes it. But once it
		// does, every source must set it: several media queries can match at once, and
		// a source that keeps the default would otherwise inherit another source's value.
		$properties = array_filter(
			array_keys($defaults),
			fn (string $property) => $defaults[$property] !== null
				&& in_array(true, array_map(fn (array $source) => $source[$property] !== $defaults[$property], $sources), true)
		);

		if (empty($properties)) {
			return [];
		}

		$selector = $this->getImgSelector();
		$rules = [];

		// <picture> uses the first matching <source>, whereas CSS applies the last
		// matching rule — so emit in reverse to let the first source win.
		foreach (array_reverse($sources) as $source) {
			$declarations = array_map(fn (string $property) => "{$property}: {$source[$property]} !important;", $properties);
			$rules[] = '@media ' . $source['media'] . ' { ' . $selector . ' { ' . implode(' ', $declarations) . ' } }';
		}

		return $rules;
	}

	/**
	 * Get the CSS selector targeting this instance's <img>.
	 *
	 * @return string The escaped `#id` selector.
	 */
	private function getImgSelector(): string
	{
		return '#' . escapeCssIdentifier($this->resolveArtDirectionId());
	}

	/**
	 * Resolves a ratio to its CSS `aspect-ratio` value (e.g. '16 / 9').
	 *
	 * @param string $ratio Aspect ratio string or 'intrinsic'.
	 * @param File $image Image used to resolve 'intrinsic'.
	 * @return string CSS `aspect-ratio` value.
	 */
	private function getRatioCss(string $ratio, File $image): string
	{
		['x' => $ratioX, 'y' => $ratioY] = getAspectRatio($ratio, $image);

		return "{$ratioX} / {$ratioY}";
	}

	/**
	 * Get HTML attributes for the <picture> element based on loading mode and user defined attributes.
	 *
	 * @return array HTML attributes for the <picture> element.
	 */
	public function getPictureAttributes(): array
	{
		return mergeHTMLAttributes($this->pictureAttributes, $this->loading);
	}

	/**
	 * Get HTML attributes for a <source> element within a <picture>, including responsive and art direction settings.
	 *
	 * @param string $format Image format for the source.
	 * @param string $srcsetValue Srcset definition string.
	 * @param array $srcsetPreset Srcset configuration array.
	 * @param array $source Additional source settings for art direction.
	 * @return array HTML attributes for the source element.
	 */
	private function getSourceAttributes(string $format, string $srcsetValue, array $srcsetPreset, array $source = []): array
	{
		['width' => $width, 'height' => $height] = A::first($srcsetPreset[$format]);

		if ($format === 'originalformat') {
			$image = $source['image'] ?? $this->image;
			$format = $this->getImageFormat($image);
		}

		$customLazyloading = $this->customLazyloading;
		$defaultAttributes = [
			'shared' => [
				'type' => F::extensionToMime($format),
				'width' => $width,
				'height' => $height,
				'media' => $source['media'] ?? null,
				...($this->sourcesAttributes['shared'] ?? []),
			],
			'eager' => [
				'srcset' => $srcsetValue,
				...($this->sourcesAttributes['eager'] ?? []),
			],
			'lazy' => [
				'srcset' => $customLazyloading ? null : $srcsetValue,
				'data-srcset' => $customLazyloading ? $srcsetValue : null,
				...($this->sourcesAttributes['lazy'] ?? []),
			],
		];

		$mergedAttributes = mergeHTMLAttributes($source['attributes'] ?? [], $this->loading, $defaultAttributes);

		// Apply urlHandler to all URL-based attributes (handles user-overridden attributes)
		return applyUrlHandlerToAttributes($mergedAttributes);
	}

	/**
	 * Get art-directed picture sources for a specific format.
	 * When compareFormats is enabled and a different image is used,
	 * performs per-image format comparison.
	 *
	 * @param string $format Image format.
	 * @return array HTML attributes for art-directed picture sources.
	 */
	private function getArtDirectedSourcesPerFormat(string $format): array
	{
		$sources = [];
		$formats = $this->formats;

		foreach ($this->artDirection as $source) {
			$sourceRatio = $source['ratio'] ?? 'intrinsic';
			$sourceImage = $source['image'] ?? null;

			// Per-image format decision when using a different image
			if ($this->compareFormats && $sourceImage !== null) {
				$sourceSmallestFormat = $this->getSmallestFormatForImage($sourceImage, $sourceRatio);

				if ($sourceSmallestFormat && isFormatSkippable($format, $formats, $sourceSmallestFormat)) {
					continue;
				}
			}

			$srcsetPreset = $this->getDynamicSrcsetPreset($sourceRatio, $sourceImage);
			$srcsetValue = $this->getSrcsetValue($srcsetPreset[$format], $sourceImage);
			$sourceAttributes = $this->getSourceAttributes($format, $srcsetValue, $srcsetPreset, $source);

			$sources[] = $sourceAttributes;
		}

		return $sources;
	}

	/**
	 * Get default picture sources for a specific format.
	 *
	 * @param string $format Image format.
	 * @return array HTML attributes for default picture sources.
	 */
	private function getDefaultSourcesPerFormat(string $format): array
	{
		$srcsetPreset = $this->getDynamicSrcsetPreset();
		$srcsetValue = $this->getSrcsetValue($srcsetPreset[$format]);

		return $this->getSourceAttributes($format, $srcsetValue, $srcsetPreset);
	}

	/**
	 * Get all picture sources, including both art-directed and default sources for formats.
	 *
	 * @return array Compiled data of all picture sources.
	 */
	public function getPictureSources(): array
	{
		$formats = $this->formats;
		$sources = [];

		// Determine smallest format for main image
		$mainSmallestFormat = $this->compareFormats
			? $this->getSmallestFormatForImage()
			: null;

		foreach ($formats as $format) {
			// Skip format if a smaller format exists for main image
			if ($mainSmallestFormat && isFormatSkippable($format, $formats, $mainSmallestFormat)) {
				continue;
			}

			if (!empty($this->artDirection)) {
				// Per-source format decision for art-directed images
				$sources = array_merge($sources, $this->getArtDirectedSourcesPerFormat($format));
			}

			$sources[] = $this->getDefaultSourcesPerFormat($format);
		}

		return $sources;
	}
}
