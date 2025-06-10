<?php

namespace Journal3;

use Journal3\Utils\Arr;
use Journal3\Utils\Str;
use Spatie\ImageOptimizer\OptimizerChain;
use Spatie\ImageOptimizer\Optimizers\Jpegoptim;
use Spatie\ImageOptimizer\Optimizers\Optipng;
use Symfony\Component\Process\Process;
use WebPConvert\WebPConvert;

/**
 * Class Image is used to resize and optimise images
 *
 * It's similar to $this->model_tool_image object in Opencart, but with additional tools
 * We use this to avoid calling extra events when calling a model method in Opencart
 *
 * @package Journal3
 */
class Image extends Base {

	/**
	 * Stores last resized image width
	 *
	 * @var
	 */
	public $width;

	/**
	 * Stores last resized image height
	 *
	 * @var
	 */
	public $height;

	/**
	 * @var null
	 */
	private static $status = null;

	/**
	 * @var OptimizerChain
	 */
	private static $jpeg_optimiser;

	/**
	 * @var OptimizerChain
	 */
	private static $png_optimiser;

	/**
	 *
	 * Supported image types resize
	 *
	 * @var
	 */
	private static $IMAGE_TYPES = [IMAGETYPE_PNG, IMAGETYPE_JPEG, IMAGETYPE_GIF];

	public function __construct($registry) {
		parent::__construct($registry);

		if (defined('IMAGETYPE_WEBP') && version_compare(VERSION, '3.0.3.8', '>=')) {
			static::$IMAGE_TYPES[] = IMAGETYPE_WEBP;
		}
	}

