<?php
	
	function controller_passports($data) {
		// validate
		if (Session::$access != 3) access_error(Session::$mode);
		// common
		if (in_array(1, Session::$user_groups)) {
			// info
			$passports_active = Passport::passports_list(['type'=>'active']);
			$passports_archive = Passport::passports_list(['type'=>'archive']);
			// output
			HTML::assign('passports_active', $passports_active['info']);
			HTML::assign('paginator_active', $passports_active['paginator']);
			HTML::assign('passports_archive', $passports_archive['info']);
			HTML::assign('paginator_archive', $passports_archive['paginator']);
			return HTML::main_content('./partials/section/passports/passports.html', Session::$mode);
		}
		// worker
		else {
			// info
			$passports_active = Passport::passports_list(['type'=>'active', 'mode'=>'worker']);
			// output
			HTML::assign('passports_active', $passports_active['info']);
			HTML::assign('paginator_active', $passports_active['paginator']);
			return HTML::main_content('./partials/section/passports/passports_worker.html', Session::$mode);
		}
	}

	function controller_passport($data, $passport_id) {
		// validate
		if (Session::$access != 3) access_error(Session::$mode);
		// info
		$passport = Passport::passport_info($passport_id);
		$passport_sections = Passport_Section::passport_sections_list($passport_id);
		$passport_annexes = Passport_Annex::passport_annexes_list($passport_id);
		// output
		HTML::assign('passport_id', $passport_id);
		HTML::assign('passport', $passport);
		HTML::assign('passport_sections', $passport_sections);
		HTML::assign('passport_annexes', $passport_annexes);
		return HTML::main_content('./partials/section/passports/passport.html', Session::$mode);
	}

	function controller_passport_files($data, $passport_id) {
		// validate
		if (Session::$access != 3) access_error(Session::$mode);
		// info
		$passport = Passport::passport_info($passport_id);
		$files = Passport_File::files_list(['passport_id'=>$passport_id]);
		// output
		HTML::assign('passport_id', $passport_id);
		HTML::assign('passport', $passport);
		HTML::assign('files', $files);
		return HTML::main_content('./partials/section/passports/passport_files.html', Session::$mode);
	}

	function controller_passport_section($data, $passport_id) {
		// validate
		if (Session::$access != 3) access_error(Session::$mode);
		// vars
		$section_id = isset($data['section']) && is_numeric($data['section']) ? $data['section'] : 0;
		// validate
		if (!Session::$access) access_error(Session::$mode);
		// info
		$passport = Passport::passport_info($passport_id);
		$section = Passport_Section::passport_section_info(['section_id'=>$section_id]);
		$files = Passport_File::files_list(['passport_id'=>$passport_id, 'sub_id'=>$section_id, 'sub_type'=>1]);
		// output
		HTML::assign('passport', $passport);
		HTML::assign('section', $section);
		HTML::assign('files', $files);
		return HTML::main_content('./partials/section/passports/passport_section.html', Session::$mode);
	}

	function controller_passport_annex($data, $passport_id) {
		// validate
		if (Session::$access != 3) access_error(Session::$mode);
		// vars
		$annex_id = isset($data['annex']) && is_numeric($data['annex']) ? $data['annex'] : 0;
		// validate
		if (!Session::$access) access_error(Session::$mode);
		// info
		$passport = Passport::passport_info($passport_id);
		$annex = Passport_Annex::passport_annex_info($annex_id);
		$files = Passport_File::files_list(['passport_id'=>$passport_id, 'sub_id'=>$annex_id, 'sub_type'=>2]);
		// output
		HTML::assign('passport', $passport);
		HTML::assign('annex', $annex);
		HTML::assign('files', $files);
		return HTML::main_content('./partials/section/passports/passport_annex.html', Session::$mode);
	}

?>