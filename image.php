<?php
/**
 * Image Manipulation Classes
 *
 * @author Robert Brodrecht
 * @version 0.01
 */

/**
 * Image
 *
 * This is an effort to make manipulating images easier in PHP.
 *
 * @author Robert Brodrecht
 * @version 0.01
 */
class Image {
	/** The current image object. */
	public $image = false;

	/** The current image path. */
	public $path = false;

	/** The current image filename. */
	public $filename = false;

	/** The current image mime type. */
	public $mime = false;

	/** The current image extension handler. */
	public $ext = false;

	/** The current image width. */
	public $width = false;

	/** The current image height. */
	public $height = false;

	/** The current image aspect ratio as a decimal number. */
	public $aspect = false;

	/** The current image orientation: landscape, portrait, or square. */
	public $orientation = false;

	/**
	 * Constructor
	 *
	 * Load an image.
	 *
	 * @param $img string An image or multiple images to manipulate.
	 * @returns boolean Whether the image was set up.
	 * @author Robert Brodrecht
	 * @version 0.01
	 */
	public function __construct($img = false) {
		if($img) {
			$img = (string) $img;
			if(file_exists($img) && is_readable($img)) {
				// Basic details we need to figure out how to handle the image.
				$supported_image_type = false;
				$img_extension = pathinfo($img, PATHINFO_EXTENSION);
				$img_file_name = pathinfo($img, PATHINFO_BASENAME);
				$img_mime = mime_content_type($img);
				$img_size = getimagesize($img);

				// Convert valid types to a true color image.
				switch($img_extension) {
					case 'gif':
						if($img_mime === 'image/gif') {
							$supported_image_type = true;
							$gif_img = imagecreatefromgif($img);
							if($gif_img) {
								$this->image = imagecreatetruecolor($img_size[0], $img_size[1]);
								if(!imagecopy($this->image, $gif_img, 0, 0, 0, 0, $img_size[0], $img_size[1])) {
									$this->image = false;
								}
							}
						}
					break;
					case 'png':
						if($img_mime === 'image/png') {
							$supported_image_type = true;
							$png_img = imagecreatefrompng($img);
							if($png_img) {
								$this->image = imagecreatetruecolor($img_size[0], $img_size[1]);
								if(!imagecopy($this->image, $png_img, 0, 0, 0, 0, $img_size[0], $img_size[1])) {
									$this->image = false;
								}
							}
						}
					break;
					case 'jpg':
					case 'jpeg':
						$img_extension = 'jpg';
						if($img_mime === 'image/jpeg') {
							$supported_image_type = true;
							$jpg_img = imagecreatefromjpeg($img);
							if($jpg_img) {
								$this->image = imagecreatetruecolor($img_size[0], $img_size[1]);
								if(!imagecopy($this->image, $jpg_img, 0, 0, 0, 0, $img_size[0], $img_size[1])) {
									$this->image = false;
								}
							}
						}
					break;
				}

				// If we have a working image, get rolling.
				if($supported_image_type && $this->image) {
					// Make alpha work.
					imagealphablending($this->image, true);
					imagesavealpha($this->image, true);

					// Set helpful info about the file.
					$this->width = $img_size[0];
					$this->height = $img_size[1];
					$this->path = $img;
					$this->filename = $img_file_name;
					$this->mime = $img_mime;
					$this->ext = $img_extension;
					$this->aspect = $this->width/$this->height;
					if($this->aspect > 1) {
						$this->orientation = 'landscape';
					} else if($this->aspect < 1) {
						$this->orientation = 'portrait';
					} else {
						$this->orientation = 'square';
					}
					return true;
				} else {
					trigger_error('The image could not be opened because it is not a valid image.', E_USER_WARNING);
					return false;
				}
			} else {
				trigger_error('The image does not exist or is not readable.', E_USER_WARNING);
				return false;
			}
		}
		return false;
	}

	/**
	 * Destructor
	 *
	 * @author Robert Brodrecht
	 * @version 0.01
	 */
	public function __destruct() {
		if($this->image) {
			imagedestroy($this->image);
		}
	}

