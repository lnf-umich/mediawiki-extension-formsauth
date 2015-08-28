<?php
class FormsAuthHooks{
	static function onUserLoadAfterLoadFromSession($user){
		global $wgAuth;

		if ($user->isLoggedIn()){
			return;
		}
		else{
			$wgAuth->log("no one is logged in, performing authCheck");
		
			//do the authCheck
			$auth = $wgAuth->authCheck();
			
			if ($auth && $auth->success){
				//make sure the username is a proper mediawiki name
				$munged = User::getCanonicalName($auth->username);
				
				//get the id of a local account, this will create the account if it doesn't exist
				$id = $wgAuth->getFormsAuthId($munged);
				
				if ($id != 0){
					$user->setId($id);
					$user->loadFromId();
					$user->setEmail($auth->email);
					$user->mEmailAuthenticated = wfTimestampNow();
					$user->setRealName($auth->firstName . ' ' . $auth->lastName);
					$user->setPassword(null);
					
					// add user to any available groups
					$allGroups = User::getAllGroups();
					$userGroups = $user->getGroups();
					foreach ($allGroups as $g){
						//only add the group if it is already defined (in LocalSettings.php) and the user is not already a member
						if (in_array($g, $auth->roles) && !in_array($g, $userGroups)){
							$wgAuth->log("adding group for $munged: $g");
							$user->addGroup($g);
						}
					}
					
					$user->saveSettings();
					$wgAuth->updateUser($user);
					$user->saveToCache();
					$user->setCookies();
					wfSetupSession();
					
					$wgAuth->log("done logging in user: " . json_encode($user));
				}
			}
		}
	}
	
	static function onUserLoginForm(&$template){
		global $wgFormsAuthConfig, $wgOut;
		
        //use the wgFormsAuthConfig value unles redirect querystring parameter is used, then this parameter must be equal to 1
        $redirect = (isset($_GET["redirect"]) ? $_GET["redirect"] == "1" : true) && $wgFormsAuthConfig['redirect'];
        
		$returnTo = isset($_GET["returnto"]) ? $_GET["returnto"] : "";
		$returnQuery = isset($_GET["returntoquery"]) ? $_GET["returntoquery"] : "";
		$pathAndQuery = $returnTo;
		
		if ($pathAndQuery)
			$pathAndQuery = "/".$pathAndQuery;
		
		if ($returnQuery)
			$pathAndQuery .= "?".$returnQuery;
		
		$template->data["message"] = '<div style="font-size: 18pt; font-weight: bold;"><a href="'.$wgFormsAuthConfig["loginUrl"].urlencode($pathAndQuery).'">Click here to log in using LNF Online Services</div>';
		$template->data["messagetype"] = "warning";
        
        if ($redirect){
            $wgOut->redirect($wgFormsAuthConfig["loginUrl"].urlencode($pathAndQuery));
       }
	}
}