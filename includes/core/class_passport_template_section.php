<?php
	class Passport_Template_Section {
		
		// GENERAL
		
		public static function passport_template_section_info($section_id) {
			// info
			$q = DB::query("SELECT section_id, template_id, number, title FROM passport_template_sections WHERE section_id='".$section_id."' LIMIT 1;") or die (DB::error());
			if ($row = DB::fetch_row($q)) {
				return [
					'id'=>$row['section_id'],
					'template_id'=>$row['template_id'],
					'number'=>$row['number'],
					'title'=>$row['title']
				];
			} else {
				return [
					'id'=>0,
					'template_id'=>0,
					'number'=>0,
					'title'=>''
				];
			}
		}
		
		public static function passport_template_sections_list($template_id) {
			// vars
			$info = [];
			// info
			$q = DB::query("SELECT section_id, number, title FROM passport_template_sections WHERE template_id='".$template_id."' AND hidden<>'1' ORDER BY sort DESC;") or die (DB::error());
			while ($row = DB::fetch_row($q)) {
				$info[] = [
					'id'=>$row['section_id'],
					'number'=>$row['number'],
					'title'=>$row['title']
				];
			}
			// output
			return $info;
		}

	}
?>