	/**
	 * Export Image To File
	 *
	 * Exports the image to the specified type with the specified quality to the specified
	 * path. If no path is set, the file will be dumped to the screen with the proper
	 * content-type header.
	 *
	 * @param $type string The export type: jpg, png, gif.
	 * @param $quality int The export quality from 0 to 100.
	 * @param $path string The path of where to save the image.  If it has a file name, save to that specific file.  If not set, save over the current image.
	 * @author Robert Brodrecht
	 * @version 0.01
	 */
	public function export($type = 'jpg', $quality = 50, $path = false) {
		if(!$this->image) {
			trigger_error('The image does not exist or is not readable.', E_USER_WARNING);
			return false;
		}

		// If there is a path, make sure it is a real one.
		if(!is_null($path)) {
			if($path === false) {
				$path = $this->path;
			}

			$output_path = pathinfo($path);

			if(!$output_path['extension']) {
				if(substr($path, -1) !== '/') {
					$path .= '/';
				}
				$path .= $this->filename;
				$output_path['basename'] = $this->filename;
			}

			if(!is_dir($output_path['dirname']) && !is_file($output_path['dirname'])) {
				mkdir($output_path['dirname'], 0777, true);
			}
		}

		// Whether the conversion / output of the image was successful.
		$save_status = false;

		// Handle output for each type, accounting for whether to output to the screen or a file based on is_null($path).
		switch($type) {
			case 'gif':
				if(!is_null($path)) {
					$path = preg_replace('/(png|jpg|jpeg)$/', 'gif', $path);
				} else {
					header('Content-type: image/jpeg');
				}
				$save_status = imagegif($this->image, $path, round($quality/10));
			break;
			case 'png':
				if(!is_null($path)) {
					$path = preg_replace('/(gif|jpg|jpeg)$/', 'png', $path);
				} else {
					header('Content-type: image/jpeg');
				}
				$quality = 10-round($quality/10);
				if($quality > 9) {
					$quality = 9;
				}
				if($quality < 0) {
					$quality = 0;
				}
				$save_status = imagepng($this->image, $path, $quality, PNG_FILTER_NONE);
			break;
			case 'jpg':
			case 'jpeg':
				if(!is_null($path)) {
					$path = preg_replace('/(png|gif)$/', 'jpg', $path);
				} else {
					header('Content-type: image/jpeg');
				}
				$save_status = imagejpeg($this->image, $path, $quality);
			break;
			default:
				trigger_error('The export type is not a valid export type.  Use jpg, png, or gif.', E_USER_WARNING);
				return false;
			break;
		}
		if($save_status) {
			return $path;
		} else {
			trigger_error('The image could not be created.', E_USER_WARNING);
			return false;
		}
	}

	/**
	 * Dump The Image To The Screen
	 *
	 * @param $type string The export type: jpg, png, gif.
	 * @param $quality int The export quality from 0 to 100.
	 * @returns boolean The result of the export.
	 * @uses Image::export To handle writing the image out to the screen.
	 * @author Robert Brodrecht
	 * @version 0.01
	 */
	public function display($type = 'jpg', $quality = 50) {
		return $this->export($type, $quality, null);
	}

	/**
	 * Scale an Image
	 *
	 * Scale an image by a percentage.
	 *
	 * @param $scale string|int The amount to scale.  If scaling to a percentage, include the '%'.
	 * @returns boolean Whether the resize was successful.
	 * @author Robert Brodrecht
	 * @version 0.01
	 */
	public function scale($scale = 1) {
		if(stristr($scale, '%') !== false) {
			$scale = (float) preg_replace('/[^0-9\.]/', '', $scale);
			$scale = $scale/100;
		}
		$scale = (float) $scale;
		$new_width = ceil($this->width * $scale);
		$new_height = ceil($this->height * $scale);

		$working_image = imagecreatetruecolor($new_width, $new_height);

		if(imagecopyresampled($working_image, $this->image, 0, 0, 0, 0, $new_width, $new_height, $this->width, $this->height)) {
			$this->image = $working_image;
			$this->width = $new_width;
			$this->height = $new_height;
			return true;
		} else {
			trigger_error('Resize failed.', E_USER_WARNING);
			return false;
		}
	}

