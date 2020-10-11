<?php

	class Product_Export {

		public static function products_export_list($product_ids) {
			// vars
			$info = [];
			// info
			$q = DB::query("SELECT product_id, title FROM products WHERE product_id IN (".implode(",", $product_ids).");") or die (DB::error());
			while ($row = DB::fetch_row($q)) {
				$info[] = [
					'id'=>$row['product_id'],
					'title'=>$row['title']
				];
			}
			// output
			return $info;
		}

		public static function products_export($product_ids, $group_id) {
			// vars
			$products = self::products_export_list($product_ids);
			$options = Product_Option::options_simple_list($product_ids);
			$last_col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($options) + 1);
			$last_row = count($products) + 3;
			// excel
			$sheet_array = [];
			$spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
			$sheet = $spreadsheet->getActiveSheet();
			// sheet (header)
			$sheet->setCellValue('A1', 'Партия №'.$group_id);
			//$sheet->setCellValue('A3', 'Компания: '.$user['company_title']);
			//$sheet->setCellValue('A4', 'Комплектовщик: '.$user['full_name']);
			//$sheet->setCellValue('A5', 'Дата: '.date('d.m.Y H:i', ts_timezone(Session::$ts, Session::$tz)));
			$sheet->getStyle('A1')->getFont()->setBold(true);
			$sheet->setTitle('Партия №'.$group_id);
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
			$sheet->fromArray($sheet_array, NULL, 'A3');
			foreach (range(0, count($options) + 1) as $col) $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);                
			$spreadsheet->getDefaultStyle()->applyFromArray(['alignment'=>['horizontal'=>'left']]);
			$spreadsheet->getActiveSheet()->getStyle('A3:'.$last_col.$last_row)->applyFromArray(['borders'=>['allBorders'=>['borderStyle'=>\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN, 'color'=>['rgb'=>'000000']]]]);
			$spreadsheet->getActiveSheet()->getStyle('A3:'.$last_col.'3')->getFont()->setBold(true);
			$spreadsheet->getActiveSheet()->getRowDimension(3)->setRowHeight(35);
			$spreadsheet->getActiveSheet()->setSelectedCell('A1');
			// output
			return $spreadsheet;
		}

	}
?>