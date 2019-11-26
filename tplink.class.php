<?php
/*MIT License

Copyright (c) 2017 

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
*/
error_reporting(E_ERROR | E_PARSE);

function get($url, $data, $method = "GET", $content = "normal", $cookies = false) {
	$options = array(
		'http' => array(
			'header'  => $content == "json" ? "Content-Type: application/json\r\n"."Accept: */*; \r\n" : "Content-type: application/x-www-form-urlencoded\r\n",
			'method'  => $method,
			'content' => $content == "json" ? json_encode($data) : http_build_query($data),
		),
	);
	$context  = stream_context_create($options);
	$result = file_get_contents($url, false, $context);
	if ($result === FALSE) { }

	$cookies = array();
	foreach ($http_response_header as $hdr) {
		if (preg_match('/^Set-Cookie:\s*([^;]+)/', $hdr, $matches)) {
			parse_str($matches[1], $tmp);
			$cookies += $tmp;
		}
	}

	$bearer = json_decode($result, JSON_PRETTY_PRINT);
	if (json_last_error() === 0) {
		if($cookies) {
			return array($bearer, $cookies);
		} else {
			return $bearer;
		}
	} else {
		if($cookies) {
			return array($result, $cookies);
		} else {
			return $result;
		}
	}
}


class TpLink {
	public function myTerminal() {
		if($_COOKIE['tplink_device']) {
			$ret = $_COOKIE['tplink_device'];
		} else {
			$data = openssl_random_pseudo_bytes(16);
			assert(strlen($data) == 16);

			$data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
			$data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10

			$ret = strtoupper(vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4)));
			setcookie ("tplink_device", $ret, time() + (3600*24*25));
		}
		return $ret;
	}
	public function returnToken() {
		if($_COOKIE['tplink_token']) {
			return $_COOKIE['tplink_token'];
		} else {
			$response =get("https://wap.tplinkcloud.com/", json_decode('{"method":"login","params":{"cloudUserName":"'.TPLINK_USER.'","appType":"Kasa_iOS","terminalUUID":"'.$this->myTerminal().'","cloudPassword":"'.TPLINK_PASS.'"}}'), "POST", "json", true);
			if($response['error_code'] == 0) {
				setcookie ("tplink_token",$response["result"]['token'], time() + (3600*24*20));
				return $response["result"]['token'];
			} else {
				throw new Exception("Invalid credentials");
			}
		}
		return $ret;
	}
	
	public function getDeviceList() {
		set_time_limit(0);
		$response = get("https://wap.tplinkcloud.com/?token=".$this->returnToken()."&appName=Kasa_iOS&termID=".$this->myTerminal()."&ospf=iOS%2010.2.1&appVer=1.4.3.390&netType=wifi&locale=es_AR", array("method" => "getDeviceList"), "POST", "json")['result']['deviceList'];
		$plugs = array();
		for ($i = 0; $i < count($response); $i++) {
			$plugJson = array("method" => "passthrough", "params" => array("deviceId" =>  $response[$i]['deviceId'], "requestData" => '{"schedule":{"get_next_action":{}},"system":{"get_sysinfo":{}}}'));
			$plugstate = json_decode(get($response[$i]['appServerUrl']."/?token=".$this->returnToken()."&appName=Kasa_iOS&termID=".$this->myTerminal()."&ospf=iOS%2010.2.1&appVer=1.4.3.390&netType=wifi&locale=es_AR", $plugJson, "POST", "json")['result']['responseData'], JSON_PRETTY_PRINT);
			$plugs[$i] = array("deviceId" => $response[$i]['deviceId'],
							 "name" => $response[$i]['alias'],
							 "deviceName" => $response[$i]['deviceName']." ".$response[$i]['deviceModel'],
							 "mac" => $response[$i]['deviceMac'],
							 "useUrl" => $response[$i]['appServerUrl'],
							 "working" => $response[$i]['status'],
							 "relay_status" => (($plugstate['error_code'] != 0)?"off":(($plugstate['system']['get_sysinfo']['relay_state'] == 0)?'off':'on')),
							 "geo" => array("lat" => "".(($plugstate['system']['get_sysinfo']['latitude'])?$plugstate['system']['get_sysinfo']['latitude']:0)."", "lon" => "".(($plugstate['system']['get_sysinfo']['longitude'])?$plugstate['system']['get_sysinfo']['longitude']:0).""),
							 "Ids" => array("fwId" => $response[$i]['fwId'], "hwId" =>  $response[$i]['hwId'], "oemId" => $response[$i]['oemId']));
		}
		return $plugs;
	}
	
	public function plugSwitch($bool, $device) {
		$switchData = array("method" => "passthrough", "params" => array("deviceId" => "".$device['deviceId']."", "requestData" => '{"system":{"set_relay_state":{"state":'.(($bool)?1:0).'}}}'));
		$response = get((($device['useUrl'])?$device['useUrl']:'https://use1-wap.tplinkcloud.com')."/?token=".$this->returnToken()."&appName=Kasa_iOS&termID=".$this->myTerminal()."&ospf=iOS%2010.2.1&appVer=1.4.3.390&netType=wifi&locale=es_AR", $switchData, "POST", "json");
	}
	
}
?>
