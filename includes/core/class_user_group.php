<?php
	class User_Group {

		// GENERAL
		
		public static function user_group_info($data) {
			// vars
			$group_id = isset($data['group_id']) && is_numeric($data['group_id']) ? $data['group_id'] : 0;
			$user_id = isset($data['user_id']) && is_numeric($data['user_id']) ? $data['user_id'] : 0;
			// where
			$where = $group_id ? "group_id='".$group_id."'" : "user_id='".$user_id."'";
			// query
			$q = DB::query("SELECT user_id, group_id FROM user_groups WHERE ".$where." LIMIT 1;") or die (DB::error());
			if ($row = DB::fetch_row($q)) {
				$group = self::user_group_title($row['group_id']);
				return [
					'user_id'=>$row['user_id'],
					'group_id'=>$row['group_id'],
					'group_title'=>$group['title']
				];
			} else {
				return [
					'user_id'=>0,
					'group_id'=>0,
					'group_title'=>''
				];
			}
		}

		public static function user_group_list($user_id) {
			// vars
			$info = [];
			// info
			$q = DB::query("SELECT group_id FROM user_groups WHERE user_id='".$user_id."';") or die (DB::error());
			while ($row = DB::fetch_row($q)) {
				$group = self::user_group_title($row['group_id']);
				$info[] = [
					'group_id'=>$row['group_id'],
					'group_title'=>$group['title']
				];
			}
			// output
			return $info;
		}
		
		// ACTIONS
		
		public static function user_group_update($user_id, $group_id) {
			$user = User::user_info($user_id);
			DB::query("DELETE FROM user_groups WHERE user_id='".$user_id."';") or die (DB::error());
			DB::query("INSERT INTO user_groups (user_id, company_id, group_id) VALUES ('".$user_id."', '".$user['company_id']."', '".$group_id."');") or die (DB::error());
		}

		// SERVICE
		
		public static function user_group_list_str($user_id) {
			$res = [];
			$groups = self::user_group_list($user_id);
			foreach($groups as $group) $res[] = $group['group_title'];
			return implode(', ', $res);
		}
		
		public static function user_group_title($id = -1) {
			// vars
			$info = [];
			// info
			if ($id == 0 || $id == -1) $info[] = ['id'=>0, 'title'=>'Не выбрано'];
			if ($id == 1 || $id == -1) $info[] = ['id'=>1, 'title'=>'ПГ'];
			if ($id == 2 || $id == -1) $info[] = ['id'=>2, 'title'=>'ОГК'];
			if ($id == 3 || $id == -1) $info[] = ['id'=>3, 'title'=>'БТК'];
			if ($id == 4 || $id == -1) $info[] = ['id'=>4, 'title'=>'ОТК'];
			if ($id == 5 || $id == -1) $info[] = ['id'=>5, 'title'=>'ЦЗЛ'];
			// output
			if ($id == -1) return $info;
			else return isset($info[0]) ? $info[0] : ['id'=>0, 'title'=>'Не указана'];
		}

	}
?>