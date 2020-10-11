<?php
	class Page {

		// GENERAL

		public static function page_info($page_id) {
			// info
			$q = DB::query("SELECT page_id, title, content, screen_name FROM pages WHERE page_id='".$page_id."' AND hidden<>'1' LIMIT 1;") or die (DB::error());
			if ($row = DB::fetch_row($q)) {
				return [
					'id'=>$row['page_id'],
					'title'=>$row['title'],
					'content'=>preg_replace('~[\n]~iu', '<br>', $row['content']),
                    'screen_name'=>$row['screen_name']
				];
			} else {
                return [
					'id'=>0,
					'title'=>'',
					'content'=>'',
                    'screen_name'=>''
				];
            }
		}

	}
?>