<?php
	class Route {
		
		// VARS

		public static $path = '';
		public static $query = [];
		
		// GENERAL

		public static function init() {
			// vars
			$url = $_SERVER['REQUEST_URI'];
			if (substr($url, 0, 1) == '/') $url = substr($url, 1);
			$url = explode('?', $url);
			// path
			self::$path = isset($url[0]) && $url[0] ? flt_input($url[0]) : 'home';
			// queries
			isset($url[1]) ? parse_str($url[1], $queries) : $queries = [];
			foreach ($queries as $key => $value) self::$query[flt_input($key)] = flt_input($value);
			// seo
			return self::route_common();
		}

		private static function route_common() {
			// vars
			$company = Company::company_info(Session::$company_id);
			// account
			if (Session::$access) {
				self::route_home($company);
				HTML::assign('section_content', './partials/home.html');
				if (self::$path == 'dashboard') return controller_dashboard(self::$query);
				else if (self::$path == 'labels') return self::labels();
				else if (self::$path == 'logout') Session::logout();
				else if (self::$path == 'notifications') return controller_notifications(self::$query);
				else if (self::$path == 'picks') return controller_picks(self::$query);
				else if (self::$path == 'scans') return controller_scans(self::$query);
				else if (self::$path == 'settings') return controller_settings(self::$query);
				else if (self::$path == 'search') return controller_search(self::$query);
				else if (self::$path == 'users') return controller_users(self::$query);
				else if (strpos(self::$path, 'bills') !== false) return self::bills();
				else if (strpos(self::$path, 'passports') !== false) return self::passports();
                else if (strpos(self::$path, 'products') !== false) return self::products();
				else if (strpos(self::$path, 'support') !== false) return self::support();
				else if (strpos(self::$path, 'tasks') !== false) return self::tasks();
                else return self::owners();
            }
            // intro
			else {
				if (self::$path == 'login') {
					HTML::assign('section_content', './partials/section/intro/login.html');
				} else if (in_array(self::$path, ['home', 'marker', 'scaner', 'help'])) {
					controller_intro(self::$path);
				} else if (preg_match('~^products/([\d]+)$~i', self::$path, $m)) {
					$id = isset($m[1]) ? $m[1] : 0;
					controller_product_simple($id);
				} else if (self::$path == 'password_restore') {
					HTML::assign('section_content', './partials/section/intro/password_restore.html');
				} else if (self::$path == 'new_password') {
					HTML::assign('token', self::$query['token']);
					HTML::assign('section_content', './partials/section/intro/new_password.html');
				} else {
                    $page_id = self::route_page();
                    if ($page_id) return controller_page($page_id);
                    else HTML::assign('section_content', './partials/section/intro/login.html');
                }
			}
		}
		
		public static function route_call($dpt, $sub, $act, $data) {
			// routes
			if ($dpt == 'common') $result = controller_common($sub, $act, $data);
			else if ($dpt == 'bill') $result = controller_bill($sub, $act, $data);
			else if ($dpt == 'dashboard') $result = controller_dashboard($sub, $act, $data);
			else if ($dpt == 'label') $result = controller_label($sub, $act, $data);
			else if ($dpt == 'owner') $result = controller_owner($sub, $act, $data);
			else if ($dpt == 'passport') $result = controller_passport($sub, $act, $data);
			else if ($dpt == 'pick') $result = controller_pick($sub, $act, $data);
			else if ($dpt == 'product') $result = controller_product($sub, $act, $data);
			else if ($dpt == 'scan') $result = controller_scan($sub, $act, $data);
			else if ($dpt == 'settings') $result = controller_settings($sub, $act, $data);
			else if ($dpt == 'support') $result = controller_support($sub, $act, $data);
			else if ($dpt == 'task') $result = controller_task($sub, $act, $data);
			else if ($dpt == 'user') $result = controller_user($sub, $act, $data);
			else $result = [];
			// output
			echo json_encode($result, true);
			exit();
		}
		
		// ROUTES
		
		private static function bills() {
			$act = isset(self::$query['act']) ? self::$query['act'] : '';
			$id = isset(self::$query['id']) ? self::$query['id'] : 0;
			if ($act == 'download') return controller_bill_download($id);
			else return controller_bills(self::$query);
		}
		
		private static function labels() {
			$act = isset(self::$query['act']) ? self::$query['act'] : '';
			if ($act == 'edit') return controller_label_edit(self::$query);
			return controller_labels(self::$query);
		}
		
		private static function passports() {
			// list
			if (self::$path == 'passports') return controller_passports(self::$query);
			// details
			preg_match('~^passports/([\d]+)$~i', self::$path, $m);
			$id = isset($m[1]) ? $m[1] : 0;
			if (isset(self::$query['section'])) {
				self::$path = 'passport_section';
				return controller_passport_section(self::$query, $id);
			} else if (isset(self::$query['annex'])) {
				self::$path = 'passport_annex';
				return controller_passport_annex(self::$query, $id);
			} else if (isset(self::$query['files'])) {
				self::$path = 'passport_files';
				return controller_passport_files(self::$query, $id);
			}else {
				self::$path = 'passport';
				return controller_passport(self::$query, $id);
			}
		}
		
		private static function products() {
			// list
			if (self::$path == 'products') return controller_products(self::$query);
			// details
			preg_match('~^products/([\d]+)$~i', self::$path, $m);
			$id = isset($m[1]) ? $m[1] : 0;
			$act = isset(self::$query['act']) ? self::$query['act'] : '';
			self::$path = 'product';
			if ($act == 'print') return controller_product_print($id, self::$query);
			else if ($act == 'print_group') return controller_product_print_group($id, self::$query);
			else if ($act == 'download') return controller_product_download($id, self::$query);
			else return controller_product(self::$query, $id);
		}
		
		private static function support() {
			// list
			if (self::$path == 'support') return controller_support(self::$query);
			// details
			preg_match('~^support/([\d]+)$~i', self::$path, $m);
			$id = isset($m[1]) ? $m[1] : 0;
			return controller_support_ticket($id, self::$query);
		}
		
		private static function tasks() {
			// list
			if (self::$path == 'tasks') return controller_tasks(self::$query);
			// details
			preg_match('~^tasks/([\d]+)$~i', self::$path, $m);
			$id = isset($m[1]) ? $m[1] : 0;
			$act = isset(self::$query['act']) ? self::$query['act'] : '';
			if ($act == 'edit') return controller_task_edit($id, self::$query);
		}
		
		private static function owners() {
			$page_id = self::route_page();
			if ($page_id) return controller_page($page_id);
			else error_404();
		}
		
		// SERVICE
        
        private static function route_page() {
			$q = DB::query("SELECT page_id FROM pages WHERE screen_name='".self::$path."' LIMIT 1;") or die (DB::error());
			return ($row = DB::fetch_row($q)) ? $row['page_id'] : 0;
        }
		
		private static function route_home($company) {
			if (self::$path != 'home') return false;
			if (Session::$access == 2) self::$path = 'dashboard';
			if (Session::$access == 3 && $company['show_passports']) self::$path = 'passports';
			if (Session::$access == 3 && $company['show_products']) self::$path = 'products';
			if (Session::$access == 4) self::$path = 'picks';
		}

	}
?>