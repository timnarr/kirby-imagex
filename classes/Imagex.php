<?php

namespace TimNarr;

use Kirby\Cms\File;
use Kirby\Exception\Exception;
use Kirby\Exception\InvalidArgumentException;
use Kirby\Filesystem\F;
use Kirby\Toolkit\A;

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
	protected bool $includeInitialFormat;
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
		$this->formats = $this->kirby->option('timnarr.imagex.formats');
		$this->includeInitialFormat = $this->kirby->option('timnarr.imagex.includeInitialFormat');
		$this->noSrcsetInImg = $this->kirby->option('timnarr.imagex.noSrcsetInImg');
		$this->thumbsSrcsets = $this->kirby->option('thumbs.srcsets');
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
		$formats = $this->includeInitialFormat ? A::append($configFormats, ['initialformat']) : $configFormats;

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

		if (!isset($allSrcsetPresets[$this->srcset])) {
			$available = implode(', ', array_keys($allSrcsetPresets));

			throw new Exception("[kirby-imagex] Srcset configuration '{$this->srcset}' not found. Available presets: {$available}");
		}

		$srcsetName = $this->srcset;
		$srcsetPreset[$this->getImageFormat()] = $allSrcsetPresets[$srcsetName];

		foreach ($this->getFormats() as $format) {
			if ($format === 'initialformat') {
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
	 * Get the smallest image format based on file size.
	 *
	 * @return string|null Format of the smallest format or null if unable to determine.
	 * @throws Exception If not enough formats are provided for comparison.
	 */
	public function getSmallestFormat(): string|null
	{
		$formats = $this->getFormats();
		$formatsCount = A::count($formats);
		$compareFormats = $this->compareFormats;

		// Throw an exception if compareFormats is active and there are one or less formats
		if ($compareFormats && $formatsCount <= 1) {
			throw new Exception('[kirby-imagex] Not enough formats to determine the smallest. Please set "compareFormats" to false or add at least two formats in the configuration.');
		}

		// Check for the specific condition where only the 'initialformat' is present and includeInitialFormat is true.
		if (!$compareFormats && $formatsCount === 1 && A::has($formats, 'initialformat') && $this->includeInitialFormat) {
			return null;
		}

		// Return the first format if there is only one format, regardless of compareFormats's state.
		if (!$compareFormats || $formatsCount === 1) {
			return A::first($formats);
		}

		$image = $this->image;
		$srcsets = $this->getDynamicSrcsetPreset();
		$formatSizes = [];

		foreach ($formats as $format) {
			if (!isset($srcsets[$format])) {
				throw new Exception("[kirby-imagex] No srcset configurations found for format: {$format}");
			}

			$midSizedsrcsetValue = findMiddleArray($srcsets[$format])['middleValue'];
			$formatSizes[$format] = $image->thumb($midSizedsrcsetValue)->size();
		}

		// Find the format with the smallest size
		$smallestFormat = findSmallestValueAndKey($formatSizes);

		return $smallestFormat;
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
	 * @return array HTML attributes for the source element.
	 */
	private function getSourceAttributes(string $format, string $srcsetValue, array $srcsetPreset, array $source = []): array
	{
		['width' => $width, 'height' => $height] = A::first($srcsetPreset[$format]);

		if ($format === 'initialformat') {
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

		$mergedAttributes = mergeHTMLAttributes($source['attributes'] ?? [], $this->getLoadingMode(), $defaultAttributes);

		// Apply urlHandler to all URL-based attributes (handles user-overridden attributes)
		return applyUrlHandlerToAttributes($mergedAttributes);
	}

	/**
	 * Get art-directed picture sources for a specific format.
	 *
	 * @param string $format Image format.
	 * @return array HTML attributes for art-directed picture sources.
	 */
	private function getArtDirectedSourcesPerFormat(string $format): array
	{
		$sources = [];

		foreach ($this->artDirection as $source) {
			$sourceRatio = $source['ratio'] ?? 'intrinsic';
			$sourceImage = $source['image'] ?? null;

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
		$attributes = $this->getSourceAttributes($format, $srcsetValue, $srcsetPreset);

		return $attributes;
	}

	/**
	 * Get all picture sources, including both art-directed and default sources for formats.
	 *
	 * @return array Compiled data of all picture sources.
	 */
	public function getPictureSources(): array
	{
		$formats = $this->getFormats();
		$smallestFormat = $this->getSmallestFormat();
		$sources = [];

		for ($i = 0; $i < count($formats); $i++) {
			$format = $formats[$i];

			// If compareFormats is true, skip the current format if the next format is smaller
			if ($smallestFormat && isset($formats[$i + 1])) {
				$nextFormat = $formats[$i + 1];

				if ($smallestFormat === $nextFormat) {
					continue;
				}
			}

			if (!empty($this->artDirection)) {
				$sources = array_merge($sources, $this->getArtDirectedSourcesPerFormat($format));
			}

			$sources[] = $this->getDefaultSourcesPerFormat($format);
		}

		return $sources;
	}
}
