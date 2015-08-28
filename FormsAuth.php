<?php
$wgExtensionCredits['forms-auth'][] = array(
    'path' => __FILE__,
    'name' => 'FormsAuth',
    'author' => 'Jim Getty', 
    'url' => 'http://jgetty.com', 
    'description' => 'Use .NET FormsAuthentication for wiki log ins',
    'version'  => 0.1,
    'license-name' => "",   // Short name of the license, links LICENSE or COPYING file if existing - string, added in 1.23.0
);

$wgAutoloadClasses['FormsAuth'] = __DIR__ . '/FormsAuth.body.php';
$wgAutoloadClasses['FormsAuthHooks'] = __DIR__ . '/FormsAuth.hooks.php';

$wgAuth = new FormsAuth();
$wgHooks['UserLoadAfterLoadFromSession'][] = 'FormsAuthHooks::onUserLoadAfterLoadFromSession';
$wgHooks['UserLoginForm'][] = 'FormsAuthHooks::onUserLoginForm';