<?php

	//use PhpOffice\PhpSpreadsheet\Spreadsheet;

	class Pick {

		// GENERAL
		
		public static function picks_list($data) {
			// vars
			$info = [];
			$status = isset($data['status']) && in_array($data['status'], [0,1]) ? $data['status'] : -1;
			$offset = isset($data['offset']) && is_numeric($data['offset']) ? $data['offset'] : 0;
			$limit = 20;
			// where
			$where = [];
			$where[] = "company_id='".Session::$company_id."'";
			$where = implode(' AND ', $where);
			// info
			$q = DB::query("SELECT pick_id, user_id, status, count_products, created FROM picks WHERE ".$where." ORDER BY pick_id DESC LIMIT ".$offset.", ".$limit.";") or die (DB::error());
			while ($row = DB::fetch_row($q)) {
				$products = Pick_Product::pick_products_list($row['pick_id']);
				$info[] = [
					'id'=>$row['pick_id'],
					'user_id'=>$row['user_id'],
					'status'=>$row['status'],
					'products'=>$products['items'],
					'count_products'=>$row['count_products'],
					'manufacturers'=>$products['manufacturers'],
					'created'=>date('d.m.y в H:i', ts_timezone($row['created'], Session::$tz))
				];
			}
			// paginator
			$q = DB::query("SELECT count(*) FROM picks WHERE ".$where.";") or die (DB::error());
			$count = ($row = DB::fetch_row($q)) ? $row['count(*)'] : 0;
			$paginator = paginator($count, $offset, $limit, 'pick.paginator');
			// output
			return ['info'=>$info, 'paginator'=>$paginator];
		}
		
		public static function picks_fetch($data = []) {
			$picks = self::picks_list($data);
			HTML::assign('picks', $picks['info']);
			return ['info'=>HTML::fetch('./partials/section/picks/picks_table.html'), 'paginator'=>$picks['paginator']];
		}
		
		// ACTIONS
		
		public static function pick_export($data) {
			// vars
			$pick_id = isset($data['pick_id']) && is_numeric($data['pick_id']) ? $data['pick_id'] : 0;
			$product_ids = Pick_Product::pick_product_ids($pick_id);
			// export
			$filename = Pick_Product::pick_products_export($pick_id, $product_ids);
			// open
			$fp = fopen($filename, 'rb');
			fpassthru($fp);
			exit();
		}
		
		public static function pick_archive_toggle($data) {
			// vars
			$pick_id = isset($data['pick_id']) && is_numeric($data['pick_id']) ? $data['pick_id'] : 0;
			$offset = isset($data['offset']) && is_numeric($data['offset']) ? $data['offset'] : 0;
			$status = isset($data['status']) && in_array($data['status'], [0,1]) ? $data['status'] : 0;
			$status_prev = $status == 0 ? 1 : 0;
			// update
			DB::query("UPDATE picks SET status='".$status."' WHERE pick_id='".$pick_id."' LIMIT 1;") or die (DB::error());
			// output
			$info = self::picks_fetch(['offset'=>$offset, 'status'=>$status_prev]);
			return ['info'=>$info['info'], 'paginator'=>$info['paginator']];
		}

	}
?>