	/**
	 * Resize an Image
	 *
	 * Resize an image to set dimensions.  The developer can decide whether to fit or
	 * stretch the image if the new size is not the same aspect ratio.
	 *
	 * @param $method string How to resize: fit (resize to fit in the bounding box), cover (like a cropped thumbnail), or stretch (yuck).
	 * @param $dimensions int|array New size either as an integer width or an array of dimensions where index 0 is width and index 1 is height.
	 * @returns boolean Whether the resize was successful.
	 * @author Robert Brodrecht
	 * @version 0.01
	 */
	public function resize($dimensions = false, $method = 'fit') {
		if($dimensions === false || empty($dimensions)) {
			return false;
		}

		$user_cover_method = false;

		// If we only have one dimension, it's the width.  Copy it down.
		if(is_array($dimensions) && (count($dimensions) === 1 || empty($dimensions[1]))) {
			$dimensions = $dimensions[0];
		}

		// The developer only sent the width, so do the math.
		if(!is_array($dimensions)) {
			$dimensions = (int) $dimensions;
			$new_width = $dimensions;
			$new_height = ceil($this->height*$new_width/$this->width);

		// The developer sent width and height or just height.
		} else {
			// The developer sent only the new height, so do the math.
			if(empty($dimensions[0])) {
				$dimensions = (int) $dimensions[1];
				$new_height = $dimensions;
				$new_width = $this->width*$new_height/$this->height;

			// The developer sent width and height, so we either need to make it fit or stretch it.
			} else {
				switch($method) {
					default:
						// The width and height if we scaled the new dimensions up to current width and height.
						$pretend_height = $this->width*$dimensions[1]/$dimensions[0];
						$pretend_width = $this->height*$dimensions[0]/$dimensions[1];

						if($pretend_height > $this->height) {
							$new_width = $dimensions[0];
							$new_height = $this->height*$new_width/$this->width;
						} else if($pretend_width > $this->width) {
							$new_height = $dimensions[1];
							$new_width = $this->width*$new_height/$this->height;
						} else {
							$new_width = $dimensions[0];
							$new_height = $this->height*$new_width/$this->width;
						}
					break;
					case 'cover':
						// The width and height if we scaled the new dimensions up to current width and height.
						$pretend_height = $this->width*$dimensions[1]/$dimensions[0];
						$pretend_width = $this->height*$dimensions[0]/$dimensions[1];

						// We have two dimensions and the user chose to cover.
						$user_cover_method = true;

						if($pretend_width > $this->width) {
							$new_width = $dimensions[0];
							$new_height = $this->height*$new_width/$this->width;
						} else if($pretend_height > $this->height) {
							$new_height = $dimensions[1];
							$new_width = $this->width*$new_height/$this->height;
						} else {
							$new_width = $dimensions[0];
							$new_height = $this->height*$new_width/$this->width;
						}
					break;
					case 'stretch':
						// Stretching only happens if both dimensions are set and stretch is set.
						$new_width = $dimensions[0];
						$new_height = $dimensions[1];
					break;
				}
			}
		}

		// Photoshop rounds up, so we'll go with that.
		$new_width = ceil($new_width);
		$new_height = ceil($new_height);

		// If we definitely have a cover method, we will use the dimensions as the new image dimensions.
		if($user_cover_method) {
			$working_image = imagecreatetruecolor((int) $dimensions[0], (int) $dimensions[1]);
			// The image will be centered.
			$offset_x = ceil(abs(($new_width-$dimensions[0])/2));
			$offset_y = ceil(abs(($new_height-$dimensions[1])/2));

		// If we do not have a cover method, we will use the new width and height.
		} else {
			$working_image = imagecreatetruecolor($new_width, $new_height);
			// The image will go on the top left.
			$offset_x = 0;
			$offset_y = 0;
		}

		if(imagecopyresampled($working_image, $this->image, 0, 0, $offset_x, $offset_y, $new_width, $new_height, $this->width, $this->height)) {
			$this->image = $working_image;
			$this->width = $new_width;
			$this->height = $new_height;
			$this->aspect = $this->width/$this->height;
			if($this->aspect > 1) {
				$this->orientation = 'landscape';
			} else if($this->aspect < 1) {
				$this->orientation = 'portrait';
			} else {
				$this->orientation = 'square';
			}
			return true;
		} else {
			trigger_error('Resize failed.', E_USER_WARNING);
			return false;
		}
	}

