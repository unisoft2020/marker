<?php
	class Passport_Template_File {
		
		// GENERAL
		
		public static function passport_template_file_info($id) {
			// info
			$q = DB::query("SELECT id, group_id, sub_number, title FROM passport_template_files WHERE id='".$id."' LIMIT 1;") or die (DB::error());
			if ($row = DB::fetch_row($q)) {
				$group = User_Group::user_group_title($row['group_id']);
				return [
					'id'=>$row['id'],
					'group_id'=>$row['group_id'],
					'group_title'=>$group['title'],
					'sub_number'=>$row['sub_number'],
					'title'=>$row['title']
				];
			} else {
				return [
					'id'=>0,
					'group_id'=>0,
					'group_title'=>'',
					'sub_number'=>0,
					'title'=>''
				];
			}
		}
		
		public static function passport_template_files_list($mode, $group_id = 0) {
			// vars
			$info = [];
			// extra
			$info[] = [
				'id'=>0,
				'group_id'=>0,
				'group_title'=>'',
				'sub_number'=>0,
				'title'=>'Дополнительные документы'
			];
			// where
			$where = $mode == 'worker' ? "WHERE group_id='".$group_id."'" : "";
			// info
			$q = DB::query("SELECT id, group_id, sub_number, title FROM passport_template_files ".$where." ORDER BY sub_number, title;") or die (DB::error());
			while ($row = DB::fetch_row($q)) {
				$group = User_Group::user_group_title($row['group_id']);
				$info[] = [
					'id'=>$row['id'],
					'group_id'=>$row['group_id'],
					'group_title'=>$group['title'],
					'sub_number'=>$row['sub_number'],
					'title'=>$row['title']
				];
			}
			// output
			return $info;
		}

	}
?>