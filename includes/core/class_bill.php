<?php

	require_once 'vendor/dompdf/autoload.inc.php';
	use Dompdf\Dompdf;

	class Bill {
		
		// GENERAL
		
		public static function bill_info($bill_id) {
			$q = DB::query("SELECT bill_id, status, number, amount, created, paid FROM bills WHERE bill_id='".$bill_id."' LIMIT 1;") or die (DB::error());
			if ($row = DB::fetch_row($q)) {
				return [
					'id'=>$row['bill_id'],
					'status'=>self::bill_status($row['status']),
					'number'=>$row['number'],
					'amount'=>number_format($row['amount'] * 0.01, 2, '.', ' '),
					'created'=>date('d.m.Y', ts_timezone($row['created'], Session::$tz)),
					'paid'=>$row['paid'] ? date('d.m.Y', ts_timezone($row['paid'], Session::$tz)) : ''
				];
			} else {
				return [
					'id'=>0,
					'status'=>'',
					'number'=>'',
					'amount'=>'0.00',
					'created'=>'',
					'paid'=>''
				];
			}
		}
		
		public static function bills_list($data) {
			// vars
			$info = [];
			$offset = isset($data['offset']) && is_numeric($data['offset']) ? $data['offset'] : 0;
			$limit = 20;
			// where
			$where = [];
			$where[] = "company_id='".Session::$company_id."'";
			$where[] = "hidden<>'1'";
			$where = implode(" AND ", $where);
			// info
			$q = DB::query("SELECT bill_id, status, number, amount, created, paid FROM bills WHERE ".$where." ORDER BY bill_id DESC LIMIT ".$offset.", ".$limit.";") or die (DB::error());
			while ($row = DB::fetch_row($q)) {
				$info[] = [
					'id'=>$row['bill_id'],
					'status'=>self::bill_status($row['status']),
					'number'=>$row['number'],
					'amount'=>number_format($row['amount'] * 0.01, 2, '.', ' '),
					'created'=>date('d.m.Y', ts_timezone($row['created'], Session::$tz)),
					'paid'=>$row['paid'] ? date('d.m.Y', ts_timezone($row['paid'], Session::$tz)) : '-'
				];
			}
			// paginator
			$q = DB::query("SELECT count(*) FROM bills WHERE ".$where.";") or die (DB::error());
			$count = ($row = DB::fetch_row($q)) ? $row['count(*)'] : 0;
			$paginator = paginator($count, $offset, $limit, 'bill.paginator');
			// output
			return ['info'=>$info, 'paginator'=>$paginator];
		}
		
		public static function bills_fetch($data = []) {
			$bills = self::bills_list($data);
			HTML::assign('bills', $bills['info']);
			return ['bills'=>HTML::fetch('./partials/section/bills/bills_table.html'), 'paginator'=>$bills['paginator']];
		}
		
		// ACTIONS
		
		public static function bill_create_window() {
			return ['html'=>HTML::fetch('./partials/modal/bills/bill_create.html')];
		}
		
		public static function bill_create_update($data) {
			// vars
			$amount = isset($data['amount']) && is_numeric($data['amount']) ? $data['amount'] * 100 : 0;
			// add
			DB::query("INSERT INTO bills (company_id, amount, created) VALUES ('".Session::$company_id."', '".$amount."', '".Session::$ts."');") or die (DB::error());
			$bill_id = DB::insert_id();
			// update
			$number = date('Y').'-'.date('m').'-'.date('d').'-'.str_pad($bill_id, 5, '0', STR_PAD_LEFT);
			DB::query("UPDATE bills SET number='".$number."' WHERE bill_id='".$bill_id."' LIMIT 1;") or die (DB::error());
			// output
			return self::bills_fetch();
		}
		
		public static function bill_delete($data) {
			// vars
			$bill_id = isset($data['bill_id']) && is_numeric($data['bill_id']) ? $data['bill_id'] : 0;
			// query
			DB::query("UPDATE bills SET hidden='1' WHERE bill_id='".$bill_id."' LIMIT 1;") or die (DB::error());
			// output
			return self::bills_fetch();
		}
		
		public static function bill_download($bill_id) {
			// info
			$bill = self::bill_info($bill_id);
			$company = Company::company_info(Session::$company_id);
			$stamp = SITE_SCHEME.'://'.SITE_DOMAIN.'/images/stamp_small.jpg';
			// template
			HTML::assign('bill', $bill);
			HTML::assign('company', $company);
			HTML::assign('stamp', $stamp);
			$tpl = HTML::fetch('./partials/print/bill.html');
			// html
			$html = '<html><body style="padding: 35px; font-family: \'roboto\'">'.$tpl.'</body></html>';
			$html .= '<link type="text/css" media="dompdf" href="css/common.css" rel="stylesheet" />';
			$html .= '<link type="text/css" media="dompdf" href="css/section/print.css" rel="stylesheet" />';
			// pdf
			$dompdf = new Dompdf();
			$dompdf->set_option('isRemoteEnabled', TRUE);
			$dompdf->set_option('isHtml5ParserEnabled', true);
			$dompdf->load_html($html);
			$dompdf->render();
			$dompdf->stream($bill['number'].'.pdf'); 
			// output
			exit();
		}
		
		// SERVICE
		
		private static function bill_status($id) {
			if ($id == 0) return 'выставлен';
			if ($id == 1) return 'оплачен';
			if ($id == -1) return 'отклонен';
			return '';
		}

	}
?>