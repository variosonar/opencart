<?php

namespace Journal3;

/**
 * Class Browser is used to determine current browser from HTTP_USER_AGENT
 * This detection helps serve different content based on user device
 *
 * @package Journal3
 */
class Browser extends \Browser {

	/**
	 * @var string
	 */
	private $device;
	/**
	 * @var
	 */
	private $classes;

	/**
	 * Browser constructor.
	 */
	public function __construct($registry) {
		parent::__construct();

		// ipad fix
		if ($this->isBrowser(Browser::BROWSER_SAFARI)) {
			if (($registry->get('session')->data['journal3_device'] ?? null) === 'ipad') {
				$this->setMobile(true);
				$this->setTablet(true);
				$this->setPlatform(Browser::PLATFORM_IPAD);
			}
		}

		// device detect
		if ($this->isMobile()) {
			if ($this->isTablet()) {
				$this->device = 'tablet';
			} else {
				$this->device = 'phone';
			}
		} else {
			$this->device = 'desktop';
		}

		// browser platform
		switch ($this->getPlatform()) {
			case Browser::PLATFORM_ANDROID:
				$this->classes[] = 'android';
				break;

			case Browser::PLATFORM_APPLE:
				$this->classes[] = 'mac';
				if ($this->getBrowser() === Browser::BROWSER_SAFARI) {
					$this->classes[] = 'apple';
				}
				break;

			case Browser::PLATFORM_IPAD:
				$this->classes[] = 'ipad';
				$this->classes[] = 'ios';
				$this->classes[] = 'apple';
				$this->device = 'tablet';
				break;

			case Browser::PLATFORM_IPHONE:
				$this->classes[] = 'iphone';
				$this->classes[] = 'ios';
				$this->classes[] = 'apple';
				break;

			case Browser::PLATFORM_LINUX:
				$this->classes[] = 'linux';
				break;

			case Browser::PLATFORM_WINDOWS:
				$this->classes[] = 'win';
				break;
		}

		// browser version
		$version = explode('.', $this->getVersion());
		$version = is_array($version) && count($version) ? $version[0] : '';

		// browser type
		switch ($this->getBrowser()) {
			case Browser::BROWSER_CHROME:
				$this->classes[] = 'chrome';
				$this->classes[] = 'chrome' . $version;
				$this->classes[] = 'webkit';
				break;

			case Browser::BROWSER_FIREFOX:
				$this->classes[] = 'firefox';
				$this->classes[] = 'firefox' . $version;
				break;

			case Browser::BROWSER_EDGE;
				$this->classes[] = 'edge';
				break;

			case Browser::BROWSER_IE:
				$this->classes[] = 'ie';
				$this->classes[] = 'ie' . $version;
				break;

			case Browser::BROWSER_OPERA:
				$this->classes[] = 'opera';
				$this->classes[] = 'opera' . $version;
				$this->classes[] = 'webkit';
				break;

			case Browser::BROWSER_SAFARI:
			case Browser::BROWSER_IPHONE:
			case Browser::BROWSER_IPAD:
				$this->classes[] = 'safari';
				$this->classes[] = 'safari' . $version;
				$this->classes[] = 'webkit';
				break;

			default:
				$this->classes[] = strtolower(str_replace(' ', '', $this->getBrowser()));
		}
	}

	/**
	 * @return string
	 */
	public function getDevice() {
		return $this->device;
	}

	/**
	 * @return mixed
	 */
	public function getClasses() {
		return $this->classes;
	}

}