	/**
	 * Rotate an Image
	 *
	 * Rotate an image by a particular angle.
	 *
	 * @param $angle float The angle to rotate.
	 * @param $bg_color string If the rotate is not exact, what the background color should be.
	 * @returns boolean Whether the rotate was successful.
	 * @author Robert Brodrecht
	 * @version 0.01
	 */
	public function rotate($angle = 0, $bg_color = array(255, 255, 255)) {
		if(!$this->image) {
			trigger_error('The image does not exist or is not readable.', E_USER_WARNING);
			return false;
		}

		$angle = (float) $angle;
		$bg_color = $this->normalizeColor($bg_color);

		if($bg_color === false) {
			trigger_error('Rotate failed because background color could not be generated.  Try sending an array(RRR, GGG, BBB).', E_USER_WARNING);
			return false;
		}

		$working_image = imagerotate($this->image, $angle, imagecolorallocate($this->image, $bg_color[0], $bg_color[1], $bg_color[2]));

		if($working_image) {
			$this->image = $working_image;
			$this->width = imagesx($this->image);
			$this->height = imagesy($this->image);
			$this->aspect = $this->width/$this->height;
			if($this->aspect > 1) {
				$this->orientation = 'landscape';
			} else if($this->aspect < 1) {
				$this->orientation = 'portrait';
			} else {
				$this->orientation = 'square';
			}
			return true;
		} else {
			trigger_error('Rotate failed.', E_USER_WARNING);
			return false;
		}
	}

	/**
	 * Rotate an Image Clockwise
	 *
	 * Rotate an image by a particular angle clockwise.
	 *
	 * @param $angle float The angle to rotate.
	 * @param $bg_color string If the rotate is not exact, what the background color should be.
	 * @returns boolean Whether the rotate was successful.
	 * @author Robert Brodrecht
	 * @version 0.01
	 */
	public function rotateClockwise($angle = 0, $bg_color = array(255, 255, 255)) {
		if(!$this->image) {
			trigger_error('The image does not exist or is not readable.', E_USER_WARNING);
			return false;
		}
		$angle = 0 - (float) $angle;
		return $this->rotate($angle, $bg_color);
	}

	/**
	 * Rotate an Image Counterclockwise
	 *
	 * Rotate an image by a particular angle counterclockwise.
	 *
	 * @param $angle float The angle to rotate.
	 * @param $bg_color string If the rotate is not exact, what the background color should be.
	 * @returns boolean Whether the rotate was successful.
	 * @author Robert Brodrecht
	 * @version 0.01
	 */
	public function rotateCounterclockwise($angle = 0, $bg_color = array(255, 255, 255)) {
		if(!$this->image) {
			trigger_error('The image does not exist or is not readable.', E_USER_WARNING);
			return false;
		}
		return $this->rotate($angle, $bg_color);
	}

	/**
	 * Rotate an Image Clockwise by 90 Degrees
	 *
	 * @param $bg_color string If the rotate is not exact, what the background color should be.
	 * @returns boolean Whether the rotate was successful.
	 * @author Robert Brodrecht
	 * @version 0.01
	 */
	public function rotateRight($bg_color = array(255, 255, 255)) {
		if(!$this->image) {
			trigger_error('The image does not exist or is not readable.', E_USER_WARNING);
			return false;
		}
		return $this->rotate(-90, $bg_color);
	}

