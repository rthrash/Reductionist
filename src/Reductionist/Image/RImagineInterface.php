<?php
namespace Reductionist\Image;

use Imagine\Image\BoxInterface;

interface RImagineInterface
{
	/**
	 * Opens an existing jpeg from $path at $size or larger. Specifying the needed
	 * size allows the jpeg decoder to only read as much of the image as is necessary
	 * which can be much faster
	 *
	 * @param string $path
	 *
	 * @param BoxInterface $size
	 *
	 * @throws RuntimeException
	 *
	 * @return ImageInterface
	 */
	public function Ropenjpg($path, BoxInterface $size);
}
