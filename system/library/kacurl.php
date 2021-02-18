<?php
/* 
 $Project: Ka Extensions $
 $Author: karapuz team <support@ka-station.com> $
 $Version: 4.1.0.16 $ ($Revision: 197 $) 
*/

class KaCURL {

	protected $lastError = '';
	const MAX_RESOLVE_ATTEMPTS = 3;
	
	public function getLastError() {
		return $this->lastError;
	}
	

	/*
		RETURNS:
			false - on error
			array - on success. It looks like:
				array(
					'status'
						'http_version'  =>
						'status_code'   =>
						'reason_phrase' =>
					'headers'
						'<hdr1>' => value
						'<hdr2>' => value
				)
	*/
	protected function parseHttpHeader($header) {
	
		if (!preg_match("/^(.*)\s(.*)\s(.*)\x0D\x0A/U", $header, $matches)) {
			return false;
		}

		$status = array(
			'http_version'  => $matches[1],
			'status_code'   => $matches[2],
			'reason_phrase' => $matches[3]
		);
		
		$headers = array();		
		$header_lines = explode("\x0D\x0A", $header);
		
		foreach ($header_lines as $line) {
			$pair        = array();
			$value_start = strpos($line, ': ');
			$name        = substr($line, 0, $value_start);
			$value       = substr($line, $value_start + 2);
						
			$headers[strtolower($name)] = $value;
		}
		
		$result = array(
			'status' => $status,
			'headers' => $headers
		);
		
		return $result;					
	}

	/*
		options - array of possible values.
			user-agent - text for user-agent value
	*/		
	public function request($url, $data = array(), $options = array()) {

		if (empty($options['timeout'])) {
			$options['timeout'] = 8;
		}
	
		if (!function_exists('curl_init')) {
			trigger_error(__METHOD__ . ": CURL does not exist");
			return false;
		}
	
		$message = null;
		$this->lastError = '';
		
		$tmp_url        = $url;
		$redirect_count = 0;
		$parsed_url = parse_url($url);

		while (++$redirect_count <= 5) {
			$headers = '';
			$message = null;
			
			if (preg_match("/^\/\/.*/", $tmp_url)) {
				$tmp_url = "http:" . $tmp_url;
			}
				
			$parsed_tmp_url = parse_url($tmp_url);

			$curl = curl_init($tmp_url);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_HEADER, true);
			curl_setopt($curl, CURLOPT_TIMEOUT, $options['timeout']);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
			if (!empty($data)) {
				curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data, '', '&'));			
			}
				
			// add custom headers to emulate a regular user activity so it will prevent bans
			// by the user-agent string
			//
			$opt_headers = array();

			if (!empty($options['user-agent'])) {
				$opt_headers[] = 'User-Agent: ' . $options['user-agent'];
			}  else {
				if (preg_match("/dropbox\.com/i", $parsed_url['host'])) {
					$opt_headers[] = "User-Agent: Wget/1.11.4";
				} else {
					$opt_headers[] = "User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64; rv:42.0) Gecko/20100101 Firefox/42.0";
				}
			}
			$opt_headers[] = "Host: " . $parsed_tmp_url['host'];

			if (preg_match("/\.yimg\.com/i", $parsed_tmp_url['host'])) {
				$opt_headers[] = "Accept-Language: en-US,en;q=0.5";
				$opt_headers[] = "Accept-Encoding: gzip, deflate";
				$opt_headers[] = "DNT: 1";
				$opt_headers[] = "Upgrade-Insecure-Requests: 1";
			}

			if (!empty($opt_headers)) {
				curl_setopt($curl, CURLOPT_HTTPHEADER, $opt_headers);
			}
				
			// use more attempts to resolve host name. Sometimes curl fails at resolving 
			// a valid host name.
			//
			$resolve_attempt = 0;
			while (true) {
				$response = curl_exec($curl);
				
				if ($response === false) {
					
					// curl_error code 6 means 'could not resolve host name'
					//
					if (curl_errno($curl) == 6) {
						if ($resolve_attempt++ < self::MAX_RESOLVE_ATTEMPTS) {
							continue;
						}
					}
					$this->lastError = 'CURL error (' . curl_errno($curl) . '): ' . curl_error($curl);
				}
					
				break;					
			}				
			curl_close($curl);
				
			if ($response === false) {
				break;
			}
				
			$msg_start    = strpos($response, "\x0D\x0A\x0D\x0A");
			$header_block = substr($response, 0, $msg_start);
			$headers      = $this->parseHttpHeader($header_block);				
			if (empty($headers)) {
				if (strlen($response) > 1000) {
					$this->lastError = 'No headers received. Response size is ' . strlen($response);
				} else {
					$this->lastError = 'No headers received. Response is "' . $response . '"';
				}
				break;
			}

			if ($headers['status']['status_code'] >= 200 && $headers['status']['status_code'] < 300) {
				$message = substr($response, $msg_start+4);
				break;
				
			} elseif ($headers['status']['status_code'] >= 300 && $headers['status']['status_code'] < 400) {
				$tmp_url = $headers['headers']['location'];
				continue;
			} elseif ($headers['status']['status_code'] == 400 and preg_match("/\.yimg\.com/i", $parsed_url['host'])) {
				$this->lastError = "Status code: 400";
				continue;
			} else {
				$this->lastError = 'Invalid status code: ' . $headers['status']['status_code'];
				break;
			}
		};
		
		return $message;
	}
}