	/**
	 * Rotate an Image Counterclockwise by 90 Degrees
	 *
	 * @param $bg_color string If the rotate is not exact, what the background color should be.
	 * @returns boolean Whether the rotate was successful.
	 * @author Robert Brodrecht
	 * @version 0.01
	 */
	public function rotateLeft($bg_color = array(255, 255, 255)) {
		if(!$this->image) {
			trigger_error('The image does not exist or is not readable.', E_USER_WARNING);
			return false;
		}
		return $this->rotate(90, $bg_color);
	}

	/**
	 * Flip an Image Vertically and/or Horizontally
	 *
	 * @param $dir string The direction to flip.
	 * @returns boolean Whether the flip was successful.
	 * @author Robert Brodrecht
	 * @version 0.01
	 */
	public function flip($dir = 'both') {
		if(!$this->image) {
			trigger_error('The image does not exist or is not readable.', E_USER_WARNING);
			return false;
		}
		$dir = substr(strtolower($dir), 0, 1);
		switch($dir) {
			default:
				 $res_v = $this->flipVertical();
				 $res_h = $this->flipHorizontal();
				 return $res_v && $res_h;
			break;
			case 'v':
				return $this->flipVertical();
			break;
			case 'h':
				return $this->flipHorizontal();
			break;
		}
	}

	/**
	 * Flip an Image Vertically
	 *
	 * @returns boolean Whether the flip was successful.
	 * @author Robert Brodrecht
	 * @version 0.01
	 */
	public function flipVertical() {
		if(!$this->image) {
			trigger_error('The image does not exist or is not readable.', E_USER_WARNING);
			return false;
		}

		$working_image = imagecreatetruecolor($this->width, $this->height);
		if(imagecopyresampled($working_image, $this->image, 0, 0, 0, $this->height-1, $this->width, $this->height, $this->width, 0-$this->height)) {
			$this->image = $working_image;
		} else {
			trigger_error('Flip vertical failed.', E_USER_WARNING);
			return false;
		}
	}

	/**
	 * Flip an Image Horizontally
	 *
	 * @returns boolean Whether the flip was successful.
	 * @author Robert Brodrecht
	 * @version 0.01
	 */
	public function flipHorizontal() {
		if(!$this->image) {
			trigger_error('The image does not exist or is not readable.', E_USER_WARNING);
			return false;
		}

		$working_image = imagecreatetruecolor($this->width, $this->height);
		if(imagecopyresampled($working_image, $this->image, 0, 0, $this->width-1, 0, $this->width, $this->height, 0-$this->width, $this->height)) {
			$this->image = $working_image;
		} else {
			trigger_error('Flip horizontal failed.', E_USER_WARNING);
			return false;
		}
	}

