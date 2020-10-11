<?php

	use PhpOffice\PhpSpreadsheet\IOFactory;
	use PhpOffice\PhpSpreadsheet\Spreadsheet;
	use PhpOffice\PhpSpreadsheet\Style;

	class Pick_Product {

		// GENERAL
		
		public static function pick_products_list($pick_id) {
			// vars
			$items = [];
			$manufacturers = [];
			// query
			$q = DB::query("SELECT pick_id, product_id, created FROM pick_products WHERE pick_id='".$pick_id."';") or die (DB::error());
			while ($row = DB::fetch_row($q)) {
				$product = Product::product_info_full($row['product_id']);
				if (!in_array($product['manufacturer']['title'], $manufacturers)) $manufacturers[] = $product['manufacturer']['title'];
				$items[] = [
					'id'=>$row['product_id'],
					'title'=>$product['title'],
					'manufacturer'=>$product['manufacturer']['title'],
					'created'=>date('d.m.y в H:i', ts_timezone($row['created'], Session::$tz))
				];
			}
			// output
			return ['items'=>$items, 'manufacturers'=>implode(', ', $manufacturers)];
		}

		public static function pick_products_report($data) {
			// vars
			$product_ids_raw = isset($data['product_ids']) && is_array($data['product_ids']) ? $data['product_ids'] : [];
			$email_raw = isset($data['email']) ? $data['email'] : '';
			$product_ids = [];
			// emails
			$emails = [];
			$email_raw = preg_split('~[\s\,\;]+~iu', $email_raw);
			foreach($email_raw as $email) {
				if (!is_email($email)) $emails[] = $email;
			}
			// info
			DB::query("INSERT INTO picks (company_id, user_id, count_products, created) VALUES ('".Session::$company_id."', '".Session::$user_id."', '".count($product_ids_raw)."', '".Session::$ts."');") or die (DB::error());
			$pick_id = DB::insert_id();
			$company = Company::company_info(Session::$company_id);
			// add
			foreach($product_ids_raw as $product_id) {
				if (!is_numeric($product_id)) continue;
				DB::query("INSERT INTO pick_products (user_id, pick_id, product_id, created) VALUES ('".Session::$user_id."', '".$pick_id."', '".$product_id."', '".Session::$ts."');") or die (DB::error());
				$product_ids[] = $product_id;
			}
			// export
			$filename = self::pick_products_export($pick_id, $product_ids);
			// notification (email)
			$total = count($product_ids);
			if ($total && $emails) {
				$file_xlsx = SITE_SCHEME.'://'.SITE_DOMAIN.'/storage/xlsx/'.$filename;
				foreach($emails as $email) Email::product_picks($email, $file_xlsx, $pick_id);
			}
			// notification (server)
			if ($total && $company['pick_server']) {
				$info = $products;
				$response = Rest::rest_pick_report($company['pick_server'], $info);
			}
			// output
			return ['message'=>'pick: '.$total.' product(s), pick_id: '.$pick_id];
		}
		
		public static function pick_products_export($pick_id, $product_ids) {
			// info
			$user = User::user_info(Session::$user_id);
			$products = Product::products_simple_list($product_ids);
			$options = Product_Option::options_simple_list($product_ids);
			$last_col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($options) + 1);
			$last_row = count($products) + 7;
			// excel
			$filepath = './../storage/xlsx';
			$filename = get_random_filename($filepath, 'xlsx');
			$sheet_array = [];
			$spreadsheet = new Spreadsheet();
			$sheet = $spreadsheet->getActiveSheet();
			// sheet (header)
			$sheet->setCellValue('A1', 'Оприходование №'.$pick_id);
			$sheet->setCellValue('A3', 'Компания: '.$user['company_title']);
			$sheet->setCellValue('A4', 'Исполнитель: '.$user['full_name']);
			$sheet->setCellValue('A5', 'Дата: '.date('d.m.Y H:i', ts_timezone(Session::$ts, Session::$tz)));
			$sheet->getStyle('A1:A5')->getFont()->setBold(true);
			$sheet->setTitle('Оприходование №'.$pick_id);
			// sheet (title)
			$a = [];
			$a[] = 'Наименование';
			foreach($options as $option_title => $option_value) $a[] = $option_title;
			$sheet_array[] = $a;
			// sheet (body)
			for($i = 0, $total = count($products); $i < $total; $i++) {
				$a = [];
				$a[] = $products[$i]['title'];
				foreach($options as $option_item) {
					$value = '-';
					foreach($option_item as $item) {
						if ($item['id'] == $products[$i]['id']) $value = $item['value'];
					}
					$a[] = $value;
				}
				$sheet_array[] = $a;
			}
			// sheet (styles)
			$sheet->fromArray($sheet_array, NULL, 'A7');
			foreach (range(0, count($options) + 1) as $col) $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);                
			$spreadsheet->getDefaultStyle()->applyFromArray(['alignment'=>['horizontal'=>'left']]);
			$spreadsheet->getActiveSheet()->getStyle('A7:'.$last_col.$last_row)->applyFromArray(['borders'=>['allBorders'=>['borderStyle'=>Style\Border::BORDER_THIN, 'color'=>['rgb'=>'000000']]]]);
			$spreadsheet->getActiveSheet()->getStyle('A7:'.$last_col.'7')->getFont()->setBold(true);
			$spreadsheet->getActiveSheet()->getRowDimension(7)->setRowHeight(35);
			$spreadsheet->getActiveSheet()->setSelectedCell('A1');
			// write
			$writer = IOFactory::createWriter($spreadsheet, "Xlsx");
			$writer->save($filepath.'/'.$filename);
			// output
			return $filename;
		}
		
		public static function pick_product_ids($pick_id) {
			// vars
			$product_ids = [];
			// query
			$q = DB::query("SELECT product_id FROM pick_products WHERE pick_id='".$pick_id."';") or die (DB::error());
			while ($row = DB::fetch_row($q)) $product_ids[] = $row['product_id'];
			// output
			return $product_ids;
		}

	}
?>