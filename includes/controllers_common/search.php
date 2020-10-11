<?php
	
	function controller_search($data) {
		// validate
		if (!Session::$access) access_error(Session::$mode);
		// vars
		$data['mode'] = 'search';
		$data['query'] = isset($data['q']) ? $data['q'] : '';
		// info
		$products = Product::products_list($data);
		// output
		HTML::assign('products', $products['info']);
		HTML::assign('products_count', $products['count']);
		HTML::assign('products_label', $products['label']);
		HTML::assign('search', $data['query']);
		return HTML::main_content('./partials/section/search/search.html', Session::$mode);
	}

?>