	/**
	 * Make an Image Monochrome
	 *
	 * Makes an image monochrome, then converts the color values of black and white to
	 * the specified values.  We use the monochrome image to get a percentage of black and
	 * use that to create alpha'd colors to apply on top of the white-color background.
	 *
	 * @param $white string|array Replace white colors with this color. Values include: HTML hex string, HTML rgb string, array of RGB values, or the string 'transparent'.
	 * @param $black string|array Replace black colors with this color. Values include: HTML hex string, HTML rgb string, array of RGB values, or the string 'transparent'.
	 * @returns boolean Whether the conversion was successful.
	 * @author Robert Brodrecht
	 * @version 0.01
	 */
	public function monochrome($white = array(255, 255, 255), $black = array(0, 0, 0)) {
		if(!$this->image) {
			trigger_error('The image does not exist or is not readable.', E_USER_WARNING);
			return false;
		}

		// If black is transparent, we have to work with the image in a different way.  Keep track of it.
		$black_is_transparent = false;

		if($white !== 'transparent') {
			// Try to figure out the white value.
			if(!is_array($white)) {
				$white = $this->normalizeColor($white);
				if(!$white) {
					$white = array(255, 255, 255);
					trigger_error('White value could not be determined. Using #FFF instead.', E_USER_WARNING);
				}
			}
			$white_raw_value = imagecolorallocate($this->image, $white[0], $white[1], $white[2]);
		} else {
			$white = imagecolorallocatealpha($this->image, 255, 255, 255, 127);
			$white_raw_value = $white;
		}

		if($black !== 'transparent') {
			// Try to figure out the black value.
			if(!is_array($black)) {
				$black = $this->normalizeColor($black);
				if(!$black) {
					$black = array(255, 255, 255);
					trigger_error('Black value could not be determined. Using #000 instead.', E_USER_WARNING);
				}
			}
			$black_raw_value = imagecolorallocate($this->image, $black[0], $black[1], $black[2]);
		} else {
			$black = imagecolorallocatealpha($this->image, 0, 0, 0, 127);
			$black_raw_value = $black;
			$black_is_transparent = true;
		}

		if(imagefilter($this->image, IMG_FILTER_GRAYSCALE)) {
			// If it just needs to be grayscale, we don't need to do anything else.
			if($white != array(255, 255, 255) || $black != array(0, 0, 0)) {
				// Pure black and pure white as determined by imagecolorat.
				$pure_black = 0;
				$pure_white = 16777215;

				// Create the base image that acts as the background.
				$base_image = imagecreatetruecolor($this->width, $this->height);
				imagealphablending($base_image, true);
				imagesavealpha($base_image, true);
				// If black is transparent, we'll be adding colors to the base, so it should start as transparent.
				if($black_is_transparent) {
					imagefill($base_image, 0, 0, $black);
				// Otherwise, we'll be applying colors to the top to copy onto the bottom, so it should be filled with the white color.
				} else {
					imagefill($base_image, 0, 0, $white_raw_value);
				}

				// Create the top image where the translucent black colors will go.
				$top_image = imagecreatetruecolor($this->width, $this->height);
				imagealphablending($top_image, true);
				imagesavealpha($top_image, true);
				imagefill($top_image, 0, 0, imagecolorallocatealpha($top_image, 0, 0, 0, 127));

				// Loop the height.
				for($y = 0; $y < $this->height; $y++) {
					// Loop the width.
					for($x = 0; $x < $this->width; $x++) {
						// If black is transparent, we need to apply colors to the base image instead of the top image.
						if($black_is_transparent) {
							// Get the current pixel's color value.
							$color = imagecolorsforindex($this->image, imagecolorat($this->image, $x, $y));
							// Get an alpha value between 0 and 127 based on how white the current pixel is.
							$alpha = 127-round(127*($color['red']/255));
							// Get the new alpha'd white color.
							$color = imagecolorallocatealpha($base_image, $white[0], $white[1], $white[2], $alpha);
							// Apply the color.
							imagesetpixel($base_image, $x, $y, $color);
						} else {
							// Get the current pixel's color value.
							$color = imagecolorsforindex($this->image, imagecolorat($this->image, $x, $y));
							// Get an alpha value between 0 and 127 based on how white the current pixel is.
							$alpha = round(127*($color['red']/255));
							// Get the new alpha'd black color.
							$color = imagecolorallocatealpha($top_image, $black[0], $black[1], $black[2], $alpha);
							// Apply the color.
							imagesetpixel($top_image, $x, $y, $color);
						}
						$color = null;
					}
					$x = null;
				}
				$y = null;

				// Copy the top image onto the base.
				imagecopy($base_image, $top_image, 0, 0, 0, 0, $this->width, $this->height);
				$this->image = $base_image;
			}
			return true;
		} else {
			trigger_error('Image failed to convert to grayscale.', E_USER_WARNING);
			return false;
		}
	}

	/**
	 * Negative Image
	 *
	 * @returns boolean Whether the filter was successful.
	 * @author Robert Brodrecht
	 * @version 0.01
	 */
	public function negative() {
		if(!$this->image) {
			trigger_error('The image does not exist or is not readable.', E_USER_WARNING);
			return false;
		}
		return imagefilter($this->image, IMG_FILTER_NEGATE);
	}

