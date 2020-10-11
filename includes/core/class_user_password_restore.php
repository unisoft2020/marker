<?php
	class User_Password_Restore extends User {

		public static function password_restore_window() {
			return ['html'=>HTML::fetch('./partials/modal/login/password_restore.html')];
		}

		public static function password_restore_request($data) {
			// vars
			$login = isset($data['login']) ? mb_convert_case($data['login'], MB_CASE_LOWER, 'UTF-8') : '';
			$user_id = 0; $name = ''; $email = ''; $token = '';
			// validate
			$errors = is_email($login);
			if ($errors) return ['errors'=>['login'=>$errors]];
			// search
			$q = DB::query("SELECT user_id, first_name, last_name, email, password_restore_attempts, updated FROM users WHERE email='".$login."' LIMIT 1;") or die (DB::error());
			if ($row = DB::fetch_row($q)) {
				// validate
				if (self::password_restore_attempts($row['user_id'], $row['password_restore_attempts'], $row['updated']) >= 5) return ['errors'=>['login'=>'Превышен лимит попыток восстановления доступа к аккаунту']];
				// info
				$user_id = $row['user_id'];
				$name = $row['first_name'] . ' ' . $row['last_name'];
				$email = $row['email'];
				$token = generate_rand_str(40);
				$expires = Session::$ts + 3600*24*14;
				// update
				DB::query("UPDATE users SET password_restore='".$token."', password_restore_attempts=password_restore_attempts+1, password_restore_expires='".$expires."' WHERE user_id='".$user_id."' LIMIT 1;") or die (DB::error());
			} else return ['errors'=>['login'=>'Введенный адрес электронной почты не найден']];
			// notification
			if ($email) Email::password_restore($email, $name, $token);
			// output
			return ['response'=>'ok'];
		}

		public static function password_restore_update($data) {
			// vars
			$token = isset($data['token']) ? $data['token'] : '';
			$password = isset($data['password']) ? $data['password'] : '';
			// validate
			$errors = self::password_restore_validate($token, $password);
			if ($errors) return ['errors'=>$errors];
			// user
			$q = DB::query("SELECT user_id, email FROM users WHERE password_restore='".$token."' LIMIT 1;") or die (DB::error());
			$user = ($row = DB::fetch_row($q)) ? ['user_id'=>$row['user_id'], 'login'=>$row['email']] : ['user_id'=>0, 'login'=>''];
			// actions
			if ($user['user_id'] && $user['login']) {
				// vars
				$password_salt = p_salt();
				$password_hash = p_hash($password, $password_salt);
				// update
				DB::query("UPDATE users SET password='".$password_hash."', password_salt='".$password_salt."', password_restore='', password_restore_attempts=0, password_restore_expires=0 WHERE user_id='".$user['user_id']."';") or die (DB::error());
				// session
				Session::login(['login'=>$user['login'], 'password'=>$password]);
			}
			// output
			return ['response'=>'ok'];
		}
		
		private static function password_restore_attempts($user_id, $attempts, $updated) {
			// vars
			$ts = ts_timezone(Session::$ts, Session::$tz);
			$need_update = (date('Y.m.d', $ts) > date('Y.m.d', $updated)) ? true : false;
			// update
			if ($need_update) {
				DB::query("UPDATE users SET password_restore_attempts='0', updated='".Session::$ts."' WHERE user_id='".$user_id."' LIMIT 1;") or die (DB::error());
				$attempts = 0;
			}
			// output
			return $attempts;
		}
		
		private static function password_restore_validate($token, $password) {
			// vars
			$result = [];
			// validate
			$val['new_password'] = is_password($password);
			$val['common'] = !$val['new_password'] ? is_password_restore_token($token, Session::$ts) : '';
			// output
			if ($val['new_password']) $result['new_password'] = $val['new_password'];
			if ($val['common']) $result['common'] = $val['common'];
			return $result;
		}

	}
?>