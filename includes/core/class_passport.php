<?php
	class Passport {
		
		// GENERAL
		
		public static function passport_info($passport_id) {
			// info
			$q = DB::query("SELECT passport_id, status, type, title, number, order_number, sections_all, sections_completed, produced, created FROM passports WHERE passport_id='".$passport_id."' LIMIT 1;") or die (DB::error());
			if ($row = DB::fetch_row($q)) {
				$type = Passport_Template::passport_template_info($row['type']);
				return [
					'id'=>$row['passport_id'],
					'status_id'=>$row['status'],
					'status_title'=>self::passport_status($row['status']),
					'type_id'=>$type['id'],
					'type_title'=>$type['title'],
					'title'=>$row['title'],
					'number'=>$row['number'],
					'order_number'=>$row['order_number'],
					'sections_all'=>$row['sections_all'],
					'sections_completed'=>$row['sections_completed'],
					'produced'=>$row['produced'] ? date('d.m.Y', ts_timezone($row['produced'], Session::$tz)) : '',
					'created'=>date('d.m.Y H:i', ts_timezone($row['created'], Session::$tz))
				];
			} else {
				$type = Passport_Template::passport_template_info(1);
				return [
					'id'=>0,
					'status_id'=>0,
					'status_title'=>'',
					'type_id'=>$type['id'],
					'type_title'=>$type['title'],
					'title'=>'',
					'number'=>'',
					'order_number'=>'',
					'sections_all'=>0,
					'sections_completed'=>0,
					'produced'=>'',
					'created'=>''
				];
			}
		}
		
		public static function passports_list($data) {
			// vars
			$info = [];
			$type = isset($data['type']) && in_array($data['type'], ['active','archive']) ? $data['type'] : 'all';
			$query = isset($data['query']) && trim($data['query']) ? trim($data['query']) : '';
			$offset = isset($data['offset']) && is_numeric($data['offset']) ? $data['offset'] : 0;
			$mode = isset($data['mode']) && in_array($data['mode'], ['common','worker']) ? $data['mode'] : 'common';
			$limit = 10;
			// info
			$user = User::user_info(Session::$user_id);
			// where
			$where = [];
			$where[] = "company_id='".Session::$company_id."'";
			if ($type == 'active') $where[] = "(status='0' OR status='1')";
			if ($type == 'archive') $where[] = "status='2'";
			$where[] = "hidden<>'1'";
			$where = implode(" AND ", $where);
			// sort
			$sort = $user['sort_passports'] == 'asc' ? "ASC" : "DESC";
			// info
			$q = DB::query("SELECT passport_id, status, type, title, number, order_number, sections_all, sections_completed, annexes_all, produced, created FROM passports WHERE ".$where." ORDER BY passport_id ".$sort." LIMIT ".$offset.", ".$limit.";") or die (DB::error());
			while ($row = DB::fetch_row($q)) {
				$template = Passport_Template::passport_template_info($row['type']);
				$info[] = [
					'id'=>$row['passport_id'],
					'status_id'=>$row['status'],
					'status_title'=>self::passport_status($row['status']),
					'type_id'=>$template['id'],
					'type_title'=>$template['title'],
					'title'=>$row['title'],
					'number'=>$row['number'],
					'order_number'=>$row['order_number'],
					'sections_all'=>$row['sections_all'],
					'sections_completed'=>$row['sections_completed'],
					'annexes_all'=>$row['annexes_all'],
					'files'=>$mode == 'worker' ? Passport_File::worker_files_list($row['passport_id']) : [],
					'produced'=>$row['produced'] ? date('d.m.Y', ts_timezone($row['produced'], Session::$tz)) : 'не указано',
					'created'=>date('d.m.Y', ts_timezone($row['created'], Session::$tz))
				];
			}
			// paginator
			$q = DB::query("SELECT count(*) FROM passports WHERE ".$where.";") or die (DB::error());
			$count = ($row = DB::fetch_row($q)) ? $row['count(*)'] : 0;
			$paginator = paginator($count, $offset, $limit, 'passport.paginator', ['type'=>$type, 'query'=>$query, 'mode'=>$mode]);
			// output
			return ['info'=>$info, 'paginator'=>$paginator];
		}
		
		public static function passports_fetch($data = []) {
			// vars
			$offset_active = isset($data['offset_active']) && is_numeric($data['offset_active']) ? $data['offset_active'] : 0;
			$offset_archive = isset($data['offset_archive']) && is_numeric($data['offset_archive']) ? $data['offset_archive'] : 0;
			$mode = isset($data['mode']) && in_array($data['mode'], ['common','worker']) ? $data['mode'] : 'common';
			$tpl = $mode == 'worker' ? 'passports_worker_table.html' : 'passports_table.html';
			// info
			$active = self::passports_list(['type'=>'active', 'offset'=>$offset_active, 'mode'=>$mode]);
			$archive = self::passports_list(['type'=>'archive', 'offset'=>$offset_archive, 'mode'=>$mode]);
			// active
			HTML::assign('passports', $active['info']);
			HTML::assign('type', 'active');
			$passports_active = HTML::fetch('./partials/section/passports/'.$tpl);
			// archive
			HTML::assign('passports', $archive['info']);
			HTML::assign('type', 'archive');
			$passports_archive = HTML::fetch('./partials/section/passports/'.$tpl);
			// output
			return [
				'passports_active'=>$passports_active,
				'paginator_active'=>$active['paginator'],
				'passports_archive'=>$passports_archive,
				'paginator_archive'=>$archive['paginator']
			];
		}
		
		// ACTIONS
		
		public static function passport_edit_window($data) {
			// vars
			$passport_id = isset($data['passport_id']) && is_numeric($data['passport_id']) ? $data['passport_id'] : 0;
			// info
			$passport = self::passport_info($passport_id);
			$types = Passport_Template::passport_templates_list();
			// output
			HTML::assign('passport', $passport);
			HTML::assign('types', $types);
			return ['html'=>HTML::fetch('./partials/modal/passports/passport_edit.html')];
		}
		
		public static function passport_edit_update($data) {
			// vars
			$passport_id = isset($data['passport_id']) && is_numeric($data['passport_id']) ? $data['passport_id'] : 0;
			$title = isset($data['title']) && trim($data['title']) ? trim($data['title']) : '';
			$number = isset($data['number']) && trim($data['number']) ? trim($data['number']) : '';
			$order_number = isset($data['order_number']) && trim($data['order_number']) ? trim($data['order_number']) : '';
			$type = isset($data['type']) && is_numeric($data['type']) ? $data['type'] : 0;
			$produced = isset($data['produced']) && $data['produced'] ? strtotime($data['produced']) : 0;
			// update
			if ($passport_id) {
				DB::query("UPDATE passports SET title='".$title."', number='".$number."', order_number='".$order_number."', type='".$type."', produced='".$produced."' WHERE passport_id='".$passport_id."' LIMIT 1;") or die (DB::error());
			} else {
				// create
				DB::query("INSERT INTO passports (company_id, title, number, order_number, type, produced, created) VALUES ('".Session::$company_id."', '".$title."', '".$number."', '".$order_number."', '".$type."', '".$produced."', '".Session::$ts."');") or die (DB::error());
				$passport_id = DB::insert_id();
				// add sections
				if ($type) {
					$sections = Passport_Template_Section::passport_template_sections_list($type);
					foreach($sections as $section) {
						DB::query("INSERT INTO passport_sections (passport_id, template_section_id, number, title, created) VALUES ('".$passport_id."', '".$section['id']."', '".$section['number']."', '".$section['title']."', '".Session::$ts."');") or die (DB::error());
					}
					Passport_Section::passport_sections_count_update($passport_id);
				}
			}
			// output
			return self::passports_fetch();
		}
		
		public static function passport_sort($data) {
			// vars
			$sort = isset($data['sort']) && in_array($data['sort'], ['asc', 'dsc']) ? $data['sort'] : 'dsc';
			$new_sort = $sort == 'dsc' ? 1 : 0;
			// query
			DB::query("UPDATE users SET sort_passports='".$new_sort."' WHERE user_id='".Session::$user_id."' LIMIT 1;") or die (DB::error());
			// output
			return self::passports_fetch($data);
		}
		
		public static function passport_archive_toggle($data) {
			// vars
			$passport_id = isset($data['passport_id']) && is_numeric($data['passport_id']) ? $data['passport_id'] : 0;
			$status = isset($data['status']) && in_array($data['status'], [1,2]) ? $data['status'] : 0;
			// update
			DB::query("UPDATE passports SET status='".$status."' WHERE passport_id='".$passport_id."' LIMIT 1;") or die (DB::error());
			// output
			return self::passports_fetch();
		}
		
		public static function passport_delete_window($data) {
			// vars
			$passport_id = isset($data['passport_id']) && is_numeric($data['passport_id']) ? $data['passport_id'] : 0;
			// output
			HTML::assign('passport_id', $passport_id);
			return ['html'=>HTML::fetch('./partials/modal/passports/passport_delete.html')];
		}
		
		public static function passport_delete_update($data) {
			// vars
			$passport_id = isset($data['passport_id']) && is_numeric($data['passport_id']) ? $data['passport_id'] : 0;
			// query
			DB::query("UPDATE passports SET hidden='1' WHERE passport_id='".$passport_id."' LIMIT 1;") or die (DB::error());
			// output
			return self::passports_fetch();
		}
		
		// SERVICE
		
		private static function passport_status($id) {
			if ($id == 0) return 'Новый';
			if ($id == 1) return 'В работе';
			if ($id == 2) return 'Завершено';
			return '';
		}

	}
?>