	/**
	 * Adjust Birghtness
	 *
	 * @param $amount int The level to adjust.  Negative is darker, Positive is lighter.
	 * @returns boolean Whether the filter was successful.
	 * @author Robert Brodrecht
	 * @version 0.01
	 */
	public function brightness($amount = 0) {
		if(!$this->image) {
			trigger_error('The image does not exist or is not readable.', E_USER_WARNING);
			return false;
		}
		$amount = (int) $amount;
		if($amount > 100) {
			$amount = 100;
		} else if($amount < -100) {
			$amount = -100;
		}
		return imagefilter($this->image, IMG_FILTER_BRIGHTNESS, $amount);
	}

	/**
	 * Adjust Contrast
	 *
	 * @param $amount int The level to adjust.  Negative is less contrast, Positive is more contrast.
	 * @returns boolean Whether the filter was successful.
	 * @author Robert Brodrecht
	 * @version 0.01
	 */
	public function contrast($amount = 0) {
		if(!$this->image) {
			trigger_error('The image does not exist or is not readable.', E_USER_WARNING);
			return false;
		}
		$amount = 0 - (int) $amount;
		if($amount > 100) {
			$amount = 100;
		} else if($amount < -100) {
			$amount = -100;
		}
		return imagefilter($this->image, IMG_FILTER_CONTRAST, $amount);
	}

	/**
	 * Adjust Color
	 *
	 * @param $amount string|array The HTML hex, HTML RGB, or array off color values.
	 * @returns boolean Whether the filter was successful.
	 * @author Robert Brodrecht
	 * @version 0.01
	 */
	public function colorize($amount = '#FFF') {
		if(!$this->image) {
			trigger_error('The image does not exist or is not readable.', E_USER_WARNING);
			return false;
		}
		$amount = $this->normalizeColor($amount);
		return imagefilter($this->image, IMG_FILTER_COLORIZE, $amount[0], $amount[1], $amount[2]);
	}

	/**
	 * Detect Edges
	 *
	 * @returns boolean Whether the filter was successful.
	 * @author Robert Brodrecht
	 * @version 0.01
	 */
	public function edgeDetect() {
		if(!$this->image) {
			trigger_error('The image does not exist or is not readable.', E_USER_WARNING);
			return false;
		}
		return imagefilter($this->image, IMG_FILTER_EDGEDETECT);
	}

	/**
	 * Emboss Image
	 *
	 * @returns boolean Whether the filter was successful.
	 * @author Robert Brodrecht
	 * @version 0.01
	 */
	public function emboss() {
		if(!$this->image) {
			trigger_error('The image does not exist or is not readable.', E_USER_WARNING);
			return false;
		}
		return imagefilter($this->image, IMG_FILTER_EMBOSS);
	}

	/**
	 * Blur Image
	 *
	 * @param $amount int The number of times to apply the blur.
	 * @returns boolean Whether the filter was successful.
	 * @author Robert Brodrecht
	 * @version 0.01
	 */
	public function blur($amount) {
		if(!$this->image) {
			trigger_error('The image does not exist or is not readable.', E_USER_WARNING);
			return false;
		}
		$amount = (int) $amount;
		$res = true;
		while($amount > 0) {
			$res = imagefilter($this->image, IMG_FILTER_GAUSSIAN_BLUR) && $res;
			$amount--;
		}
		return $res;
	}

	/**
	 * Selective Blur Image
	 *
	 * This should help remove noise.
	 *
	 * @param $amount int The number of times to apply the blur.
	 * @returns boolean Whether the filter was successful.
	 * @author Robert Brodrecht
	 * @version 0.01
	 */
	public function selectiveBlur($amount) {
		if(!$this->image) {
			trigger_error('The image does not exist or is not readable.', E_USER_WARNING);
			return false;
		}
		$amount = (int) $amount;
		$res = true;
		while($amount > 0) {
			$res = imagefilter($this->image, IMG_FILTER_SELECTIVE_BLUR) && $res;
			$amount--;
		}
		return $res;
	}

