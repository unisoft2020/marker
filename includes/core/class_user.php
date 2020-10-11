<?php
	class User {

		// GENERAL

		public static function user_info($user_id, $api = false) {
			// info
			$q = DB::query("SELECT user_id, access, company_id, company_title, first_name, last_name, middle_name, occupation, note, email, phone, password, password_salt, sort_products, sort_passports, sort_users, sort_scans, blocked, count_notifications FROM users WHERE user_id='".$user_id."' LIMIT 1;") or die (DB::error());
			if ($row = DB::fetch_row($q)) {
				$company = Company::company_info($row['company_id']);
				$group = User_Group::user_group_info(['user_id'=>$row['user_id']]);
				return [
					'user_id'=>$row['user_id'],
					'access'=>$row['access'],
					'type'=>$company['type'],
					'company'=>$company,
					'company_id'=>$row['company_id'],
					'company_title'=>Session::$mode !=2 ? flt_output($row['company_title']) : $row['company_title'],
					'first_name'=>Session::$mode !=2 ? flt_output($row['first_name']) : $row['first_name'],
					'last_name'=>Session::$mode !=2 ? flt_output($row['last_name']) : $row['last_name'],
					'middle_name'=>Session::$mode !=2 ? flt_output($row['middle_name']) : $row['middle_name'],
					'full_name'=>Session::$mode !=2 ? flt_output(trim($row['last_name'].' '.$row['first_name'])) : trim($row['last_name'].' '.$row['first_name']),
					'occupation'=>$row['occupation'],
					'note'=>$row['note'],
					'email'=>$row['email'],
					'phone'=>$row['phone'] ? phone_formatting($row['phone']) : '',
					'phone_raw'=>$row['phone'],
					'password'=>$api ? '' : $row['password'],
					'password_salt'=>$api ? '' : $row['password_salt'],
					'group_id'=>$group['group_id'],
					'group_title'=>$group['group_title'],
					'show_home'=>$company['show_home'],
					'sort_products'=>$row['sort_products'] ? 'asc' : 'dsc',
					'sort_passports'=>$row['sort_passports'] ? 'asc' : 'dsc',
					'sort_users'=>$row['sort_users'] ? 'asc' : 'dsc',
					'sort_scans'=>$row['sort_scans'],
					'blocked'=>$api ? '' : $row['blocked'],
					'count_notifications'=>$row['count_notifications']
				];
			} else {
				return [
					'user_id'=>0,
					'access'=>0,
					'type'=>0,
					'company'=>['show_home'=>0, 'show_tasks'=>0],
					'company_id'=>0,
					'company_title'=>'',
					'first_name'=>'',
					'last_name'=>'',
					'middle_name'=>'',
					'full_name'=>'',
					'occupation'=>'',
					'note'=>'',
					'email'=>'',
					'phone'=>'',
					'phone_raw'=>'',
					'password'=>'',
					'password_salt'=>'',
					'group_id'=>0,
					'group_title'=>'',
					'sort_products'=>'dsc',
					'sort_passports'=>'dsc',
					'sort_users'=>'dsc',
					'sort_scans'=>0,
					'blocked'=>0,
					'count_notifications'=>0
				];
			}
		}

		public static function users_list($data) {
			// vars
			$info = [];
			$query = isset($data['query']) && trim($data['query']) ? trim($data['query']) : '';
			$offset = isset($data['offset']) && is_numeric($data['offset']) ? $data['offset'] : 0;
			$limit = 20;
			// info
			$user = self::user_info(Session::$user_id);
			// where
			$where = [];
			$where[] = "company_id='".Session::$company_id."'";
			if ($query) $where[] = "(first_name LIKE '".$query."%' OR last_name LIKE '".$query."%' OR email LIKE '".$query."%')";
			$where[] = "hidden<>'1'";
			$where = implode(' AND ', $where);
			// sort
			$sort = $user['sort_users'] == 'asc' ? "ASC" : "DESC";
			// info
			$q = DB::query("SELECT user_id, access, company_id, company_title, first_name, last_name, middle_name, occupation, note, email, phone, blocked, created, last_login FROM users WHERE ".$where." ORDER BY user_id ".$sort." LIMIT ".$offset.", ".$limit.";") or die (DB::error());
			while ($row = DB::fetch_row($q)) {
				$group = User_Group::user_group_info(['user_id'=>$row['user_id']]);
				$info[] = [
					'user_id'=>$row['user_id'],
					'access'=>$row['access'],
					'status'=>self::user_status($row['access']),
					'company_id'=>$row['company_id'],
					'company_title'=>$row['company_title'],
					'first_name'=>$row['first_name'],
					'last_name'=>$row['last_name'],
					'full_name'=>flt_output($row['last_name'].' '.$row['first_name'].' '.$row['middle_name']),
					'occupation'=>$row['occupation'],
					'note'=>$row['note'],
					'email'=>$row['email'],
					'phone'=>$row['phone'] ? phone_formatting($row['phone']) : 'не указан',
					'group_id'=>$group['group_id'],
					'group_title'=>$group['group_title'],
					'blocked'=>$row['blocked'],
					'created'=>$row['created'] ? date('d.m.y в H:i', ts_timezone($row['created'], Session::$tz)) : 'нет данных',
					'last_login'=>$row['last_login'] ? date('d.m.y в H:i', ts_timezone($row['last_login'], Session::$tz)) : 'нет данных'
				];
			}
			// paginator
			$q = DB::query("SELECT count(*) FROM users WHERE ".$where.";") or die (DB::error());
			$count = ($row = DB::fetch_row($q)) ? $row['count(*)'] : 0;
			$paginator = paginator($count, $offset, $limit, 'company.user_paginator', ['query'=>$query]);
			// output
			return ['info'=>$info, 'paginator'=>$paginator];
		}
		
		public static function users_fetch($data = []) {
			$users = self::users_list($data);
			$owner = self::user_info(Session::$user_id);
			HTML::assign('users', $users['info']);
			return ['users'=>HTML::fetch('./partials/section/users/users_table.html'), 'paginator'=>$users['paginator'], 'owner_name'=>$owner['full_name']];
		}
		
		public static function users_count() {
			// count (new)
			$q = DB::query("SELECT count(*) FROM users WHERE company_id='".Session::$company_id."' AND blocked='0' AND hidden='0' AND created>'".(Session::$ts - 3600 * 24 * 3)."';") or die (DB::error());
			$new = ($row = DB::fetch_row($q)) ? $row['count(*)'] : 0;
			// count (active)
			$q = DB::query("SELECT count(*) FROM users WHERE company_id='".Session::$company_id."' AND blocked='0' AND hidden='0' AND created<='".(Session::$ts - 3600 * 24 * 3)."' AND last_active>'".(Session::$ts - 3600 * 24 * 7)."';") or die (DB::error());
			$active = ($row = DB::fetch_row($q)) ? $row['count(*)'] : 0;
			// count (inactive)
			$q = DB::query("SELECT count(*) FROM users WHERE company_id='".Session::$company_id."' AND blocked='0' AND hidden='0' AND created<='".(Session::$ts - 3600 * 24 * 3)."' AND last_active<='".(Session::$ts - 3600 * 24 * 7)."';") or die (DB::error());
			$inactive = ($row = DB::fetch_row($q)) ? $row['count(*)'] : 0;
			// count (blocked)
			$q = DB::query("SELECT count(*) FROM users WHERE company_id='".Session::$company_id."' AND blocked='1' AND hidden='0';") or die (DB::error());
			$blocked = ($row = DB::fetch_row($q)) ? $row['count(*)'] : 0;
			// output
			return ['new'=>$new, 'active'=>$active, 'inactive'=>$inactive, 'blocked'=>$blocked];
		}
		
		// ACTIONS
		
		public static function user_edit_window($data) {
			// vars
			$user_id = isset($data['user_id']) && is_numeric($data['user_id']) ? $data['user_id'] : 0;
			// output
			HTML::assign('user', self::user_info($user_id));
			HTML::assign('groups', User_Group::user_group_title());
			return ['html'=>HTML::fetch('./partials/modal/users/user_edit.html')];
		}
		
		public static function user_edit_update($data) {
			// vars
			$user_id = isset($data['user_id']) && is_numeric($data['user_id']) ? $data['user_id'] : 0;
			$first_name = isset($data['first_name']) ? title_case_str($data['first_name']) : '';
			$last_name = isset($data['last_name']) ? title_case_str($data['last_name']) : '';
			$middle_name = isset($data['middle_name']) ? title_case_str($data['middle_name']) : '';
			$email = isset($data['email']) ? mb_convert_case(trim($data['email']), MB_CASE_LOWER, 'UTF-8') : '';
			$phone = isset($data['phone']) ? preg_replace('/[^\d]+/', '', $data['phone']) : '';
			$group_id = isset($data['group_id']) && is_numeric($data['group_id']) ? $data['group_id'] : 0;
			$password = isset($data['password']) ? $data['password'] : '';
			$password_update = false;
			$occupation = isset($data['occupation']) ? $data['occupation'] : '';
			$note = isset($data['note']) ? $data['note'] : '';
			$access = 3;
			if (!$phone) $phone = 0;
			// info
			$company = Company::company_info(Session::$company_id);
			$group = User_Group::user_group_info(['user_id'=>$user_id]);
			// update
			if ($user_id) {
				// password
				if ($password) {
					$info = self::user_info($user_id);
					$password_hash = p_hash($password, $info['password_salt']);
					$password_set = ", password='".$password_hash."'";
					$password_update = true;
				} else {
					$password_set = "";
				}
				// query
				DB::query("UPDATE users SET first_name='".$first_name."', last_name='".$last_name."', middle_name='".$middle_name."', email='".$email."', phone='".$phone."', occupation='".$occupation."', note='".$note."'".$password_set." WHERE user_id='".$user_id."' LIMIT 1;") or die (DB::error());
			}
			// create
			else {
				// password
				if (!$password) $password = p_create();
				$password_salt = p_salt();
				$password_hash = p_hash($password, $password_salt);
				$password_update = true;
				// query
				DB::query("INSERT INTO users (access, company_id, company_title, first_name, last_name, middle_name, occupation, note, email, phone, password, password_salt, created) VALUES ('".$access."', '".$company['id']."', '".unflt_output($company['title'])."', '".$first_name."', '".$last_name."', '".$middle_name."', '".$occupation."', '".$note."', '".$email."', '".$phone."', '".$password_hash."', '".$password_salt."', '".Session::$ts."');") or die (DB::error());
				$user_id = DB::insert_id();
			}
			// group
			if ($group_id != $group['group_id']) User_Group::user_group_update($user_id, $group_id);
			// output
			$info = self::users_fetch();
			if ($password_update) {
				Email::user_password($first_name, $email, $password);
				HTML::assign('user_id', $user_id);
				HTML::assign('full_name', trim($last_name.' '.$first_name));
				HTML::assign('email', $email);
				HTML::assign('password', $password);
				$info['password'] = HTML::fetch('./partials/modal/users/user_password.html');
			}
			return $info;
		}
		
		public static function user_sort($data) {
			// vars
			$sort = isset($data['sort']) && in_array($data['sort'], ['asc', 'dsc']) ? $data['sort'] : 'dsc';
			$new_sort = $sort == 'dsc' ? 1 : 0;
			// query
			DB::query("UPDATE users SET sort_users='".$new_sort."' WHERE user_id='".Session::$user_id."' LIMIT 1;") or die (DB::error());
			// output
			return self::users_fetch($data);
		}

		public static function user_block($data) {
			// vars
			$user_id = isset($data['user_id']) && is_numeric($data['user_id']) ? $data['user_id'] : 0;
			$status = isset($data['status']) && in_array($data['status'], [0,1]) ? $data['status'] : 0;
			// update
			DB::query("UPDATE users SET blocked='".$status."' WHERE user_id='".$user_id."' LIMIT 1;") or die (DB::error());
			if ($status == 1) DB::query("UPDATE sessions SET user_id='0', access='0', token='' WHERE user_id='".$user_id."';") or die (DB::error());
			// output
			return self::users_fetch($data);
		}
		
		public static function user_delete_window($data) {
			// vars
			$user_id = isset($data['user_id']) && is_numeric($data['user_id']) ? $data['user_id'] : 0;
			// output
			HTML::assign('user_id', $user_id);
			return ['html'=>HTML::fetch('./partials/modal/users/user_delete.html')];
		}
		
		public static function user_delete_update($data) {
			// vars
			$user_id = isset($data['user_id']) && is_numeric($data['user_id']) ? $data['user_id'] : 0;
			// query
			DB::query("UPDATE users SET hidden='1' WHERE user_id='".$user_id."' LIMIT 1;") or die (DB::error());
			// output
			return self::users_fetch();
		}

		public static function user_count_notifications_update($data) {
			// vars
			$user_id = isset($data['user_id']) && is_numeric($data['user_id']) ? $data['user_id'] : 0;
			$count_notifications = isset($data['count_notifications']) && is_numeric($data['count_notifications']) ? $data['count_notifications'] : 0;
			// query
			DB::query("UPDATE users SET count_notifications='".$count_notifications."' WHERE user_id='".$user_id."' LIMIT 1;") or die (DB::error());
		}
		
		// SERVICE
		
		public static function user_status($id) {
			if ($id == 1) return 'Разработчик';
			if ($id == 2) return 'Администратор';
			if ($id == 3) return 'Пользователь';
			return '';
		}

		public static function search_users($query) {
			// vars
			$info = [];
			$query_arr = explode(' ', $query);
			$f_value = $query_arr[0];
			$s_value = $query_arr[1];
			// query
			if ($query != '') {
				$q = DB::query("SELECT user_id FROM users WHERE (first_name LIKE '%".$f_value."%' AND last_name LIKE '%".$s_value."%') or (first_name LIKE '%".$s_value."%' AND last_name LIKE '%".$f_value."%');") or die (DB::error());
				while ($row = DB::fetch_row($q)) {
					// info
					$info[] = [
						'id'=>$row['user_id'],
					];
				}
			}
			// output
			return ['info'=>$info];
		}

	}
?>