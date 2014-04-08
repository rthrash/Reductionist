<?php
namespace Reductionist\Filter;

use Imagine\Filter\FilterInterface;
use Imagine\Image\ImageInterface;
use Imagine\Image\BoxInterface;

/**
 * Create a quick thumbnail of an image, converting CMYK to RGB if necessary and
 * stripping any profiles
 */
class Reduce implements FilterInterface
{
	/**
	 * @var BoxInterface
	 */
	private $size;
	private $filter;

	private static $graphicsLib;
	private static $rgb;

	/**
	 * Constructs Resize filter with given width and height
	 *
	 * @param BoxInterface $size
	 * @param string       $filter
	 */
	public function __construct(BoxInterface $size) {
		$this->size = $size;
	}

	/**
	 * {@inheritdoc}
	 */
	public function apply(ImageInterface $image) {
		if (self::$graphicsLib === null) {  // figure out which extension we're using and get the driver
			if (method_exists($image, 'getImagick'))  {
				self::$graphicsLib = 1;
				$driver = $image->getImagick();
			}
			elseif (method_exists($image, 'getGmagick'))  {
				self::$graphicsLib = 2;
				$driver = $image->getGmagick();
			}
			else { self::$graphicsLib = 0; }  // GD
		}
		else {
			if (self::$graphicsLib === 1) {
				$driver = $image->getImagick();
			}
			elseif (self::$graphicsLib === 2) {
				$driver = $image->getGmagick();
			}
		}

		if (self::$graphicsLib && $image->palette()->name() === \Imagine\Image\Palette\PaletteInterface::PALETTE_CMYK) {  // Imagick or Gmagick: convert CMYK > RGB
			try {
				if (!self::$rgb) {
					self::$rgb = new \Imagine\Image\Palette\RGB();
				}
				$image->usePalette(self::$rgb);
			}
			catch (\Exception $e) {
				// echo "{$e->getMessage()}. ** Skipping conversion **";
			}

			if (self::$graphicsLib === 1) {
				$driver->stripimage();  // Imagick: make sure all profiles are removed
			}
		}

		if (self::$graphicsLib === 1) {
			$driver->thumbnailImage($this->size->getWidth(), $this->size->getHeight());
			return $image;
		}
		elseif (self::$graphicsLib === 2) {
			$driver->scaleimage($this->size->getWidth(), $this->size->getHeight())
				   ->stripimage();
			return $image;
		}
		else {
			return $image->resize($this->size);
		}
	}

}
