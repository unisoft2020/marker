<?php
	class Label {

		// GENERAL
		
		public static function label_info($label_id) {
			$q = DB::query("SELECT label_id, type_id, company_title, title, size_width, size_height, size_code, size_row FROM labels WHERE label_id='".$label_id."' AND company_id='".Session::$company_id."' AND hidden<>'1';") or die (DB::error());
			if ($row = DB::fetch_row($q)) {
				$type = self::label_type($row['type_id']);
				return [
					'id'=>$row['label_id'],
					'type_id'=>$type['id'],
					'type_title'=>$type['title'],
					'company_title'=>flt_output($row['company_title']),
					'product_title'=>'Наименование изделия',
					'title'=>flt_output($row['title']),
					'size_width'=>$row['size_width'],
					'size_height'=>$row['size_height'],
					'size_code'=>$row['size_code'],
					'size_row'=>$row['size_row'],
					'preview_width'=>$row['size_width'] * 10,
					'preview_height'=>$row['size_height'] * 10,
					'preview_top'=>-$row['size_height'] * 0.5 * 0.5 * 10, // $row['size_height'] * 0.4 * 0.5 * 10,
					'preview_left'=>-$row['size_width'] * 0.5 * 0.5 * 10, // $row['size_width'] * 0.4 * 0.5 * 10,
					'preview_code'=>$row['size_code'] * 10,
					'preview_row'=>$row['size_row'] * 10,
					'qr'=>'/images/qr.png',
					'options'=>Label_Option::label_options_list($row['label_id'])
				];
			} else {
				$type = self::label_type(0);
				$company = Company::company_info(Session::$company_id);
				return [
					'id'=>0,
					'type_id'=>$type['id'],
					'type_title'=>$type['title'],
					'company_title'=>$company['title'].' тел. '.$company['phone'],
					'product_title'=>'Наименование изделия',
					'title'=>'Универсальная, '.$type['w'].'x'.$type['h'].' мм',
					'size_width'=>$type['w'],
					'size_height'=>$type['h'],
					'size_code'=>22,
					'size_row'=>6.4,
					'preview_width'=>$type['w'] * 10,
					'preview_height'=>$type['h'] * 10,
					'preview_top'=>-$type['h'] * 0.5 * 0.5 * 10, // $type['h'] * 0.4 * 0.5 * 10,
					'preview_left'=>-$type['w'] * 0.5 * 0.5 * 10, // $type['w'] * 0.4 * 0.5 * 10,
					'preview_code'=>22 * 10,
					'preview_row'=>6.4 * 10,
					'qr'=>'/images/qr.png',
					'options'=>[
						['id'=>0, 'title'=>'Заводской номер', 'title_print'=>'Заводской номер', 'value'=>'Значение'],
						['id'=>0, 'title'=>'Дата изготовления', 'title_print'=>'Дата изготовления', 'value'=>'Значение'],
						['id'=>0, 'title'=>'Дата отгрузки', 'title_print'=>'Дата отгрузки', 'value'=>'Значение'],
						['id'=>0, 'title'=>'', 'title_print'=>'', 'value'=>'Значение']
					]
				];
			}
		}

		public static function labels_list($data = []) {
			// vars
			$info = [];
			// info
			$q = DB::query("SELECT label_id, type_id, title FROM labels WHERE company_id='".Session::$company_id."' AND hidden<>'1' ORDER BY label_id DESC;") or die (DB::error());
			while ($row = DB::fetch_row($q)) {
				$label = self::label_info($row['label_id']);
				HTML::assign('label', $label);
				$preview = HTML::fetch('./partials/print/label_'.$row['type_id'].'.html');
				$info[] = [
					'id'=>$row['label_id'],
					'title'=>$row['title'],
					'preview'=>$preview,
					'preview_width'=>$label['preview_width'] * 0.3,
					'preview_height'=>$label['preview_height'] * 0.3
				];
			}
			// output
			return $info;
		}
		
		public static function labels_full_list() {
			// vars
			$default = [];
			$default[] = [
				'id'=>0,
				'title'=>'Стандартная',
				'preview'=>'',
				'preview_width'=>0,
				'preview_height'=>0
			];
			// info
			$labels = self::labels_list();
			// output
			return array_merge($default, $labels);
		}
		
		// ACTIONS
		
		public static function label_update($data) {
			// vars
			$id = isset($data['id']) && is_numeric($data['id']) ? $data['id'] : 0;
			$title = isset($data['title']) ? $data['title'] : '';
			$type_title = isset($data['type_title']) ? $data['type_title'] : '';
			$type_id = isset($data['type_id']) && is_numeric($data['type_id']) ? $data['type_id'] : 0;
			$company_title = isset($data['company_title']) && trim($data['company_title']) ? trim($data['company_title']) : '';
			$size_width = isset($data['size_width']) && is_numeric($data['size_width']) ? $data['size_width'] : 0;
			$size_height = isset($data['size_height']) && is_numeric($data['size_height']) ? $data['size_height'] : 0;
			$size_code = isset($data['size_code']) && is_numeric($data['size_code']) ? $data['size_code'] : 0;
			$size_row = isset($data['size_row']) && is_numeric($data['size_row']) ? $data['size_row'] : 0;
			$options_raw = isset($data['options']) && is_array($data['options']) ? $data['options'] : [];
			// options
			$options = [];
			foreach($options_raw as $a) {
				if ($a[0]) $options[] = ['id'=>0, 'title'=>$a[0], 'title_print'=>$a[1], 'value'=>'Значение'];
			}
			// label
			$label = [
				'id'=>$id,
				'title'=>$title,
				'type_title'=>$type_title,
				'type_id'=>$type_id,
				'company_title'=>unflt_input($company_title),
				'product_title'=>'Наименование изделия',
				'size_width'=>$size_width,
				'size_height'=>$size_height,
				'size_code'=>$size_code,
				'size_row'=>$size_row,
				'preview_width'=>$size_width * 10,
				'preview_height'=>$size_height * 10,
				'preview_top'=>-$size_height * 0.5 * 0.5 * 10,
				'preview_left'=>-$size_width * 0.5 * 0.5 * 10,
				'preview_code'=>$size_code * 10,
				'preview_row'=>$size_row * 10,
				'qr'=>'/images/qr.png',
				'options'=>$options
			];
			// info
			$types = Label::label_type();
			// output
			HTML::assign('label', $label);
			HTML::assign('types', $types);
			HTML::assign('label_preview', HTML::fetch('./partials/print/label_'.$type_id.'.html'));
			return ['params'=>HTML::fetch('./partials/section/labels/label_edit/params.html'), 'preview'=>HTML::fetch('./partials/section/labels/label_edit/preview.html'), 'params_optional'=>HTML::fetch('./partials/section/labels/label_edit/params_optional.html')];
		}
		
		public static function label_save($data) {
			// vars
			$label_id = isset($data['label_id']) && is_numeric($data['label_id']) ? $data['label_id'] : 0;
			$title = isset($data['title']) && trim($data['title']) ? trim($data['title']) : '';
			$type_id = isset($data['type_id']) && is_numeric($data['type_id']) ? $data['type_id'] : 0;
			$company_title = isset($data['company_title']) && trim($data['company_title']) ? trim($data['company_title']) : '';
			$size_width = isset($data['size_width']) && is_numeric($data['size_width']) ? $data['size_width'] : 0;
			$size_height = isset($data['size_height']) && is_numeric($data['size_height']) ? $data['size_height'] : 0;
			$size_code = isset($data['size_code']) && is_numeric($data['size_code']) ? $data['size_code'] : 0;
			$size_row = isset($data['size_row']) && is_numeric($data['size_row']) ? $data['size_row'] : 0;
			$options = isset($data['options']) && is_array($data['options']) ? $data['options'] : [];
			// update (labels)
			if ($label_id) {
				error_log($label_id);
				DB::query("UPDATE labels SET type_id='".$type_id."', company_title='".$company_title."', title='".$title."', size_width='".$size_width."', size_height='".$size_height."', size_code='".$size_code."', size_row='".$size_row."' WHERE label_id='".$label_id."' LIMIT 1;") or die (DB::error());
			} else {
				DB::query("INSERT INTO labels (type_id, company_id, company_title, title, size_width, size_height, size_code, size_row, created) VALUES ('".$type_id."', '".Session::$company_id."', '".$company_title."', '".$title."', '".$size_width."', '".$size_height."', '".$size_code."', '".$size_row."', '".Session::$ts."');") or die (DB::error());
				$label_id = DB::insert_id();
			}
			// update (options)
			DB::query("DELETE FROM label_options WHERE label_id='".$label_id."';") or die (DB::error());
			foreach($options as $option) {
				$op_title = isset($option[0]) ? $option[0] : '';
				$op_title_print = isset($option[1]) ? $option[1] : '';
				if ($op_title) DB::query("INSERT INTO label_options (label_id, title, title_print) VALUES ('".$label_id."', '".$op_title."', '".$op_title_print."');") or die (DB::error());
			}
			// output
			return ['response'=>'ok'];
		}
		
		public static function label_delete($data) {
			// vars
			$label_id = isset($data['label_id']) && is_numeric($data['label_id']) ? $data['label_id'] : 0;
			// query
			DB::query("UPDATE labels SET hidden='1' WHERE label_id='".$label_id."' LIMIT 1;") or die (DB::error());
			DB::query("UPDATE products SET label_id='0' WHERE label_id='".$label_id."';") or die (DB::error());
			DB::query("UPDATE product_groups SET label_id='0' WHERE label_id='".$label_id."';") or die (DB::error());
			// output
			return ['response'=>'ok'];
		}
		
		// SERVICE
		
		public static function label_type($id = -1) {
			$result = [];
			if ($id == 0 || $id == -1) $result[] = ['id'=>0, 'title'=>'Универсальная этикетка', 'w'=>80, 'h'=>38];
			if ($id == 1 || $id == -1) $result[] = ['id'=>1, 'title'=>'Расширенная этикетка 1', 'w'=>80, 'h'=>38];
			if ($id == 2 || $id == -1) $result[] = ['id'=>2, 'title'=>'Расширенная этикетка 2', 'w'=>80, 'h'=>38];
			if ($id == 3 || $id == -1) $result[] = ['id'=>3, 'title'=>'Расширенная этикетка 3', 'w'=>80, 'h'=>38];
			if ($id == 4 || $id == -1) $result[] = ['id'=>4, 'title'=>'QR-код', 'w'=>80, 'h'=>38];
			if ($id == -1) return $result; else return isset($result[0]) ? $result[0] : ['id'=>0, 'title'=>'', 'w'=>80, 'h'=>38];
		}
		
		public static function label_info_handling($product) {
			// label
			$label_id = $product['label_id'];
			if (!$label_id && $product['group_id']) {
				$group = Product_Group::product_group_info($product['group_id']);
				$label_id = $group['label_id'];
			}
			// info
			$label = self::label_info($label_id);
			$product['options'] = Product_Option::options_list($product['id']);
			// vars
			$result['product_title'] = mb_strlen($product['title'], 'UTF-8') > 90 ? mb_substr($product['title'], 0, 88, 'UTF-8').'...' : $product['title'];
			$result['company_title'] = $label['company_title'];
			$result['preview_width'] = $label['preview_width'];
			$result['preview_height'] = $label['preview_height'];
			$result['preview_code'] = $label['preview_code'];
			$result['preview_row'] = $label['preview_row'];
			$result['qr'] = $product['qr'];
			$result['template'] = './partials/print/label_'.$label['type_id'].'.html';
			// vars (options)
			$options = [];
			foreach($label['options'] as $a) {
				$found = false;
				foreach($product['options'] as $b) {
					if (preg_match('~^'.$a['title'].'$~iu', $b['title'])) {
						$options[] = ['id'=>0, 'title'=>$a['title'], 'title_print'=>$a['title_print'], 'value'=>$b['value']];
						$found = true;
					}
				}
				if (!$found) {
					if (preg_match('~^'.$a['title'].'$~iu', 'заводской номер')) { $options[] = ['id'=>0, 'title'=>'Заводской номер', 'title_print'=>'Заводской номер', 'value'=>$product['code'] ? $product['code'] : '-']; $found = true; }
					if (preg_match('~^'.$a['title'].'$~iu', 'дата изготовления')) { $options[] = ['id'=>0, 'title'=>'Дата изготовления', 'title_print'=>'Дата изготовления', 'value'=>$product['produced'] ? $product['produced'] : '-']; $found = true; }
					if (preg_match('~^'.$a['title'].'$~iu', 'дата отгрузки')) { $options[] = ['id'=>0, 'title'=>'Дата отгрузки', 'title_print'=>'Дата отгрузки', 'value'=>$product['shipped'] ? $product['shipped'] : '-']; $found = true; }
				}
				if (!$found) {
					$options[] = ['id'=>0, 'title'=>$a['title'], 'title_print'=>$a['title_print'], 'value'=>'-'];
				}
			}
			if (!$options) $options = [
				['id'=>0, 'title'=>'Заводской номер', 'title_print'=>'Заводской номер', 'value'=>$product['code'] ? $product['code'] : '-'],
				['id'=>0, 'title'=>'Дата изготовления', 'title_print'=>'Дата изготовления', 'value'=>$product['produced'] ? $product['produced'] : '-'],
				['id'=>0, 'title'=>'Дата отгрузки', 'title_print'=>'Дата отгрузки', 'value'=>$product['shipped'] ? $product['shipped'] : '-'],
			];
			$result['options'] = $options;
			// output
			return $result;
		}

	}
?>