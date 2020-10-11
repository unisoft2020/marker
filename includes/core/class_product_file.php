<?php
	class Product_File {
		
		// GENERAL
		
		public static function file_info($file_id) {
			// info
			$q = DB::query("SELECT file_id, product_id, type, path, title, size, created FROM product_files WHERE file_id='".$file_id."' LIMIT 1;") or die (DB::error());
			if ($row = DB::fetch_row($q)) {
				return [
					'id'=>$row['file_id'],
					'product_id'=>$row['product_id'],
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
					'product_id'=>0,
					'type'=>0,
					'path'=>'',
					'title'=>'',
					'size'=>'',
					'size_raw'=>0,
					'created'=>''
				];
			}
		}
		
		public static function files_list($product_id, $mode = 'default') {
			// vars
			$info = [];
			// info
			$q = DB::query("SELECT file_id, type, path, title, size, created FROM product_files WHERE product_id='".$product_id."' AND hidden<>'1';") or die (DB::error());
			while ($row = DB::fetch_row($q)) {
				$info[] = [
					'id'=>$row['file_id'],
					'type'=>$row['type'],
					'path'=>$mode != 'copy' ? SITE_SCHEME.'://'.SITE_DOMAIN.$row['path'] : $row['path'],
					'title'=>$mode != 'copy' ? flt_output($row['title']) : $row['title'],
					'size'=>(string) round($row['size'] / 1048576, 2),
					'size_raw'=>$row['size'],
					'created'=>date('d.m.Y H:i', ts_timezone($row['created'], Session::$tz))
				];
			}
			// output
			return $info;
		}
		
		// ACTIONS

		public static function file_upload($data, $files) {
			// vars
			$names = $files['name'];
			$paths = $files['tmp_name'];
			$sizes = $files['size'];
			$types = $files['type'];
			$product_id = isset($data['product_id']) && is_numeric($data['product_id']) ? $data['product_id'] : 0;
			$max_size = 100;
			$error = '';
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
				// upload
				if ($val_size && $val_type) {
					// file
					$ext = explode('.', $names[$i]);
					$ext = end($ext);
					$file = get_random_filename('.'.$path, $ext);
					move_uploaded_file($paths[$i], '.'.$path.$file);
					// update
					DB::query("INSERT INTO product_files (product_id, type, path, title, size, created) VALUES ('".$product_id."', '".$val_type."', '".$path.$file."', '".$title."', '".$size_raw."', '".Session::$ts."');") or die (DB::error());
					Company::company_size_update($size_raw);
				} else {
					if (!$val_size) $str = 'Один или несколько файлов не были загружены, так как их размер больше максимально допустимого ('.$max_size.' Mb). Попробуйте загрузить файлы меньшего размера.';
					if (!$val_type) $str = 'Один или несколько файлов не были загружены, так как их формат не поддерживается.';
					HTML::assign('error', $str);
					$error = HTML::fetch('./partials/modal/error.html');
				}
			}
			// output
			HTML::assign('files', self::files_list($product_id));
			return ['html'=>HTML::fetch('./partials/modal/products/product_edit_files.html'), 'error'=>$error];
		}

		public static function file_delete($data) {
			// vars
			$file_id = isset($data['file_id']) && is_numeric($data['file_id']) ? $data['file_id'] : 0;
			// info
			$file = self::file_info($file_id);
			// query
			DB::query("UPDATE product_files SET hidden='1' WHERE file_id='".$file_id."' LIMIT 1;") or die (DB::error());
			// output
			HTML::assign('files', self::files_list($file['product_id']));
			return ['html'=>HTML::fetch('./partials/modal/products/product_edit_files.html')];
		}

	}
?>