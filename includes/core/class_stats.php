<?php
	class Stats {
		
		// PERIODS
		
		public static function periods_list() {
			// vars
			$info = [];
			// info
			$q = DB::query("SELECT period, files, products, size FROM stats_periods WHERE company_id='".Session::$company_id."' ORDER BY period DESC LIMIT 7;") or die (DB::error());
			while ($row = DB::fetch_row($q)) {
				$info[] = [
					'period'=>date('d.m.Y', $row['period']),
					'files'=>$row['files'] ? $row['files'] : '-',
					'products'=>$row['products'] ? $row['products'] : '-',
					'size'=>$row['size'] ? round($row['size'] / 1048576, 2).' Мб' : '-'
				];
			}
			// output
			return $info;
		}
		
		// USERS
		
		public static function users_list($ts) {
			// vars
			$info = [];
			$week_start = strtotime('monday this week', $ts);
			$week_end = $week_start + 604800 - 1;
			// info
			$q = DB::query("SELECT access, user_id, group_id, SUM(files), SUM(products), SUM(size), SUM(time), SUM(visits), SUM(views), SUM(logins), MAX(last_active) FROM stats_users WHERE company_id='".Session::$company_id."' AND period>='".$week_start."' AND period<'".$week_end."' GROUP BY user_id;") or die (DB::error());
			while ($row = DB::fetch_row($q)) {
				$user = User::user_info($row['user_id']);
				$group = User_Group::user_group_title($row['group_id']);
				$period = date('d.m.Y', $week_start).'-'.date('d.m.Y', $week_end);
				$info[] = [
					'name'=>$user['full_name'],
					'period'=>$period,
					'group'=>$group['title'],
					'files'=>$row['SUM(files)'] ? $row['SUM(files)'] : '-',
					'products'=>$row['SUM(products)'] ? $row['SUM(products)'] : '-',
					'size'=>$row['SUM(size)'] ? round($row['SUM(size)'] / 1048576, 2).' Мб' : '-',
					'time'=>round($row['SUM(time)']/60, 1),
					'visits'=>$row['SUM(visits)'],
					'views'=>$row['SUM(views)'],
					'logins'=>$row['SUM(logins)'],
					'last_active'=>$row['MAX(last_active)'] ? date('d.m.y в H:i', ts_timezone($row['MAX(last_active)'], Session::$tz)) : '-'
				];
			}
			// output
			return $info;
		}
		
		// ACTIONS
		
		public static function stats_update($files = 0, $products = 0, $size = 0, $logins = 0) {
			// vars
			$company_id = Session::$company_id;
			$user_id = Session::$user_id;
			$period = strtotime(date('d.m.Y'));
			$ts = time();
			// validate
			if (!$user_id) return false;
			// users (exist)
			$q = DB::query("SELECT id, last_active FROM stats_users WHERE user_id='".$user_id."' AND period='".$period."' LIMIT 1;") or die (DB::error());
			$user = ($row = DB::fetch_row($q)) ? ['id'=>$row['id'], 'last_active'=>$row['last_active']] : ['id'=>0, 'last_active'=>0];
			// users (update)
			if ($user['id']) {
				$diff = $ts - $user['last_active'];
				$time = $diff > 60 ? 5 : $diff;
				$visit = $diff > 1800 ? 1 : 0;
				DB::query("UPDATE stats_users SET files=files+'".$files."', products=products+'".$products."', size=size+'".$size."', time=time+'".$time."', visits=visits+'".$visit."', views=views+'1', logins=logins+'".$logins."', last_active='".$ts."' WHERE id='".$user['id']."' LIMIT 1;") or die (DB::error());
			} else {
				$group = User_Group::user_group_info(['user_id'=>$user_id]);
				$group_id = $group['group_id'];
				DB::query("INSERT INTO stats_users (access, company_id, user_id, group_id, period, files, products, size, time, visits, views, logins, last_active) VALUES ('".Session::$access."', '".$company_id."', '".$user_id."', '".$group_id."', '".$period."', '".$files."', '".$products."', '".$size."', '5', '1', '1', '".$logins."', '".$ts."');") or die (DB::error());
			}
			// periods
			if ($files || $products) {
				// exist
				$q = DB::query("SELECT id FROM stats_periods WHERE company_id='".$company_id."' AND period='".$period."' LIMIT 1;") or die (DB::error());
				$id = ($row = DB::fetch_row($q)) ? $row['id'] : 0;
				// update
				if ($id) DB::query("UPDATE stats_periods SET files=files+'".$files."', products=products+'".$products."', size=size+'".$size."' WHERE id='".$id."' LIMIT 1;") or die (DB::error());
				else DB::query("INSERT INTO stats_periods (company_id, period, files, products, size) VALUES ('".$company_id."', '".$period."', '".$files."', '".$products."', '".$size."');") or die (DB::error());
			}
		}
		
		public static function stats_empty_fill() {
			// vars
			$periods = [];
			$period_0 = strtotime(date('d.m.Y'));
			$period_1 = strtotime(date('d.m.Y', strtotime('-1 days')));
			$period_2 = strtotime(date('d.m.Y', strtotime('-2 days')));
			$period_3 = strtotime(date('d.m.Y', strtotime('-3 days')));
			$period_4 = strtotime(date('d.m.Y', strtotime('-4 days')));
			$period_5 = strtotime(date('d.m.Y', strtotime('-5 days')));
			$period_6 = strtotime(date('d.m.Y', strtotime('-6 days')));
			// exist
			$q = DB::query("SELECT id, period FROM stats_periods WHERE company_id='".Session::$company_id."' AND period>='".$period_6."';") or die (DB::error());
			while ($row = DB::fetch_row($q)) $periods[] = $row['period'];
			// add
			if (!in_array($period_0, $periods)) DB::query("INSERT INTO stats_periods (company_id, period) VALUES ('".Session::$company_id."', '".$period_0."');") or die (DB::error());
			if (!in_array($period_1, $periods)) DB::query("INSERT INTO stats_periods (company_id, period) VALUES ('".Session::$company_id."', '".$period_1."');") or die (DB::error());
			if (!in_array($period_2, $periods)) DB::query("INSERT INTO stats_periods (company_id, period) VALUES ('".Session::$company_id."', '".$period_2."');") or die (DB::error());
			if (!in_array($period_3, $periods)) DB::query("INSERT INTO stats_periods (company_id, period) VALUES ('".Session::$company_id."', '".$period_3."');") or die (DB::error());
			if (!in_array($period_4, $periods)) DB::query("INSERT INTO stats_periods (company_id, period) VALUES ('".Session::$company_id."', '".$period_4."');") or die (DB::error());
			if (!in_array($period_5, $periods)) DB::query("INSERT INTO stats_periods (company_id, period) VALUES ('".Session::$company_id."', '".$period_5."');") or die (DB::error());
			if (!in_array($period_6, $periods)) DB::query("INSERT INTO stats_periods (company_id, period) VALUES ('".Session::$company_id."', '".$period_6."');") or die (DB::error());
		}
		
		public static function stats_export() {
			// info
			$stats = [];
			$ts = Session::$ts;
			for($i = 0; $i < 10; $i++) {
				$stats_period = self::users_list($ts);
				foreach($stats_period as $a) $stats[] = $a;
				$ts -= 604800;
			}
			// spreadsheet
			$spreadsheet = self::stats_export_do($stats);
			$writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, "Xlsx");
			$writer->save('php://output');
			exit();
		}
		
		public static function stats_export_do($stats) {
			// vars
			$cols = isset($stats[0]) ? count($stats[0]) : 0;
			$last_col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($cols);
			$last_row = count($stats) + 1;
			// excel
			$sheet_array = [];
			$spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
			$sheet = $spreadsheet->getActiveSheet();
			// sheet (header)
			$sheet_array[] = [
				'ФИО',
				'Период',
				'Группа',
				'Документы',
				'Изделия',
				'Размер, мб',
				'Время на сайте',
				'Визиты',
				'Просмотры',
				'Логины',
				'Последний визит'
			];
			//$sheet->getStyle('A1')->getFont()->setBold(true);
			//$sheet->setTitle('Партия №'.$group_id);
			// sheet (title)
			//$a = [];
			//$a[] = 'Наименование';
			//foreach($options as $option_title => $option_value) $a[] = $option_title;
			//$sheet_array[] = $a;
			// sheet (body)
			foreach ($stats as $a) {
				//$a = [];
				//$a[] = $products[$i]['title'];
				/*foreach($options as $option_item) {
					$value = '-';
					foreach($option_item as $item) {
						if ($item['id'] == $products[$i]['id']) $value = $item['value'];
					}
					$a[] = $value;
				}*/
				$sheet_array[] = $a;
			}
			// sheet (styles)
			$sheet->fromArray($sheet_array, NULL, 'A1');
			foreach (range(0, $cols + 1) as $col) $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);                
			$spreadsheet->getDefaultStyle()->applyFromArray(['alignment'=>['horizontal'=>'left']]);
			$spreadsheet->getActiveSheet()->getStyle('A1:'.$last_col.$last_row)->applyFromArray(['borders'=>['allBorders'=>['borderStyle'=>\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color'=>['rgb'=>'000000']]]]);
			//$spreadsheet->getActiveSheet()->getStyle('A3:'.$last_col.'3')->getFont()->setBold(true);
			$spreadsheet->getActiveSheet()->getRowDimension(1)->setRowHeight(35);
			//$spreadsheet->getActiveSheet()->setSelectedCell('A1');
			// output
			return $spreadsheet;
		}

	}
?>