<?php

	use PhpOffice\PhpSpreadsheet\Spreadsheet;

	class Product_Group {

		// GENERAL
        
		public static function product_group_info($group_id) {
			// info
			$q = DB::query("SELECT group_id, local_id, customer_id, contract_id, label_id, status, title, quantity, created FROM product_groups WHERE group_id='".$group_id."' AND company_id='".Session::$company_id."' LIMIT 1;") or die (DB::error());
			if ($row = DB::fetch_row($q)) {
				$label = Label::label_info($row['label_id']);
                return [
                    'id'=>$row['group_id'],
					'local_id'=>$row['local_id'],
					'label_id'=>$row['label_id'],
					'label_title'=>!$row['label_id'] ? 'Стандартная' : $label['title'],
					'status'=>$row['status'],
					'title'=>$row['title'],
					'customer'=>Company::company_info($row['customer_id']),
					'contract'=>Contract::contract_info($row['contract_id']),
                    'quantity'=>$row['quantity'],
                    'created'=>$row['created'] ? date('d.m.Y', ts_timezone($row['created'], Session::$tz)) : ''
                ];
            } else {
                return [
                    'id'=>0,
					'local_id'=>0,
					'label_id'=>0,
					'label_title'=>'Стандартная',
					'status'=>0,
					'title'=>'',
					'customer'=>['id'=>0, 'title'=>''],
					'contract'=>['id'=>0, 'title'=>'', 'annex_number'=>0],
                    'quantity'=>0,
                    'created'=>''
                ];
            }
        }

		public static function product_groups_list($data = []) {
            // vars
			$info = [];
			$offset = isset($data['offset']) && is_numeric($data['offset']) ? $data['offset'] : 0;
			$group_status = isset($data['group_status']) && is_numeric($data['group_status']) ? $data['group_status'] : 0;
            $limit = 20;
            // where
			$where = [];
			$where[] = "company_id='".Session::$company_id."'";
			$where[] = "status='".$group_status."'";
			$where[] = "hidden='0'";
			$where = implode(' AND ', $where);
            // solo group
			if (!$offset && !$group_status) {
				$info[] = [
					'id'=>0,
					'label_id'=>0,
					'label_title'=>'Стандартная',
					'status'=>0,
					'title'=>'',
					'customer'=>['id'=>0, 'title'=>''],
					'contract'=>['id'=>0, 'title'=>'', 'annex_number'=>0],
					'title'=>'Не распределено',
					'quantity'=>self::product_group_solo_quantity(),
					'created'=>''
				];
				$limit = 19;
			}
            // info
			$q = DB::query("SELECT group_id, local_id, user_id, customer_id, contract_id, label_id, status, title, quantity, created FROM product_groups WHERE ".$where." ORDER BY group_id DESC LIMIT ".$offset.", ".$limit.";") or die (DB::error());
			while ($row = DB::fetch_row($q)) {
				$created = $row['created'] ? date('d.m.Y', ts_timezone($row['created'], Session::$tz)) : '';
				$created_time = $row['created'] ? date('H:i', ts_timezone($row['created'], Session::$tz)) : '';
				$label = Label::label_info($row['label_id']);
                $info[] = [
					'id'=>$row['group_id'],
					'user_id'=>$row['user_id'],
					'label_id'=>$row['label_id'],
					'label_title'=>!$row['label_id'] ? 'Стандартная' : $label['title'],
					'status'=>$row['status'],
					'title'=>$row['title'] ? $row['title'] : 'Партия #'.$row['local_id'].' от '.$created,
					'customer'=>Company::company_info($row['customer_id']),
					'contract'=>Contract::contract_info($row['contract_id']),
					'quantity'=>$row['quantity'],
					'created'=>$created.' в '.$created_time
				];
            }
			// paginator
			$q = DB::query("SELECT count(*) FROM product_groups WHERE ".$where.";") or die (DB::error());
			$count = ($row = DB::fetch_row($q)) ? $row['count(*)'] : 0;
			$paginator = paginator($count, $offset, $limit, 'product.group_paginator');
            // output
			return ['info'=>$info, 'paginator'=>$paginator];
        }
		
		public static function product_group_ids($data) {
			// vars
			$ids = [];
			$group_id = isset($data['group_id']) ? $data['group_id'] : 0;
			$where = isset($data['where']) ? $data['where'] : "company_id='".Session::$company_id."' AND group_id='".$group_id."' AND hidden='0'";
			$user = User::user_info(Session::$user_id);
			$sort = $user['sort_products'] == 'asc' ? "ASC" : "DESC";
			// query
			$q = DB::query("SELECT product_id FROM products WHERE ".$where." ORDER BY product_id ".$sort.";") or die (DB::error());
			while ($row = DB::fetch_row($q)) $ids[] = $row['product_id'];
			// output
			return $ids;
		}

		public static function product_group_solo_quantity() {
			$q = DB::query("SELECT count(*) FROM products WHERE company_id='".Session::$company_id."' AND group_id='0' AND hidden='0';") or die (DB::error());
			return ($row = DB::fetch_row($q)) ? $row['count(*)'] : 0;
		}
		
		public static function product_group_update_quantity($group_id) {
			if (!$group_id) return false;
			$q = DB::query("SELECT count(*) FROM products WHERE company_id='".Session::$company_id."' AND group_id='".$group_id."' AND hidden='0';") or die (DB::error());
			$quantity = ($row = DB::fetch_row($q)) ? $row['count(*)'] : 0;
			DB::query("UPDATE product_groups SET quantity='".$quantity."' WHERE group_id='".$group_id."' AND company_id='".Session::$company_id."' LIMIT 1;") or die (DB::error());
		}
		
		public static function product_group_fetch($data = []) {
			// vars
			$group_id = isset($data['group_id']) && is_numeric($data['group_id']) ? $data['group_id'] : 0;
			// info
			$groups = self::product_groups_list($data);
			// output
			HTML::assign('groups', $groups['info']);
			HTML::assign('group_id', $group_id);
			return ['html'=>HTML::fetch('./partials/section/products/product_groups_table.html'), 'paginator'=>$groups['paginator']];
		}
		
		// ACTIONS
		
		public static function product_group_edit_window($data) {
			// vars
			$group_id = isset($data['group_id']) && is_numeric($data['group_id']) ? $data['group_id'] : 0;
			// actions
			$group = Product_Group::product_group_info($group_id);
			$labels = Label::labels_full_list();
			// output
			HTML::assign('group', $group);
			HTML::assign('labels', $labels);
			return ['html'=>HTML::fetch('./partials/modal/products/product_group_edit.html')];
		}
		
		public static function product_group_edit_update($data) {
			// vars
			$group_id = isset($data['group_id']) && is_numeric($data['group_id']) ? $data['group_id'] : 0;
			$title = isset($data['title']) && trim($data['title']) ? trim($data['title']) : '';
			$customer_id = isset($data['customer_id']) && is_numeric($data['customer_id']) ? $data['customer_id'] : 0;
			$contract_id = isset($data['contract_id']) && is_numeric($data['contract_id']) ? $data['contract_id'] : 0;
			$label_id = isset($data['label_id']) && is_numeric($data['label_id']) ? $data['label_id'] : 0;
			$local_id = self::product_group_max_id(Session::$company_id);
			// update
			if ($group_id) DB::query("UPDATE product_groups SET customer_id='".$customer_id."', contract_id='".$contract_id."', label_id='".$label_id."', title='".$title."' WHERE group_id='".$group_id."' AND company_id='".Session::$company_id."' LIMIT 1;") or die (DB::error());
			else {
				DB::query("INSERT INTO product_groups (local_id, company_id, user_id, customer_id, contract_id, label_id, title, created) VALUES ('".$local_id."', '".Session::$company_id."', '".Session::$user_id."', '".$customer_id."', '".$contract_id."', '".$label_id."', '".$title."', '".Session::$ts."');") or die (DB::error());
				$group_id = DB::insert_id();
			}
			// output
			$info = self::product_group_fetch($data);
			return ['html'=>$info['html'], 'paginator'=>$info['paginator'], 'group_id'=>$group_id];
		}
		
		public static function product_group_archive_toggle($data) {
			// vars
			$group_id = isset($data['group_id']) && is_numeric($data['group_id']) ? $data['group_id'] : 0;
			$group_status = isset($data['group_status']) && $data['group_status'] == 'archive' ? 1 : 0;
			$offset = isset($data['offset']) && is_numeric($data['offset']) ? $data['offset'] : 0;
			$status = isset($data['status']) && in_array($data['status'], [0,1]) ? $data['status'] : 0;
			$status_prev = $status == 0 ? 1 : 0;
			// update
			DB::query("UPDATE product_groups SET status='".$status."' WHERE group_id='".$group_id."' LIMIT 1;") or die (DB::error());
			// output
			error_log($offset);
			$info = self::product_group_fetch(['offset'=>$offset, 'status'=>$status_prev, 'group_status'=>$group_status]);
			return ['html'=>$info['html'], 'paginator'=>$info['paginator']];
		}
		
		public static function product_group_export($data) {
			// vars
			$group_id = isset($data['group_id']) && is_numeric($data['group_id']) ? $data['group_id'] : 0;
			$group = self::product_group_info($group_id);
			// file
			$product_ids = self::product_group_ids($data);
			$spreadsheet = Product_Export::products_export($product_ids, $group['local_id']);
			// write
			$writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, "Xlsx");
			$writer->save('php://output');
			exit();
		}
		
		public static function product_group_files_window($data) {
			// vars
			$group_id = isset($data['group_id']) && is_numeric($data['group_id']) ? $data['group_id'] : 0;
			// info
			$products = Product::products_list(['group_id'=>$group_id, 'offset'=>0, 'mode'=>'group_files']);
			$product_ids = self::product_group_ids(['group_id'=>$group_id]);
			$files = Product_Group_File::group_files_list($group_id);
			// output
			HTML::assign('products', $products['info']);
			HTML::assign('products_paginator', $products['paginator']);
			HTML::assign('files', $files);
			HTML::assign('group_id', $group_id);
			return ['html'=>HTML::fetch('./partials/modal/products/product_group_files.html'), 'product_ids'=>$product_ids];
		}
		
		public static function product_group_files_paginator($data) {
			// vars
			$group_id = isset($data['group_id']) && is_numeric($data['group_id']) ? $data['group_id'] : 0;
			$offset = isset($data['offset']) && is_numeric($data['offset']) ? $data['offset'] : 0;
			$query = isset($data['query']) && trim($data['query']) ? trim($data['query']) : '';
			$group_file_id = isset($data['group_file_id']) && is_numeric($data['group_file_id']) ? $data['group_file_id'] : 0;
			// info
			$products = Product::products_list(['group_id'=>$group_id, 'offset'=>$offset, 'query'=>$query, 'group_file_id'=>$group_file_id, 'mode'=>'group_files']);
			$product_ids = self::product_group_ids(['where'=>$products['where']]);
			// output
			HTML::assign('products', $products['info']);
			return ['html'=>HTML::fetch('./partials/modal/products/product_group_files_products.html'), 'paginator'=>$products['paginator'], 'product_ids'=>$product_ids];
		}
		
		public static function product_group_delete_window($data) {
			// vars
			$group_id = isset($data['group_id']) && is_numeric($data['group_id']) ? $data['group_id'] : 0;
			// output
			HTML::assign('group_id', $group_id);
			return ['html'=>HTML::fetch('./partials/modal/products/product_group_delete.html')];
		}
		
		public static function product_group_delete_update($data) {
			// vars
			$group_id = isset($data['group_id']) && is_numeric($data['group_id']) ? $data['group_id'] : 0;
			$group_cur_id = isset($data['group_cur_id']) && is_numeric($data['group_cur_id']) ? $data['group_cur_id'] : 0;
			$group_offset = isset($data['group_offset']) && is_numeric($data['group_offset']) ? $data['group_offset'] : 0;
			$group_status = isset($data['group_status']) && $data['group_status'] == 'archive' ? 1 : 0;
			$product_offset = isset($data['product_offset']) && is_numeric($data['product_offset']) ? $data['product_offset'] : 0;
			// query
			DB::query("UPDATE product_options SET group_id='0' WHERE group_id='".$group_id."';") or die (DB::error());
			DB::query("UPDATE products SET group_id='0' WHERE group_id='".$group_id."' AND company_id='".Session::$company_id."';") or die (DB::error());
			DB::query("UPDATE product_groups SET hidden='1' WHERE group_id='".$group_id."' AND company_id='".Session::$company_id."' LIMIT 1;") or die (DB::error());
			// output
			$products = Product::product_fetch(['offset'=>$product_offset, 'group_id'=>$group_cur_id, 'group_status'=>$group_status]);
			$groups = Product_Group::product_group_fetch(['offset'=>$group_offset, 'group_id'=>$group_cur_id, 'group_status'=>$group_status]);
			return ['products'=>$products, 'groups'=>$groups];
		}
		
		public static function product_group_change($data) {
			// info
			$products = Product::products_list($data);
			// output
			HTML::assign('products', $products['info']);
			return ['html'=>HTML::fetch('./partials/section/products/products_table.html'), 'paginator'=>$products['paginator']];
		}
		
		// SERVICE
		
		private static function product_group_max_id($company_id) {
			$q = DB::query("SELECT local_id FROM product_groups WHERE company_id='".$company_id."' ORDER BY local_id DESC LIMIT 1;") or die (DB::error());
			return ($row = DB::fetch_row($q)) ? $row['local_id'] + 1 : 1;
		}

	}
?>