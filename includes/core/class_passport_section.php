<?php
	class Passport_Section {
		
		// GENERAL
		
		public static function passport_section_info($data) {
			// vars
			$section_id = isset($data['section_id']) && is_numeric($data['section_id']) ? $data['section_id'] : 0;
			$passport_id = isset($data['passport_id']) && is_numeric($data['passport_id']) ? $data['passport_id'] : 0;
			$number = isset($data['number']) && is_numeric($data['number']) ? $data['number'] : 0;
			// default
			$def = [
				'id'=>0,
				'template_section_id'=>0,
				'number'=>0,
				'title'=>'',
				'sort'=>0,
				'created'=>''
			];
			// where
			if ($section_id) $where = "section_id='".$section_id."'";
			else if ($passport_id && $number) $where = "passport_id='".$passport_id."' AND number='".$number."'";
			else return $def;
			// info
			$q = DB::query("SELECT section_id, template_section_id, number, title, sort, created FROM passport_sections WHERE ".$where." LIMIT 1;") or die (DB::error());
			if ($row = DB::fetch_row($q)) {
				return [
					'id'=>$row['section_id'],
					'template_section_id'=>$row['template_section_id'],
					'number'=>$row['number'],
					'title'=>$row['title'],
					'sort'=>$row['sort'],
					'created'=>date('d.m.Y H:i', ts_timezone($row['created'], Session::$tz))
				];
			} else {
				return $def;
			}
		}
		
		public static function passport_sections_list($passport_id) {
			// vars
			$info = [];
			$number = 1;
			// where
			$where = [];
			$where[] = "passport_id='".$passport_id."'";
			$where[] = "hidden<>'1'";
			$where = implode(" AND ", $where);
			// info
			$q = DB::query("SELECT section_id, user_id, passport_id, status, title, sort, files, created FROM passport_sections WHERE ".$where." ORDER BY sort DESC;") or die (DB::error());
			while ($row = DB::fetch_row($q)) {
				$info[] = [
					'id'=>$row['section_id'],
					'passport_id'=>$row['passport_id'],
					'number'=>$number++,
					'title'=>$row['title'],
					'status_id'=>$row['status'],
					'status_title'=>self::passport_section_status($row['status']),
					'sort'=>$row['sort'],
					'files'=>$row['files'] ? $row['files'].' '.item_case($row['files'], ['файл', 'файла', 'файлов']) : '-',
					'owner'=>$row['user_id'] ? self::passport_section_owner($row['user_id']) : '-',
					'created'=>date('d.m.Y H:i', ts_timezone($row['created'], Session::$tz))
				];
			}
			// output
			return $info;
		}
		
		public static function passport_sections_fetch($data = []) {
			// vars
			$passport_id = isset($data['passport_id']) && is_numeric($data['passport_id']) ? $data['passport_id'] : 0;
			// info
			$passport_sections = self::passport_sections_list($passport_id);
			// output
			HTML::assign('passport_id', $passport_id);
			HTML::assign('passport_sections', $passport_sections);
			return ['html'=>HTML::fetch('./partials/section/passports/passport_sections_table.html')];
		}
		
		public static function passport_sections_count_update($passport_id) {
			// sections all
			$q = DB::query("SELECT count(*) FROM passport_sections WHERE passport_id='".$passport_id."' AND hidden<>'1';") or die (DB::error());
			$sections_all = ($row = DB::fetch_row($q)) ? $row['count(*)'] : 0;
			// sections completed
			$q = DB::query("SELECT count(*) FROM passport_sections WHERE passport_id='".$passport_id."' AND hidden<>'1' AND (status='1' OR status='2');") or die (DB::error());
			$sections_completed = ($row = DB::fetch_row($q)) ? $row['count(*)'] : 0;
			// update
			DB::query("UPDATE passports SET sections_all='".$sections_all."', sections_completed='".$sections_completed."' WHERE passport_id='".$passport_id."' LIMIT 1;") or die (DB::error());
		}
		
		// ACTIONS
		
		public static function passport_section_edit_window($data) {
			// vars
			$passport_id = isset($data['passport_id']) && is_numeric($data['passport_id']) ? $data['passport_id'] : 0;
			$section_id = isset($data['section_id']) && is_numeric($data['section_id']) ? $data['section_id'] : 0;
			$mode = isset($data['mode']) && in_array($data['mode'], ['list', 'detail']) ? $data['mode'] : 'list';
			// info
			$passport_section = self::passport_section_info(['section_id'=>$section_id]);
			// output
			HTML::assign('passport_id', $passport_id);
			HTML::assign('passport_section', $passport_section);
			HTML::assign('mode', $mode);
			return ['html'=>HTML::fetch('./partials/modal/passports/passport_section_edit.html')];
		}
		
		public static function passport_section_edit_update($data) {
			// vars
			$passport_id = isset($data['passport_id']) && is_numeric($data['passport_id']) ? $data['passport_id'] : 0;
			$section_id = isset($data['section_id']) && is_numeric($data['section_id']) ? $data['section_id'] : 0;
			$title = isset($data['title']) && trim($data['title']) ? trim($data['title']) : '';
			// update
			if ($section_id) {
				DB::query("UPDATE passport_sections SET title='".$title."' WHERE section_id='".$section_id."' LIMIT 1;") or die (DB::error());
			} else {
				DB::query("INSERT INTO passport_sections (passport_id, title, created) VALUES ('".$passport_id."', '".$title."', '".Session::$ts."');") or die (DB::error());
				$section_id = DB::insert_id();
			}
			// count update
			self::passport_sections_count_update($passport_id);
			// output
			return self::passport_sections_fetch($data);
		}
		
		public static function passport_section_archive_toggle($data) {
			// vars
			$passport_id = isset($data['passport_id']) && is_numeric($data['passport_id']) ? $data['passport_id'] : 0;
			$section_id = isset($data['section_id']) && is_numeric($data['section_id']) ? $data['section_id'] : 0;
			$mode = isset($data['mode']) && in_array($data['mode'], ['list', 'detail']) ? $data['mode'] : 'list';
			$status = isset($data['status']) && in_array($data['status'], ['close','open']) ? $data['status'] : 'open';
			$new_status = $status == 'open' ? 1 : 2;
			// update
			DB::query("UPDATE passport_sections SET status='".$new_status."' WHERE section_id='".$section_id."' LIMIT 1;") or die (DB::error());
			// count update
			self::passport_sections_count_update($passport_id);
			// output
			return self::passport_sections_fetch($data);
		}
		
		public static function passport_section_delete_window($data) {
			// vars
			$passport_id = isset($data['passport_id']) && is_numeric($data['passport_id']) ? $data['passport_id'] : 0;
			$section_id = isset($data['section_id']) && is_numeric($data['section_id']) ? $data['section_id'] : 0;
			// output
			HTML::assign('passport_id', $passport_id);
			HTML::assign('section_id', $section_id);
			return ['html'=>HTML::fetch('./partials/modal/passports/passport_section_delete.html')];
		}
		
		public static function passport_section_delete_update($data) {
			// vars
			$passport_id = isset($data['passport_id']) && is_numeric($data['passport_id']) ? $data['passport_id'] : 0;
			$section_id = isset($data['section_id']) && is_numeric($data['section_id']) ? $data['section_id'] : 0;
			// query
			DB::query("UPDATE passport_sections SET hidden='1' WHERE section_id='".$section_id."' LIMIT 1;") or die (DB::error());
			// count update
			self::passport_sections_count_update($passport_id);
			// output
			return self::passport_sections_fetch($data);
		}
		
		// SERVICE
		
		private static function passport_section_status($id) {
			if ($id == 0) return 'Не заполнен';
			if ($id == 1) return 'В работе';
			if ($id == 2) return 'Завершен';
			return '';
		}
		
		private static function passport_section_owner($user_id) {
			$user = User::user_info($user_id);
			$title = $user['last_name'].' '.mb_substr($user['first_name'], 0, 1, 'UTF8').'.'.' '.mb_substr($user['middle_name'], 0, 1, 'UTF8').'.';
			return $title;
		}

	}
?>