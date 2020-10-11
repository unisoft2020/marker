<?php
	class Product_Option {
		
		// GENERAL
		
		public static function options_list_full($product_id, $code) {
			// info
			$options = self::options_list($product_id);
			// add
			$res = [];
			if ($code && Session::$mode != 2) $res[] = ['id'=>0, 'title'=>'Заводской номер', 'value'=>$code, 'units_title'=>'', 'created'=>''];
			$res = array_merge($res, $options);
			// output
			return $res;
		}
		
		public static function options_simple_list($product_ids) {
			// vars
			$info = [];
			// info
			$q = DB::query("SELECT product_id, option_title, option_value FROM product_options WHERE product_id IN (".implode(",", $product_ids).");") or die (DB::error());
			while ($row = DB::fetch_row($q)) {
				if (!isset($info[$row['option_title']])) $info[$row['option_title']] = [];
				$info[$row['option_title']][] = ['id'=>$row['product_id'], 'value'=>$row['option_value']];
			}
			// output
			return $info;
		}

		public static function options_list($product_id, $mode = 'default') {
			// vars
			$info = [];
			// info
			$q = DB::query("SELECT option_id, group_id, option_title, option_value, option_units_title, option_units_id, created FROM product_options WHERE product_id='".$product_id."' AND hidden<>'1';") or die (DB::error());
			while ($row = DB::fetch_row($q)) {
				$info[] = [
					'id'=>$row['option_id'],
					'group_id'=>$row['group_id'],
					'title'=>$mode != 'copy' && Session::$mode != 2 ? flt_output($row['option_title']) : $row['option_title'],
					'value'=>$mode != 'copy' && Session::$mode != 2 ? flt_output($row['option_value']) : $row['option_value'],
					'units_title'=>$mode != 'copy' && Session::$mode != 2 ? flt_output($row['option_units_title']) : $row['option_units_title'],
					'units_id'=>$row['option_units_id'],
					'created'=>date('d.m.Y H:i', ts_timezone($row['created'], Session::$tz))
				];
			}
			// output
			return $info;
		}

		// ACTIONS
		
		public static function option_add($data) {
			// vars
			$title = isset($data['title']) ? $data['title'] : '';
			$option = ['id'=>0, 'title'=>$title, 'value'=>'', 'units_title'=>'', 'created'=>''];
			$count = 2;
			// info
			$units = Unit::units_list();
        	// output
			HTML::assign('option', $option);
			HTML::assign('count', $count);
			HTML::assign('units', $units);
			return ['html'=>HTML::fetch('./partials/modal/products/product_edit_option.html')];
		}
		
		public static function options_from_label($data) {
			// vars
			$label_id = isset($data['label_id']) && is_numeric($data['label_id']) ? $data['label_id'] : 0;
			$label_options = [];
			// info
			$options = Label_Option::label_options_list($label_id);
			// parse
			foreach($options as $option) {
				if (in_array($option['title'], ['Заводской номер', 'Дата изготовления', 'Дата отгрузки'])) continue;
				$label_options[] = $option;
			}
			// output
			return ['label_options'=>$label_options];
		}
		
		// CUSTOM
		
		public static function options_tube($product_id) {
			// vars
			$info = [
				'melt'=>0,
				'length'=>0,
				'coating'=>'',
				'coating_date'=>'',
				'part'=>'',
				'certificate'=>'',
				'tu'=>'',
				'skk'=>'',
				'package'=>'',
				'tube_number'=>'',
			];
			// info
			$options = self::options_list($product_id);
			// search
			foreach($options as $option) {
				if (preg_match('~(номер|№)[ ]*плавки|плавка~iu', $option['title'])) $info['melt'] = $option['value'];
				if (preg_match('~длина~iu', $option['title'])) $info['length'] = $option['value'];
				if (preg_match('~покрытие~iu', $option['title'])) $info['coating'] = $option['value'];
				if (preg_match('~дата покрытия~iu', $option['title'])) $info['coating_date'] = $option['value'];
				if (preg_match('~(номер|№)[ ]*партии|партия([ ]*№)?~iu', $option['title'])) $info['part'] = $option['value'];
				if (preg_match('~сертификат~iu', $option['title'])) $info['certificate'] = $option['value'];
				if (preg_match('~^ту$~iu', $option['title'])) $info['tu'] = $option['value'];
				if (preg_match('~^скк$~iu', $option['title'])) $info['skk'] = $option['value'];
				if (preg_match('~пакет~iu', $option['title'])) $info['package'] = $option['value'];
				if (preg_match('~(номер|№)[ ]*трубы~iu', $option['title'])) $info['tube_number'] = $option['value'];
			}
			// output
			return $info;
		}

	}
?>