	/**
	 * Resizes an image, similar to $this->model_tool_image object
	 * It also optimises the image if there are optimisers available
	 *
	 * @param $filename
	 * @param null $width
	 * @param null $height
	 * @param string $resize_type
	 * @param bool $convert_cwebp
	 * @return mixed|string|void|null
	 */
	public function resize($filename, $width = null, $height = null, $resize_type = '', $convert_cwebp = true) {
		if (is_array($filename)) {
			$filename = Arr::get($filename, $this->config->get('config_language_id'));
		}

		// Interstore remote image
		if (Str::endsWith($filename, '.http')) {
			return trim(file_get_contents(DIR_IMAGE . $filename));
		}

		// svg image
		if (Str::endsWith($filename, '.svg') || Str::endsWith($filename, '.gif')) {
			return $this->url($filename);
		}

		// external image
		if (Str::startsWith($filename, 'http://') || Str::startsWith($filename, 'https://')) {
			return $filename;
		}

		// no image dev only
		if (JOURNAL3_NO_IMAGE) {
			$filename = '';
			$resize_type = '';
		}

		// replace ampersand
		if ($filename) {
			$filename = str_replace('&amp;', '&', $filename);
		}

		// if image is not found, use a placeholder
		if (!$filename || !is_file(DIR_IMAGE . $filename)) {
			if (is_file(DIR_IMAGE . $this->journal3->get('placeholder'))) {
				$filename = $this->journal3->get('placeholder');
			} else if (is_file(DIR_IMAGE . 'placeholder.png')) {
				$filename = 'placeholder.png';
			} else {
				$filename = false;
			}
		}

		if (!$filename) {
			return $this->transparent($width, $height);
		}

		// determine current image dimensions
		list($width_orig, $height_orig) = $this->dimensions($filename);

		// image is corrupted, cannot be resized
		if (!$this->isNumeric($width_orig) || !$this->isNumeric($height_orig)) {
			trigger_error('Image <b>' . DIR_IMAGE . $filename . '</b> is invalid!');
			return;
		}

		if (!$this->isNumeric($width) && !$this->isNumeric($height)) {
			$width = $width_orig;
			$height = $height_orig;
		}

		// determine resize type based on image dimensions and resize dimensions
		$ratio_orig = (float)$width_orig / $height_orig;

		if ($this->isNumeric($width) && $this->isNumeric($height)) {
			if ($resize_type === 'fill' || $resize_type === 'crop') {
				$ratio = (float)$width / $height;

				if ($ratio > $ratio_orig) {
					$resize_type = 'w';
				} else if ($ratio < $ratio_orig) {
					$resize_type = 'h';
				} else {
					$resize_type = '';
				}
			} else {
				$ratio = (float)$width / $height;

				if ($ratio > $ratio_orig) {
					$resize_type = 'h';
				} else if ($ratio < $ratio_orig) {
					$resize_type = 'w';
				} else {
					$resize_type = '';
				}
			}
		} else if ($this->isNumeric($width)) {
			$resize_type = '';
			$height = $width / $ratio_orig;
		} else {
			$resize_type = '';
			$width = $height * $ratio_orig;
		}

		$extension = pathinfo($filename, PATHINFO_EXTENSION);

		// store last resized image width and height
		$this->width = (int)$width;
		$this->height = (int)$height;

		$image_old = $filename;
		$image_new = 'cache/' . \Journal3\Utils\Str::utf8_substr($filename, 0, \Journal3\Utils\Str::utf8_strrpos($filename, '.')) . '-' . $this->width . 'x' . $this->height . $resize_type . '.' . $extension;

		if (JOURNAL3_ENV === 'development' || defined('JOURNAL3_DEMO')) {
			$image_ext = $this->journal3_request->is_webp && $convert_cwebp && $this->journal3->get('performanceCompressImagesWebpStatus') ? 'webp' : $extension;
			$image_hash = substr(md5_file(DIR_IMAGE . $filename), 0, 16);
			$image_resize_params = "{$this->width}-{$this->height}" . ($resize_type ? "-" . $resize_type : "");
			$image_resize_hash = $image_new . '.' . md5($image_resize_params);
			$image_resize_path = "catalog/view/theme/journal3/image.php/{$image_hash}.{$image_ext}/{$image_resize_params}/{$filename}";

			if (!is_file(DIR_IMAGE . $image_resize_hash)) {
				$path = '';

				$directories = explode('/', dirname($image_resize_hash));

				foreach ($directories as $directory) {
					$path = $path . '/' . $directory;

					if (!is_dir(DIR_IMAGE . $path)) {
						@mkdir(DIR_IMAGE . $path, 0777);
					}
				}

				file_put_contents(DIR_IMAGE . $image_resize_hash, md5($image_resize_params));
			}

			return $this->url($image_resize_path, '');
		}

		// if image is already resized, skip resizing
		if (!is_file(DIR_IMAGE . $image_new) || (filemtime(DIR_IMAGE . $image_old) > filemtime(DIR_IMAGE . $image_new))) {
			list($width_orig, $height_orig, $image_type) = getimagesize(DIR_IMAGE . $image_old);

			if (!in_array($image_type, static::$IMAGE_TYPES)) {
				$this->load->model('tool/image');

				return $this->model_tool_image->resize($filename, $width, $height);
			}

			$path = '';

			$directories = explode('/', dirname($image_new));

			foreach ($directories as $directory) {
				$path = $path . '/' . $directory;

				if (!is_dir(DIR_IMAGE . $path)) {
					@mkdir(DIR_IMAGE . $path, 0777);
				}
			}

			if ($width_orig != $this->width || $height_orig != $this->height) {
				if ($this->journal3_opencart->is_oc4) {
					$image = new \Opencart\System\Library\Image(DIR_IMAGE . $image_old);
				} else {
					$image = new \Image(DIR_IMAGE . $image_old);
				}
				$image->resize($this->width, $this->height, $resize_type);
				$image->save(DIR_IMAGE . $image_new);
			} else {
				copy(DIR_IMAGE . $image_old, DIR_IMAGE . $image_new);
			}

			// optimise resized jpeg using jpegoptim
			if ($image_type === IMAGETYPE_JPEG && $this->journal3->get('performanceCompressImagesJpegStatus')) {
				$this->optimiseJpeg($image_new);
			}

			// optimise resized png using pngoptim
			if ($image_type === IMAGETYPE_PNG && $this->journal3->get('performanceCompressImagesPngStatus')) {
				$this->optimisePng($image_new);
			}
		}

		// convert to webp
		if (in_array(strtolower($extension), ['jpg', 'jpeg', 'png']) && $this->journal3_request->is_webp && $convert_cwebp && $this->journal3->get('performanceCompressImagesWebpStatus')) {
			$image_cwebp = $image_new . '.webp';

			if (!is_file(DIR_IMAGE . $image_cwebp) || (filemtime(DIR_IMAGE . $image_old) > filemtime(DIR_IMAGE . $image_cwebp))) {
				$this->convertWebp($image_new, $image_cwebp);
			}

			$image_new = $image_cwebp;
		}

		return $this->url($image_new);
	}

	/**
	 * Generates a transparent png with specified dimensions
	 * It's used in general for lazyload images placeholders
	 *
	 * @param $width
	 * @param $height
	 * @return mixed|string
	 */
	public function transparent($width, $height) {
		static $imgs = [];

		$width = (int)$width ?: 1;
		$height = (int)$height ?: 1;
		$key = "{$width}x{$height}";

		if (!isset($imgs[$key])) {
			// REF: http://stackoverflow.com/questions/9370847/php-create-image-with-imagepng-and-convert-with-base64-encode-in-a-single-file
			ob_start();

			$img = imagecreatetruecolor($width, $height);

			imagesavealpha($img, true);
			imagetruecolortopalette($img, false, 1);

			$color = imagecolorallocatealpha($img, 0, 0, 0, 127);

			imagefill($img, 0, 0, $color);
			imagepng($img, null, 9);
			imagedestroy($img);

			$data = ob_get_contents();

			ob_end_clean();

			$imgs[$key] = 'data:image/png;base64,' . base64_encode($data);
		}

		return $imgs[$key];
	}

	/**
	 * Convert image to base64 encoding, used for language flags in order to avoid extra images requests to server
	 *
	 * @param $image
	 * @return string
	 */
	public function base64($image) {
		$type = pathinfo($image, PATHINFO_EXTENSION);
		$data = file_get_contents($image);

		return 'data:image/' . $type . ';base64,' . base64_encode($data);
	}

