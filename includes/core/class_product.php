<?php
	class Product {

		// GENERAL
		
		public static function product_info($product_id) {
			// info
			$q = DB::query("SELECT product_id, title, hidden FROM products WHERE product_id='".$product_id."' LIMIT 1;") or die (DB::error());
			if ($row = DB::fetch_row($q)) {
				return [
					'id'=>$row['product_id'],
					'title'=>Session::$mode != 2 ? flt_output($row['title']) : $row['title'],
					'hidden'=>$row['hidden']
				];
			} else {
				return [
					'id'=>0,
					'title'=>'',
					'hidden'=>0
				];
			}
		}

		public static function product_info_full($product_id, $mode = 'default') {
			// info
			$q = DB::query("SELECT product_id, company_id, user_id, customer_id, consignee_id, contract_id, group_id, label_id, status, product_status, code, title, destination, quantity, hidden, produced, marked, shipped, created FROM products WHERE product_id='".$product_id."' LIMIT 1;") or die (DB::error());
			if ($row = DB::fetch_row($q)) {
				// info
				$qr = self::product_qr($row['product_id']);
				$manufacturer = Company::company_info($row['company_id']);
				$label = Label::label_info($row['label_id']);
				$code = Session::$mode != 2 ? flt_output($row['code']) : $row['code'];
				if (!$code && Session::$mode == 2) $code = 'не указан';
				// output
				return [
					'id'=>$row['product_id'],
					'user_id'=>$row['user_id'],
                    'group_id'=>$row['group_id'],
					'label_id'=>$row['label_id'],
					'label_title'=>!$row['label_id'] ? 'Стандартная' : $label['title'],
					'code'=>$code,
					'title'=>Session::$mode != 2 && $mode != 'copy' ? flt_output($row['title']) : $row['title'],
					'destination'=>Session::$mode != 2 && $mode != 'copy' ? flt_output($row['destination']) : $row['destination'],
					'quantity'=>$row['quantity'],
					'qr'=>SITE_SCHEME.'://'.SITE_DOMAIN.$qr,
					'url'=>SITE_SCHEME.'://'.SITE_DOMAIN.'/products/'.$row['product_id'],
					'manufacturer'=>$manufacturer,
					'customer'=>Company::company_info($row['customer_id']),
					'consignee'=>Company::company_info($row['consignee_id']),
					'contract'=>Contract::contract_info($row['contract_id']),
					'options'=>Product_Option::options_list_full($row['product_id'], $row['code']),
					'files'=>Product_File::files_list($row['product_id']),
					'draft'=>0,
					'status'=>$row['status'],
					'product_status'=>$row['status'],
					'produced'=>date_str($row['produced'], $mode),
					'produced_ts'=>$row['produced'],
					'marked'=>$row['marked'] ? date('d.m.Y H:i', ts_timezone($row['marked'], Session::$tz)) : '',
					'marked_ts'=>$row['marked'],
					'shipped'=>date_str($row['shipped'], $mode),
					'shipped_ts'=>$row['shipped'],
					'created'=>$row['created'] ? date('d.m.Y H:i', ts_timezone($row['created'], Session::$tz)) : '',
					'created_ts'=>$row['created'],
					'company_title'=>$manufacturer['title'],
					'company_address'=>$manufacturer['address']
				];
			} else {
				return [
					'id'=>0,
                    'group_id'=>0,
					'label_id'=>0,
					'label_title'=>'Стандартная',
					'code'=>'',
					'title'=>'',
					'destination'=>'',
					'quantity'=>1,
					'qr'=>'',
					'url'=>'',
					'manufacturer'=>['id'=>0, 'title'=>''],
					'customer'=>['id'=>0, 'title'=>''],
					'consignee'=>['id'=>0, 'title'=>''],
					'contract'=>['id'=>0, 'title'=>''],
					'options'=>[],
					'files'=>[],
					'draft'=>0,
					'status'=>0,
					'product_status'=>0,
					'produced'=>'',
					'produced_ts'=>'',
					'marked'=>'',
					'marked_ts'=>'',
					'shipped'=>'',
					'shipped_ts'=>'',
					'created'=>'',
					'created_ts'=>'',
					'company_title'=>'',
					'company_address'=>''
				];
			}
		}
		
		public static function products_simple_list($product_ids) {
			// vars
			$info = [];
			// info
			$q = DB::query("SELECT product_id, title FROM products WHERE product_id IN (".implode(",", $product_ids).");") or die (DB::error());
			while ($row = DB::fetch_row($q)) {
				$info[] = [
					'id'=>$row['product_id'],
					'title'=>$row['title']
				];
			}
			// output
			return $info;
		}

		public static function products_list($data = []) {
			// vars
			$info = [];
			$mode = isset($data['mode']) ? $data['mode'] : 'default';
			$show = isset($data['show']) ? $data['show'] : 'all';
			$query = isset($data['query']) && trim($data['query']) ? trim($data['query']) : '';
			$offset = isset($data['offset']) && is_numeric($data['offset']) ? $data['offset'] : 0;
            $group_id = isset($data['group_id']) && is_numeric($data['group_id']) ? $data['group_id'] : -1;
			$group_file_id = isset($data['group_file_id']) && is_numeric($data['group_file_id']) ? $data['group_file_id'] : 0;
			$group_status = isset($data['group_status']) && in_array($data['group_status'], ['active','archive']) ? $data['group_status'] : 'active';
			// limit
			if ($mode == 'search') $limit = 5;
			else if ($mode == 'group_files') $limit = 10;
			else $limit = 10;
			// info
			$company = Company::company_info(Session::$company_id);
			$user = User::user_info(Session::$user_id);
			$print_id = $company['print_id'];
			// where
			$where = [];
			if (Session::$access != 1) $where[] = "company_id='".Session::$company_id."'";
			if ($show == 'active') $where[] = "status='0'";
			if ($show == 'complete') $where[] = "status='1'";
            if ($group_id != -1) $where[] = "group_id='".$group_id."'";
			if ($query) {
				if ($group_id >= 0 && $mode == 'group_files') {
					$product_ids = [];
					$q = DB::query("SELECT product_id, option_title, option_value FROM product_options WHERE group_id='".$group_id."' AND option_value LIKE '%".$query."%';") or die (DB::error());
					while ($row = DB::fetch_row($q)) $product_ids[] = "'".$row['product_id']."'";
					if ($product_ids) $where[] = "(title LIKE '%".$query."%' OR product_id IN (".implode(",", $product_ids)."))";
					else $where[] = "title LIKE '%".$query."%'";
				} else {
					$where[] = "title LIKE '%".$query."%'";
				}
			}
			$where[] = "hidden='0'";
			$where = implode(' AND ', $where);
			// sort
			$sort = $user['sort_products'] == 'asc' ? "ASC" : "DESC";
			// info
			$q = DB::query("SELECT product_id, user_id, customer_id, consignee_id, contract_id, group_id, label_id, status, product_status, code, title, destination, quantity, produced, marked, shipped, created FROM products WHERE ".$where." ORDER BY product_id ".$sort." LIMIT ".$offset.", ".$limit.";") or die (DB::error());
			while ($row = DB::fetch_row($q)) {
				// vars
				$custom = [];
				if ($print_id == 1) $custom = Product_Option::options_tube($row['product_id']);
				$label = Label::label_info($row['label_id']);
				// info
				$info[] = [
					'id'=>$row['product_id'],
					'user_id'=>$row['user_id'],
                    'group_id'=>$row['group_id'],
					'group_status'=>$group_status,
					'print_id'=>$print_id,
					'label_title'=>!$row['label_id'] ? 'Стандартная' : $label['title'],
					'status'=>$row['status'],
					'product_status'=>$row['product_status'],
					'code'=>Session::$mode != 2 ? flt_output($row['code']) : $row['code'],
					'title'=>Session::$mode != 2 ? flt_output($row['title']) : $row['title'],
					'destination'=>Session::$mode != 2 ? flt_output($row['destination']) : $row['destination'],
					'customer'=>Company::company_info($row['customer_id']),
					'consignee'=>Company::company_info($row['consignee_id']),
					'contract'=>Contract::contract_info($row['contract_id']),
					'options'=>Product_Option::options_list($row['product_id']),
					'custom'=>$custom,
					'quantity'=>$row['quantity'],
					'produced'=>$row['produced'] ? date('d.m.Y', ts_timezone($row['produced'], Session::$tz)) : '',
					'marked'=>$row['marked'] ? date('d.m.Y', ts_timezone($row['marked'], Session::$tz)) : '',
					'shipped'=>$row['shipped'] ? date('d.m.Y', ts_timezone($row['shipped'], Session::$tz)) : '',
					'created'=>$row['created'] ? date('d.m.Y H:i', ts_timezone($row['created'], Session::$tz)) : '',
					'group_file_attached'=>($mode == 'group_files' && $group_file_id) ? Product_Group_File::group_file_attached($row['product_id'], $group_file_id) : 0
				];
			}
			// paginator
			$q = DB::query("SELECT count(*) FROM products WHERE ".$where.";") or die (DB::error());
			$count = ($row = DB::fetch_row($q)) ? $row['count(*)'] : 0;
			$callback = $mode == 'group_files' ? 'product.group_files_product_paginator' : 'product.product_paginator';
			$paginator = paginator($count, $offset, $limit, $callback, ['group_id'=>$group_id, 'query'=>$query, 'mode'=>'page', 'group_status'=>$group_status]);
			// label
			$label = name_case($count, ['изделие', 'изделия', 'изделий']);
			// output
			return ['info'=>$info, 'paginator'=>$paginator, 'count'=>$count, 'label'=>$label, 'where'=>$where];
		}
		
		public static function product_draft($group_id) {
            // vars
            $group = Product_Group::product_group_info($group_id);
			// clear
			DB::query("DELETE FROM products WHERE created<='".(Session::$ts - 3600)."' AND hidden='-1';") or die (DB::error());
			// query
			DB::query("INSERT INTO products (company_id, user_id, group_id, quantity, hidden, created) VALUES ('".Session::$company_id."', '".Session::$user_id."', '".$group_id."', '1', '-1', '".Session::$ts."');") or die (DB::error());
			$product_id = DB::insert_id();
			// output
			return [
				'id'=>$product_id,
				'label_id'=>0,
				'label_title'=>'Стандартная',
				'title'=>'',
				'destination'=>'',
				'quantity'=>1,
				'code'=>'',
				'qr'=>'',
				'manufacturer'=>['id'=>0, 'title'=>''],
				'customer'=>['id'=>0, 'title'=>''],
				'consignee'=>['id'=>0, 'title'=>''],
				'contract'=>['id'=>0, 'title'=>''],
				'options'=>[],
				'files'=>[],
				'produced'=>'',
				'marked'=>'',
				'shipped'=>'',
				'created'=>'',
				'draft'=>1,
				'status'=>0,
			];
		}
		
		public static function product_fetch($data = []) {
			// info
			$products = self::products_list($data);
			// output
			HTML::assign('products', $products['info']);
			return ['html'=>HTML::fetch('./partials/section/products/products_table.html'), 'paginator'=>$products['paginator']];
		}
		
		// ACTIONS
		
		public static function product_copy($data) {
			// vars
			$product_id = isset($data['product_id']) && is_numeric($data['product_id']) ? $data['product_id'] : 0;
			$mode = isset($data['mode']) ? $data['mode'] : 'list';
			// actions
			$product = Product::product_copy_execute($product_id);
			$product['options'] = Product_Option::options_list($product['id']);
			$product['options'][] = ['id'=>0, 'title'=>'', 'value'=>'', 'units_title'=>'', 'created'=>''];
			$count_options = count($product['options']);
			$units = Unit::units_list();
			// output
			HTML::assign('product', $product);
			HTML::assign('count_options', $count_options);
			HTML::assign('units', $units);
			HTML::assign('modal_title', 'Дублирование изделия');
			HTML::assign('mode', $mode);
			return ['html'=>HTML::fetch('./partials/modal/products/product_edit.html')];
		}
		
		private static function product_copy_execute($copy_product_id) {
			// info
			$copy = self::product_info_full($copy_product_id, 'copy');
			$copy_options = Product_Option::options_list($copy_product_id, 'copy');
			$copy_files = Product_File::files_list($copy_product_id, 'copy');
			// query (product)
			DB::query("INSERT INTO products (company_id, user_id, customer_id, consignee_id, contract_id, group_id, label_id, code, title, destination, quantity, hidden, produced, shipped, created) VALUES ('".Session::$company_id."', '".Session::$user_id."', '".$copy['customer']['id']."', '".$copy['consignee']['id']."', '".$copy['contract']['id']."', '".$copy['group_id']."', '".$copy['label_id']."', '".$copy['code']."', '".$copy['title']."', '".$copy['destination']."', '".$copy['quantity']."', '-1', '".$copy['produced_ts']."', '".$copy['shipped_ts']."', '".Session::$ts."');") or die (DB::error());
			$product_id = DB::insert_id();
			// query (options)
			foreach($copy_options as $a) {
				DB::query("INSERT INTO product_options (product_id, group_id, option_title, option_value, option_units_title, created) VALUES ('".$product_id."', '".$a['group_id']."', '".$a['title']."', '".$a['value']."', '".$a['units_title']."', '".Session::$ts."');") or die (DB::error());
			}
			// query (files)
			foreach($copy_files as $a) {
				DB::query("INSERT INTO product_files (product_id, type, path, title, size, created) VALUES ('".$product_id."', '".$a['type']."', '".$a['path']."', '".$a['title']."', '".$a['size_raw']."', '".Session::$ts."');") or die (DB::error());
			}
			// output
			return self::product_info_full($product_id);
		}
		
		public static function product_edit_window($data) {
			// vars
			$product_id = isset($data['product_id']) && is_numeric($data['product_id']) ? $data['product_id'] : 0;
			$group_id = isset($data['group_id']) && is_numeric($data['group_id']) ? $data['group_id'] : 0;
			$mode = isset($data['mode']) ? $data['mode'] : 'list';
			// info
			$product = $product_id ? Product::product_info_full($product_id) : Product::product_draft($group_id);
			$product['options'] = Product_Option::options_list($product_id);
			$product['options'][] = ['id'=>0, 'title'=>'', 'value'=>'', 'units_title'=>'', 'created'=>''];
			$count_options = count($product['options']);
			$units = Unit::units_list();
			$labels = Label::labels_full_list();
			$modal_title = $product['draft'] ? 'Добавить новое изделие' : 'Редактировать изделие';
			// output
			HTML::assign('product', $product);
			HTML::assign('count_options', $count_options);
			HTML::assign('units', $units);
			HTML::assign('labels', $labels);
			HTML::assign('modal_title', $modal_title);
			HTML::assign('mode', $mode);
			return ['html'=>HTML::fetch('./partials/modal/products/product_edit.html')];
		}
		
		public static function product_edit_update($data) {
			// vars
			$product_id = isset($data['product_id']) && is_numeric($data['product_id']) ? $data['product_id'] : 0;
			$title = isset($data['title']) && trim($data['title']) ? trim($data['title']) : '';
			$code = isset($data['code']) && trim($data['code']) ? trim($data['code']) : '';
			$customer_id = isset($data['customer_id']) && is_numeric($data['customer_id']) ? $data['customer_id'] : 0;
			$consignee_id = isset($data['consignee_id']) && is_numeric($data['consignee_id']) ? $data['consignee_id'] : 0;
			$contract_id = isset($data['contract_id']) && is_numeric($data['contract_id']) ? $data['contract_id'] : 0;
			$label_id = isset($data['label_id']) && is_numeric($data['label_id']) ? $data['label_id'] : 0;
			$destination = isset($data['destination']) && trim($data['destination']) ? trim($data['destination']) : '';
			$quantity = isset($data['quantity']) && is_numeric($data['quantity']) ? $data['quantity'] : 1;
			$produced = isset($data['produced']) && $data['produced'] ? strtotime($data['produced']) : 0;
			$shipped = isset($data['shipped']) && $data['shipped'] ? strtotime($data['shipped']) : 0;
			$group_id = isset($data['group_id']) && is_numeric($data['group_id']) ? $data['group_id'] : 0;
			$group_offset = isset($data['group_offset']) && is_numeric($data['group_offset']) ? $data['group_offset'] : 0;
			$options = isset($data['options']) && is_array($data['options']) ? $data['options'] : [];
			$old_options = [];
			// stats
			$product = Product::product_info($product_id);
			if ($product['hidden'] == -1) Stats::stats_update(0, 1, 0);
			// products (update)
			DB::query("UPDATE products SET customer_id='".$customer_id."', consignee_id='".$consignee_id."', contract_id='".$contract_id."', group_id='".$group_id."', label_id='".$label_id."', code='".$code."', title='".$title."', destination='".$destination."', quantity='".$quantity."', hidden='0', produced='".$produced."', shipped='".$shipped."', created='".Session::$ts."' WHERE product_id='".$product_id."' LIMIT 1;") or die (DB::error());
			// options (update)
			foreach($options as $option) {
				// vars
				$id = isset($option[0]) ? $option[0] : 0;
				$title = isset($option[1]) ? trim($option[1]) : '';
				$value = isset($option[2]) ? trim($option[2]) : '';
				$units_title = isset($option[3]) ? trim($option[3]) : '';
				// update
				if ($id) {
					DB::query("UPDATE product_options SET option_title='".$title."', option_value='".$value."', option_units_title='".$units_title."' WHERE option_id='".$id."' LIMIT 1;") or die (DB::error());
					$old_options[] = $id;
				} else {
					DB::query("INSERT INTO product_options (group_id, product_id, option_title, option_value, option_units_title, created) VALUES ('".$group_id."', '".$product_id."', '".$title."', '".$value."', '".$units_title."', '".Session::$ts."');") or die (DB::error());
					$old_options[] = DB::insert_id();
				}
			}
			// options (clear old)
			$q = DB::query("SELECT option_id FROM product_options WHERE product_id='".$product_id."';") or die (DB::error());
			while ($row = DB::fetch_row($q)) {
				if (!in_array($row['option_id'], $old_options)) DB::query("DELETE FROM product_options WHERE option_id='".$row['option_id']."' LIMIT 1;") or die (DB::error());
			}
			// output
			Product_Group::product_group_update_quantity($group_id);
			$products = self::product_fetch($data);
			$groups = Product_Group::product_group_fetch(['offset'=>$group_offset, 'group_id'=>$group_id]);
			return ['products'=>$products, 'groups'=>$groups, 'group_id'=>$group_id];
		}
		
		public static function product_change_group($data) {
			// vars
            $group_id = isset($data['group_id']) && is_numeric($data['group_id']) ? $data['group_id'] : 0;
			$product_id = isset($data['product_id']) && is_numeric($data['product_id']) ? $data['product_id'] : 0;
			$cur_group_id = isset($data['cur_group_id']) && is_numeric($data['cur_group_id']) ? $data['cur_group_id'] : 0;
			$cur_group_offset = isset($data['cur_group_offset']) && is_numeric($data['cur_group_offset']) ? $data['cur_group_offset'] : 0;
			$cur_product_offset = isset($data['cur_product_offset']) && is_numeric($data['cur_product_offset']) ? $data['cur_product_offset'] : 0;
			// info
			$product = self::product_info_full($product_id);
			// query
			DB::query("UPDATE products SET group_id='".$group_id."' WHERE product_id='".$product_id."' AND company_id='".Session::$company_id."' LIMIT 1;") or die (DB::error());
			DB::query("UPDATE product_options SET group_id='".$group_id."' WHERE product_id='".$product_id."';") or die (DB::error());
			// quantity
			Product_Group::product_group_update_quantity($group_id);
			Product_Group::product_group_update_quantity($product['group_id']);
			// output
			$products = self::product_fetch(['offset'=>$cur_product_offset, 'group_id'=>$cur_group_id]);
			$groups = Product_Group::product_group_fetch(['offset'=>$cur_group_offset, 'group_id'=>$cur_group_id]);
			return ['products'=>$products, 'groups'=>$groups];
		}
		
		public static function product_status_toggle($data) {
			// vars
			$product_id = isset($data['product_id']) && is_numeric($data['product_id']) ? $data['product_id'] : 0;
            $status = isset($data['status']) && is_numeric($data['status']) ? $data['status'] : 0;
			$marked = $status ? Session::$ts : 0;
			// query
			DB::query("UPDATE products SET status='".$status."', marked='".$marked."' WHERE product_id='".$product_id."' LIMIT 1;") or die (DB::error());
			// output
			return ['response'=>'ok'];
		}

		public static function product_status_change($data) {
			// vars
			$product_id = isset($data['product_id']) && is_numeric($data['product_id']) ? $data['product_id'] : 0;
			$product_status = isset($data['product_status']) && is_numeric($data['product_status']) ? $data['product_status'] : 0;
			$group_id = isset($data['group_id']) && is_numeric($data['group_id']) ? $data['group_id'] : 0;
			$group_offset = isset($data['group_offset']) && is_numeric($data['group_offset']) ? $data['group_offset'] : 0;
			// query
			DB::query("UPDATE products SET product_status=1 WHERE product_id='".$product_id."' LIMIT 1;") or die (DB::error());
			// output
			$products = self::product_fetch($data);
			$groups = Product_Group::product_group_fetch(['offset'=>$group_offset, 'group_id'=>$group_id]);
			return ['products'=>$products, 'groups'=>$groups];
		}
		
		public static function product_sort($data) {
			// vars
			$mode = isset($data['mode']) && in_array($data['mode'], ['asc', 'dsc']) ? $data['mode'] : 'dsc';
			$new_mode = $mode == 'dsc' ? 1 : 0;
			// query
			DB::query("UPDATE users SET sort_products='".$new_mode."' WHERE user_id='".Session::$user_id."' LIMIT 1;") or die (DB::error());
			// output
			$products = self::product_fetch($data);
			return ['products'=>$products];
		}
		
		public static function product_delete_window($data) {
			// vars
			$product_id = isset($data['product_id']) && is_numeric($data['product_id']) ? $data['product_id'] : 0;
			// output
			HTML::assign('product_id', $product_id);
			return ['html'=>HTML::fetch('./partials/modal/products/product_delete.html')];
		}
		
		public static function product_delete_update($data) {
			// vars
			$product_id = isset($data['product_id']) && is_numeric($data['product_id']) ? $data['product_id'] : 0;
			$group_id = isset($data['group_id']) && is_numeric($data['group_id']) ? $data['group_id'] : 0;
			$group_offset = isset($data['group_offset']) && is_numeric($data['group_offset']) ? $data['group_offset'] : 0;
			// query
			DB::query("UPDATE products SET hidden='1' WHERE product_id='".$product_id."' LIMIT 1;") or die (DB::error());
			// output
			Product_Group::product_group_update_quantity($group_id);
			$products = self::product_fetch($data);
			$groups = Product_Group::product_group_fetch(['offset'=>$group_offset, 'group_id'=>$group_id]);
			return ['products'=>$products, 'groups'=>$groups];
		}

		// SERVICE

		private static function product_qr($product_id) {
			// vars
			$qr = '/storage/product_'.$product_id.'.png';
			// create
			if (Session::$mode != 2 && !file_exists('.'.$qr)) {
				require_once('./vendor/qr/qrlib.php');
				$url = SITE_SCHEME.'://'.SITE_DOMAIN.'/products/'.$product_id;
				QRcode::png($url, '.'.$qr, 'H', 16, 0);
			}
			// output
			return $qr;
		}
		
		public static function product_back_url($group_id, $group_status) {
			// vars
			$url = '/products';
			// queries
			$q = [];
			if ($group_status == 1) $q[] = 'sub=archive';
			if ($group_id) $q[] = 'group_id='.$group_id;
			if ($q) $url .= '?'.implode('&', $q);
			// output
			return $url;
		}

	}
?>