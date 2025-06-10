<?php

class ControllerJournal3EventSitemap extends Controller {

	public function sitemap(&$route, &$args) {
		if ($this->model_journal3_blog->isEnabled()) {
			$args['blog_sitemap'] = array(
				'name'       => $this->journal3->get('blogPageTitle'),
				'href'       => $this->journal3_url->link('journal3/blog'),
				'categories' => array(),
			);

			foreach ($this->model_journal3_blog->getCategories() as $category) {
				$args['blog_sitemap']['categories'][] = array(
					'name' => $category['name'],
					'href' => $this->journal3_url->link('journal3/blog', 'journal_blog_category_id=' . $category['category_id']),
				);
			}
		}
	}

	public function google_sitemap(&$route, &$args, &$output) {
		if ($this->model_journal3_blog->isEnabled() && $this->response->getOutput()) {
			$output = '';

			$posts = $this->model_journal3_blog->getPosts();

			foreach ($posts as $post) {
				$output .= '<url>';
				$output .= '<loc>' . $this->journal3_url->link('journal3/blog/post', 'journal_blog_post_id=' . $post['post_id']) . '</loc>';
				$output .= '<changefreq>weekly</changefreq>';
				$output .= '<priority>1.0</priority>';
				$output .= '</url>';
			}

			$results = $this->model_journal3_blog->getCategories();

			foreach ($results as $result) {
				$output .= '<url>';
				$output .= '<loc>' . $this->journal3_url->link('journal3/blog', 'journal_blog_category_id=' . $result['category_id']) . '</loc>';
				$output .= '<changefreq>weekly</changefreq>';
				$output .= '<priority>0.7</priority>';
				$output .= '</url>';

				$posts = $this->model_journal3_blog->getPosts(array('category_id' => $result['category_id']));

				foreach ($posts as $post) {
					$output .= '<url>';
					$output .= '<loc>' . $this->journal3_url->link('journal3/blog/post', 'journal_blog_category_id=' . $result['category_id'] . '&journal_blog_post_id=' . $post['post_id']) . '</loc>';
					$output .= '<changefreq>weekly</changefreq>';
					$output .= '<priority>1.0</priority>';
					$output .= '</url>';
				}
			}

			$output = str_replace('</urlset>', $output . '</urlset>', $this->response->getOutput());

			$this->response->setOutput($output);
		}
	}

}

class_alias('ControllerJournal3EventSitemap', '\Opencart\Catalog\Controller\Journal3\Event\Sitemap');
