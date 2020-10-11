<?php

	// GENERAL

	function is_name($val, $empty = false) {
		// errors
		if (!$val && !$empty) return 'заполните поле';
		if ($val && !preg_match('/^[а-яё \'\-\\\\]+$/iu', $val)) return 'неверный формат';	
		if ($val && mb_strlen($val,'UTF-8') < 2) return 'недостаточная длина';
		// success
		return '';
	}

	function is_title($val) {
		// errors
		if (!$val) return 'заполните поле';
		if (!preg_match('/^[а-яёa-z0-9 \"\'\-\+\\\\]+$/iu', $val)) return 'неверный формат';	
		if ($val && mb_strlen($val,'UTF-8') < 2) return 'недостаточная длина';
		// success
		return '';
	}

	function is_ogrn($val) {
		// errors
		if (!$val) return 'заполните поле';
		if (!preg_match('/^[\d]+$/iu', $val)) return 'неверный формат';	
		if (strlen($val) != 13) return 'неверная длина поля';
		// success
		return '';
	}

	function is_inn($val) {
		// errors
		if (!$val) return 'заполните поле';
		if (!preg_match('/^[\d]+$/iu', $val)) return 'неверный формат';	
		if (strlen($val) != 10) return 'неверная длина поля';
		// success
		return '';
	}

	function is_kpp($val) {
		// errors
		if (!$val) return 'заполните поле';
		if (!preg_match('/^[\d]+$/iu', $val)) return 'неверный формат';	
		if (strlen($val) != 9) return 'неверная длина поля';
		// success
		return '';
	}
	
	function is_num($val) {
		// errors
		if (!$val) return 'заполните поле';
		if (!preg_match('/^[\d]+$/iu', $val)) return 'неверный формат';	
		// success
		return '';
	}

	// CONTACTS

	function is_address($val) {
		// errors
		if (!$val) return 'заполните поле';
		if (mb_strlen($val,'UTF-8') < 15) return 'недостаточная длина';
		// success
		return '';
	}

	function is_email($val) {
		// errors
		if (!$val) return 'заполните поле';
		if (!preg_match('/[a-z0-9][a-z0-9_\.\-]+[@]([a-z0-9]{1,2}|[a-z0-9][a-z0-9\-]+[a-z0-9])([\.][a-z]+){1,3}/i', $val)) return 'неверный формат';
		// success
		return '';
	}
	
	function is_used_email($email, $user_id = 0) {
		// errors
		$q = DB::query("SELECT user_id FROM users WHERE email='".$email."' AND hidden='0' LIMIT 1;") or die (DB::error());
		if ($row = DB::fetch_row($q)) return $row['user_id'] == $user_id ? '' : 'почта уже зарегистрирована';
		// success	
		return '';
	}

	function is_phone($val) {
		// errors
		if (!$val) return 'заполните поле';
		if (!preg_match('/^[\d\s\+\-]+$/i', $val)) return 'неверный формат';
		if (strlen($val) < 8) return 'неверный формат';	
		// success
		return '';
	}
	
	// AUTH

	function is_password($val) {
		// errors
		if (!$val) return 'заполните поле';
		if (mb_strlen($val, 'utf-8') < 4) return 'пароль должен быть от 4 символов';
		//if (mb_strlen($val, 'utf-8') < 8) return 'пароль должен быть от 8 символов, включать цифры, строчные и прописные буквы';
		//if (!preg_match('~[a-z]~u', $val)) return 'пароль должен быть от 8 символов, включать цифры, строчные и прописные буквы';
		//if (!preg_match('~[A-Z]~u', $val)) return 'пароль должен быть от 8 символов, включать цифры, строчные и прописные буквы';
		//if (!preg_match('~[0-9]~u', $val)) return 'пароль должен быть от 8 символов, включать цифры, строчные и прописные буквы';
		// success
		return '';
	}
	
	function is_password_restore_token($token, $ts) {
		// empty
		if (!$token) return 'отсутствует токен';
		// search
		$q = DB::query("SELECT user_id, email, password_restore_expires FROM users WHERE password_restore='".$token."' LIMIT 1;") or die (DB::error());
		if ($row = DB::fetch_row($q)) {
			if ($ts > $row['password_restore_expires']) return 'истек срок действия ссылки';
			else if (!$row['email']) return 'не указан email';
			else return '';
		}
		// not found
		else return 'токен не найден';
	}

?>