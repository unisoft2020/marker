<?php
	class Contract {
		
		// GENERAL

		public static function contract_info($contract_id) {
			// info
			$q = DB::query("SELECT contract_id, company_id, contract_title, contract_date, annex_number, annex_date FROM contracts WHERE contract_id='".$contract_id."' LIMIT 1;") or die (DB::error());
			if ($row = DB::fetch_row($q)) {
				return [
					'id'=>$row['contract_id'],
					'company_id'=>$row['company_id'],
					'title'=>$row['contract_title'],
					'date'=>$row['contract_date'] ? date('d.m.Y', $row['contract_date']) : '',
					'annex_number'=>$row['annex_number'] ? $row['annex_number'] : '',
					'annex_date'=>$row['annex_date'] ? date('d.m.Y', $row['annex_date']) : ''
				];
			} else {
				return [
					'id'=>0,
					'company_id'=>0,
					'title'=>'',
					'date'=>'',
					'annex_number'=>'',
					'annex_date'=>''
				];
			}
		}
		
		public static function contracts_list($data) {
			// vars
			$offset = isset($data['offset']) && is_numeric($data['offset']) ? $data['offset'] : 0;
			$query = isset($data['query']) && trim($data['query']) ? trim($data['query']) : '';
			$limit = 20;
			$info = [];
			// where
			$where = [];
			if ($query) $where[] = "contract_title LIKE '%".$query."%'";
			$where[] = "hidden<>'1'";
			$where = implode(' AND ', $where);
			// query
			$q = DB::query("SELECT contract_id, company_id, contract_title, contract_date, annex_number, annex_date FROM contracts WHERE ".$where." ORDER BY contract_id LIMIT ".$offset.", ".$limit.";") or die (DB::error());
			while ($row = DB::fetch_row($q)) {
				$info[] = [
					'id'=>$row['contract_id'],
					'company_id'=>$row['company_id'],
					'title'=>$row['contract_title'],
					'date'=>$row['contract_date'] ? date('d.m.Y', $row['contract_date']) : '',
					'annex_number'=>$row['annex_number'] ? $row['annex_number'] : '',
					'annex_date'=>$row['annex_date'] ? date('d.m.Y', $row['annex_date']) : ''
				];
			}
			// paginator
			$q = DB::query("SELECT count(*) FROM contracts WHERE ".$where.";") or die (DB::error());
			$count = ($row = DB::fetch_row($q)) ? $row['count(*)'] : 0;
			$paginator = paginator($count, $offset, $limit, 'contract.contract_paginator', ['query'=>$query]);
			// output
			return ['info'=>$info, 'paginator'=>$paginator];
		}
		
		// SEARCH
		
		public static function contract_search($data) {
			// vars
			$query = isset($data['query']) && trim($data['query']) ? trim($data['query']) : '';
			// info
			$contracts = self::contracts_list(['type'=>1, 'query'=>$query]);
			// output
			HTML::assign('contracts', $contracts['info']);
			return ['html'=>HTML::fetch('./partials/modal/products/product_edit_contracts.html')];
		}
	}
?>