	/**
	 * Sketchify Image
	 *
	 * @returns boolean Whether the filter was successful.
	 * @author Robert Brodrecht
	 * @version 0.01
	 */
	public function removeMean() {
		if(!$this->image) {
			trigger_error('The image does not exist or is not readable.', E_USER_WARNING);
			return false;
		}
		return imagefilter($this->image, IMG_FILTER_MEAN_REMOVAL);
	}

	/**
	 * Smooth Image
	 *
	 * @param $amount int The amount to smooth.
	 * @returns boolean Whether the filter was successful.
	 * @author Robert Brodrecht
	 * @version 0.01
	 */
	public function smooth($amount) {
		if(!$this->image) {
			trigger_error('The image does not exist or is not readable.', E_USER_WARNING);
			return false;
		}
		$amount = (int) $amount;
		return imagefilter($this->image, IMG_FILTER_SMOOTH, $amount);
	}

	/**
	 * Pixelate Image
	 *
	 * @param $block_site int The size of the blocks.
	 * @param $advanced bool Whether to use advanced pixels.
	 * @returns boolean Whether the filter was successful.
	 * @author Robert Brodrecht
	 * @version 0.01
	 */
	public function pixelate($amount = 0, $advanced = true) {
		if(!$this->image) {
			trigger_error('The image does not exist or is not readable.', E_USER_WARNING);
			return false;
		}
		$amount = (int) $amount;
		return imagefilter($this->image, IMG_FILTER_PIXELATE, $amount, $advanced);
	}

	/**
	 * Normalize A Color String
	 *
	 * @param $color string HTML hex color codes or HTML rgb color codes.
	 * @returns RGB array.
	 * @author Robert Brodrecht
	 * @version 0.01
	 */
	private function normalizeColor($color = false) {
		if($color === false) {
			return false;
		}

		// Probably already an RGB array.
		if(is_array($color)) {
			return $color;
		}

		// Remove characters that aren't hex or rgb().
		$ret = explode(
						',',
						preg_replace(
								array(
									'/^.*?rgb\(/',	// Remove start of rgb() formatted values.
									'/\).*?$/',		// Remove end of rgb() formatted values.
									'/[^A-F0-9,]/i'	// Remove anythingthat isn't hex or RGB.
								),
								array('', '', ''),
								(string) $color
							)
					);

		// Convert from HEX to an RGB array if we don't have an RGB array.
		if(count($ret) !== 3) {
			$ret = $this->hexToRGB($ret[0]);
		}
		return $ret;
	}

	/**
	 * Convert Hex Color to RGB Array
	 *
	 * @param $hex string Either a 3 or 6 character hex color string, with or without #.
	 * @returns RGB array.
	 * @author Robert Brodrecht
	 * @version 0.01
	 */
	private function hexToRGB($hex = false) {
		if($hex === false) {
			return false;
		}
		$hex = (string) $hex;

		// Remove the #.
		if(substr($hex, 0, 1) === '#') {
			$hex = substr($hex, 1);
		}

		// Used a 3-character hex, so duplicate the value to make it a 2-character string.
		if(strlen($hex) === 3) {
			$hex = array(
					str_repeat(substr($hex, 0, 1), 2),
					str_repeat(substr($hex, 1, 1), 2),
					str_repeat(substr($hex, 2, 1), 2)
				);
		// Full on 6-character, so just get each 2-character combo.
		} else if(strlen($hex) === 6) {
			$hex = array(
					substr($hex, 0, 2),
					substr($hex, 2, 2),
					substr($hex, 4, 2)
				);
		// If it is neither 3- nor 6-character, we can't figure it out.
		} else {
			$hex = false;
		}

		// Convert each value from hex to decimal.
		if($hex) {
			array_walk(
					$hex,
					function(&$val) {
						$val = hexdec($val);
					}
				);
		}
		return $hex;
	}
}