	/**
	 * Checks if a value is a natural number
	 *
	 * @param $value
	 * @return bool
	 */
	private function isNumeric($value) {
		return is_numeric($value) && $value > 0;
	}

	/**
	 * Determine dimensions of an image
	 *
	 * @param $filename
	 * @return null[]
	 */
	private function dimensions($filename) {
		if ($filename && is_file(DIR_IMAGE . $filename)) {
			list($width, $height) = @getimagesize(DIR_IMAGE . $filename);
		} else {
			$width = null;
			$height = null;
		}

		return [$width, $height];
	}

	/**
	 * Check if image optimisers are available
	 *
	 * @param null $tool
	 * @return false|mixed|null
	 */
	public function canOptimise($tool = null) {
		if (static::$status === null) {
			if (function_exists('clock')) {
				clock()->event('Image Optimisers Status')->name('image_optimisers_status')->begin();
			}

			$tools = [
				'cwebp' => 'cwebp -version',
				'jpeg'  => 'jpegoptim --version',
				'png'   => 'optipng --version',
			];

			foreach ($tools as $key => $cmd) {
				try {
					$process = Process::fromShellCommandline($cmd);
					$process->run();

					static::$status[$key] = [
						'status'  => $process->isSuccessful(),
						'details' => explode(PHP_EOL, $process->getOutput())[0],
					];
				} catch (\Exception $e) {
					static::$status[$key] = [
						'status'  => false,
						'details' => $e->getMessage(),
					];
				}
			}

			if (static::$status['cwebp']['status']) {
				static::$status['cwebp']['details'] = 'cwebp ' . static::$status['cwebp']['details'];
			} else if (function_exists('imagewebp')) {
				static::$status['cwebp']['status'] = true;
				static::$status['cwebp']['details'] = 'PHP GD';
			}

			if (function_exists('clock')) {
				clock()->event('image_optimisers_status')->end();
			}
		}

		return $tool ? (static::$status[$tool]['status'] ?? false) : static::$status;
	}

	/**
	 * Optimises a jpg image
	 *
	 * @param $image
	 */
	public function optimiseJpeg($image) {
		if (static::$jpeg_optimiser === null) {
			static::$jpeg_optimiser = new OptimizerChain();
			static::$jpeg_optimiser->addOptimizer(new Jpegoptim([
				'-p',
				'--strip-all',
				'--max=85',
			]));
		}

		if (function_exists('clock')) {
			clock()->event('JPEG:' . $image)->begin();
		}

		static::$jpeg_optimiser->optimize(DIR_IMAGE . $image);

		if (function_exists('clock')) {
			clock()->event('JPEG:' . $image)->end();
		}
	}

	/**
	 * Optimises a png image
	 *
	 * @param $image
	 */
	public function optimisePng($image) {
		if (static::$png_optimiser === null) {
			static::$png_optimiser = new OptimizerChain();
			static::$png_optimiser->addOptimizer(new Optipng([
				'-preserve',
				'-strip all',
				'-quiet',
			]));
		}

		if (function_exists('clock')) {
			clock()->event('PNG:' . $image)->begin();
		}

		static::$png_optimiser->optimize(DIR_IMAGE . $image);

		if (function_exists('clock')) {
			clock()->event('PNG:' . $image)->end();
		}
	}

	/**
	 * Converts an image to .webp format
	 *
	 * @param $image
	 * @param $image_cwebp
	 * @throws \WebPConvert\Convert\Exceptions\ConversionFailedException
	 */
	public function convertWebp($image, $image_cwebp) {
		if (function_exists('clock')) {
			clock()->event('WEBP:' . $image)->begin();
		}

		try {
			WebPConvert::convert(DIR_IMAGE . $image, DIR_IMAGE . $image_cwebp, [
				'encoding'      => 'lossy',
				'near-lossless' => 100,
				'quality'       => 85,
				'method'        => 4,
			]);
		} catch (\Exception $e) {
			$this->log->write('WebP Conversion Error: ' . $e->getMessage() . '(' . $image . ' at ' . $this->journal3_request->url . ')');
		}

		if (function_exists('clock')) {
			clock()->event('WEBP:' . $image)->end();
		}
	}

	/**
	 * @param $func
	 * @return bool
	 *
	 * @deprecated
	 */
	public function is_func_enabled($func) {
		if (!function_exists($func)) {
			return false;
		}

		if (in_array(strtolower(ini_get('safe_mode')), ['on', '1'], true)) {
			return false;
		}

		$disabled_functions = array_map('trim', explode(',', ini_get('disable_functions')));

		if (in_array($func, $disabled_functions)) {
			return false;
		}

		return true;
	}

	public function url($image, $prefix = 'image/') {
		$image = str_replace(' ', '%20', $image);  // fix bug when attach image on email (gmail.com). it is automatic changing space " " to +

		// return image url
		if (JOURNAL3_STATIC_URL) {
			return JOURNAL3_STATIC_URL . $prefix . $image;
		} elseif ($this->request->server['HTTPS']) {
			return ($this->config->get('config_ssl') ?: $this->config->get('config_url')) . $prefix . $image;
		} else {
			return $this->config->get('config_url') . $prefix . $image;
		}
	}

}
