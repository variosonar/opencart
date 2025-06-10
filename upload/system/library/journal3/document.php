<?php

namespace Journal3;

use Journal3\Utils\Arr;
use Nette\Utils\Arrays;

/**
 * Class Document is used to store current page head elements like classes, css, js, fonts
 *
 * It's an extension to Opencart Document class
 *
 * @package Journal3
 */
class Document {

	/**
	 * @var array
	 */
	private $metas = [];

	/**
	 * @var array
	 */
	private $classes = [];

	/**
	 * @var array
	 */
	private $css = [];

	/**
	 * @var array
	 */
	private $js = [];

	/**
	 * @var array
	 */
	private $fonts = [];

	/**
	 * @var array
	 */
	private $links = [];

	/**
	 * @var
	 */
	private $page_route;

	/**
	 * @var
	 */
	private $page_id;

	/**
	 * @param $key
	 * @param $value
	 */
	public function addMeta($name, $content, $attr = 'name') {
		$this->metas[] = [
			'name'    => $name,
			'content' => $content,
			'attr'    => $attr,
		];
	}

	/**
	 * @return array
	 */
	public function getMetas() {
		return $this->metas;
	}

	/**
	 * @param $class
	 */
	public function addClass($class) {
		$class = htmlspecialchars(trim($class), ENT_COMPAT, 'UTF-8');
		$this->classes[$class] = $class;
	}

	/**
	 * @return array
	 */
	public function getClasses() {
		return $this->classes;
	}

	/**
	 * @param $css
	 * @param null|string $id
	 * @param int $priority
	 */
	public function addCss($css, $id = null, $priority = 0) {
		static $index = 0;

		if ($css) {
			$this->css[] = [
				'id'       => $id,
				'css'      => $css,
				'priority' => $priority ? $priority : ++$index,
			];
		}
	}

	/**
	 * @return array
	 */
	public function getCss() {
		$sort_order = [];

		foreach ($this->css as $key => $value) {
			$sort_order[$key] = $value['priority'];
		}

		array_multisort($sort_order, SORT_ASC, $this->css);

		return $this->css;
	}

	/**
	 * @param $js
	 */
	public function addJs($js) {
		if ($js) {
			$this->js = array_merge($this->js, $js);
		}
	}

	/**
	 * @return array
	 */
	public function getJs() {
		return $this->js;
	}

	/**
	 * @param $href
	 * @param $rel
	 * @param array $attrs
	 */
	public function addLink($href, $rel, $attrs = []) {
		$this->links[] = [
			'href'  => $href,
			'rel'   => $rel,
			'attrs' => implode(' ', Arrays::map($attrs, function ($value, $key) {
				return is_numeric($key) ? $value : $key . '="' . $value . '"';
			})),
		];
	}

	/**
	 * @return array
	 */
	public function getLinks() {
		return $this->links;
	}

	/**
	 * @param $fonts
	 */
	public function addFonts($fonts) {
		if ($fonts) {
			$this->fonts = Arr::merge($this->fonts, $fonts);
		}
	}

	/**
	 * @return array|null
	 */
	public function getFonts() {
		if (empty($this->fonts['fonts'])) {
			return null;
		}

		$fonts = Arrays::map($this->fonts['fonts'], function ($value, $key) {
			$family = str_replace(' ', '+', $key);

			if ($value) {
				return $family . ':' . implode(',', $value);
			} else {
				return $family;
			}
		});

		return [
			'family' => implode('%7C', $fonts),
			'subset' => implode(',', $this->fonts['subsets']),
		];
	}

	/**
	 * @return array|null
	 */
	public function getFontsCustom() {
		if (empty($this->fonts['fonts_custom'])) {
			return null;
		}

		return $this->fonts['fonts_custom'];
	}

	/**
	 * @param $page_id
	 */
	public function setPageId($page_id) {
		$this->page_id = (int)$page_id;
	}

	/**
	 * @param $page_route
	 */
	public function setPageRoute($page_route) {
		$this->page_route = $page_route;
	}

	/**
	 * @return mixed
	 */
	public function getPageId() {
		return $this->page_id;
	}

	/**
	 * @return mixed
	 */
	public function getPageRoute() {
		return $this->page_route;
	}

}
