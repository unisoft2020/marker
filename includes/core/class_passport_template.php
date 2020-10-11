<?php
	class Passport_Template {
		
		// GENERAL
		
		public static function passport_template_info($template_id) {
			// info
			$q = DB::query("SELECT template_id, title FROM passport_templates WHERE template_id='".$template_id."' LIMIT 1;") or die (DB::error());
			if ($row = DB::fetch_row($q)) {
				return [
					'id'=>$row['template_id'],
					'title'=>$row['title']
				];
			} else {
				return [
					'id'=>0,
					'title'=>'Другой стандарт'
				];
			}
		}
		
		public static function passport_templates_list() {
			// vars
			$info = [];
			// info
			$q = DB::query("SELECT template_id, title FROM passport_templates WHERE hidden<>'1' ORDER BY title;") or die (DB::error());
			while ($row = DB::fetch_row($q)) $info[] = ['id'=>$row['template_id'], 'title'=>$row['title']];
			$info[] = ['id'=>0, 'title'=>'Другой стандарт'];
			// output
			return $info;
		}

	}
?>