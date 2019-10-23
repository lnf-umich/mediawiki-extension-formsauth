<?php

class FormsAuth extends AuthPlugin{

	private $remoteAuth = null;
	
	function getFormsAuthId($username){
		//at this point $username should already be munged
		$temp = User::newFromName($username);
		if ($temp != null){
			if ($temp->getID() == 0){
				// user does not exist so create it
				$temp->loadDefaults($username);
				$temp->addToDatabase();
				$this->initUser($temp, true);
				$this->log("added new user to database: $username");
			}
			else
				$this->log("found existing user: $username");
				
			return $temp->getID();
		}
		$this->log("User::newFromName failed: $username");
		return 0;
	}
	
	function authCheck(){	
		$postData = array("cookieValue" => $this->getCookieValue());
		$postFields = http_build_query($postData);
		
		$ch = curl_init($this->getRemoteUrl());
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $output = curl_exec($ch);
        curl_close($ch);
		
		$this->remoteAuth = json_decode($output);
		
		return $this->remoteAuth;
	}
	
	function getRemoteUrl(){
		global $wgFormsAuthConfig;
		return $wgFormsAuthConfig["checkUrl"];
	}
	
	function getCookieValue(){
		global $wgFormsAuthConfig;
		$name = $wgFormsAuthConfig["cookie"];
		$result = (isset($_COOKIE[$name])) ? $_COOKIE[$name] : "";
		return $result;
	}
	
	function debugPrint($msg){
		echo '<textarea style="position: absolute; z-index:999; width: 400px;">';
		print_r($msg);
		echo '</textarea>';
	}

	function log($msg){
	
		global $wgFormsAuthConfig;
		if ($wgFormsAuthConfig['log'] === true){
			$logFile = __DIR__ . '/forms-auth.log';
			try{
				@file_put_contents($logFile, "[" . date("Y-m-d h:i:s A") . "] " . $msg . PHP_EOL, FILE_APPEND);
			} catch (Exception $ex){
				echo "FormsAuth: cannot write to log, current user is " . get_current_user();
			}
		}
	}
}
