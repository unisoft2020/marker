<?php

	class Intro {
		
		public static function change_page($data) {
			// vars
			$section = isset($data['section']) ? $data['section'] : 'home';
			$url = in_array($section, ['marker', 'scaner', 'help']) ? '/'.$section : '/';
			// output
			HTML::assign('async', true);
			HTML::assign('section', $section);
			return ['html'=>HTML::fetch('./partials/intro/'.$section.'.html'), 'title'=>self::page_title($section), 'url'=>$url];
		}

		public static function video_window($data) {
			// vars
			$id = isset($data['id']) && in_array($data['id'], [1,2]) ? $data['id'] : 1;
			// output
			return ['html'=>HTML::fetch('./partials/intro/modal_video_'.$id.'.html')];
		}
		
		public static function page_title($section) {
			if ($section == 'marker') return 'Маркер - Маркировать продукцию просто';
			if ($section == 'scaner') return 'Сканер - Сканировать продукцию стало быстрее';
			if ($section == 'help') return 'Помощь - Остались вопросы?';
			return 'МАСК - Платформа промышленной маркировки №1';
		}

		public static function send_request_window() {
			return ['html'=>HTML::fetch('./partials/modal/login/send_request.html')];
		}

	}
?>