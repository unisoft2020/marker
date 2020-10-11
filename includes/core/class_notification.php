<?php

	class Notification {
		
		// GENERAL
		
		public static function notifications_list($data) {
			// vars
			$limit = isset($data['limit']) && is_numeric($data['limit']) ? $data['limit'] : 20;
			$offset = isset($data['offset']) && is_numeric($data['offset']) && $limit == 20 ? $data['offset'] : 0;
			$info = [];
			$ts_today = time() + Session::$tz * 60;
			$ts_yesterday = time() + Session::$tz * 60 - 24 * 3600;
			// where
			$where = [];
			$where[] = "user_id='".Session::$user_id."'";
			$where = implode(" AND ", $where);
			// info
			$q = DB::query("SELECT id, company_id, unread, title, body, created FROM notifications WHERE ".$where." ORDER BY created DESC LIMIT ".$offset.", ".$limit.";") or die (DB::error());
			while ($row = DB::fetch_row($q)) {
				$info[] = [
					'id'=>$row['id'],
					'company_id'=>$row['company_id'],
					'unread'=>$row['unread'],
					'title'=>$row['title'],
					'body'=>$row['body'],
					'created'=>dynamic_datetime($row['created'], Session::$tz, ['today'=>date('dmY', $ts_today), 'yesterday'=>date('dmY', $ts_yesterday)])
				];
			}
			// paginator
			$q = DB::query("SELECT count(*) FROM notifications WHERE ".$where.";") or die (DB::error());
			$count = ($row = DB::fetch_row($q)) ? $row['count(*)'] : 0;
			$paginator = paginator($count, $offset, $limit, 'owner.notifications_paginator');
			// output
			return ['info'=>$info, 'paginator'=>$paginator];
		}

		public static function notifications_fetch($data = []) {
			// vars
			$owner = isset($data['owner']) ? $data['owner'] : [];
			$notifications = self::notifications_list($data);
			HTML::assign('notifications', $notifications['info']);
			// info
			if ($owner) {
				$notifications_limit = Notification::notifications_list(['limit'=>10, 'offset'=>0]);
				HTML::assign('owner', $owner);
				HTML::assign('notifications_limit', $notifications_limit);
				return ['notifications'=>HTML::fetch('./partials/section/notifications/notifications_table.html'), 'owner'=>HTML::fetch('./partials/section/notifications/notifications_button.html'), 'notifications_limit'=>HTML::fetch('./partials/section/notifications/notifications_popup.html'), 'paginator'=>$notifications['paginator']];
			}
			return ['notifications'=>HTML::fetch('./partials/section/notifications/notifications_table.html'), 'paginator'=>$notifications['paginator']];
		}
		
		// ACTIONS
		
		public static function notifications_read() {
			DB::query("UPDATE notifications SET unread='0' WHERE user_id='".Session::$user_id."';") or die (DB::error());
			DB::query("UPDATE users SET count_notifications='0' WHERE user_id='".Session::$user_id."' LIMIT 1;") or die (DB::error());
		}

		public static function notification_create($user_id, $title, $body) {
			$user = User::user_info($user_id);
			DB::query("INSERT INTO notifications (user_id, company_id, title, body, created) VALUES ('".$user_id."', '".$user['company_id']."', '".$title."', '".$body."', '".Session::$ts."');") or die (DB::error());
			DB::query("UPDATE users SET count_notifications=count_notifications+1 WHERE user_id='".$user_id."' LIMIT 1;") or die (DB::error());
			// send email
			Email::send_notification($user['email'], $title, $body);
		}

		public static function email_notification_create($data = []) {
			// vars
			$full_name = isset($data['full_name']) ? $data['full_name'] : '';
			$email = isset($data['email']) ? $data['email'] : '';
			$phone = isset($data['phone']) && is_numeric($data['phone']) ? $data['phone'] : 0;
			$company_title = isset($data['company']) ? $data['company'] : '';
			$comment = isset($data['comment']) ? $data['comment'] : '';
			$subscribe = isset($data['subscribe']) ? $data['subscribe'] : 'Нет';
			// query
			DB::query("INSERT INTO email_notifications (full_name, email, phone, company_title, comment, subscribe, created) VALUES ('".$full_name."', '".$email."', '".$phone."', '".$company_title."', '".$comment."', '".$subscribe."', '".Session::$ts."');") or die (DB::error());
		}
		
	}
?>