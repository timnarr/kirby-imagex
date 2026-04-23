<?php

namespace TimNarr;

use Kirby\Cms\File;
use Kirby\Exception\Exception;
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
	protected array $artDirection;
	protected array $sourcesAttributes;
	protected string $srcset;
	protected bool $compareFormats;
	protected array $compareFormatsWeights;
	protected bool $contentNegotiation;
	protected bool $addOriginalFormatAsSource;
	protected bool $noSrcsetInImg;
	protected array $thumbsSrcsets;
	protected $kirby;

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

		// Assign options to properties
		$this->loading = $loading;
		$this->image = $options['image'];
		$this->ratio = $options['ratio'];
		$this->srcset = $options['srcset'];
		$this->compareFormats = $options['compareFormats'];

		// Normalize and assign attributes
		$attributes = $options['attributes'] ?? [];
		$this->imgAttributes = normalizeAttributesStructure($attributes['img'] ?? []);
		$this->pictureAttributes = normalizeAttributesStructure($attributes['picture'] ?? []);
		$this->sourcesAttributes = normalizeAttributesStructure($attributes['sources'] ?? []);

		// Assign art direction
		$this->artDirection = $options['artDirection'] ?? [];

		// Cache kirby instance and assign options
		$this->kirby = kirby();
		$this->customLazyloading = $this->kirby->option('timnarr.imagex.customLazyloading');
		$this->compareFormatsWeights = resolveCompareFormatsWeights($this->kirby->option('timnarr.imagex.compareFormatsWeights'));
		$this->contentNegotiation = $this->kirby->option('timnarr.imagex.contentNegotiation');
		$this->formats = $this->kirby->option('timnarr.imagex.formats');
		$this->addOriginalFormatAsSource = $this->kirby->option('timnarr.imagex.addOriginalFormatAsSource');
		$this->noSrcsetInImg = $this->kirby->option('timnarr.imagex.noSrcsetInImg');
		$this->thumbsSrcsets = $this->kirby->option('thumbs.srcsets');

		if ($this->contentNegotiation && $this->compareFormats) {
			throw new InvalidArgumentException("[kirby-imagex] 'compareFormats' cannot be used when 'timnarr.imagex.contentNegotiation' is enabled. Content negotiation delegates format selection to the server.");
		}

		$this->validateSrcsetPresets();
	}

	/**
	 * Validates that all required srcset presets exist in the Kirby thumbs config.
	 *
	 * Checks the base preset and all format-specific variants (e.g. 'my-srcset-webp',
	 * 'my-srcset-avif') up front so misconfiguration is caught immediately with a
	 * helpful error message rather than failing silently later during rendering.
	 *
	 * @throws InvalidArgumentException If required presets are missing.
	 */
	private function validateSrcsetPresets(): void
	{
		if (!is_array($this->thumbsSrcsets) || empty($this->thumbsSrcsets)) {
			// getSrcsetPresetFromConfig() will handle the detailed error at render time
			return;
		}

		if (!isset($this->thumbsSrcsets[$this->srcset])) {
			$available = implode(', ', array_keys($this->thumbsSrcsets));

			throw new InvalidArgumentException("[kirby-imagex] Srcset preset '{$this->srcset}' not found in 'thumbs.srcsets'. Available: {$available}");
		}

		$missing = [];

		foreach ($this->getFormats() as $format) {
			if ($format === 'originalformat') {
				continue;
			}

			$key = $this->srcset . '-' . $format;

			if (!isset($this->thumbsSrcsets[$key])) {
				$missing[] = "'{$key}'";
			}
		}

		if (!empty($missing)) {
			$available = implode(', ', array_keys($this->thumbsSrcsets));
			$missingList = implode(', ', $missing);

			throw new InvalidArgumentException("[kirby-imagex] Missing srcset preset(s) for active formats: {$missingList}. Add them to 'thumbs.srcsets' in config.php, or remove the corresponding format from 'timnarr.imagex.formats'. Available presets: {$available}");
		}
	}

	/**
	 * Determines the loading mode based on the loading option.
	 *
	 * @return string The loading mode - 'eager' or 'lazy'
	 */
	private function getLoadingMode(): string
	{
		return $this->loading;
	}

	/**
	 * Get image formats, ensuring uniqueness and correct naming.
	 *
	 * @return array Array with all formats as strings
	 */
	private function getFormats(): array
	{
		$configFormats = $this->formats;
		$formats = $this->addOriginalFormatAsSource ? A::append($configFormats, ['originalformat']) : $configFormats;

		$formats = array_unique(array_map(function ($item) {
			return normalizeFormat($item);
		}, $formats));

		// Reindex the array to ensure the keys start from 0
		return array_values($formats);
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
	 * Get the srcset preset by name from the Kirby config.
	 *
	 * @return array Srcset presets for used formats.
	 * @throws Exception If srcset preset is not found.
	 */
	private function getSrcsetPresetFromConfig(): array
	{
		$allSrcsetPresets = $this->thumbsSrcsets;

		if (!is_array($allSrcsetPresets) || empty($allSrcsetPresets)) {
			throw new Exception('[kirby-imagex] No srcset presets found. Please configure "thumbs.srcsets" in your config.');
		}

		if (!isset($allSrcsetPresets[$this->srcset])) {
			$available = implode(', ', array_keys($allSrcsetPresets));

			throw new Exception("[kirby-imagex] Srcset configuration '{$this->srcset}' not found. Available presets: {$available}");
		}

		$srcsetName = $this->srcset;
		$srcsetPreset[$this->getImageFormat()] = $allSrcsetPresets[$srcsetName];

		foreach ($this->getFormats() as $format) {
			if ($format === 'originalformat') {
				$srcsetPreset[$format] = $allSrcsetPresets[$srcsetName];
			} else {
				// Check if specific format configuration exists
				if (!isset($allSrcsetPresets[$srcsetName . '-' . $format])) {
					$available = implode(', ', array_keys($allSrcsetPresets));

					throw new Exception("[kirby-imagex] Srcset configuration '{$srcsetName}-{$format}' not found. Available presets: {$available}");
				}

				$srcsetPreset[$format] = $allSrcsetPresets[$srcsetName . '-' . $format];
			}
		}

		return $srcsetPreset;
	}

	/**
	 * Get srcset preset with dynamic heights based on aspect ratio.
	 *
	 * @param string|null $ratio Optional aspect ratio; defaults to object's ratio.
	 * @param File|null $image Optional file object; defaults to main image.
	 * @return array Srcset preset with dynamic heights.
	 */
	private function getDynamicSrcsetPreset(string|null $ratio = null, File|null $image = null): array
	{
		$srcsetPreset = $this->getSrcsetPresetFromConfig();
		$targetRatio = $ratio ?? $this->ratio;
		$targetImage = $image ?? $this->image;
		['x' => $ratioX, 'y' => $ratioY] = getAspectRatio($targetRatio, $targetImage);

		// Cache settings
		$version = $this->kirby->plugin('timnarr/imagex')->version();
		$cache = $this->kirby->cache('timnarr.imagex');
		$cacheKey = implode('-', [$version, $ratioX, $ratioY, json_encode($srcsetPreset)]);
		$cacheId = 'srcset-config-' . hash('xxh3', $cacheKey);

		// Get srcsetPreset from cache or set it
		$data = $cache->getOrSet($cacheId, function () use ($srcsetPreset, $ratioX, $ratioY) {
			return addRatioBasedHeightToSrcsetPreset($srcsetPreset, $ratioX, $ratioY);
		});

		return $data;
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
	 * @throws Exception If not enough formats are provided for comparison.
	 */
	public function getSmallestFormatForImage(File|null $image = null, string|null $ratio = null): string|null
	{
		$image = $image ?? $this->image;
		$ratio = $ratio ?? $this->ratio;

		$formats = $this->getFormats();
		$formatsCount = A::count($formats);
		$compareFormats = $this->compareFormats;

		// Throw an exception if compareFormats is active and there are one or less formats
		if ($compareFormats && $formatsCount <= 1) {
			throw new Exception('[kirby-imagex] Not enough formats to determine the smallest. Please set "compareFormats" to false or add at least two formats in the configuration.');
		}

		// Check for the specific condition where only the 'originalformat' is present and addOriginalFormatAsSource is true.
		if (!$compareFormats && $formatsCount === 1 && A::has($formats, 'originalformat') && $this->addOriginalFormatAsSource) {
			return null;
		}

		// Return the first format if there is only one format, regardless of compareFormats's state.
		if (!$compareFormats || $formatsCount === 1) {
			return A::first($formats);
		}

		// Cache the expensive format comparison result
		$version = $this->kirby->plugin('timnarr/imagex')->version();
		$cache = $this->kirby->cache('timnarr.imagex');
		$cacheKey = implode('-', [
			$version,
			$image->id(),
			(string)$image->modified(),
			$ratio,
			json_encode($this->getSrcsetPresetFromConfig()),
			implode(',', $formats),
			json_encode($this->compareFormatsWeights),
		]);
		$imageSlug = Str::slug($image->id());
		$cacheId = 'compare-formats-' . $imageSlug . '-' . hash('xxh3', $cacheKey);

		return $cache->getOrSet($cacheId, function () use ($image, $ratio, $formats) {
			$srcsets = $this->getDynamicSrcsetPreset($ratio, $image);
			$formatSizes = [];

			foreach ($formats as $format) {
				if (!isset($srcsets[$format])) {
					throw new Exception("[kirby-imagex] No srcset configurations found for format: {$format}");
				}

				$formatSizes[$format] = calculateWeightedFormatSize($image, $srcsets[$format], $this->compareFormatsWeights);
			}

			return findSmallestValueAndKey($formatSizes);
		});
	}

	/**
	 * Get the smallest image format based on file size (wrapper for backwards compatibility).
	 *
	 * @return string|null Format of the smallest format or null if unable to determine.
	 * @throws Exception If not enough formats are provided for comparison.
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

		if ($this->contentNegotiation) {
			$src = $this->stripExtensionFromUrl($src);
			$srcsetValue = $this->stripExtensionsFromSrcset($srcsetValue);
		}

		$defaultAttributes = [
			'shared' => [
				'src' => srcHandler($src, $userAttributes, 'shared'),
				'width' => $width,
				'height' => $height,
				'decoding' => 'async',
				'fetchpriority' => $isEager ? 'high' : null,
			],
			'eager' => [
				'src' => srcHandler($src, $userAttributes, 'eager'),
				'srcset' => $useNoSrcsetInImg ? null : $srcsetValue,
			],
			'lazy' => [
				'loading' => $customLazyloading ? null : 'lazy',
				'data-src' => $customLazyloading ? $src : null,
				'src' => srcHandler($src, $userAttributes, 'lazy'),
				'data-srcset' => $useNoSrcsetInImg ? null : ($customLazyloading ? $srcsetValue : null),
				'srcset' => $useNoSrcsetInImg ? null : (!$customLazyloading ? $srcsetValue : null),
			],
		];

		$mergedAttributes = mergeHTMLAttributes($userAttributes, $this->getLoadingMode(), $defaultAttributes);

		// Apply urlHandler to all URL-based attributes (handles user-overridden attributes)
		return applyUrlHandlerToAttributes($mergedAttributes);
	}

	/**
	 * Get HTML attributes for the <picture> element based on loading mode and user defined attributes.
	 *
	 * @return array HTML attributes for the <picture> element.
	 */
	public function getPictureAttributes(): array
	{
		return mergeHTMLAttributes($this->pictureAttributes, $this->getLoadingMode());
	}

	/**
	 * Get HTML attributes for a <source> element within a <picture>, including responsive and art direction settings.
	 *
	 * @param string $format Image format for the source.
	 * @param string $srcsetValue Srcset definition string.
	 * @param array $srcsetPreset Srcset configuration array.
	 * @param array $source Additional source settings for art direction.
	 * @param bool $includeType Whether to include the type attribute (omitted for content negotiation).
	 * @return array HTML attributes for the source element.
	 */
	private function getSourceAttributes(string $format, string $srcsetValue, array $srcsetPreset, array $source = [], bool $includeType = true): array
	{
		['width' => $width, 'height' => $height] = A::first($srcsetPreset[$format]);

		if ($format === 'originalformat') {
			$image = $source['image'] ?? $this->image;
			$format = $this->getImageFormat($image);
		}

		$customLazyloading = $this->customLazyloading;
		$defaultAttributes = [
			'shared' => [
				'type' => $includeType ? F::extensionToMime($format) : null,
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

		$mergedAttributes = mergeHTMLAttributes($source['attributes'] ?? [], $this->getLoadingMode(), $defaultAttributes);

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
		$formats = $this->getFormats();

		foreach ($this->artDirection as $source) {
			$sourceRatio = $source['ratio'] ?? 'intrinsic';
			$sourceImage = $source['image'] ?? null;

			// Per-image format decision when using a different image
			if ($this->compareFormats && $sourceImage !== null) {
				$sourceSmallestFormat = $this->getSmallestFormatForImage($sourceImage, $sourceRatio);

				// Skip if this format is not the smallest for this specific image
				if ($sourceSmallestFormat && $format !== $sourceSmallestFormat) {
					// Only skip if we're not the smallest format
					// and a smaller format exists in the array
					$formatIndex = array_search($format, $formats);
					$smallestIndex = array_search($sourceSmallestFormat, $formats);

					if ($formatIndex < $smallestIndex) {
						continue;
					}
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
		if ($this->contentNegotiation) {
			return $this->getContentNegotiationSources();
		}

		$formats = $this->getFormats();
		$formatsCount = count($formats);
		$sources = [];

		// Determine smallest format for main image
		$mainSmallestFormat = $this->compareFormats
			? $this->getSmallestFormatForImage()
			: null;

		for ($i = 0; $i < $formatsCount; $i++) {
			$format = $formats[$i];

			// Skip format if a smaller format exists for main image
			if ($mainSmallestFormat && $this->shouldSkipFormat($format, $formats, $i, $mainSmallestFormat)) {
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

	/**
	 * Generate thumbs for all configured formats as a side-effect (used for content negotiation).
	 * Triggers Kirby's thumb generation pipeline without outputting anything.
	 *
	 * @param string|null $ratio Optional aspect ratio; defaults to object's ratio.
	 * @param File|null $image Optional file object; defaults to main image.
	 */
	private function generateAllFormatThumbs(string|null $ratio = null, File|null $image = null): void
	{
		$srcsetPreset = $this->getDynamicSrcsetPreset($ratio, $image);

		foreach ($this->getFormats() as $format) {
			if (isset($srcsetPreset[$format])) {
				$this->getSrcsetValue($srcsetPreset[$format], $image);
			}
		}
	}

	/**
	 * Get picture sources for content negotiation mode.
	 * Generates thumbs for all formats as a side-effect, but outputs only one <source>
	 * per art-direction breakpoint without a type attribute — the server handles format selection.
	 *
	 * @return array Compiled data of all picture sources.
	 */
	private function getContentNegotiationSources(): array
	{
		$this->generateAllFormatThumbs();

		$sources = [];

		foreach ($this->artDirection as $source) {
			$sourceRatio = $source['ratio'] ?? 'intrinsic';
			$sourceImage = $source['image'] ?? null;

			if ($sourceImage !== null) {
				$this->generateAllFormatThumbs($sourceRatio, $sourceImage);
			}

			$srcsetPreset = $this->getDynamicSrcsetPreset($sourceRatio, $sourceImage);
			$originalFormat = $this->getImageFormat($sourceImage ?? $this->image);
			$srcsetValue = $this->stripExtensionsFromSrcset(
				$this->getSrcsetValue($srcsetPreset[$originalFormat], $sourceImage)
			);

			$sources[] = $this->getSourceAttributes($originalFormat, $srcsetValue, $srcsetPreset, $source, false);
		}

		return $sources;
	}

	/**
	 * Strips the file extension from a single URL.
	 *
	 * @param string $url A fully qualified or relative image URL.
	 * @return string The URL without its file extension.
	 */
	private function stripExtensionFromUrl(string $url): string
	{
		return preg_replace('/\.[a-z0-9]+$/i', '', $url);
	}

	/**
	 * Strips file extensions from all entries in a srcset string.
	 * Transforms "image.jpg 400w, image.jpg 800w" into "image 400w, image 800w".
	 *
	 * @param string $srcset A srcset attribute value.
	 * @return string The srcset string with all extensions removed.
	 */
	private function stripExtensionsFromSrcset(string $srcset): string
	{
		return preg_replace('/\.[a-z0-9]+(\s+[\d.]+[wx])/i', '$1', $srcset);
	}

	/**
	 * Determines if a format should be skipped based on smallest format comparison.
	 *
	 * @param string $format Current format being processed.
	 * @param array $formats All available formats.
	 * @param int $index Current index in formats array.
	 * @param string $smallestFormat The determined smallest format.
	 * @return bool True if format should be skipped.
	 */
	private function shouldSkipFormat(string $format, array $formats, int $index, string $smallestFormat): bool
	{
		if (!$smallestFormat) {
			return false;
		}

		if (!isset($formats[$index + 1])) {
			return false;
		}

		return $smallestFormat === $formats[$index + 1];
	}
}
