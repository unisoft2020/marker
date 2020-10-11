<?php
	class Scan {
		
		// GENERAL
		
		public static function scans_list($data) {
			// vars
			$info = [];
			$query = isset($data['query']) ? trim($data['query']) : '';
			$offset = isset($data['offset']) && is_numeric($data['offset']) ? $data['offset'] : 0;
			$limit = 20;
			// info
			$user = User::user_info(Session::$user_id);
			// where
			$where = [];
			$where[] = "company_id='".Session::$company_id."'";
			if ($query) {
				$search = self::scans_search($query);
				$where_search = [];
				if ($search['product_ids']) $where_search[] = "product_id IN (".implode(",", $search['product_ids']).")";
				if ($search['user_ids']) $where_search[] = "user_id IN (".implode(",", $search['user_ids']).")";
				$where[] = $where_search ? implode(" OR ", $where_search) : "id='0'";
			}
			$where = implode(" AND ", $where);
			// sort
			$sort_scans = $user['sort_scans'];
			$sort = $sort_scans == 1 ? "ASC" : "DESC";
			// query
			$q = DB::query("SELECT id, user_id, product_id, created FROM scans WHERE ".$where." ORDER BY id ".$sort." LIMIT ".$offset.", ".$limit.";") or die (DB::error());
			while ($row = DB::fetch_row($q)) {
				$product = Product::product_info_full($row['product_id']);
				$user = User::user_info($row['user_id']);
				$info[] = [
					'id'=>$row['id'],
					'product_id'=>$row['product_id'],
					'product_title'=>$product['title'],
					'user_name'=>$user['full_name'],
					'manufacturer'=>$product['manufacturer']['title'],
					'created'=>date('d.m.y в H:i', ts_timezone($row['created'], Session::$tz))
				];
			}
			// sort
			if ($sort_scans == 2) usort($info, 'self::sort_manufacturer_acs');
			if ($sort_scans == 3) usort($info, 'self::sort_manufacturer_desc');
			// paginator
			$q = DB::query("SELECT count(*) FROM scans WHERE ".$where.";") or die (DB::error());
			$count = ($row = DB::fetch_row($q)) ? $row['count(*)'] : 0;
			$paginator = paginator($count, $offset, $limit, 'scan.paginator');
			// output
			return ['info'=>$info, 'paginator'=>$paginator];
		}
		
		public static function scans_fetch($data = []) {
			$scans = self::scans_list($data);
			HTML::assign('scans', $scans['info']);
			return ['info'=>HTML::fetch('./partials/section/scans/scans_table.html'), 'paginator'=>$scans['paginator']];
		}

		public static function scans_list_simple($data) {
			// vars
			$info = [];
			$product_id = isset($data['product_id']) && is_numeric($data['product_id']) ? $data['product_id'] : 0;
			// where
			$where = $product_id ? "product_id='".$product_id."'" : "user_id='".Session::$user_id."'";
			// info
			$q = DB::query("SELECT id, product_id, user_id, source, lat, lng, created FROM scans WHERE ".$where." ORDER BY id DESC LIMIT 20;") or die (DB::error());
			while ($row = DB::fetch_row($q)) {
				$product = Product::product_info($row['product_id']);
				$user = User::user_info($row['user_id']);
				$info[] = [
					'id'=>$row['id'],
					'title'=>$product['title'],
					'user_title'=>$user['full_name'],
					'company_title'=>$user['company_title'],
					'source'=>$row['source'],
					'lat'=>$row['lat'],
					'lng'=>$row['lng'],
					'created'=>date('d.m.Y H:i', ts_timezone($row['created'], Session::$tz)),
					'created_date'=>date_str($row['created'], 'default'),
					'created_time'=>date('H:i', ts_timezone($row['created'], Session::$tz)),
				];
			}
			// output
			return ['info'=>$info, 'next_offset'=>0];
		}
		
		public static function scan_last() {
			// query
			$q = DB::query("SELECT id, user_id, product_id, created FROM scans WHERE user_id='".Session::$user_id."' ORDER BY id DESC LIMIT 1;") or die (DB::error());
			if ($row = DB::fetch_row($q)) {
				return [
					'id'=>$row['id'],
					'user_id'=>$row['user_id'],
					'product_id'=>$row['product_id'],
					'created'=>date('d.m.Y H:i', ts_timezone($row['created'], Session::$tz))
				];
			} else {
				return [
					'id'=>0,
					'user_id'=>0,
					'product_id'=>0,
					'created'=>''
				];
			}
		}
		
		// ACTIONS
		
		public static function scans_window($data) {
			// info
			$info = self::scans_list_simple($data);
			// output
			HTML::assign('info', $info['info']);
			return ['html'=>HTML::fetch('./partials/modal/products/product_history.html')];
		}
		
		public static function scan_create($data) {
			// vars
			$product_id = isset($data['product_id']) && is_numeric($data['product_id']) ? $data['product_id'] : 0;
			$lat = isset($data['lat']) && is_numeric($data['lat']) ? $data['lat'] : 0;
			$lng = isset($data['lng']) && is_numeric($data['lng']) ? $data['lng'] : 0;
			$source = isset($data['source']) && is_numeric($data['source']) ? $data['source'] : 0;
			// info
			$last = self::scan_last();
			$product = Product::product_info($product_id);
			// query
			if ($last['product_id'] == $product_id) $message = 'product already exist';
			else if ($product['hidden'] != 0) $message = 'product is not available';
			else {
				DB::query("INSERT INTO scans (company_id, user_id, product_id, source, lat, lng, created) VALUES ('".Session::$company_id."', '".Session::$user_id."', '".$product_id."', '".$source."', '".$lat."', '".$lng."', '".Session::$ts."');") or die (DB::error());
        		$message = 'product add to history';
			}
			// output
			return ['message'=>$message];
		}

		public static function scan_sort($data) {
			// vars
			$value = isset($data['value']) ? $data['value'] : 0;
			// query
			DB::query("UPDATE users SET sort_scans='".$value."' WHERE user_id='".Session::$user_id."' LIMIT 1;") or die (DB::error());
			// output
			return self::scans_fetch($data);
		}
		
		// SERVICE
		
		private static function scans_search($query) {
			// vars
			$company_ids = [];
			$product_ids = [];
			$user_ids = [];
			// companies
			$q = DB::query("SELECT company_id FROM companies WHERE title_full LIKE '%".$query."%';") or die (DB::error());
			while ($row = DB::fetch_row($q)) $company_ids[] = $row['company_id'];
			// products (where)
			$where = [];
			$where[] = "title LIKE '%".$query."%'";
			if ($company_ids) $where[] = "company_id IN (".implode(",", $company_ids).")";
			$where = implode(" OR ", $where);
			// products
			$q = DB::query("SELECT product_id FROM products WHERE ".$where.";") or die (DB::error());
			while ($row = DB::fetch_row($q)) $product_ids[] = $row['product_id'];
			// users (where)
			$where = [];
			$queries = explode(" ", $query);
			if (count($queries) == 1) $where[] = "(first_name LIKE '%".$query."%' OR last_name LIKE '%".$query."%')";
			else {
				$where_tmp = [];
				foreach($queries as $q) $where_tmp[] = "(first_name LIKE '%".$q."%' OR last_name LIKE '%".$q."%')";
				$where[] = implode(" AND ", $where_tmp);
			}
			$where = implode(" OR ", $where);
			// users
			$q = DB::query("SELECT user_id FROM users WHERE ".$where.";") or die (DB::error());
			while ($row = DB::fetch_row($q)) $user_ids[] = $row['user_id'];
			// output
			return ['product_ids'=>$product_ids, 'user_ids'=>$user_ids];
		}
		
		private static function sort_manufacturer_acs($a, $b) {
			if ($a['manufacturer'] == $b['manufacturer']) return 0;
			return $a['manufacturer'] < $b['manufacturer'] ? -1 : 1;
		}
		
		private static function sort_manufacturer_desc($a, $b) {
			if ($a['manufacturer'] == $b['manufacturer']) return 0;
			return $a['manufacturer'] < $b['manufacturer'] ? 1 : -1;
		}

	}
?>	