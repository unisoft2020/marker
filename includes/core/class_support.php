<?php

	class Support {
		
		// GENERAL

		public static function support_messages_list($ticket_id) {
			// vars
			$info = [];
			// where
			$where = [];
			$where[] = "ticket_id='".$ticket_id."'";
			$where = implode(" AND ", $where);
			// info
			$q = DB::query("SELECT body, role, created FROM support_messages WHERE ".$where." ORDER BY message_id ASC;") or die (DB::error());
			while ($row = DB::fetch_row($q)) {
				$info[] = [
					'body'=>$row['body'],
					'role'=>$row['role'],
					'created'=>date('d.m.y H:i', ts_timezone($row['created'], Session::$tz))
				];
			}
			// output
			return ['info'=>$info];
		}

		public static function support_info($ticket_id) {
			// where
			$where = [];
			$where[] = "ticket_id='".$ticket_id."'";
			$where = implode(" AND ", $where);
			// info
			$q = DB::query("SELECT ticket_id, status, title, created, updated FROM support_tickets WHERE ".$where." LIMIT 1;") or die (DB::error());
			if ($row = DB::fetch_row($q)) {
				return [
					'id'=>$row['ticket_id'],
					'status'=>self::support_status($row['status']),
					'title'=>$row['title'],
					'created'=>date('d.m.y H:i', ts_timezone($row['created'], Session::$tz)),
					'updated'=>$row['updated'] == 0 ? $row['updated'] : date('d.m.y H:i', ts_timezone($row['updated'], Session::$tz))
				];
			} else {
				return [
					'id'=>0,
					'status'=>'',
					'title'=>'',
					'created'=>0,
					'updated'=>0
				];
			}
		}

		public static function support_list($data) {
			// vars
			$info = [];
			$offset = isset($data['offset']) && is_numeric($data['offset']) ? $data['offset'] : 0;
			$status = isset($data['status']) && in_array($data['status'], ['active','archive']) ? $data['status'] : 'active';
			$limit = 20;
			// where
			$where = [];
			$where[] = "user_id='".Session::$user_id."'";
			$where[] = $status == 'active' ? "status=0 OR status=1" : "status=2";
			$where = implode(" AND ", $where);
			// info
			$q = DB::query("SELECT ticket_id, status, title, snippet, created, updated FROM support_tickets WHERE ".$where." ORDER BY ticket_id DESC LIMIT ".$offset.", ".$limit.";") or die (DB::error());
			while ($row = DB::fetch_row($q)) {
				$info[] = [
					'id'=>$row['ticket_id'],
					'status'=>self::support_status($row['status']),
					'title'=>$row['title'],
					'snippet'=>$row['snippet'],
					'created'=>date('d.m.y H:i', ts_timezone($row['created'], Session::$tz)),
					'updated'=>$row['updated'] == 0 ? $row['updated'] : date('d.m.y H:i', ts_timezone($row['updated'], Session::$tz))
				];
			}
			// paginator
			$q = DB::query("SELECT count(*) FROM support_tickets WHERE ".$where.";") or die (DB::error());
			$count = ($row = DB::fetch_row($q)) ? $row['count(*)'] : 0;
			$paginator = paginator($count, $offset, $limit, 'support.paginator');
			// output
			return ['info'=>$info, 'paginator'=>$paginator];
		}

		public static function support_fetch($data = []) {
			$support = self::support_list($data);
			HTML::assign('support', $support['info']);
			return ['support'=>HTML::fetch('./partials/section/support/support_table.html'), 'paginator'=>$support['paginator']];
		}

		public static function support_messages_fetch($ticket_id = 0) {
			// info
			$support = self::support_info($ticket_id);
			$user = User::user_info(Session::$user_id);
			$messages = self::support_messages_list($ticket_id);
			// output
			HTML::assign('support', $support);
			HTML::assign('user', $user);
			HTML::assign('messages', $messages['info']);
			return ['messages'=>HTML::fetch('./partials/section/support/support_ticket_table.html'), 'support'=>HTML::fetch('./partials/section/support/support_ticket.html')];
		}
		
		// ACTIONS
		
		public static function support_create_window() {
			return ['html'=>HTML::fetch('./partials/modal/support/support_create.html')];
		}

		public static function support_create_update($data) {
			// vars
			$title = isset($data['title']) ? $data['title'] : '';
			$msg = isset($data['msg']) ? $data['msg'] : '';
			$status = 0;
			$snippet = mb_strlen($msg, 'UTF-8') > 50 ? mb_strimwidth($msg, 0, 50, "...") : $msg;
			$updated = 0;
			$role = 0;
			// add in table support_tickets
			DB::query("INSERT INTO support_tickets (user_id, status, title, snippet, created, updated) VALUES ('".Session::$user_id."', '".$status."', '".$title."', '".$snippet."', '".Session::$ts."', '".$updated."');") or die (DB::error());
			$ticket_id = DB::insert_id();
			// add in table support_messages
			DB::query("INSERT INTO support_messages (ticket_id, body, role, created) VALUES ('".$ticket_id."', '".$msg."', '".$role."', '".Session::$ts."');") or die (DB::error());
			// output
			return self::support_fetch();
		}

		public static function support_close_ticket($data) {
			// vars
			$ticket_id = isset($data['ticket_id']) && is_numeric($data['ticket_id']) ? $data['ticket_id'] : 0;
			$page = isset($data['page']) ? $data['page'] : 'support';
			// queries
			DB::query("UPDATE support_tickets SET status=2, updated='".Session::$ts."' WHERE ticket_id='".$ticket_id."' LIMIT 1;") or die (DB::error());
			DB::query("INSERT INTO support_messages (ticket_id, body, role, created) VALUES ('".$ticket_id."', 'Тикет закрыт', 0, '".Session::$ts."');") or die (DB::error());
			// output
			if ($page == 'support') return self::support_fetch();
			else return self::support_messages_fetch($ticket_id);
		}

		public static function support_send_message($data) {
			// vars
			$ticket_id = isset($data['ticket_id']) && is_numeric($data['ticket_id']) ? $data['ticket_id'] : 0;
			$msg = isset($data['msg']) ? $data['msg'] : '';
			// queries
			DB::query("UPDATE support_tickets SET updated='".Session::$ts."' WHERE ticket_id='".$ticket_id."' LIMIT 1;") or die (DB::error());
			DB::query("INSERT INTO support_messages (ticket_id, body, role, created) VALUES ('".$ticket_id."', '".$msg."', 0, '".Session::$ts."');") or die (DB::error());
			// output
			return self::support_messages_fetch($ticket_id);
		}

		// SERVICE
		
		private static function support_status($id) {
			if ($id == 0) return 'Новый';
			if ($id == 1) return 'В работе';
			if ($id == 2) return 'Закрыт';
			return '';
		}
	}
?>