<?php

	function controller_product($sub, $act, $data) {
		
		if ($sub == 'common') {
			if (!Session::$access) Session::access_error();
			if ($act == 'paginator') return Product::product_fetch($data);
			if ($act == 'change_group') return Product::product_change_group($data);
			if ($act == 'status_toggle') return Product::product_status_toggle($data);
			if ($act == 'status_change') return Product::product_status_change($data);
			if ($act == 'sort') return Product::product_sort($data);
			if ($act == 'history') return Scan::scans_window($data);
			if ($act == 'copy') return Product::product_copy($data);
			if ($act == 'edit_window') return Product::product_edit_window($data);
			if ($act == 'edit_update') return Product::product_edit_update($data);
			if ($act == 'options_from_label') return Product_Option::options_from_label($data);
			if ($act == 'delete_window') return Product::product_delete_window($data);
			if ($act == 'delete_update') return Product::product_delete_update($data);
		}
		
		if ($sub == 'group') {
			if (!Session::$access) Session::access_error();
			if ($act == 'paginator') return Product_Group::product_group_fetch($data);
			if ($act == 'change') return Product_Group::product_group_change($data);
			if ($act == 'edit_window') return Product_Group::product_group_edit_window($data);
			if ($act == 'edit_update') return Product_Group::product_group_edit_update($data);
			if ($act == 'export') return Product_Group::product_group_export($data);
			if ($act == 'archive_toggle') return Product_Group::product_group_archive_toggle($data);
			if ($act == 'files_window') return Product_Group::product_group_files_window($data);
			if ($act == 'files_paginator') return Product_Group::product_group_files_paginator($data);
			if ($act == 'delete_window') return Product_Group::product_group_delete_window($data);
			if ($act == 'delete_update') return Product_Group::product_group_delete_update($data);
		}
		
		if ($sub == 'search') {
			if (!Session::$access) Session::access_error();
			if ($act == 'customer') return Company::company_search_customer($data);
			if ($act == 'consignee') return Company::company_search_consignee($data);
			if ($act == 'contract') return Contract::contract_search($data);
		}
		
		if ($sub == 'option') {
			if (!Session::$access) Session::access_error();
			if ($act == 'add') return Product_Option::option_add($data);
		}
		
		if ($sub == 'file') {
			if (!Session::$access) Session::access_error();
			if ($act == 'upload') return Product_File::file_upload($data, $_FILES['tmp_file']);
			if ($act == 'delete') return Product_File::file_delete($data);
			if ($act == 'group_upload') return Product_Group_File::group_file_upload($data, $_FILES['tmp_file']);
			if ($act == 'group_toggle') return Product_Group_File::group_file_toggle($data);
			if ($act == 'group_delete') return Product_Group_File::group_file_delete($data);
		}
	
	}

?>