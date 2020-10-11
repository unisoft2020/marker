<?php
	class Rest {
		
		public static function rest_pick_report($url, $info) {
			// options
			$options = [
				CURLOPT_CONNECTTIMEOUT => 1,
				CURLOPT_HEADER => false,
				CURLOPT_POST => true,
				CURLOPT_POSTFIELDS => json_encode($server, JSON_UNESCAPED_UNICODE),
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_SSL_VERIFYPEER => false,
				CURLOPT_TIMEOUT => 1,
				CURLOPT_URL => $url
			];
			// output
			return json_decode(self::rest_curl($options), true);
		}

		// SERVICE
		
		private static function rest_curl($options) {
			// curl
			$curl = curl_init();
			curl_setopt_array($curl, $options);
			$result = curl_exec($curl);
			curl_close($curl);
			// output
			return $result;
		}

	}
?>