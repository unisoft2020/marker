<?php
	class Email {
		
		public static $from_title = 'Market Team';
		
		// GENERAL

		public static function product_picks($email, $file_xlsx, $group_id) {
			$info = ['file_xlsx'=>$file_xlsx, 'group_id'=>$group_id];
			self::email_send($email, $info, 'Оприходование #'.$group_id, 'product_picks.html');
		}
		
		public static function user_password($first_name, $email, $password) {
			$info = ['first_name'=>$first_name, 'email'=>$email, 'password'=>$password];
			self::email_send($email, $info, 'Доступ', 'user_password.html');
		}

		public static function send_request($data) {
			$info = ['full_name'=>$data['full_name'], 'company'=>$data['company'], 'email'=>$data['email'], 'phone'=>$data['phone'], 'comment'=>$data['comment'], 'subscribe'=>$data['subscribe']];
			self::email_send('m.smirnova@marker.team', $info, 'Запрос доступа', 'send_request.html');
			Notification::email_notification_create($data);
		}

		public static function send_demo($data) {
			$info = ['email'=>$data['email']];
			self::email_send('m.smirnova@marker.team', $info, 'Запрос тестового доступа', 'send_demo.html');
			Notification::email_notification_create($data);
		}

		public static function send_notification($email, $title, $body) {
			$info = ['title'=>$title, 'body'=>$body];
			self::email_send($email, $info, 'Новое уведомление', 'send_notification.html');
		}

		public static function password_restore($email, $name, $token) {
			$info = ['name'=>$name, 'link'=>'https://marker.team/new_password?token=' . $token];
			self::email_send($email, $info, 'Восстановление доступа к аккаунту', 'password_restore.html');
		}
		
		// SERVICE
		
		private static function email_send($email, $info, $title, $tpl) {
			// title
			$preferences = ['input-charset'=>'UTF-8', 'output-charset'=>'UTF-8'];
			$title = iconv_mime_encode('Subject', $title, $preferences);
			$title = substr($title, strlen('Subject: '));
			// html
			HTML::assign('info', $info);
			$tpl = Session::$mode == 2 ? './../partials/mail/'.$tpl : './partials/mail/'.$tpl;
			$html = HTML::fetch($tpl);
			error_log($html);
			// send
			$from = SITE_EMAIL;
			$headers = "From: \"".self::$from_title."\"<".$from.">\n".stripslashes("Content-Type: text/plain; charset=utf-8")."\nReturn-path: <".$from.">";
			mail($email, $title, $html, $headers, '-f'.$from);
		}

	}
?>