<?php

class ControllerJournal3EventSearch extends Controller {

	public function view_common_search_before(&$route, &$args) {

		if ($this->journal3->get('searchStyleSearchCategoriesSelectorStatus')) {
			$this->load->language('product/search');
			$this->load->model('catalog/category');
			$this->load->model('journal3/category');

			$category_id = (int)\Journal3\Utils\Arr::get($this->request->get, 'category_id', 0);
			$category = $this->language->get('text_category');

			if ($category_id) {
				$category_info = $this->model_catalog_category->getCategory($category_id);

				if ($category_info) {
					$category = $category_info['name'];
				}
			}

			$args['text_category'] = $this->language->get('text_category');
			$args['category'] = $category;
			$args['category_id'] = $category_id;

			$cache_key = "catalog.category.l{$this->config->get('config_language_id')}.s{$this->config->get('config_store_id')}";
			$cache = $this->journal3_cache->get($cache_key, false);

			if ($cache === false) {
				switch ($this->journal3->get('searchStyleSearchCategoriesType')) {
					case 'all':
						$categories = $this->model_journal3_category->getCategoryTree(0);
						$categories = $categories['items'] ?? [];

						break;

					case 'top':
						$categories = $this->model_journal3_category->getCategoryTree(0, 1);
						$categories = $categories['items'] ?? [];

						break;

					case 'top_only':
						$categories = $this->model_journal3_category->getTopCategories();

						$categories = array_map(function ($category) {
							$category['items'] = [];

							return $category;
						}, $categories);
						break;
				}

				$cache = $this->buildCategoryTree($categories);

				$this->journal3_cache->set($cache_key, $cache, false);
			}

			$args['categories'] = $cache;
		}
	}

	private function buildCategoryTree(&$categories) {
		$results = [];

		foreach ($categories as $category) {
			$results[] = [
				'category_id' => $category['category_id'],
				'title'       => $category['name'],
				'items'       => [],
			];

			$subcategories = $this->buildCategoryTree($category['items']);

			foreach ($subcategories as $subcategory) {
				$results[] = [
					'category_id' => $subcategory['category_id'],
					'title'       => '&nbsp;&nbsp;' . $subcategory['title'],
					'items'       => [],
				];
			}
		}

		return $results;
	}

}

class_alias('ControllerJournal3EventSearch', '\Opencart\Catalog\Controller\Journal3\Event\Search');
