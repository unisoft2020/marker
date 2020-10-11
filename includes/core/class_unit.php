<?php
	class Unit {

		// GENERAL

		public static function units_list() {
			// vars
			$info = [];
			// info
			$q = DB::query("SELECT unit_id, title_full, title_short FROM units WHERE hidden<>'1';") or die (DB::error());
			while ($row = DB::fetch_row($q)) {
				$info[] = [
					'id'=>$row['unit_id'],
					'title_full'=>$row['title_full'],
					'title_short'=>$row['title_short']
				];
			}
			// output
			return $info;
		}

	}
?>