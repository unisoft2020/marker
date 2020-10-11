<?php
	class Company {

		// GENERAL

		public static function company_info($company_id) {
			// info
			$q = DB::query("SELECT company_id, type, title_full, address, address_lat, address_lng, inn, kpp, ogrn, bank_title, bank_code, bank_cs, bank_rs, phone, email, balance, print_id, pick_email, pick_server, show_home, show_passports, show_products, show_tasks, size_used, size_total, created FROM companies WHERE company_id='".$company_id."' LIMIT 1;") or die (DB::error());
			if ($row = DB::fetch_row($q)) {
				return [
					'id'=>$row['company_id'],
					'type'=>$row['type'],
					'title'=>Session::$mode != 2 ? flt_output($row['title_full']) : $row['title_full'],
					'address'=>Session::$mode != 2 ? flt_output($row['address']) : $row['address'],
					'address_lat'=>(string) $row['address_lat'],
					'address_lng'=>(string) $row['address_lng'],
					'inn'=>$row['inn'] ? $row['inn'] : '',
					'kpp'=>$row['kpp'] ? $row['kpp'] : '',
					'ogrn'=>$row['ogrn'] ? $row['ogrn'] : '',
					'bank_title'=>$row['bank_title'],
					'bank_code'=>$row['bank_code'],
					'bank_cs'=>$row['bank_cs'],
					'bank_rs'=>$row['bank_rs'],
					'phone'=>$row['phone'] ? phone_formatting($row['phone']) : '',
					'email'=>$row['email'],
					'balance'=>number_format($row['balance'] * 0.01, 0, ',', ' '),
					'print_id'=>$row['print_id'],
					'pick_email'=>$row['pick_email'],
					'pick_server'=>$row['pick_server'],
					'show_home'=>$row['show_home'],
					'show_passports'=>$row['show_passports'],
					'show_products'=>$row['show_products'],
					'show_tasks'=>$row['show_tasks'],
					'size_free'=>round(($row['size_total'] - $row['size_used']) / 1073741824, 2),
					'size_used'=>round($row['size_used'] / 1073741824, 2),
					'size_total'=>round($row['size_total'] / 1073741824, 2),
					'created'=>date('d.m.Y', ts_timezone($row['created'], Session::$tz))
				];
			} else {
				return [
					'id'=>0,
					'code'=>'',
					'type'=>0,
					'title'=>'',
					'address'=>'',
					'address_lat'=>'0.00000000',
					'address_lng'=>'0.00000000',
					'inn'=>'',
					'kpp'=>'',
					'ogrn'=>'',
					'bank_title'=>'',
					'bank_code'=>'',
					'bank_cs'=>'',
					'bank_rs'=>'',
					'phone'=>'',
					'email'=>'',
					'balance'=>0,
					'print_id'=>0,
					'pick_email'=>'',
					'pick_server'=>'',
					'show_home'=>0,
					'show_passports'=>0,
					'show_products'=>0,
					'show_tasks'=>0,
					'size_free'=>0,
					'size_used'=>0,
					'size_total'=>0,
					'created'=>''
				];
			}
		}
		
		private static function companies_list($data) {
			// vars
			$offset = isset($data['offset']) && is_numeric($data['offset']) ? $data['offset'] : 0;
			$query = isset($data['query']) && trim($data['query']) ? trim($data['query']) : '';
			$type = isset($data['type']) && in_array($data['type'], [-1, 0, 1, 2, 3]) ? $data['type'] : -1;
			$mode = isset($data['mode']) ? $data['mode'] : 'default';
			$limit = 20;
			$info = [];
			// where
			$where = [];
			if ($query) $where[] = "title_full LIKE '%".$query."%'";
			if ($type != -1) $where[] = "type='".$type."'";
			if ($mode == 'search') $where[] = "company_id!='".Session::$company_id."'";
			$where[] = "hidden<>'1'";
			$where = implode(' AND ', $where);
			// query
			$q = DB::query("SELECT company_id, type, title_full, address, inn, kpp, ogrn, phone, email, created FROM companies WHERE ".$where." ORDER BY company_id LIMIT ".$offset.", ".$limit.";") or die (DB::error());
			while ($row = DB::fetch_row($q)) {
				$info[] = [
					'id'=>$row['company_id'],
					'type'=>$row['type'],
					'title'=>flt_output($row['title_full']),
					'address'=>flt_output($row['address']),
					'inn'=>$row['inn'] ? $row['inn'] : '',
					'kpp'=>$row['kpp'] ? $row['kpp'] : '',
					'ogrn'=>$row['ogrn'] ? $row['ogrn'] : '',
					'phone'=>$row['phone'] ? phone_formatting($row['phone']) : '',
					'email'=>$row['email']
				];
			}
			// paginator
			$q = DB::query("SELECT count(*) FROM companies WHERE ".$where.";") or die (DB::error());
			$count = ($row = DB::fetch_row($q)) ? $row['count(*)'] : 0;
			$paginator = paginator($count, $offset, $limit, 'company.company_paginator', ['query'=>$query]);
			// output
			return ['info'=>$info, 'paginator'=>$paginator];
		}
		
		// SEARCH
		
		public static function company_search_customer($data) {
			// vars
			$query = isset($data['query']) && trim($data['query']) ? trim($data['query']) : '';
			// info
			$customers = self::companies_list(['type'=>1, 'query'=>$query, 'mode'=>'search']);
			// output
			HTML::assign('customers', $customers['info']);
			return ['html'=>HTML::fetch('./partials/modal/products/product_edit_customers.html')];
		}
		
		public static function company_search_consignee($data) {
			// vars
			$query = isset($data['query']) && trim($data['query']) ? trim($data['query']) : '';
			// info
			$consignees = self::companies_list(['type'=>1, 'query'=>$query, 'mode'=>'search']);
			// output
			HTML::assign('consignees', $consignees['info']);
			return ['html'=>HTML::fetch('./partials/modal/products/product_edit_consignees.html')];
		}
		
		// ACTIONS
		
		public static function company_edit($data) {
			// vars (contractor)
			$id = isset($data['id']) && is_numeric($data['id']) ? $data['id'] : 0;
			$type = isset($data['type']) && in_array($data['type'], [1, 2, 3]) ? $data['type'] : 1;
			if ($id == 1) $type = 0;
			$code = isset($data['code']) && is_numeric($data['code']) ? $data['code'] : 0;
			$title = isset($data['title']) ? $data['title'] : '';
			$title_short = title_short($title);
			$title_hash = $id != 1 ? str_hash($title_short) : 0;
			$ogrn = isset($data['ogrn']) ? preg_replace('/[^\d]+/', '', $data['ogrn']) : 0;
			$inn = isset($data['inn']) ? preg_replace('/[^\d]+/', '', $data['inn']) : 0;
			$kpp = isset($data['kpp']) ? preg_replace('/[^\d]+/', '', $data['kpp']) : 0;
			$address = isset($data['address']) ? $data['address'] : '';
			$phone = isset($data['phone']) ? preg_replace('/[^\d]+/', '', $data['phone']) : 0;
			$email = isset($data['email']) && trim($data['email']) ? mb_convert_case(trim($data['email']), MB_CASE_LOWER, 'UTF-8') : '';
			// vars (user)
			$last_name = isset($data['last_name']) && trim($data['last_name']) ? trim($data['last_name']) : '';
			$first_name = isset($data['first_name']) && trim($data['first_name']) ? trim($data['first_name']) : '';
			$middle_name = isset($data['middle_name']) && trim($data['middle_name']) ? trim($data['middle_name']) : '';
			$user_email = isset($data['user_email']) && trim($data['user_email']) ? mb_convert_case(trim($data['user_email']), MB_CASE_LOWER, 'UTF-8') : '';
			$user_phone = isset($data['user_phone']) ? preg_replace('/[^\d]+/', '', $data['user_phone']) : 0;
			// validate
			$errors = self::company_edit_val($id, $code, $title, $ogrn, $inn, $kpp, $address, $phone, $email, $first_name, $last_name, $middle_name, $user_email, $user_phone);
			if ($errors) return ['errors'=>$errors];
			// create
			if (!$id) {
				// contractor
				DB::query("INSERT INTO companies (type, title_full, title_short, title_hash, address, inn, kpp, ogrn, phone, email, created) VALUES ('".$type."', '".$title."', '".$title_short."', '".$title_hash."', '".$address."', '".$inn."', '".$kpp."', '".$ogrn."', '".$phone."', '".$email."', '".Session::$ts."');") or die (DB::error());
				$company_id = DB::insert_id();
				// user
				$info = User::user_edit(['last_name'=>$last_name, 'first_name'=>$first_name, 'middle_name'=>$middle_name, 'email'=>$user_email, 'phone'=>$user_phone, 'company_id'=>$company_id, 'company_title'=>$title]);
				// note
				$note = 'Компания добавлена<br><span id="info_note_sub">Учетные данные отправлены на электронную почту <span>'.$info['email'].'</span>. Используйте для входа в аккаунт <span>'.str_replace('\\', '', $title).'</span> логин <span>'.$info['email'].'</span> и пароль <span>'.$info['password'].'</span>.</span>';
			}
			// update
			else {
				$query = "UPDATE companies SET type='".$type."', title_full='".$title."', title_short='".$title_short."', title_hash='".$title_hash."', address='".$address."', inn='".$inn."', kpp='".$kpp."', ogrn='".$ogrn."', phone='".$phone."', email='".$email."' WHERE company_id='".$id."' LIMIT 1;";
				$query .= "UPDATE users SET company_title='".$title."' WHERE company_id='".$id."';";
				DB::query($query) or die (DB::error());
				$note = 'Изменения сохранены<br><span id="info_note_sub">Новые данные будут отражены в профиле организации.</span>';
			}
			// output
			return ['note'=>$note];
		}
		
		private static function company_edit_val($id, $code, $title, $ogrn, $inn, $kpp, $address, $phone, $email, $first_name, $last_name, $middle_name, $user_email, $user_phone) {
			// vars
			$result = [];
			// validate
			$val['title'] = is_title($title);
			$val['ogrn'] = is_ogrn($ogrn);
			$val['inn'] = is_inn($inn);
			$val['kpp'] = is_kpp($kpp);
			$val['address'] = is_address($address);
			$val['phone'] = is_phone($phone);
			$val['email'] = is_email($email);
			$val['first_name'] = !$id ? is_name($first_name) : '';
			$val['last_name'] = !$id ? is_name($last_name) : '';
			$val['middle_name'] = !$id ? is_name($middle_name, true) : '';
			$val['user_email'] = !$id ? is_email($user_email) : '';
			$val['user_phone'] = !$id ? is_phone($user_phone) : '';
			// output
			if ($val['title']) $result['title'] = $val['title'];
			if ($val['ogrn']) $result['ogrn'] = $val['ogrn'];
			if ($val['inn']) $result['inn'] = $val['inn'];
			if ($val['kpp']) $result['kpp'] = $val['kpp'];
			if ($val['address']) $result['address'] = $val['address'];
			if ($val['phone']) $result['phone'] = $val['phone'];
			if ($val['email']) $result['email'] = $val['email'];
			if ($val['first_name']) $result['first_name'] = $val['first_name'];
			if ($val['last_name']) $result['last_name'] = $val['last_name'];
			if ($val['middle_name']) $result['middle_name'] = $val['middle_name'];
			if ($val['user_email']) $result['user_email'] = $val['user_email'];
			if ($val['user_phone']) $result['user_phone'] = $val['user_phone'];
			return $result;
		}
		
		public static function company_delete($data) {
			// vars
			$id = isset($data['id']) ? $data['id'] : 0;
			// queries
			$query = "UPDATE users SET hidden='1' WHERE company_id='".$id."' LIMIT 1;";
			$query .= "UPDATE companies SET hidden='1' WHERE company_id='".$id."' LIMIT 1;";
			DB::query($query) or die (DB::error());
		}
		
		// SETTINGS
		
		public static function company_settings_update($data) {
			// vars
			$pick_email = isset($data['pick_email']) ? $data['pick_email'] : '';
			$pick_server = isset($data['pick_server']) ? $data['pick_server'] : '';
			// query
			DB::query("UPDATE companies SET pick_email='".$pick_email."', pick_server='".$pick_server."' WHERE company_id='".Session::$company_id."';") or die (DB::error());
			// output
			return ['response'=>'ok'];
		}
		
		// SERVICE
		
		public static function company_size_update($size) {
			Stats::stats_update(1, 0, $size);
			DB::query("UPDATE companies SET size_used=size_used+'".$size."' WHERE company_id='".Session::$company_id."';") or die (DB::error());
		}
		
	}
?>