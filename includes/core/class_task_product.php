<?php
	class Task_Product {

		// GENERAL

		public static function task_products_list($data = []) {
			// vars
			$info = [];
			$id = isset($data['id']) && is_numeric($data['id']) ? $data['id'] : 0;
			// where
			$where = [];
			$where[] = "task_id='".$id."'";
			$where = implode(" AND ", $where);
			// info
			$q = DB::query("SELECT product_id FROM task_products WHERE ".$where.";") or die (DB::error());
			while ($row = DB::fetch_row($q)) {
				$product = Product::product_info($row['product_id']);
				$info[] = ['id'=>$row['product_id'], 'title'=>$product['title']];
			}
			// output
			return $info;
		}
		
		// ACTIONS
		
		public static function task_products_search($data) {
			// info
			$products = self::task_products_add_list($data);
			// output
			HTML::assign('products', $products);
			return ['html'=>HTML::fetch('./partials/modal/tasks/task_add_product_search.html')];
		}
		
		public static function task_products_add_list($data = []) {
			// vars
			$info = [];
			$query = isset($data['query']) && trim($data['query']) ? trim($data['query']) : '';
			$selected = isset($data['products']) ? $data['products'] : [];
			// where
			$where = [];
			$where[] = "company_id='".Session::$company_id."'";
			if ($query) $where[] = "title LIKE '%".$query."%'";
			$where[] = "hidden='0'";
			$where = implode(" AND ", $where);
			// info
			$q = DB::query("SELECT product_id, code, title FROM products WHERE ".$where." ORDER BY product_id DESC LIMIT 5;") or die (DB::error());
			while ($row = DB::fetch_row($q)) {
				$info[] = [
					'id'=>$row['product_id'],
					'code'=>$row['code'],
					'title'=>$row['title'],
					'selected'=>in_array($row['product_id'], $selected) ? true : false
				];
			}
			// output
			return $info;
		}

		public static function task_products_add_window($data) {
			// info
			$products = self::task_products_add_list($data);
        	// output
			HTML::assign('products', $products);
			return ['html'=>HTML::fetch('./partials/modal/tasks/task_add_product.html')];
		}
	}
?>