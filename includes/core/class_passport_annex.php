<?php
	class Passport_Annex {
		
		// GENERAL
		
		public static function passport_annex_info($annex_id) {
			// info
			$q = DB::query("SELECT annex_id, number, title, sort, created FROM passport_annexes WHERE annex_id='".$annex_id."' LIMIT 1;") or die (DB::error());
			if ($row = DB::fetch_row($q)) {
				return [
					'id'=>$row['annex_id'],
					'number'=>$row['number'],
					'title'=>$row['title'],
					'sort'=>$row['sort'],
					'created'=>date('d.m.Y H:i', ts_timezone($row['created'], Session::$tz))
				];
			} else {
				return [
					'id'=>0,
					'number'=>0,
					'title'=>'',
					'sort'=>0,
					'created'=>''
				];
			}
		}
		
		public static function passport_annexes_list($passport_id) {
			// vars
			$info = [];
			$number = 1;
			// where
			$where = [];
			$where[] = "passport_id='".$passport_id."'";
			$where[] = "hidden<>'1'";
			$where = implode(" AND ", $where);
			// info
			$q = DB::query("SELECT annex_id, user_id, passport_id, status, title, sort, files, created FROM passport_annexes WHERE ".$where." ORDER BY sort DESC;") or die (DB::error());
			while ($row = DB::fetch_row($q)) {
				$info[] = [
					'id'=>$row['annex_id'],
					'passport_id'=>$row['passport_id'],
					'number'=>$number++,
					'title'=>$row['title'],
					'status_id'=>$row['status'],
					'status_title'=>self::passport_annex_status($row['status']),
					'sort'=>$row['sort'],
					'files'=>$row['files'] ? $row['files'].' '.item_case($row['files'], ['файл', 'файла', 'файлов']) : '-',
					'owner'=>$row['user_id'] ? self::passport_annex_owner($row['user_id']) : '-',
					'created'=>date('d.m.Y H:i', ts_timezone($row['created'], Session::$tz))
				];
			}
			// output
			return $info;
		}
		
		public static function passport_annexes_fetch($data = []) {
			// vars
			$passport_id = isset($data['passport_id']) && is_numeric($data['passport_id']) ? $data['passport_id'] : 0;
			// info
			$passport_annexes = self::passport_annexes_list($passport_id);
			// output
			HTML::assign('passport_id', $passport_id);
			HTML::assign('passport_annexes', $passport_annexes);
			return ['html'=>HTML::fetch('./partials/section/passports/passport_annexes_table.html')];
		}
		
		public static function passport_annexes_count_update($passport_id) {
			// annexes all
			$q = DB::query("SELECT count(*) FROM passport_annexes WHERE passport_id='".$passport_id."' AND hidden<>'1';") or die (DB::error());
			$annexes_all = ($row = DB::fetch_row($q)) ? $row['count(*)'] : 0;
			// update
			DB::query("UPDATE passports SET annexes_all='".$annexes_all."' WHERE passport_id='".$passport_id."' LIMIT 1;") or die (DB::error());
		}
		
		// ACTIONS
		
		public static function passport_annex_edit_window($data) {
			// vars
			$passport_id = isset($data['passport_id']) && is_numeric($data['passport_id']) ? $data['passport_id'] : 0;
			$annex_id = isset($data['annex_id']) && is_numeric($data['annex_id']) ? $data['annex_id'] : 0;
			$mode = isset($data['mode']) && in_array($data['mode'], ['list', 'detail'])  ? $data['mode'] : 'list';
			// info
			$passport_annex = self::passport_annex_info($annex_id);
			// output
			HTML::assign('passport_id', $passport_id);
			HTML::assign('passport_annex', $passport_annex);
			HTML::assign('mode', $mode);
			return ['html'=>HTML::fetch('./partials/modal/passports/passport_annex_edit.html')];
		}
		
		public static function passport_annex_edit_update($data) {
			// vars
			$passport_id = isset($data['passport_id']) && is_numeric($data['passport_id']) ? $data['passport_id'] : 0;
			$annex_id = isset($data['annex_id']) && is_numeric($data['annex_id']) ? $data['annex_id'] : 0;
			$title = isset($data['title']) && trim($data['title']) ? trim($data['title']) : '';
			// update
			if ($annex_id) {
				DB::query("UPDATE passport_annexes SET title='".$title."' WHERE annex_id='".$annex_id."' LIMIT 1;") or die (DB::error());
			} else {
				DB::query("INSERT INTO passport_annexes (passport_id, title, created) VALUES ('".$passport_id."', '".$title."', '".Session::$ts."');") or die (DB::error());
				$annex_id = DB::insert_id();
			}
			// count update
			self::passport_annexes_count_update($passport_id);
			// output
			return self::passport_annexes_fetch($data);
		}
		
		public static function passport_annex_archive_toggle($data) {
			// vars
			$passport_id = isset($data['passport_id']) && is_numeric($data['passport_id']) ? $data['passport_id'] : 0;
			$annex_id = isset($data['annex_id']) && is_numeric($data['annex_id']) ? $data['annex_id'] : 0;
			$mode = isset($data['mode']) && in_array($data['mode'], ['list', 'detail']) ? $data['mode'] : 'list';
			$status = isset($data['status']) && in_array($data['status'], ['close','open']) ? $data['status'] : 'open';
			$new_status = $status == 'open' ? 1 : 2;
			// update
			DB::query("UPDATE passport_annexes SET status='".$new_status."' WHERE annex_id='".$annex_id."' LIMIT 1;") or die (DB::error());
			// count update
			self::passport_annexes_count_update($passport_id);
			// output
			return self::passport_annexes_fetch($data);
		}
		
		public static function passport_annex_delete_window($data) {
			// vars
			$passport_id = isset($data['passport_id']) && is_numeric($data['passport_id']) ? $data['passport_id'] : 0;
			$annex_id = isset($data['annex_id']) && is_numeric($data['annex_id']) ? $data['annex_id'] : 0;
			// output
			HTML::assign('passport_id', $passport_id);
			HTML::assign('annex_id', $annex_id);
			return ['html'=>HTML::fetch('./partials/modal/passports/passport_annex_delete.html')];
		}
		
		public static function passport_annex_delete_update($data) {
			// vars
			$passport_id = isset($data['passport_id']) && is_numeric($data['passport_id']) ? $data['passport_id'] : 0;
			$annex_id = isset($data['annex_id']) && is_numeric($data['annex_id']) ? $data['annex_id'] : 0;
			// query
			DB::query("UPDATE passport_annexes SET hidden='1' WHERE annex_id='".$annex_id."' LIMIT 1;") or die (DB::error());
			// count update
			self::passport_annexes_count_update($passport_id);
			// output
			return self::passport_annexes_fetch($data);
		}
		
		// SERVICE
		
		private static function passport_annex_status($id) {
			if ($id == 0) return 'Не заполнен';
			if ($id == 1) return 'В работе';
			if ($id == 2) return 'Завершен';
			return '';
		}
		
		private static function passport_annex_owner($user_id) {
			$user = User::user_info($user_id);
			$title = $user['last_name'].' '.mb_substr($user['first_name'], 0, 1, 'UTF8').'.'.' '.mb_substr($user['middle_name'], 0, 1, 'UTF8').'.';
			return $title;
		}

	}
?>