<?php

	class Session {
		
		// VARS
		
		public static $agent = '';
		public static $access = 0;
		public static $company_id = 0;
		public static $cookies_secure = false;
		public static $ip = '127.0.0.1';
		public static $menu = 1; // 0 - compact, 1 - full
		public static $mode = 0; // 0 - web, 1 - call, 2 - api
		public static $short = 0;
		public static $sid = 0;
		public static $type = 0; // 0 - admin, 1 - contractor
		public static $ts = 0;
		public static $tz = 0;
		public static $token = '';
		public static $user_id = 0;
		public static $user_groups = [];
		public static $url = '';

		// INIT

		public static function init($mode = 0, $data = []) {
			self::$mode = $mode;
			self::$ts = time();
			self::$cookies_secure = SITE_SCHEME == 'https' ? true : false;
			$mode != 2 ? self::session_common() : self::session_api($data);
			self::session_last_active();
		}
		
		private static function session_common() {
			// vars (common)
			self::$sid = isset($_COOKIE['sid']) && is_numeric($_COOKIE['sid']) ? flt_input($_COOKIE['sid']) : 0;
			self::$token = isset($_COOKIE['token']) ? flt_input($_COOKIE['token']) : '';
			self::$tz = isset($_COOKIE['timezone']) && is_numeric($_COOKIE['timezone']) && $_COOKIE['timezone'] >= -720 && $_COOKIE['timezone'] <= 720 ? round(flt_input($_COOKIE['timezone'])) : DEFAULT_TIMEZONE;
			// vars (env)
			self::$agent = isset($_SERVER['HTTP_USER_AGENT']) ? flt_input(substr($_SERVER['HTTP_USER_AGENT'], 0, 255)) : '';
			self::$ip = flt_input($_SERVER['REMOTE_ADDR']);
			self::$menu = isset($_COOKIE['menu']) && is_numeric($_COOKIE['menu']) ? (int) $_COOKIE['menu'] : 1;
			self::$url = flt_input($_SERVER['REQUEST_URI']);
			// info
			self::session_common_do();
		}

		private static function session_common_do() {
			// query (session)
			$q = DB::query("SELECT user_id, company_id, access, short, token, updated FROM sessions WHERE sid='".self::$sid."' LIMIT 1;") or die (DB::error());
			$row = DB::fetch_row($q);
			// validate
			if (!$row) return self::unset_cookie_sid();
			if (!self::$token || self::$token != $row['token']) return self::unset_cookie_token();
			// vars
			self::$user_id = $row['user_id'];
			self::$company_id = $row['company_id'];
			self::$access = $row['access'];
			self::$short = $row['short'];
			// actions
			$diff = self::$short ? 60 : 3600*24;
			if ((self::$ts - $row['updated']) > $diff) self::session_refresh();
			self::session_user_groups();
		}
		
		private static function session_api($data) {
			// vars
			self::$sid = isset($data['sid']) && $data['sid'] > 0 ? $data['sid'] : 0;
			self::$token = isset($data['token']) ? $data['token'] : '';
			// query
			$q = DB::query("SELECT user_id, company_id, access, token, tz FROM sessions WHERE token='".self::$token."' LIMIT 1;") or die (DB::error());
			$row = DB::fetch_row($q);
			// validate
			if (!$row) return error_response(1005, 'User authorization failed: invalid access sid.');
			if (!self::$token || self::$token != $row['token']) return error_response(1005, 'User authorization failed: invalid access token.');
			// vars
			self::$user_id = $row['user_id'];
			self::$company_id = $row['company_id'];
			self::$access = $row['access'];
			self::$ip = flt_input($_SERVER['REMOTE_ADDR']);
			self::$tz = $row['tz'];
		}

		// AUTH

		public static function login($data) {
			// vars
			$login = isset($data['login']) && trim($data['login']) ? mb_convert_case(trim($data['login']), MB_CASE_LOWER, 'UTF-8') : '';
			$password = isset($data['password']) ? $data['password'] : '';
			$short = isset($data['short']) ? $data['short'] : 0;
			// error (empty)
			if (!$login && !$password) return error_response(2001, 'Один из параметров пропущен или передан в неверном формате.', ['login'=>'заполните поле', 'password'=>'заполните поле']);
			if (!$login) return error_response(2001, 'Один из параметров пропущен или передан в неверном формате.', ['login'=>'заполните поле']);
			if (!$password) return error_response(2001, 'Один из параметров пропущен или передан в неверном формате.', ['password'=>'заполните поле']);
			// query
			$q = DB::query("SELECT user_id, company_id, access, password, password_salt, last_login, login_attempts, blocked FROM users WHERE email='".$login."' AND hidden<>'1' LIMIT 1;") or die (DB::error());
			$row = DB::fetch_row($q);
			// error (unregistered & blocked)
			if (!$row) return error_response(2002, 'Пользователь с указанной почтой не найден', ['login'=>'пользователь не зарегистрирован']);
			if ($row['blocked']) return error_response(2003, 'Пользователь заблокирован, свяжитесь администратором для уточнения деталей', ['login'=>'пользователь заблокирован']);
			$login_attempts = LOGIN_ATTEMPTS - self::login_attempts($row['user_id'], $row['last_login'], $row['login_attempts']);
			if (!$login_attempts) return error_response(2003, 'Для этого пользователя превышено количество попыток ввода неверного пароля, попробуйте позднее.', ['password'=>'превышел лимит ошибок, попробуйте позднее']);
			// error (password)
			if ($row['password'] != p_hash($password, $row['password_salt'])) {
				DB::query("UPDATE users SET login_attempts=login_attempts+1, last_login='".self::$ts."' WHERE user_id='".$row['user_id']."' LIMIT 1;") or die (DB::error());
				return error_response(2004, 'Неверный пароль, количество оставшихся попыток - '.$login_attempts.'.', ['password'=>'неверный пароль']);
			}
			// success
			self::$user_id = $row['user_id'];
			self::$company_id = $row['company_id'];
			self::$access = $row['access'];
			// update
			Stats::stats_update(0, 0, 0, 1);
			return self::session_create($short);
		}

		public static function logout() {
			// query
			DB::query("UPDATE sessions SET user_id='0', company_id='0', access='0', token='' WHERE sid='".self::$sid."' LIMIT 1;") or die (DB::error());
			// output
			if (self::$mode != 2) {
				self::unset_cookie_token();
				header('Location: /login');
				exit();
			} else {
				return ['status'=>'logout success'];
			}
		}

		private static function session_create($short) {
			// vars
			$exp = $short ? '+10 minutes' : '+1 year';
			// init
			if (!self::$sid) self::session_create_new($short);
			// token
			if (self::$mode != 2) self::unset_cookie_token();
			self::$token = generate_rand_str(40);
			if (self::$mode != 2) setcookie('token', self::$token, strtotime($exp), '/', '', self::$cookies_secure, true);
			// update
			$query = "UPDATE users SET login_attempts='0', last_login='".self::$ts."' WHERE user_id='".self::$user_id."' LIMIT 1;";
			$query .= "UPDATE sessions SET user_id='".self::$user_id."', company_id='".self::$company_id."', access='".self::$access."', short='".$short."', token='".self::$token."', logged='".self::$ts."' WHERE sid='".self::$sid."' LIMIT 1;";
			DB::query($query) or die (DB::error());
			// output
			return ['sid'=>self::$sid, 'token'=>self::$token];
		}
		
		private static function session_create_new($short) {
			// agent
			$info = self::session_agent();
			$exp = $short ? '+1 year' : '+1 year';
			// create
            self::$ts = time();
            self::$tz = 180;
			DB::query("INSERT INTO sessions (tz, created) VALUES ('".self::$tz."', '".self::$ts."');") or die (DB::error());
			self::$sid = DB::insert_id();
			// cookies
			if (self::$mode != 2) setcookie('sid', self::$sid, strtotime($short), '/', '', self::$cookies_secure, true);
		}
		
		private static function login_attempts($user_id, $last_login, $login_attempts) {
			// clear
			if ((self::$ts - $last_login) > 3600) {
				DB::query("UPDATE users SET login_attempts='0' WHERE user_id='".$user_id."' LIMIT 1;") or die (DB::error());
				return 0;
			}
			// default
			return $login_attempts;
		}
		
		// SERVICE
		
		private static function session_agent() {
			// detect
			$browser = detection_browser(self::$agent);
			$os = detection_os(self::$agent);
			// result
			$result = [
				'browser'=>[
					'name'=>flt_input(substr($browser['browser']['n'], 0, 16)),
					'version'=>flt_input(substr($browser['browser']['v'], 0, 8)),
					'support'=>($browser['support'] == true) ? 1 : 0 
				],
				'os'=>[
					'name'=>flt_input(substr($os['name'], 0, 16)),
					'version'=>flt_input(substr($os['version'], 0, 16)),
					'mobile'=>($os['mobile'] == 1) ? 1 : 0 
				]
			];
			// output
			return $result;
		}

		private static function session_refresh() {
			// vars
			$exp = self::$short ? '+10 minutes' : '+1 year';
			// cookies
			if (self::$sid) setcookie('sid', self::$sid, strtotime($exp), '/', '', self::$cookies_secure, true);
			if (self::$token) setcookie('token', self::$token, strtotime($exp), '/', '', self::$cookies_secure, true);
			// update
			DB::query("UPDATE sessions SET tz='".self::$tz."', updated='".self::$ts."' WHERE sid='".self::$sid."' LIMIT 1;") or die (DB::error());
		}
		
		private static function session_user_groups() {
			$q = DB::query("SELECT group_id FROM user_groups WHERE user_id='".self::$user_id."';") or die (DB::error());
			while ($row = DB::fetch_row($q)) self::$user_groups[] = $row['group_id'];
		}
		
		private static function session_last_active() {
			if (self::$user_id) DB::query("UPDATE users SET last_active='".self::$ts."' WHERE user_id='".self::$user_id."' LIMIT 1;") or die (DB::error());
		}
		
		public static function set_cookie_intro($preload) {
			if ($preload == 1) setcookie('preload', 2, strtotime('+10 minutes'), '/', '', false, true);
		}

		private static function unset_cookie_sid() {
			setcookie('sid', '', 1, '/', '', self::$cookies_secure, true);
			unset($_COOKIE['sid']);
		}

		private static function unset_cookie_token() {
			setcookie('token', '', 1, '/', '', self::$cookies_secure, true);
			unset($_COOKIE['token']);
		}
		
		// SERVICE
		
		public static function access_error() {
			if (self::$mode == 1) echo '';
			else header('Location: /');
			exit();
		}

	}
?>