<?php
	class Label_Option {

		// GENERAL

		public static function label_options_list($label_id) {
			// vars
			$info = [];
			// info
			$q = DB::query("SELECT id, title, title_print FROM label_options WHERE label_id='".$label_id."';") or die (DB::error());
			while ($row = DB::fetch_row($q)) $info[] = ['id'=>$row['id'], 'title'=>$row['title'], 'title_print'=>$row['title_print'], 'value'=>'Значение'];
			// add blank
			$count = 4 - count($info);
			if ($count > 0) {
				for($i = 0; $i < $count; $i++) $info[] = ['id'=>0, 'title'=>'', 'title_print'=>'', 'value'=>''];
			}
			// output
			return $info;
		}

	}
?>