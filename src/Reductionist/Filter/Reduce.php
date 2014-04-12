<?php
namespace Reductionist\Filter;

use Imagine\Filter\FilterInterface;
use Imagine\Image\ImageInterface;
use Imagine\Image\BoxInterface;

/**
 * Create a quick thumbnail of an image, converting CMYK to RGB if necessary and
 * stripping any profiles
 */
class Reduce implements FilterInterface {
	private $size;

	private static $graphicsLib;
	private static $rgb;


	public function __construct(BoxInterface $size) {
		$this->size = $size;
	}


	public function apply(ImageInterface $image) {
		if (self::$graphicsLib === null) {  // figure out which extension we're using
			if (method_exists($image, 'getImagick'))  { self::$graphicsLib = 1; }
			elseif (method_exists($image, 'getGmagick'))  { self::$graphicsLib = 2; }
			else { self::$graphicsLib = 0; }  // GD
		}

		if (self::$graphicsLib && $image->palette()->name() === \Imagine\Image\Palette\PaletteInterface::PALETTE_CMYK) {  // Imagick or Gmagick: convert CMYK > RGB
			try {
				if (self::$rgb === null) {
					self::$rgb = new \Imagine\Image\Palette\RGB();
				}
				$image->usePalette(self::$rgb);
			}
			catch (\Exception $e) {
				if (self::$graphicsLib === 1) {
					$image->getImagick()->stripimage();  // Imagick: make sure all profiles are removed
				}
			}
		}

		if (self::$graphicsLib === 1) {
			$image->getImagick()
					->thumbnailImage($this->size->getWidth(), $this->size->getHeight());
			return $image;
		}
		elseif (self::$graphicsLib === 2) {
			$image->getGmagick()
					->scaleimage($this->size->getWidth(), $this->size->getHeight())
					->stripimage();
			return $image;
		}
		else {  // GD
			return $image->resize($this->size);
		}
	}

}
