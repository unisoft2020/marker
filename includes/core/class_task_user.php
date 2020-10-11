<?php
	class Task_User {

		// GENERAL
		
		public static function task_users_list($data = []) {
			// vars
			$info = [];
			$id = isset($data['id']) && is_numeric($data['id']) ? $data['id'] : 0;
			// where
			$where = [];
			$where[] = "task_id='".$id."'";
			$where = implode(" AND ", $where);
			// info
			$q = DB::query("SELECT user_id FROM task_users WHERE ".$where.";") or die (DB::error());
			while ($row = DB::fetch_row($q)) {
				// user
				$user = User::user_info($row['user_id']);
				// info
				$info[] = [
					'id'=>$row['user_id'],
					'full_name'=>$user['full_name']
				];
			}
			// output
			return $info;
		}
		
		// ACTIONS
		
		public static function task_users_search($data) {
			// info
			$users = self::task_users_add_list($data);
			// output
			HTML::assign('users', $users);
			return ['html'=>HTML::fetch('./partials/modal/tasks/task_add_user_search.html')];
		}
		
		public static function task_users_add_list($data = []) {
			// vars
			$info = [];
			$query = isset($data['query']) && trim($data['query']) ? trim($data['query']) : '';
			$selected = isset($data['users']) ? $data['users'] : [];
			// where
			$where = [];
			$where[] = "company_id='".Session::$company_id."'";
			if ($query) $where[] = "(first_name LIKE '%".$query."%' OR last_name LIKE '%".$query."%')";
			$where = $where ? "WHERE ".implode(" AND ", $where) : "";
			// info
			$q = DB::query("SELECT user_id, company_title, first_name, last_name, middle_name, occupation FROM users ".$where." LIMIT 5;") or die (DB::error());
			while ($row = DB::fetch_row($q)) {
				$info[] = [
					'id'=>$row['user_id'],
					'company_title'=>$row['company_title'],
					'first_name'=>$row['first_name'],
					'last_name'=>$row['last_name'],
					'name'=>$row['last_name'].' '.mb_substr($row['first_name'], 0, 1, 'UTF-8').'. '.mb_substr($row['middle_name'], 0, 1, 'UTF-8').'.',
					'occupation'=>$row['occupation'],
					'selected'=>in_array($row['user_id'], $selected) ? true : false
				];
			}
			// output
			return $info;
		}

		public static function task_users_add_window($data) {
			// info
			$users = self::task_users_add_list($data);
        	// output
			HTML::assign('users', $users);
			return ['html'=>HTML::fetch('./partials/modal/tasks/task_add_user.html')];
		}

	}
?>