<?php
	class Task {

		// GENERAL

		public static function task_info($task_id, $data) {
			// info
			$q = DB::query("SELECT task_id, status, title, products_all, products_active, date_end, created FROM tasks WHERE task_id='".$task_id."' LIMIT 1;") or die (DB::error());
			if ($row = DB::fetch_row($q)) {
				// users
				$users_simple = [];
				$users = Task_User::task_users_list($data);
				foreach($users as $a) $users_simple[] = $a['id'];
				// products
				$products_simple = [];
				$products = Task_Product::task_products_list($data);
				foreach($products as $a) $products_simple[] = $a['id'];
				// info
				return [
					'id'=>$row['task_id'],
					'status'=>$row['status'],
					'title'=>$row['title'],
					'products'=>$products,
					'products_simple'=>'['.implode(',', $products_simple).']',
					'users'=>$users,
					'users_simple'=>'['.implode(',', $users_simple).']',
					'products_all'=>$row['products_all'],
					'products_active'=>$row['products_active'],
					'date_end'=>date('d.m.Y', $row['date_end']),
					'date'=>date('d.m.Y', ts_timezone($row['created'], Session::$tz)),
					'time'=>date('H:i', ts_timezone($row['created'], Session::$tz))
				];
			} else {
				return [
					'id'=>0,
					'status'=>0,
					'title'=>'',
					'products'=>[],
					'products_simple'=>'[]',
					'users'=>[],
					'users_simple'=>'[]',
					'products_all'=>0,
					'products_active'=>0,
					'date_end'=>'',
					'date'=>'',
					'time'=>''
				];
			}
		}

		public static function tasks_list($data = []) {
			// vars
			$info = [];
			$show = isset($data['show']) ? $data['show'] : 'all';
			$query = isset($data['query']) && trim($data['query']) ? trim($data['query']) : '';
			$offset = isset($data['offset']) && is_numeric($data['offset']) ? $data['offset'] : 0;
			$limit = 20;
			// where
			$where = [];
			if (Session::$access != 1) $where[] = "company_id='".Session::$company_id."'";
			if ($show == 'active') $where[] = "status='0'";
			if ($show == 'complete') $where[] = "status='1'";
			if ($query) $where[] = "(title LIKE '%".$query."%')";
			$where[] = "hidden<>'1'";
			$where = implode(' AND ', $where);
			// info
			$q = DB::query("SELECT task_id, status, title, products_all, products_active, date_end, created FROM tasks WHERE ".$where." ORDER BY task_id DESC LIMIT ".$offset.", ".$limit.";") or die (DB::error());
			while ($row = DB::fetch_row($q)) {
				$info[] = [
					'id'=>$row['task_id'],
					'status'=>$row['status'],
					'title'=>$row['title'],
					'users'=>Task_User::task_users_list(['id'=>$row['task_id']]),
					'products_all'=>$row['products_all'],
					'products_active'=>$row['products_active'],
					'date_end'=>$row['date_end'] ? date('d.m.Y', $row['date_end']) : '-',
					'date'=>date('d.m.Y', ts_timezone($row['created'], Session::$tz)),
					'time'=>date('H:i', ts_timezone($row['created'], Session::$tz))
				];
			}
			// paginator
			$q = DB::query("SELECT count(*) FROM tasks WHERE ".$where.";") or die (DB::error());
			$count = ($row = DB::fetch_row($q)) ? $row['count(*)'] : 0;
			$paginator = paginator($count, $offset, $limit, 'task.task_paginator', ['query'=>$query]);
			// output
			return ['info'=>$info, 'paginator'=>$paginator];
		}
		
		// ACTIONS
		
		public static function task_edit($data) {
			// vars
			$task_id = isset($data['task_id']) && is_numeric($data['task_id']) ? $data['task_id'] : 0;
			$title = isset($data['title']) && trim($data['title']) ? trim($data['title']) : '';
			$date_end = isset($data['date_end']) && trim(preg_match('~^[\d]{2}\.[\d]{2}\.[\d]{4}$~', $data['date_end'])) ? trim($data['date_end']) : '';
			$users = isset($data['users']) && is_array($data['users']) ? $data['users'] : [];
			$products = isset($data['products']) && is_array($data['products']) ? $data['products'] : [];
			// handling
			$products_count = count($products);
			$date_end = strtotime($date_end);
			// update (tasks)
			if ($task_id) {
				DB::query("UPDATE tasks SET title='".$title."', products_all='".$products_count."', products_active='".$products_count."', date_end='".$date_end."' WHERE task_id='".$task_id."' LIMIT 1;") or die (DB::error());
			} else {
				DB::query("INSERT INTO tasks (company_id, title, products_all, products_active, date_end, created) VALUES ('".Session::$company_id."', '".$title."', '".$products_count."', '".$products_count."', '".$date_end."', '".Session::$ts."');") or die (DB::error());
				$task_id = DB::insert_id();
			}
			// update (users)
			$old = [];
			if ($task_id) {
				$q = DB::query("SELECT id, user_id FROM task_users WHERE task_id='".$task_id."';") or die (DB::error());
				while ($row = DB::fetch_row($q)) $old[$row['user_id']] = $row['id'];
				foreach($old as $user_id => $id) {
					if (!in_array($user_id, $users)) DB::query("DELETE FROM task_users WHERE id='".$id."';") or die (DB::error());
				}
			}
			foreach($users as $user_id) {
				if (!isset($old[$user_id])) DB::query("INSERT INTO task_users (task_id, user_id) VALUES ('".$task_id."', '".$user_id."');") or die (DB::error());
			}
			// update (products)
			$old = [];
			if ($task_id) {
				$q = DB::query("SELECT id, product_id FROM task_products WHERE task_id='".$task_id."';") or die (DB::error());
				while ($row = DB::fetch_row($q)) $old[$row['product_id']] = $row['id'];
				foreach($old as $product_id => $id) {
					if (!in_array($product_id, $products)) DB::query("DELETE FROM task_products WHERE id='".$id."';") or die (DB::error());
				}
			}
			foreach($products as $product_id) {
				if (!isset($old[$product_id])) DB::query("INSERT INTO task_products (task_id, product_id) VALUES ('".$task_id."', '".$product_id."');") or die (DB::error());
			}
			// output
			return ['response'=>'ok'];
		}
		
		public static function task_delete($data) {
			// vars
			$task_id = isset($data['task_id']) && is_numeric($data['task_id']) ? $data['task_id'] : 0;
			// query
			DB::query("UPDATE tasks SET hidden='1' WHERE task_id='".$task_id."' LIMIT 1;") or die (DB::error());
			// output
			return ['response'=>'ok'];
		}

	}
?>