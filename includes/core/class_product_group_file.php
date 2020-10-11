<?php
	class Product_Group_File {
		
		// GENERAL
		
		public static function group_file_info($group_file_id) {
			$q = DB::query("SELECT group_file_id, group_id, status_id, status_title, type, path, title, size, created FROM product_group_files WHERE group_file_id='".$group_file_id."' LIMIT 1;") or die (DB::error());
			if ($row = DB::fetch_row($q)) {
				return [
					'group_file_id'=>$row['group_file_id'],
					'group_id'=>$row['group_id'],
					'status_id'=>$row['status_id'],
					'status_title'=>$row['status_title'],
					'type'=>$row['type'],
					'path'=>SITE_SCHEME.'://'.SITE_DOMAIN.$row['path'],
					'path_inner'=>$row['path'],
					'title'=>flt_output($row['title']),
					'size'=>(string) round($row['size'] / 1048576, 2),
					'size_raw'=>$row['size'],
					'created'=>date('d.m.Y H:i', ts_timezone($row['created'], Session::$tz))
				];
			} else {
				return [
					'group_file_id'=>0,
					'group_id'=>0,
					'status_id'=>0,
					'status_title'=>'',
					'type'=>0,
					'path'=>'',
					'path_inner'=>'',
					'title'=>'',
					'size'=>'',
					'size_raw'=>0,
					'created'=>''
				];
			}
		}
		
		public static function group_files_list($group_id) {
			// vars
			$info = [];
			// info
			$q = DB::query("SELECT group_file_id, group_id, status_id, status_title, type, path, title, size, created FROM product_group_files WHERE group_id='".$group_id."' AND hidden<>'1' ORDER BY group_file_id DESC;") or die (DB::error());
			while ($row = DB::fetch_row($q)) {
				$info[] = [
					'group_file_id'=>$row['group_file_id'],
					'group_id'=>$row['group_id'],
					'status_id'=>$row['status_id'],
					'status_title'=>$row['status_title'],
					'type'=>$row['type'],
					'path'=>SITE_SCHEME.'://'.SITE_DOMAIN.$row['path'],
					'title'=>flt_output($row['title']),
					'size'=>(string) round($row['size'] / 1048576, 2),
					'created'=>date('d.m.Y H:i', ts_timezone($row['created'], Session::$tz))
				];
			}
			// output
			return $info;
		}

		// ACTIONS
		
		public static function group_file_upload($data, $files) {
			// vars
			$names = $files['name'];
			$paths = $files['tmp_name'];
			$sizes = $files['size'];
			$types = $files['type'];
			$group_id = isset($data['group_id']) && is_numeric($data['group_id']) ? $data['group_id'] : 0;
			$max_size = 100;
			// parse
			for($i = 0, $count = count($names); $i < $count; $i++) {
				// vars
				$size_raw = $sizes[$i];
				$size = $size_raw / 1048576;
				$title = preg_replace('~(\.[a-z]+)$~iu', '', $names[$i]);
				$mime_type = $types[$i];
				$file_extension = preg_replace('/^.*\.(.*)$/U', '$1', $names[$i]);
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
				if (preg_match('~^image/(jpeg|bmp|gif|webp)$~i', $mime_type)) $val_type = 5;
				if (preg_match('~^image/png$~i', $mime_type)) $val_type = 6;
				if (preg_match('~^application/octet-stream$~i', $mime_type) && $file_extension == 'cdw') $val_type = 7;
				if (preg_match('~^application/octet-stream$~i', $mime_type) && $file_extension == 'spw') $val_type = 8;
				if (preg_match('~^application/octet-stream$~i', $mime_type) && $file_extension == 'frm') $val_type = 9;
				if (preg_match('~^application/octet-stream$~i', $mime_type) && $file_extension == 'dwg') $val_type = 10;
				// file
				$ext = explode('.', $names[$i]);
				$ext = end($ext);
				$file = get_random_filename('.'.$path, $ext);
				// upload
				if ($val_size && $val_type) {
					move_uploaded_file($paths[$i], '.'.$path.$file);
					$status = [1, 'загружен'];
				} else {
					if (!$val_size) $status = [-1, 'не загружен, размер больше допустимого ('.$max_size.' Mb)'];
					if (!$val_type) $status = [-1, 'не загружен, формат не поддерживается'];
				}
				// query (group files)
				DB::query("INSERT INTO product_group_files (group_id, status_id, status_title, type, path, title, size, created) VALUES ('".$group_id."', '".$status[0]."', '".$status[1]."', '".$val_type."', '".$path.$file."', '".$title."', '".$size_raw."', '".Session::$ts."');") or die (DB::error());
				Company::company_size_update($size_raw);
			}
			// output
			HTML::assign('files', self::group_files_list($group_id));
			return ['html'=>HTML::fetch('./partials/modal/products/product_group_files_upload.html')];
		}
		
		public static function group_file_toggle($data) {
			// vars
			$group_file_id = isset($data['group_file_id']) && is_numeric($data['group_file_id']) ? $data['group_file_id'] : 0;
			$product_id = isset($data['product_id']) && is_numeric($data['product_id']) ? $data['product_id'] : 0;
			$group_id = isset($data['group_id']) && is_numeric($data['group_id']) ? $data['group_id'] : 0;
			$status = isset($data['status']) && in_array($data['status'], ['add', 'delete']) ? $data['status'] : '';
			// info
			$file = self::group_file_info($group_file_id);
			// add
			if ($status == 'add') {
				DB::query("INSERT INTO product_files (product_id, group_id, group_file_id, type, path, title, size, created) VALUES ('".$product_id."', '".$group_id."', '".$group_file_id."', '".$file['type']."', '".$file['path_inner']."', '".$file['title']."', '".$file['size_raw']."', '".Session::$ts."');") or die (DB::error());
			}
			// delete
			if ($status == 'delete') {
				DB::query("UPDATE product_files SET hidden='1' WHERE product_id='".$product_id."' AND group_file_id='".$group_file_id."' AND hidden='0';") or die (DB::error());
			}
			// output
			return ['response'=>'ok'];
		}
		
		public static function group_file_delete($data) {
			// vars
			$group_id = isset($data['group_id']) && is_numeric($data['group_id']) ? $data['group_id'] : 0;
			$group_file_id = isset($data['group_file_id']) && is_numeric($data['group_file_id']) ? $data['group_file_id'] : 0;
			// query
			DB::query("UPDATE product_group_files SET hidden='1' WHERE group_file_id='".$group_file_id."' LIMIT 1;") or die (DB::error());
			DB::query("UPDATE product_files SET hidden='1' WHERE group_file_id='".$group_file_id."';") or die (DB::error());
			// info
			$files = self::group_files_list($group_id);
			$files_count = count($files);
			// output
			HTML::assign('files', $files);
			return ['html'=>HTML::fetch('./partials/modal/products/product_group_files_upload.html'), 'files_count'=>$files_count];
		}
		
		public static function group_file_attached($product_id, $group_file_id) {
			$q = DB::query("SELECT product_id FROM product_files WHERE product_id='".$product_id."' AND group_file_id='".$group_file_id."' AND hidden='0' LIMIT 1;") or die (DB::error());
			return ($row = DB::fetch_row($q)) ? 1 : 0;
		}

	}
?>