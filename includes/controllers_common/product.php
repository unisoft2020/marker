<?php

	require_once 'vendor/dompdf/autoload.inc.php';
	use Dompdf\Dompdf;
	
	function controller_products($data) {
		// validate
		if (Session::$access != 3) access_error(Session::$mode);
		// vars
		$data['mode'] = 'default';
		$data['group_id'] = isset($data['group_id']) && is_numeric($data['group_id']) ? $data['group_id'] : 0;
		$data['offset'] = isset($data['offset']) && is_numeric($data['offset']) ? $data['offset'] : 0;
		$data['group_status'] = isset($data['sub']) && $data['sub'] == 'archive' ? 1 : 0;
		$group_status = isset($data['sub']) ? $data['sub'] : 'active';
		// info
		$groups = Product_Group::product_groups_list(['group_status'=>$data['group_status']]);
        $products = Product::products_list(['group_id'=>$data['group_id'], 'group_status'=>$group_status]);
		// output
		HTML::assign('groups', $groups['info']);
		HTML::assign('groups_paginator', $groups['paginator']);
        HTML::assign('products', $products['info']);
        HTML::assign('products_paginator', $products['paginator']);
		HTML::assign('group_id', $data['group_id']);
		HTML::assign('offset', $data['offset']);
		HTML::assign('group_status', $group_status);
		return HTML::main_content('./partials/section/products/products.html', Session::$mode);
	}

	function controller_product($data, $id) {
		// validate
		if (!in_array(Session::$access, [3,4])) access_error(Session::$mode);
		// info
		$product = Product::product_info_full($id, 'view');
		$group = Product_Group::product_group_info($product['group_id']);
		$back_url = Product::product_back_url($group['id'], $group['status']);
		$label = Label::label_info_handling($product);
		$label['preview_top'] = -$label['preview_height'] * 0.3 * 0.5;
		$label['preview_left'] = -$label['preview_width'] * 0.3 * 0.5;
		// output
		HTML::assign('product', $product);
		HTML::assign('group', $group);
		HTML::assign('label', $label);
		HTML::assign('label_preview', HTML::fetch($label['template']));
		HTML::assign('back_url', $back_url);
		return HTML::main_content('./partials/section/products/product.html', Session::$mode);
	}

	function controller_product_simple($id) {
		// info
		$product = Product::product_info_full($id, 'view');
		if ($product['id']) Scan::scan_create(['product_id'=>$product['id'], 'source'=>1]);
		// output
		HTML::assign('product', $product);
		HTML::assign('section_content', './partials/section/products/product_simple.html');
	}

	function controller_product_print($product_id, $data) {
		// validate
		if (Session::$access != 3) access_error(Session::$mode);
		// actions
		$product = Product::product_info_full($product_id);
		$label = Label::label_info_handling($product); 
		// output
		HTML::assign('label', $label);
		HTML::main_content($label['template'], Session::$mode);
		HTML::display('./partials/print/printable.html');
		exit();
	}

	function controller_product_print_group($group_id, $data) {
		// validate
		if (Session::$access != 3) access_error(Session::$mode);
		// vars
		$labels = [];
		// actions
		$product_ids = Product_Group::product_group_ids(['group_id'=>$group_id]);
		foreach($product_ids as $product_id) {
			$product = Product::product_info_full($product_id);
			$label = Label::label_info_handling($product);
			$labels[] = $label;
		}
		// output
		HTML::assign('labels', $labels);
		HTML::main_content('./partials/print/group.html', Session::$mode);
		HTML::display('./partials/print/printable.html');
		exit();
	}

	function controller_product_download($product_id, $data) {
		// validate
		if (Session::$access != 3) access_error(Session::$mode);
		// actions
		$product = Product::product_info_full($product_id);
		$product['title'] = mb_strlen($product['title'], 'UTF-8') > 90 ? mb_substr($product['title'], 0, 88, 'UTF-8').'...' : $product['title']; 
		$label = Label::label_info($product['label_id']);
		// template
		//$template = './partials/print/label_'.$label['type_id'].'.html';
		//if ($product['manufacturer']['print_id'] == 1) $template = './partials/print/code_tube.html';
		// custom
		//if ($product['manufacturer']['print_id'] == 1) HTML::assign('custom', Product_Option::options_tube($product_id));
		// output
		HTML::assign('product', $product);
		$html = HTML::fetch('./partials/print/label_'.$label['type_id'].'.html');
		
		$html = '<html><body style="padding: 20px; font-family: \'roboto\'">'.$html.'</body></html>';
		//$html .= '<link type="text/css" media="dompdf" href="css/common.css" rel="stylesheet" />';
		$html .= '<link type="text/css" media="dompdf" href="css/section/print.css" rel="stylesheet" />';
		
		$dompdf = new Dompdf();
		$dompdf->set_option('isRemoteEnabled', TRUE);
		$dompdf->set_option('isHtml5ParserEnabled', true);
		$dompdf->load_html($html);
		$dompdf->render();
		$dompdf->stream("marker.pdf"); 
		
		exit();
	}

?>