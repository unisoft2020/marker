<?php

	function controller_passport($sub, $act, $data) {

		if ($sub == 'common') {
			if (!Session::$access) Session::access_error();
			if ($act == 'paginator') return Passport::passports_fetch($data);
			if ($act == 'edit_window') return Passport::passport_edit_window($data);
			if ($act == 'edit_update') return Passport::passport_edit_update($data);
			if ($act == 'sort') return Passport::passport_sort($data);
			if ($act == 'archive_toggle') return Passport::passport_archive_toggle($data);
			if ($act == 'delete_window') return Passport::passport_delete_window($data);
			if ($act == 'delete_update') return Passport::passport_delete_update($data);
		}
		
		if ($sub == 'section') {
			if (!Session::$access) Session::access_error();
			if ($act == 'edit_window') return Passport_Section::passport_section_edit_window($data);
			if ($act == 'edit_update') return Passport_Section::passport_section_edit_update($data);
			if ($act == 'archive_toggle') return Passport_Section::passport_section_archive_toggle($data);
			if ($act == 'delete_window') return Passport_Section::passport_section_delete_window($data);
			if ($act == 'delete_update') return Passport_Section::passport_section_delete_update($data);
		}
		
		if ($sub == 'annex') {
			if (!Session::$access) Session::access_error();
			if ($act == 'edit_window') return Passport_Annex::passport_annex_edit_window($data);
			if ($act == 'edit_update') return Passport_Annex::passport_annex_edit_update($data);
			if ($act == 'archive_toggle') return Passport_Annex::passport_annex_archive_toggle($data);
			if ($act == 'delete_window') return Passport_Annex::passport_annex_delete_window($data);
			if ($act == 'delete_update') return Passport_Annex::passport_annex_delete_update($data);
		}
		
		if ($sub == 'file') {
			if (!Session::$access) Session::access_error();
			if ($act == 'list') return Passport_File::files_full_list($data);
			if ($act == 'upload') return Passport_File::file_upload($data, $_FILES['tmp_file']);
			if ($act == 'edit_window') return Passport_File::file_edit_window($data);
			if ($act == 'edit_update') return Passport_File::file_edit_update($data);
			if ($act == 'status_update') return Passport_File::file_status_update($data);
			if ($act == 'revision') return Passport_File::file_revision($data);
			if ($act == 'delete') return Passport_File::file_delete($data);
		}
	
	}

?>