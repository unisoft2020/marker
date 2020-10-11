<?php
	class Search {

		// GENERAL

		public static function search_do($data) {
			// vars
			$data['mode'] = 'search';
			// info
			$products = Product::products_list($data);
			// output
			HTML::assign('products', $products['info']);
			HTML::assign('products_count', $products['count']);
			HTML::assign('products_label', $products['label']);
			HTML::assign('search', $data['query']);
			return ['html'=>HTML::fetch('./partials/section/search/search.html')];
		}

	}
?>