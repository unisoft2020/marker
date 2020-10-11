<?php
	class Passport_File {
		
		// GENERAL
		
		public static function file_info($file_id) {
			// info
			$q = DB::query("SELECT file_id, user_id, passport_id, template_id, sub_id, sub_type, sub_number, type, path, title, size, created FROM passport_files WHERE file_id='".$file_id."' LIMIT 1;") or die (DB::error());
			if ($row = DB::fetch_row($q)) {
				return [
					'id'=>$row['file_id'],
					'user_id'=>$row['user_id'],
					'passport_id'=>$row['passport_id'],
					'template_id'=>$row['template_id'],
					'sub_id'=>$row['sub_id'],
					'sub_type'=>$row['sub_type'],
					'sub_number'=>$row['sub_number'],
					'type'=>$row['type'],
					'path'=>SITE_SCHEME.'://'.SITE_DOMAIN.$row['path'],
					'title'=>flt_output($row['title']),
					'size'=>(string) round($row['size'] / 1048576, 2),
					'size_raw'=>$row['size'],
					'created'=>date('d.m.Y H:i', ts_timezone($row['created'], Session::$tz))
				];
			} else {
				return [
					'id'=>0,
					'user_id'=>0,
					'passport_id'=>0,
					'template_id'=>0,
					'sub_id'=>0,
					'sub_type'=>0,
					'sub_number'=>0,
					'type'=>0,
					'path'=>'',
					'title'=>'',
					'size'=>'',
					'size_raw'=>0,
					'created'=>''
				];
			}
		}
		
		public static function files_list($data) {
			// vars
			$passport_id = isset($data['passport_id']) && is_numeric($data['passport_id']) ? $data['passport_id'] : 0;
			$sub_id = isset($data['sub_id']) && is_numeric($data['sub_id']) ? $data['sub_id'] : 0;
			$sub_type = isset($data['sub_type']) && is_numeric($data['sub_type']) ? $data['sub_type'] : 0;
			$status = isset($data['status']) && is_numeric($data['status']) ? $data['status'] : 0;
			$info = [];
			$number = 1;
			// where
			$where = [];
			$where[] = "passport_id='".$passport_id."'";
			if ($sub_id) $where[] = "sub_id='".$sub_id."'";
			if ($sub_type) $where[] = "sub_type='".$sub_type."'";
			if ($status) $where[] = "status='".$status."'";
			$where[] = "hidden<>'1'";
			$where = implode(" AND ", $where);
			// order by
			$order_by = $sub_id ? "file_id DESC" : "sub_type, sub_id, file_id DESC";
			// query
			$q = DB::query("SELECT file_id, user_id, sub_id, sub_type, sub_number, type, path, title, size, status, created FROM passport_files WHERE ".$where." ORDER BY ".$order_by.";") or die (DB::error());
			while ($row = DB::fetch_row($q)) {
				// sub
				if ($row['sub_type'] == 2) {
					$sub = Passport_Annex::passport_annex_info($row['sub_id']);
					$sub_title = $sub['title'];
				} else $sub_title = '';
				// owner
				$owner = User::user_info($row['user_id']);
				// info
				$info[] = [
					'id'=>$row['file_id'],
					'number'=>$number++,
					'sub_id'=>$row['sub_id'],
					'sub_type'=>$row['sub_type'],
					'sub_number'=>$row['sub_number'],
					'sub_title'=>self::file_sub_title($row['sub_type'], $row['sub_number'], $sub_title),
					'type'=>self::file_type($row['type']),
					'path'=>SITE_SCHEME.'://'.SITE_DOMAIN.$row['path'],
					'title'=>flt_output($row['title']),
					'size'=>(string) round($row['size'] / 1048576, 2),
					'size_raw'=>$row['size'],
					'owner_name'=>$owner['last_name'].' '.mb_substr($owner['first_name'], 0, 1, 'UTF8').'.'.' '.mb_substr($owner['middle_name'], 0, 1, 'UTF8').'.',
					'owner_phone'=>$owner['phone'] ? $owner['phone'] : 'не указан',
					'owner_email'=>$owner['email'],
					'owner_occupation'=>$owner['occupation'],
					'owner_groups'=>User_Group::user_group_list_str($row['user_id']),
					'status_id'=>$row['status'],
					'status_title'=>self::file_status($row['status']),
					'created'=>date('d.m.y в H:i', ts_timezone($row['created'], Session::$tz))
				];
			}
			// output
			return $info;
		}
		
		public static function files_full_list($data) {
			// vars
			$passport_id = isset($data['passport_id']) && is_numeric($data['passport_id']) ? $data['passport_id'] : 0;
			// info
			$files = self::files_list(['passport_id'=>$passport_id]);
			// output
			HTML::assign('files', $files);
			return ['html'=>HTML::fetch('./partials/modal/passports/passport_files.html')];
		}
		
		public static function files_count_update($passport_id, $sub_id, $sub_type) {
			// count
			$q = DB::query("SELECT count(*) FROM passport_files WHERE passport_id='".$passport_id."' AND sub_id='".$sub_id."' AND sub_type='".$sub_type."' AND hidden<>'1';") or die (DB::error());
			$count = ($row = DB::fetch_row($q)) ? $row['count(*)'] : 0;
			$status = $count ? 1 : 0;
			// update (subs)
			if ($sub_type == 1) DB::query("UPDATE passport_sections SET user_id='".Session::$user_id."', status='".$status."', files='".$count."' WHERE section_id='".$sub_id."' LIMIT 1;") or die (DB::error());
			if ($sub_type == 2) DB::query("UPDATE passport_annexes SET user_id='".Session::$user_id."', status='".$status."', files='".$count."' WHERE annex_id='".$sub_id."' LIMIT 1;") or die (DB::error());
			// update (counts)
			if ($sub_type == 1) Passport_Section::passport_sections_count_update($passport_id);
			// update (passport)
			DB::query("UPDATE passports SET status='1' WHERE passport_id='".$passport_id."' LIMIT 1;") or die (DB::error());
		}
		
		// ACTIONS

		public static function file_upload($data, $files) {
			// vars
			$names = [$files['name']];
			$paths = [$files['tmp_name']];
			$sizes = [$files['size']];
			$types = [$files['type']];
			$passport_id = isset($data['passport_id']) && is_numeric($data['passport_id']) ? $data['passport_id'] : 0;
			$template_id = isset($data['template_id']) && is_numeric($data['template_id']) ? $data['template_id'] : 0;
			$file_id = isset($data['file_id']) && is_numeric($data['file_id']) ? $data['file_id'] : 0;
			$sub_id = isset($data['sub_id']) && is_numeric($data['sub_id']) ? $data['sub_id'] : 0;
			$sub_type = isset($data['sub_type']) && is_numeric($data['sub_type']) ? $data['sub_type'] : 0;
			$sub_number = isset($data['sub_number']) && is_numeric($data['sub_number']) ? $data['sub_number'] : 0;
			$mode = isset($data['mode']) && in_array($data['mode'], ['common', 'worker']) ? $data['mode'] : 'common';
			$max_size = 100;
			$error = '';
			// section
			if ($sub_type == 1) {
				$section = Passport_Section::passport_section_info(['section_id'=>$sub_id, 'passport_id'=>$passport_id, 'number'=>$sub_number]);
				if (!$sub_id) $sub_id = $section['id'];
				if (!$sub_number) $sub_number = $section['number'];
			}
			// parse
			for($i = 0, $count = count($names); $i < $count; $i++) {
				// vars
				$size_raw = $sizes[$i];
				$size = $size_raw / 1048576;
				$title = preg_replace('~(\.[a-z]+)$~iu', '', $names[$i]);
				$mime_type = $types[$i];
				$file_extension = preg_replace('/^.*\.(.*)$/U', '$1', $names[$i]);
				// template
				if ($template_id) {
					$template = self::worker_template_file_info($template_id);
					$title = $template['title'];
				}
				// path
				$d = ts_explode(Session::$ts);
				$path = '/servers/media/storage/'.$d['year'].'/'.$d['month'].$d['day'].'/';
				if (!is_dir('.'.$path)) mkdir('.'.$path, 0777, true);
				// validate (size)
				$val_size = $size <= $max_size ? true : false;
				// validate (type)
				$val_type = 0;
				if (preg_match('~^application/pdf$~i', $mime_type)) $val_type = 1;
				if (preg_match('~^application/(msword|vnd\.openxmlformats\-officedocument\.wordprocessingml\.document)$~i', $mime_type)) $val_type = 2;
				if (preg_match('~^application/(vnd\.ms\-excel|vnd\.openxmlformats\-officedocument\.spreadsheetml\.sheet)~i', $mime_type)) $val_type = 3;
				if (preg_match('~^text/plain$~i', $mime_type)) $val_type = 4;
				if (preg_match('~^image/jpeg$~i', $mime_type)) $val_type = 5;
				if (preg_match('~^image/png$~i', $mime_type)) $val_type = 6;
				if (preg_match('~^image/bmp$~i', $mime_type)) $val_type = 7;
				if (preg_match('~^image/gif$~i', $mime_type)) $val_type = 8;
				if (preg_match('~^image/webp$~i', $mime_type)) $val_type = 9;
				if (preg_match('~^application/octet-stream$~i', $mime_type) && $file_extension == 'cdw') $val_type = 10;
				if (preg_match('~^application/octet-stream$~i', $mime_type) && $file_extension == 'spw') $val_type = 11;
				if (preg_match('~^application/octet-stream$~i', $mime_type) && $file_extension == 'frm') $val_type = 12;
				if (preg_match('~^application/octet-stream$~i', $mime_type) && $file_extension == 'dwg') $val_type = 13;
				// upload
				if ($val_size && $val_type) {
					// file
					$ext = explode('.', $names[$i]);
					$ext = end($ext);
					$file = get_random_filename('.'.$path, $ext);
					move_uploaded_file($paths[$i], '.'.$path.$file);
					// retry
					if ($file_id) DB::query("UPDATE passport_files SET hidden='1' WHERE file_id='".$file_id."' LIMIT 1;") or die (DB::error());
					// update
					DB::query("INSERT INTO passport_files (user_id, passport_id, template_id, sub_id, sub_type, sub_number, type, path, title, size, created) VALUES ('".Session::$user_id."', '".$passport_id."', '".$template_id."', '".$sub_id."', '".$sub_type."', '".$sub_number."', '".$val_type."', '".$path.$file."', '".$title."', '".$size_raw."', '".Session::$ts."');") or die (DB::error());
					Company::company_size_update($size_raw);
					// count
					self::files_count_update($passport_id, $sub_id, $sub_type);
				} else {
					if (!$val_size) $str = 'Один или несколько файлов не были загружены, так как их размер больше максимально допустимого ('.$max_size.' Mb). Попробуйте загрузить файлы меньшего размера.';
					if (!$val_type) $str = 'Один или несколько файлов не были загружены, так как их формат не поддерживается.';
					HTML::assign('error', $str);
					$error = HTML::fetch('./partials/modal/error.html');
				}
			}
			// notifications
			if ($mode == 'worker') {
				$q = DB::query("SELECT user_id FROM user_groups WHERE company_id='".Session::$company_id."' AND group_id='1';") or die (DB::error());
				while ($row = DB::fetch_row($q)) {
					$passport = Passport::passport_info($passport_id);
					$user = User::user_info(Session::$user_id);
					Notification::notification_create($row['user_id'], 'Загружен новый документ', 'Пользователем '.$user['full_name'].' ('.$user['group_title'].') был загружен новый документ в паспорт изделия "'.$passport['title'].'" в раздел '.$sub_number.'.');
				}
			}
			// output
			if ($mode == 'worker') {
				HTML::assign('passport_id', $passport_id);
				HTML::assign('files', self::worker_files_list($passport_id));
			} else {
				HTML::assign('files', self::files_list(['passport_id'=>$passport_id, 'sub_id'=>$sub_id, 'sub_type'=>$sub_type]));
			}
			$tpl = $mode == 'worker' ? 'passports_worker_files_table.html' : 'passport_sub_files_table.html';
			return ['html'=>HTML::fetch('./partials/section/passports/'.$tpl), 'mode'=>$mode, 'passport_id'=>$passport_id, 'error'=>$error];
		}
		
		public static function file_edit_window($data) {
			// vars
			$file_id = isset($data['file_id']) && is_numeric($data['file_id']) ? $data['file_id'] : 0;
			$mode = isset($data['mode']) && in_array($data['mode'], ['list', 'sub', 'worker']) ? $data['mode'] : 'sub';
			// info
			$file = self::file_info($file_id);
			$template = Passport_Template_File::passport_template_file_info($file['template_id']);
			$file['group_title'] = $template['group_title'];
			$group_id = $template['group_id'];
			if (!$group_id) {
				$group = User_Group::user_group_info(['user_id'=>Session::$user_id]);
				$group_id = $group['group_id'];
			}
			$sections = Passport_Template_File::passport_template_files_list($mode, $group_id);
			// output
			HTML::assign('file', $file);
			HTML::assign('sections', $sections);
			HTML::assign('mode', $mode);
			return ['html'=>HTML::fetch('./partials/modal/passports/passport_file_edit.html')];
		}
		
		public static function file_edit_update($data) {
			// vars
			$file_id = isset($data['file_id']) && is_numeric($data['file_id']) ? $data['file_id'] : 0;
			$title = isset($data['title']) && trim($data['title']) ? trim($data['title']) : '';
			$template_id = isset($data['template_id']) && is_numeric($data['template_id']) ? $data['template_id'] : 0;
			$mode = isset($data['mode']) && in_array($data['mode'], ['list', 'sub', 'worker']) ? $data['mode'] : 'sub';
			// info
			$file = self::file_info($file_id);
			$template = Passport_Template_File::passport_template_file_info($template_id);
			$section = Passport_Section::passport_section_info(['passport_id'=>$file['passport_id'], 'number'=>$template['sub_number']]);
			$user_id = self::file_owner_change($file['template_id'], $template_id);
			// update
			DB::query("UPDATE passport_files SET user_id='".$user_id."', template_id='".$template_id."', sub_id='".$section['id']."', sub_number='".$template['sub_number']."', title='".$title."' WHERE file_id='".$file_id."' LIMIT 1;") or die (DB::error());
			// output
			if ($mode == 'sub') {
				$file = self::file_info($file_id);
				HTML::assign('files', self::files_list(['passport_id'=>$file['passport_id'], 'sub_id'=>$file['sub_id'], 'sub_type'=>$file['sub_type']]));
				return ['html'=>HTML::fetch('./partials/section/passports/passport_sub_files_table.html')];
			} else if ($mode == 'worker') {
				HTML::assign('passport_id', $file['passport_id']);
				HTML::assign('files', self::worker_files_list($file['passport_id']));
				return ['html'=>HTML::fetch('./partials/section/passports/passports_worker_files_table.html'), 'passport_id'=>$file['passport_id']];
			} else {
				HTML::assign('files', self::files_list(['passport_id'=>$file['passport_id']]));
				return ['html'=>HTML::fetch('./partials/section/passports/passport_files_table.html')];
			}
		}
		
		public static function file_status_update($data) {
			// vars
			$file_id = isset($data['file_id']) && is_numeric($data['file_id']) ? $data['file_id'] : 0;
			$status = isset($data['status']) && in_array($data['status'], [0,1,2]) ? $data['status'] : 0;
			// info
			$file = self::file_info($file_id);
			$passport = Passport::passport_info($file['passport_id']);
			// update
			if ($status == 2) {
				HTML::assign('file', $file);
				return ['html'=>HTML::fetch('./partials/modal/passports/passport_file_revision.html')];
			} else {
				// query
				DB::query("UPDATE passport_files SET status='".$status."' WHERE file_id='".$file_id."' LIMIT 1;") or die (DB::error());
				// notification
				if ($status == 1) Notification::notification_create($file['user_id'], 'Документ принят', 'Ранее загруженный вами файл паспорта изделия "'.$passport['title'].'" в разделе '.$file['sub_number'].' принят специалистом паспортной группы.');
				// output
				HTML::assign('files', self::files_list(['passport_id'=>$file['passport_id']]));
				return ['html'=>HTML::fetch('./partials/section/passports/passport_files_table.html')];
			}
		}
		
		public static function file_revision($data) {
			// vars
			$file_id = isset($data['file_id']) && is_numeric($data['file_id']) ? $data['file_id'] : 0;
			$note = isset($data['note']) && trim($data['note']) ? trim($data['note']) : '';
			// info
			$file = self::file_info($file_id);
			$passport = Passport::passport_info($file['passport_id']);
			// update
			DB::query("UPDATE passport_files SET status='2', note='".$note."' WHERE file_id='".$file_id."' LIMIT 1;") or die (DB::error());
			// notification
			Notification::notification_create($file['user_id'], 'Документ отправлен на доработку', 'Ранее загруженный вами файл паспорта изделия "'.$passport['title'].'" в разделе '.$file['sub_number'].' отправлен на доработку специалистом паспортной группы. Причина отклонения файла: "'.$note.'".');
			// output
			HTML::assign('files', self::files_list(['passport_id'=>$file['passport_id']]));
			return ['html'=>HTML::fetch('./partials/section/passports/passport_files_table.html')];
		}

		public static function file_delete($data) {
			// vars
			$file_id = isset($data['file_id']) && is_numeric($data['file_id']) ? $data['file_id'] : 0;
			$mode = isset($data['mode']) && in_array($data['mode'], ['list', 'sub', 'worker']) ? $data['mode'] : 'sub';
			// info
			$file = self::file_info($file_id);
			// query
			DB::query("UPDATE passport_files SET hidden='1' WHERE file_id='".$file_id."' LIMIT 1;") or die (DB::error());
			// count
			self::files_count_update($file['passport_id'], $file['sub_id'], $file['sub_type']);
			// output
			if ($mode == 'sub') {
				HTML::assign('files', self::files_list(['passport_id'=>$file['passport_id'], 'sub_id'=>$file['sub_id'], 'sub_type'=>$file['sub_type']]));
				return ['html'=>HTML::fetch('./partials/section/passports/passport_sub_files_table.html')];
			} else if ($mode == 'worker') {
				// notifications
				$q = DB::query("SELECT user_id FROM user_groups WHERE company_id='".Session::$company_id."' AND group_id='1';") or die (DB::error());
				while ($row = DB::fetch_row($q)) {
					$passport = Passport::passport_info($file['passport_id']);
					$user = User::user_info(Session::$user_id);
					Notification::notification_create($row['user_id'], 'Документ был удален', 'Ранее загруженный документ документ был удален пользователем '.$user['full_name'].' ('.$user['group_title'].') из паспорта изделия "'.$passport['title'].'" в разделе '.$file['sub_number'].'.');
				}
				// output
				HTML::assign('passport_id', $file['passport_id']);
				HTML::assign('files', self::worker_files_list($file['passport_id']));
				return ['html'=>HTML::fetch('./partials/section/passports/passports_worker_files_table.html'), 'passport_id'=>$file['passport_id']];
			} else {
				HTML::assign('files', self::files_list(['passport_id'=>$file['passport_id']]));
				return ['html'=>HTML::fetch('./partials/section/passports/passport_files_table.html')];
			}
		}
		
		// WORKER
		
		public static function worker_template_file_info($id) {
			$q = DB::query("SELECT id, sub_number, title FROM passport_template_files WHERE id='".$id."' LIMIT 1;") or die (DB::error());
			if ($row = DB::fetch_row($q)) {
				return [
					'template_id'=>$row['id'],
					'number'=>$row['sub_number'],
					'title'=>$row['title']
				];
			} else {
				return [
					'template_id'=>0,
					'number'=>0,
					'title'=>''
				];
			}
		}
		
		public static function worker_files_list($passport_id) {
			// vars
			$group_ids = Session::$user_groups;
			$files_all = [];
			$files_not_upload = [];
			$files_upload = [];
			$files_extra = [];
			$template_ids = [];
			// files (all)
			foreach($group_ids as $group_id) {
				$info = self::worker_template_files_list($group_id);
				$files_all = array_merge($files_all, $info);
			}
			// files (extra)
			$file_extra = self::worker_extra_files_list($passport_id);
			// files (upload)
			$files_upload_raw = self::worker_upload_files_list($passport_id);
			// match (not upload)
			foreach($files_all as $a) {
				if (!isset($files_upload_raw[$a['template_id']])) $files_not_upload[] = $a;
				if (in_array($a['group_id'], $group_ids)) $template_ids[] = $a['template_id'];
			}
			// match (upload)
			foreach($files_upload_raw as $a) {
				if (in_array($a['template_id'], $template_ids)) $files_upload[] = $a;
			}
			// output
			return ['all'=>$files_all, 'upload'=>$files_upload, 'not_upload'=>$files_not_upload, 'extra'=>$file_extra];
		}
		
		public static function worker_template_files_list($group_id) {
			// vars
			$info = [];
			// query
			$q = DB::query("SELECT id, group_id, sub_number, title FROM passport_template_files WHERE group_id='".$group_id."' ORDER BY sub_number;") or die (DB::error());
			while ($row = DB::fetch_row($q)) {
				$info[] = [
					'template_id'=>$row['id'],
					'group_id'=>$row['group_id'],
					'number'=>$row['sub_number'],
					'title'=>$row['title']
				];
			}
			// output
			return $info;
		}
		
		public static function worker_extra_files_list($passport_id) {
			// vars
			$info = [];
			// query
			$q = DB::query("SELECT file_id, passport_id, template_id, sub_number, path, size, title, note, status, created FROM passport_files WHERE passport_id='".$passport_id."' AND template_id='0' AND hidden<>'1' ORDER BY title;") or die (DB::error());
			while ($row = DB::fetch_row($q)) {
				$info[] = [
					'id'=>$row['file_id'],
					'passport_id'=>$row['passport_id'],
					'template_id'=>$row['template_id'],
					'number'=>$row['sub_number'],
					'title'=>$row['title'],
					'path'=>$row['path'],
					'size'=>(string) round($row['size'] / 1048576, 2),
					'note'=>$row['note'],
					'created'=>date('d.m.y в H:i', ts_timezone($row['created'], Session::$tz))
				];
			}
			// output
			return $info;
		}
		
		public static function worker_upload_files_list($passport_id) {
			// vars
			$info = [];
			// query
			$q = DB::query("SELECT file_id, passport_id, template_id, sub_number, path, size, title, note, status, created FROM passport_files WHERE passport_id='".$passport_id."' AND template_id!='0' AND hidden<>'1' ORDER BY sub_number;") or die (DB::error());
			while ($row = DB::fetch_row($q)) {
				// common
				$template_id = $row['template_id'];
				if (!isset($info[$template_id])) $info[$template_id] = [
					'template_id'=>$row['template_id'],
					'number'=>$row['sub_number'],
					'title'=>$row['title'],
					'status_id'=>$row['status'],
					'status_title'=>self::file_status($row['status']),
					'items'=>[]
				];
				// status
				if (in_array($info[$template_id]['status_id'], [0,1]) && $row['status'] == 2) {
					$info[$template_id]['status_id'] = $row['status'];
					$info[$template_id]['status_title'] = self::file_status($row['status']);
				}
				if ($info[$template_id]['status_id'] == 1 && $row['status'] == 0) {
					$info[$template_id]['status_id'] = $row['status'];
					$info[$template_id]['status_title'] = self::file_status($row['status']);
				}
				// items
				$info[$template_id]['items'][] = [
					'id'=>$row['file_id'],
					'status_id'=>$row['status'],
					'status_title'=>self::file_status($row['status']),
					'path'=>$row['path'],
					'size'=>(string) round($row['size'] / 1048576, 2),
					'note'=>$row['note'],
					'created'=>date('d.m.y в H:i', ts_timezone($row['created'], Session::$tz))
				];
			}
			// output
			return $info;
		}
		
		// SERVICE
		
		private static function file_owner_change($template_old_id, $template_new_id) {
			// info
			$template_old = Passport_Template_File::passport_template_file_info($template_old_id);
			$template_new = Passport_Template_File::passport_template_file_info($template_new_id);
			// update
			if ($template_old['group_id'] != $template_new['group_id']) {
				$group = User_Group::user_group_info(['group_id'=>$template_new['group_id']]);
				return $group['user_id'];
			} else {
				$group = User_Group::user_group_info(['group_id'=>$template_old['group_id']]);
				return $group['user_id'];
			}
		}
		
		private static function file_type($id) {
			if ($id == 1) return 'PDF-файл';
			if ($id == 2) return 'MS Word';
			if ($id == 3) return 'MS Excel';
			if ($id == 4) return 'Текстовый';
			if ($id == 5) return 'JPEG / изображение';
			if ($id == 6) return 'PNG / изображение';
			if ($id == 7) return 'BMP / изображение';
			if ($id == 8) return 'GIF / изображение';
			if ($id == 9) return 'WEBP / изображение';
			if ($id == 10) return 'CDW / чертеж Компас';
			if ($id == 11) return 'SPW / спецификация Компас';
			if ($id == 12) return 'FRM / файл формы';
			if ($id == 13) return 'DWG / проектные данные';
			return 'не определен';
		}
		
		private static function file_status($id) {
			if ($id == 1) return 'Принято';
			if ($id == 2) return 'На доработку';
			return 'На проверке';
		}
		
		private static function file_sub_title($sub_type, $sub_number, $title) {
			if ($sub_type == 1) return 'Раздел '.$sub_number;
			if ($sub_type == 2) return $title;
			return '';
		}

	}
?>