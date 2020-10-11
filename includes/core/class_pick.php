<?php

	//use PhpOffice\PhpSpreadsheet\Spreadsheet;

	class Pick {

		// GENERAL

		public static function picks_list($data) {
			// vars
			$info = [];
            $query = isset($data['query']) ? trim($data['query']) : '';
			$status = isset($data['status']) && in_array($data['status'], [0,1]) ? $data['status'] : -1;
			$offset = isset($data['offset']) && is_numeric($data['offset']) ? $data['offset'] : 0;
			$limit = 20;
			// where
			$where = [];
			$where[] = "company_id='".Session::$company_id."'";
            if ($query) {
                $search = self::picks_search($query);
                $where_search = [];
                if ($search['pick_ids']) $where_search[] = "pick_id IN (".implode(",", $search['pick_ids']).")";
                if ($search['company_ids']) $where_search[] = "company_id IN (".implode(",", $search['company_ids']).")";
                if ($search['user_ids']) $where_search[] = "user_id IN (".implode(",", $search['user_ids']).")";
                $where_search[] = "pick_id='$query'";
                if (strtotime($query)!="") {
                    $where_search[] = "created BETWEEN '".strtotime($query." 00:00")."' AND '".strtotime($query." 23:59")."'";
                }
                $where[] = $where_search ? "(".implode(" OR ", $where_search).")" : "pick_id='0'";
            }
			$where = implode(' AND ', $where);
			// info
			$q = DB::query("SELECT pick_id, user_id, company_id, status, count_products, created FROM picks WHERE ".$where." ORDER BY pick_id DESC LIMIT ".$offset.", ".$limit.";") or die (DB::error());
			while ($row = DB::fetch_row($q)) {
				$products = Pick_Product::pick_products_list($row['pick_id'], $query);
                $manufacturer = Company::company_info($row['company_id']);
				$info[] = [
					'id'=>$row['pick_id'],
					'user_id'=>$row['user_id'],
					'status'=>$row['status'],
					'products'=>$products['items'],
					'count_products'=>$row['count_products'],
					'manufacturers'=>$manufacturer['title'],
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


        private static function picks_search($query) {
            // vars
            $company_ids = [];
            $user_ids = [];
            $pick_ids = [];
            
            // companies
            $q = DB::query("SELECT company_id FROM companies WHERE title_full LIKE '%".$query."%';") or die (DB::error());
            while ($row = DB::fetch_row($q)) $company_ids[] = $row['company_id'];
            
            // products
            $q = DB::query("SELECT picks.pick_id FROM picks LEFT JOIN pick_products using(pick_id) LEFT JOIN products using(product_id) WHERE title LIKE '%".$query."%' GROUP BY picks.pick_id;") or die (DB::error());
            while ($row = DB::fetch_row($q)) $pick_ids[] = $row['pick_id'];
            
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
            return ['company_ids'=>$company_ids, 'user_ids'=>$user_ids, 'pick_ids'=>$pick_ids];
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