<?php

	// INIT
	
	function class_autoload($api = false) {
		if ($api) {
			spl_autoload_register(function($class_name) {
				if (preg_match('~Dompdf|Svg~iu', $class_name)) return false;
				require('./../includes/core/class_'.strtolower($class_name).'.php');
			});
		} else {
			spl_autoload_register(function($class_name) {
				if (preg_match('~Dompdf|Svg~iu', $class_name)) return false;
				require('./includes/core/class_'.strtolower($class_name).'.php');
			});
		}
	}
	
	function controllers_common() {
		$includes_dir = opendir('./includes/controllers_common');
		while (($inc_file = readdir($includes_dir)) != false)
			if (strstr($inc_file,'.php')) require('./includes/controllers_common/'.$inc_file);
	}
	
	function controllers_call() {
		$includes_dir = opendir('./includes/controllers_call');
		while (($inc_file = readdir($includes_dir)) != false)
			if (strstr($inc_file,'.php')) require('./includes/controllers_call/'.$inc_file);
	}

	// COMMON

	function code_create($product_id, $type) {
		$code = $type.str_pad($product_id, 10, "0", STR_PAD_LEFT);
		$control = substr(array_sum(str_split($code)), -1);
		return $code.$control;
	}

	function code_parse($str) {
		$code = substr($str, 0, -1);
		$control = substr($str, -1);
		$check_control = substr(array_sum(str_split($code)), -1);
		if ($control != $check_control) return 0;
		$product_id = (int) substr($code, 1);
		return $product_id;
	}
	
	function ts_timezone($ts, $tz) {
		return $ts + $tz * 60;
	}

	function date_str($ts, $mode) {
		// simple
		if (!$ts) return $ts = $mode != 'view' ? '' : 'не указана';
		if ($mode != 'view') return date('d.m.Y', $ts);
		// view
		$d = date('j n Y', $ts);
		$d = explode(' ', $d);
		return $d[0].' '.month_title($d[1], 'gen').' '.$d[2].' года';
	}

	function get_random_filename($path, $extension, $length = 10) {
		// generate
		do {
			$name = substr(md5(microtime().rand(0, 9999)), 0, $length);
			$file = $path.$name.'.'.$extension;
		} while (file_exists($file));
		// output
		return $name.'.'.$extension;
	}
	
	function generate_rand_str($length, $type = 'hexadecimal') {
		// vars
		$str = '';
		if ($type == 'decimal') $chars = '0123456789';
		else if ($type == 'password') $chars = ['0123456789', 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz'];
		else $chars = 'abcdef0123456789';
		// generate
		for ($i = 0; $i < $length; $i++) {
			$microtime = round(microtime(true));
			if ($type != 'password') {
				srand($microtime + $i);
				$size = strlen($chars);
				$str .= $chars[rand(0, $size-1)];
			} else {
				$l = rand(-3, -1);
				$sub = substr($str, $l);
				if (!preg_match('~[0-9]~', $sub)) $chars_a = $chars[0];
				else if (!preg_match('~[A-Z]~', $sub)) $chars_a = $chars[1];
				else $chars_a = $chars[2];
				srand($microtime + $i);
				$size = strlen($chars_a);
				$str .= $chars_a[rand(0, $size-1)];
			}
		}
		// output
		return $str;
	}
	
	function str_hash($str) {
		// vars
		$result = '';
		$str = mb_str_split($str);
		$matches = mb_str_split(' 0123456789abcdefghijklmnopqrstuvwxyzабвгдеёжзийклмнопрстуфхцчшщьыъэюя');
		// replace
		foreach($str as $a) {
			$char = '';
			foreach($matches as $key => $value) {
				if ($a == $value) { $char = $key < 10 ? '0'.$key : $key; break; }
			}
			$result .= $char ? $char : 99; 
		}
		// output
		return str_pad(substr($result, 0, 18), 18, '0');
	}
	
	function mb_str_split($str, $len = 1) {
		$result = [];
		$str_len = mb_strlen($str, 'UTF-8');
		for ($i = 0; $i < $str_len; $i++) $result[] = mb_substr($str, $i, $len, 'UTF-8');
		return $result;
	}
	
	function normalize_phone($phone) {
		// normalize
		$phone = preg_replace('~[^\d]~iu', '', $phone);
		$phone = preg_replace('~^[8]([\d]{3}[\d]{3}[\d]{2}[\d]{2})$~', '7$1', $phone);
		// output
		return $phone;
	}
		
	function item_case($count, $values) {
		// info
		$c1 = $count % 10;
		$c2 = $count % 100;
		// output
		if ($c1 == 1 && ($c2 <= 10 || $c2 > 20)) return $values[0];
		if ($c1 >= 2 && $c1 <= 4 && ($c2 <= 10 || $c2 > 20)) return $values[1];
		return $values[2];
	}

	function dynamic_datetime($datetime, $tz, $date_prepare) {
		// interval
		$seconds = time() - $datetime;
		$timestamp = $datetime + $tz*60;
		$date = date('dmYhHia', $timestamp);
		$day = (int) substr($date, 0, 2);
		$month = month_title(substr($date, 2, 2), 'gen');
		$year = substr($date, 4, 4);
		$hour_12 = substr($date, 8, 2);
		$hour_24 = substr($date, 10, 2);
		$minute = substr($date, 12, 2);
		$meridiem = substr($date, 14, 2);
		$time = $hour_24.':'.$minute;
		$date = substr($date, 0, 8);
		// output
		if ($seconds <= 5) return 'только что';
		if ($seconds < 60) return $seconds.' '.name_case($seconds, ['секунда','секунды','секунд']).' назад';
		if ($seconds < 120) return 'минуту назад';
		if ($seconds < 3540) return round($seconds/60).' '.name_case(round($seconds/60), ['минута','минуты','минут']).' назад';
		if ($seconds < 7140) return 'час назад';
		if ($seconds < 14340) return round($seconds/3600).' '.name_case(round($seconds/3600), ['час','часа','часов']).' назад';
		if ($date == $date_prepare['today']) return 'сегодня '.$time;
		if ($date == $date_prepare['yesterday']) return 'вчера '.$time;	
		if ($seconds <= 6*30*24*60*60) return $day.' '.$month.' в '.$time;
		return $day.' '.$month.' '.$year.' в '.$time;
	}
	
	function month_title($id, $case) {
		$res = ['', ''];
		if ($id == 1) $res = ['январь', 'января'];
		if ($id == 2) $res = ['февраль', 'февраля'];
		if ($id == 3) $res = ['март', 'марта'];
		if ($id == 4) $res = ['апрель', 'апреля'];
		if ($id == 5) $res = ['май', 'мая'];
		if ($id == 6) $res = ['июнь', 'июня'];
		if ($id == 7) $res = ['июль', 'июля'];
		if ($id == 8) $res = ['август', 'августа'];
		if ($id == 9) $res = ['сентябрь', 'сентября'];
		if ($id == 10) $res = ['октябрь', 'октября'];
		if ($id == 11) $res = ['ноябрь', 'ноября'];
		if ($id == 12) $res = ['декабрь', 'декабря'];
		return $case == 'gen' ? $res[1] : $res[0];
	}

	function name_case($count, $names) {
		// calculate
		$count = abs($count);
		$a1 = $count % 10;
		$a2 = $count % 100;
		// output
		if ($a1 == 1 && ($a2 <= 10 || $a2 > 20)) return $names[0];
		if ($a1 >= 2 && $a1 <= 4 && ($a2 <= 10 || $a2 > 20)) return $names[1];
		return $names[2];
	}

	function ts_explode($ts) {
		$d = explode(' ', date('Y y m d H i s', $ts));
		return ['day'=>$d[3], 'month'=>$d[2], 'year'=>$d[0]];
	}

	function error_404() {
		header($_SERVER['SERVER_PROTOCOL'].' 404 Not Found', true, 404);
		HTML::display('./partials/service/404.html');
		exit();
	}
	
	function access_error($mode) {
		if ($mode == 1) echo '';
		else header('Location: /');
		exit();
	}
	
	function error_response($code, $msg, $data = []) {
		$result['error_code'] = $code;
		$result['error_msg'] = $msg;
		if ($data) $result['error_data'] = $data;
		return $result;
	}
	
	function response($response) {
		$response = !isset($response['error_code']) ? ['success'=>'true', 'response'=>$response] : ['success'=>'false', 'error'=>$response];
		echo json_encode($response, JSON_UNESCAPED_UNICODE);
		exit();
	}
	
	function phone_formatting($phone) {
		if (preg_match('~^[78][\d]{10}$~', $phone)) $phone = preg_replace('~^([78])([\d]{3})([\d]{3})([\d]{2})([\d]{2})$~', '$1 ($2) $3-$4-$5', $phone);
		return $phone;
	}

	function paginator($total, $offset, $limit, $method = '', $params = []) {
		// params
		$a = [];
		foreach($params as $p) $a[] = is_numeric($p) ? $p : "'".$p."'";
		$params = $a ? ', '.implode(', ', $a) : '';
		// prev
		$prev_offset = $offset - $limit > 0 ? $offset - $limit : 0;
		$prev_class = $offset == 0 ? 'disabled' : '';
		$prev_onclick = !$prev_class ? ' onclick="'.$method.'('.$prev_offset.$params.');"' : '';
		// next
		$next_offset = $offset + $limit;
		$next_class = $offset + $limit >= $total ? 'disabled' : '';
		$next_onclick = !$next_class ? ' onclick="'.$method.'('.$next_offset.$params.');"' : '';
		// result
		$result = '<div class="paginator">';
		$result .= '<i'.$prev_onclick.' class="icon icon_arrow prev '.$prev_class.'"></i>';
		$result .= '<i'.$next_onclick.' class="icon icon_arrow next '.$next_class.'"></i>';
		$result .= '</div>';
		// output
		return $result;
	}
		
	function title_case_str($str) {
		$first_letter = mb_convert_case(mb_substr($str, 0, 1, 'UTF-8'), MB_CASE_UPPER, 'UTF-8');
		$lenght = mb_strlen($str, 'UTF-8') - 1;
		return $first_letter.mb_convert_case(mb_substr($str, 1, $lenght, 'UTF-8'), MB_CASE_LOWER, 'UTF-8');
	}
	
	function title_short($str) {
		$str = mb_convert_case(trim($str), MB_CASE_LOWER, 'UTF-8');
		$str = preg_replace('/[\'\"\\\\]/iu', ' ', $str);
		$str = preg_replace('/[ ]{2,}/iu', ' ', trim($str));
		return $str;
	}
	
	function csv_import($file, $del) {
		// vars
		$f = fopen($file, 'r');
		$result = array();
		$first_flag = true;
		$column_count = 0;
		// handling
		while ($row = fgetcsv($f, 50000, $del)) {
			if ($first_flag) $column_count = count($row);
			$first_flag = false;
			while(count($row) < $column_count) $row[] = '';
			$result[] = $row;
		}
		fclose($f);
		// output
		return $result;
	}

	function cvs_num($val) {
		$val = str_replace(',', '.', $val);
		$val = preg_replace('/[^\d\.]+/', '', $val);
		return $val && is_numeric($val) ? $val : 0;
	}

    function cvs_float($val) {
        $val = str_replace(',', '.', $val);
        $val = preg_replace('/[^\d\.]+/', '', $val);
        $val = round($val, 2);
        return $val;
    }
	
	function cvs_str($val, $mode) {
		if ($mode == 'csv') return flt_input(iconv('CP1251', 'UTF-8', $val));
		else return $val;
	}
	
	function cvs_ts($val) {
		return $val ? strtotime($val) : 0;
	}

	// DETECTION

	function round_version($text) {
		// round
		$tmp = preg_split('/[\._]/', $text, -1);
		$tmp[0] = (isset($tmp[0]) && $tmp[0]) ? $tmp[0] : 0;
		$tmp[1] = (isset($tmp[1])) ? $tmp[1] : '';
		$result = ($tmp[1] != '') ? $tmp[0].'.'.$tmp[1] : $tmp[0];
		// output
		return $result;
	}
	
	function support_browser($engine_name, $engine_version, $browser_name) {
		// vars
		$engine_name = mb_strtolower($engine_name, 'UTF-8');
		$browser_name = mb_strtolower($browser_name, 'UTF-8');
		// engines
		if ($engine_name == 'webkit') return true;
		else if ($engine_name == 'presto') return ($engine_version && $engine_version < 2.5) ? false : true; // Opera 10.0 & earlier
		else if ($engine_name == 'trident') return ($engine_version && $engine_version <= 5) ? false : true; // IE 9 & earlier
		else if ($engine_name == 'msie') return ($engine_version && $engine_version <= 9) ? false : true; // IE 9 & earlier
		else if ($engine_name == 'gecko') return ($engine_version && substr($engine_version, 0, 4) > 2000 && substr($engine_version, 0, 4) < 2010) ? false : true; // FF 3.5 & earlier
		// browsers
		else if ($browser_name == 'amaya') return false;
		else if ($browser_name == 'amigavoyager') return false;
		else if ($browser_name == 'elinks') return false;
		else if ($browser_name == 'hotjava') return false;
		else if ($browser_name == 'lynx') return false;
		else if ($browser_name == 'netpositive') return false;
		else if ($browser_name == 'oregano') return false;
		else if ($browser_name == 'retawq') return false;
		else if ($browser_name == 'w3m') return false;
		// default
		else return true;
	}
	
	function detection_browser($agent) {
		// patterns (browsers)
		$p_browsers = [
			['m'=>'webkit', 'engine'=>'WebKit', 'p_os'=>'Android|BlackBerry', 'p_engine'=>'WebKit', 'p_browser'=>['Epiphany', '360Browser|360EE|360SE|ABrowse|Amigo|Arora|Avant TriCore|baidubrowser|BOLT|BIDUBrowser|brave|BrowserNG|Coast|CoolBrowser|CoolNovo|Cheshire|Chromium|Chromeum|ChromePlus|coc_coc_browser|DeskBrowse|Dooble|Dolfin|Dorothy|Dragon|Edge|Element Browser|FaBrowser|Fennec|Fluid|Hana|iCab|IEMobile|Iridium|Iris|Iron|Kindle|Kinza|Konqueror|LBBROWSER|Leechcraft|Line|luakit|Lunascape|Maxthon|Mercury|MetaSr|Midori|\bmin\b|MiuiBrowser|MQQBrowser|MxBrowser|MxNitro|Navscape|Nichrome\/self|Ninesky\-android\-mobile|NintendoBrowser|\bNG|NokiaBrowser|OWB|OmniWeb|OneBrowser|Opera Mini|OPR|osb\-browser|Otter|Perk|Polarity|Puffin|QQBrowser|QtCarBrowser|QtWeb Internet Browser|QupZilla|qutebrowser|rekonq|RockMelt|SamsungBrowser|Shiira|Silk|Skyfire|SlimBoat|SmartTV|SparkSafe|Spark|SputnikBrowser|Stainless|SunriseBrowser|Sunrise|Surf|Swing|TaoBrowser|TeaShark|TizenBrowser|Vimprobable|wOSBrowser|UCBrowser|\bUBrowser|UCWEB|Vivaldi|WebBrowser|webOSBrowser|WhiteHat Aviator|YaBrowser|YoloBrowser', 'Chrome', 'Version', 'CPU iPhone OS|CPU OS', 'Safari']],
			['m'=>'presto', 'engine'=>'Presto', 'p_os'=>'', 'p_engine'=>'Presto', 'p_browser'=>['Opera Mini|WhatsApp', 'Opera']],
			['m'=>'trident', 'engine'=>'Trident', 'p_os'=>'', 'p_engine'=>'Trident', 'p_browser'=>['Acoo Browser|AOL|Avant Browser|AvantBrowser|baidubrowser|Crazy Browser|GreenBrowser|EmbeddedWB|Foxy|IEMobile|Ion|iRider|KKMAN|Lunascape|Maxthon|MetaSr|QQBrowser|PPP|Sleipnir|SlimBrowser|TencentTraveler|TheWorld|UltraBrowser|uZardWeb', 'MSIE|rv']],
			['m'=>'msie', 'engine'=>'MSIE', 'p_os'=>'', 'p_engine'=>'MSIE', 'p_browser'=>['Acoo Browser|America Online Browser|Avant Browser|Blazer|Crazy Browser|GreenBrowser|Escape|IEMobile|iRider|Lobo|Maxthon|MyIE2|TencentTraveler|TheWorld|Tjusig|UltraBrowser', 'MSIE']],
			['m'=>'gecko', 'engine'=>'Gecko', 'p_os'=>'', 'p_engine'=>'Gecko Minefield|Gecko', 'p_browser'=>['Arora|Beonex|BonEcho|Camino|Chimera|CometBird|conkeror|Cyberfox|Cunaguaro|EnigmaFox|Epiphany|Fennec|Firebird|Flock|Flybird|Galeon|GranParadiso|Hana|IceDragon|Iceweasel|IceCat|Iceape|K\-Meleon|K\-Ninja|Kapiko|Kazehakase|KMLite|Konqueror|Lightning|Light|lolifox|Lorentz|Lunascape|LYnX|Madfox|Maemo Browser|Midori|Minimo|Minefield|MultiZilla|myibrow|Namoroka|Netscape|OmniWeb|Orca|PaleMoon|\bPB|Phoenix|Pogo|Prism|S40OviBrowser|SailfishBrowser|SeaMonkey|Shiira|Shiretoko|Sundial|Sunrise|Sylera|TenFourFox|Vonkeror|Waterfox|Wyzo', 'Firefox']],
			['m'=>'', 'engine'=>'', 'p_os'=>'', 'p_engine'=>'', 'p_browser'=>['\bABrowse|amaya|AmigaVoyager|Arachne|AtomicBrowser|Cyberdog|Deepnet Explorer|Dillo|Dolfin|Doris|Fireweb Navigator|Galaxy|Google Desktop|ELinks|GoBrowser|HotJava|IBrowse|iCab|iNet Browser|iTunes|Links|Lynx|MIB|Midori|Monyq|NetFront|NetPositive|NetSurf|OneBrowser|Oregano|Polaris|retawq|SEMC\-Browser|Sundance|Vimprobable|WebPro|UCBrowser|UCWEB|UP\.Browser|w3m|YaBrowser', 'CPU OS']]
		];
		// patterns (handling)
		$p_handling = [
			// simple
			['p_b'=>'360EE', 'r'=>'360 Browser'],
			['p_b'=>'360SE', 'r'=>'360 Browser'],
			['p_b'=>'America Online Browser', 'r'=>'AOL'],
			['p_b'=>'AvantBrowser', 'r'=>'Avant Browser'],
			['p_b'=>'baidubrowser', 'r'=>'Baidu Browser'],
			['p_b'=>'BIDUBrowser', 'r'=>'Baidu Browser'],
			['p_b'=>'coc_coc_browser', 'r'=>'Coc Coc Browser'],
			['p_b'=>'CPU iPhone OS', 'r'=>'Safari'],
			['p_b'=>'CPU OS', 'r'=>'Safari'],
			['p_b'=>'Dragon', 'r'=>'Comodo IceDragon'],
			['p_b'=>'Fireweb Navigator', 'r'=>'Fireweb'],
			['p_b'=>'IceDragon', 'r'=>'Comodo IceDragon'],
			['p_b'=>'IEMobile', 'r'=>'IE Mobile'],
			['p_b'=>'MetaSr', 'r'=>'Sogou Browser'],
			['p_b'=>'MIB', 'r'=>'Motorola Browser'],
			['p_b'=>'myibrow', 'r'=>'My Internet'],
			['p_b'=>'Nichrome/self', 'r'=>'Rambler Browser'],
			['p_b'=>'Ninesky-android-mobile', 'r'=>'NineSky'],
			['p_b'=>'NokiaBrowser', 'r'=>'Nokia Browser'],
			['p_b'=>'OPR', 'r'=>'Opera'],
			['p_b'=>'PB', 'r'=>'Pirate Browser'],
			['p_b'=>'PPP', 'r'=>'PPP Browser'],
			['p_b'=>'QtWeb Internet Browser', 'r'=>'QtWeb Browser'],
			['p_b'=>'S40OviBrowser', 'r'=>'Nokia Browser'],
			['p_b'=>'SailfishBrowser', 'r'=>'Sailfish Browser'],
			['p_b'=>'SamsungBrowser', 'r'=>'Samsung Browser'],
			['p_b'=>'TizenBrowser', 'r'=>'Tizen Browser'],
			['p_b'=>'UCBrowser', 'r'=>'UC Browser'],
			['p_b'=>'UBrowser', 'r'=>'UC Browser'],
			['p_b'=>'UCWEB', 'r'=>'UC Browser'],
			// composite
			['p_os'=>'Android', 'p_b'=>'Version', 'r'=>'Android Browser'],
			['p_os'=>'BlackBerry', 'p_b'=>'Version', 'r'=>'BB Browser'],
			['p_e'=>'WebKit', 'p_b'=>'Version', 'r'=>'Safari'],
			['p_e'=>'Trident', 'p_b'=>'rv', 'r'=>'MSIE'],
		];
		// lower case
		$agent_m = mb_strtolower($agent, 'UTF-8');
		// vars
		$os_n = '';
		$engine_n = ''; $engine_v = 0;
		$browser_n = ''; $browser_v = 0;
		$support = true;
		// search
		foreach($p_browsers as $a) {
			if (!$a['m'] || strpos($agent_m, $a['m']) !== false) {
				// os
				if ($a['p_os']) {
					preg_match('/('.$a['p_os'].')/i', $agent, $info);
					$os_n = isset($info[1]) ? $info[1] : '';
				}
				// engine
				if ($a['p_engine']) {
					preg_match('/('.$a['p_engine'].')[\/: ]{0,3}([0-9\.]+)/i', $agent, $info);
					$engine_n = $a['engine'];
					$engine_v = isset($info[2]) ? round_version($info[2]) : 0;
				}
				// browser
				foreach($a['p_browser'] as $b) {
					if (!$browser_n) {
						preg_match('/('.$b.')[\/:v ]{0,3}([0-9\._]{0,10})/i', $agent, $info);
						$browser_n = isset($info[1]) ? $info[1] : '';
						$browser_v = isset($info[2]) ? round_version($info[2]) : 0;
					}
				}
				// handling (opera 9.80)
				if ($browser_n == 'Opera' && $browser_v == '9.80') {
					$agent = preg_replace('/[^\d\.]/', '', $agent);
					$browser_v = round_version(substr($agent, -5));
				}
				// handling (common)
				foreach($p_handling as $b) {
					if ((!isset($b['p_e']) || $engine_n == $b['p_e']) && (!isset($b['p_os']) || $os_n == $b['p_os']) && $browser_n == $b['p_b']) $browser_n = $b['r'];
				}
				// support
				$support = support_browser($engine_n, $engine_v, $browser_n);
				// output
				return ['engine'=>['n'=>$engine_n, 'v'=>$engine_v], 'browser'=>['n'=>$browser_n, 'v'=>$browser_v], 'support'=>$support];
			}
		}
		// not found
		return ['engine'=>['n'=>$engine_n, 'v'=>$engine_v], 'browser'=>['n'=>$browser_n, 'v'=>$browser_v], 'support'=>$support];
	}
	
	function detection_os($agent) {
		// patterns (os)
		$p_os = [
			// mobile
			// mobile (android)
			['m'=>'android', 'os'=>'Android', 'name'=>'Android', 'p'=>['Android'], 'mobile'=>1],
			// mobile (blackberry)
			['m'=>'blackberry', 'os'=>'BlackBerry', 'name'=>'BlackBerry', 'p'=>['BlackBerry'], 'mobile'=>1],
			// mobile (ios)
			['m'=>'ipad', 'os'=>'iOS', 'name'=>'iPad', 'p'=>['CPU OS|CPU iPhone OS'], 'mobile'=>2],
			['m'=>'ipod', 'os'=>'iOS', 'name'=>'iPod', 'p'=>['iPhone OS'], 'mobile'=>1],
			['m'=>'iphone', 'os'=>'iOS', 'name'=>'iPhone', 'p'=>['iPhone OS|CPU OS', 'iPhone'], 'mobile'=>1],
			// mobile (windows)
			['m'=>'windows phone', 'os'=>'Windows', 'name'=>'Windows Phone', 'p'=>['Windows Phone OS|Windows Phone'], 'mobile'=>1],
			['m'=>'windows mobile', 'os'=>'Windows', 'name'=>'Windows Mobile', 'p'=>['Windows Mobile'], 'mobile'=>1],
			['m'=>'windows ce', 'os'=>'Windows', 'name'=>'Windows CE', 'p'=>['Windows CE'], 'mobile'=>1],
			// mobile (linux)
			['m'=>'meego', 'os'=>'Linux', 'name'=>'MeeGo', 'p'=>['MeeGo'], 'mobile'=>1],
			['m'=>'tizen', 'os'=>'Linux', 'name'=>'Tizen', 'p'=>['Tizen'], 'mobile'=>1],
			// mobile (other)
			['m'=>'bada', 'os'=>'Bada', 'name'=>'Bada', 'p'=>['Bada'], 'mobile'=>1],
			['m'=>'palm', 'os'=>'Palm OS', 'name'=>'Palm OS', 'p'=>['PalmOS'], 'mobile'=>1],
			['m'=>'rim', 'os'=>'RIM OS', 'name'=>'RIM OS', 'p'=>['RIM Tablet OS'], 'mobile'=>1],
			['m'=>'symb', 'os'=>'Symbian OS', 'name'=>'Symbian OS', 'p'=>['SymbOS|SymbianOS|Symbian'], 'mobile'=>1],
			// desktop
			// desktop (chrome os)
			['m'=>'cros', 'os'=>'Chrome OS', 'name'=>'Chrome OS', 'p'=>['CrOS'], 'mobile'=>1],
			// desktop (windows)
			['m'=>'windows nt', 'os'=>'Windows', 'name'=>'Windows NT', 'p'=>['Windows NT'], 'mobile'=>0],
			['m'=>'winnt', 'os'=>'Windows', 'name'=>'Windows NT', 'p'=>['WinNT'], 'mobile'=>0],
			['m'=>'windows', 'os'=>'Windows', 'name'=>'Windows', 'p'=>['Windows'], 'mobile'=>0],
			// desktop (linux)
			['m'=>'caixamagica', 'os'=>'Linux', 'name'=>'CaixaMagica', 'p'=>['CaixaMagica'], 'mobile'=>0],
			['m'=>'centos', 'os'=>'Linux', 'name'=>'CentOS', 'p'=>['CentOS'], 'mobile'=>0],
			['m'=>'debian', 'os'=>'Linux', 'name'=>'Debian', 'p'=>['Debian'], 'mobile'=>0],
			['m'=>'elementary os', 'os'=>'Linux', 'name'=>'Elementary OS', 'p'=>['Elementary OS'], 'mobile'=>0],
			['m'=>'fedora', 'os'=>'Linux', 'name'=>'Fedora', 'p'=>['Fedora'], 'mobile'=>0],
			['m'=>'gentoo', 'os'=>'Linux', 'name'=>'Gentoo', 'p'=>['Gentoo'], 'mobile'=>0],
			['m'=>'hpwos', 'os'=>'Linux', 'name'=>'hpwOS', 'p'=>['hpwOS'], 'mobile'=>0],
			['m'=>'mandriva', 'os'=>'Linux', 'name'=>'Mandriva', 'p'=>['Mandriva'], 'mobile'=>0],
			['m'=>'mint', 'os'=>'Linux', 'name'=>'Mint', 'p'=>['Mint'], 'mobile'=>0],
			['m'=>'red hat', 'os'=>'Linux', 'name'=>'Red Hat', 'p'=>['Red Hat Enterprise Linux|Red Hat'], 'mobile'=>0],
			['m'=>'rhel', 'os'=>'Linux', 'name'=>'Red Hat', 'p'=>['RHEL'], 'mobile'=>0],
			['m'=>'sailfish', 'os'=>'Linux', 'name'=>'Sailfish', 'p'=>['Sailfish'], 'mobile'=>0],
			['m'=>'slackware', 'os'=>'Linux', 'name'=>'Slackware', 'p'=>['Slackware'], 'mobile'=>0],
			['m'=>'startos', 'os'=>'Linux', 'name'=>'StartOS', 'p'=>['StartOS'], 'mobile'=>0],
			['m'=>'suse', 'os'=>'Linux', 'name'=>'SUSE', 'p'=>['SUSE'], 'mobile'=>0],
			['m'=>'ubuntu', 'os'=>'Linux', 'name'=>'Ubuntu', 'p'=>['Ubuntu'], 'mobile'=>0],
			['m'=>'linux', 'os'=>'Linux', 'name'=>'Linux', 'p'=>['Linux'], 'mobile'=>0],
			// desktop (unix)
			['m'=>'sunos', 'os'=>'Unix', 'name'=>'SunOS', 'p'=>['SunOS'], 'mobile'=>0],
			['m'=>'unix', 'os'=>'Unix', 'name'=>'Unix', 'p'=>['UnixWare|Unix'], 'mobile'=>0],
			// desktop (mac)
			['m'=>'mac os', 'os'=>'Mac OS', 'name'=>'Mac OS X', 'p'=>['Mac OS X Mach-O|Mac OS X'], 'mobile'=>0],
			['m'=>'mac', 'os'=>'Mac OS', 'name'=>'Mac OS', 'p'=>['Mac_PowerPC'], 'mobile'=>0],
			// desktop (other)
			['m'=>'beos', 'os'=>'BeOS', 'name'=>'BeOS', 'p'=>['BeOS'], 'mobile'=>0],
			['m'=>'darwin', 'os'=>'Darwin', 'name'=>'Darwin', 'p'=>['Darwin'], 'mobile'=>0],
			['m'=>'openbsd', 'os'=>'OpenBSD', 'name'=>'OpenBSD', 'p'=>['OpenBSD'], 'mobile'=>0],
			['m'=>'amigaos', 'os'=>'AmigaOS', 'name'=>'AmigaOS', 'p'=>['AmigaOS'], 'mobile'=>0],
			['m'=>'netbsd', 'os'=>'NetBSD', 'name'=>'NetBSD', 'p'=>['NetBSD'], 'mobile'=>0],
			['m'=>'freebsd', 'os'=>'FreeBSD', 'name'=>'FreeBSD', 'p'=>['kFreeBSD|FreeBSD'], 'mobile'=>0],
		];
		// patterns (handling)
		$p_handling = [
			['n'=>'Windows NT', 'v'=>'5.0', 'r_n'=>'Windows', 'r_v'=>'2000'],
			['n'=>'Windows NT', 'v'=>'5.1', 'r_n'=>'Windows', 'r_v'=>'XP'],
			['n'=>'Windows NT', 'v'=>'5.2', 'r_n'=>'Windows', 'r_v'=>'XP'],
			['n'=>'Windows NT', 'v'=>'6.0', 'r_n'=>'Windows', 'r_v'=>'Vista'],
			['n'=>'Windows NT', 'v'=>'6.1', 'r_n'=>'Windows', 'r_v'=>'7.0'],
			['n'=>'Windows NT', 'v'=>'6.2', 'r_n'=>'Windows', 'r_v'=>'8.0'],
			['n'=>'Windows NT', 'v'=>'6.3', 'r_n'=>'Windows', 'r_v'=>'8.1'],
			['n'=>'Windows NT', 'v'=>'10.0', 'r_n'=>'Windows', 'r_v'=>'10.0'],
		];
		// lower case
		$agent_m = mb_strtolower($agent, 'UTF-8');
		// vars
		$os = ''; $name = ''; $version = 0;
		// search
		foreach($p_os as $a) {
			if (!$a['m'] || strpos($agent_m, $a['m']) !== false) {
				// name
				foreach($a['p'] as $b) {
					if (!$name) {
						preg_match('/('.$b.')[\/\-_ ]{0,3}([0-9_\.]{0,10})/i', $agent, $info);
						$os = $a['os'];
						$name = $a['name'];
						$version = isset($info[2]) ? round_version($info[2]) : 0;
					}
				}
				// handling
				foreach($p_handling as $b) {
					if ($name == $b['n'] && $version == $b['v']) { $name = $b['r_n']; $version = $b['r_v']; }
				}
				// output
				return ['os'=>$os, 'name'=>$name, 'version'=>$version, 'mobile'=>$a['mobile']];
			}
		}
		// not found
		return ['os'=>$os, 'name'=>$name, 'version'=>$version, 'mobile'=>0];
	}
?>