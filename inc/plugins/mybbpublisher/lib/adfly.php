<?php
/**
 * MyBBPublisher Plugin for MyBB - Adf.ly library
 * Copyright 2011 CommunityPlugins.com, All Rights Reserved
 *
 * Website: http://www.communityplugins.com
 * Version 3.0.0
 * License: Creative Commons Attribution-NonCommerical ShareAlike 3.0
				http://creativecommons.org/licenses/by-nc-sa/3.0/legalcode
 *
 */
 
// Disallow direct access to this file for security reasons DO NOT REMOVE
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.<br />");
}

/**
 * Adf.ly shortening function
 * @var string url to shorten
 * @var string userID of Adf.ly account
 * @var string APIkey of Adf.ly account to use
 * @var string domain to use in short url
 * @return string shortened url or input url
 */
function get_adf_ly_url($url,$uid,$apikey,$domain="adf.ly")
{
		global $mybb;
		
        $adfurl = "http://api.adf.ly/api.php?key=".$apikey."&uid=".$uid."&advert_type=int&domain=".$domain."&url=".$url;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $adfurl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_REFERER, $mybb->settings['homeurl']);
        $short_url = curl_exec($ch);
        curl_close($ch);
        
        return $short_url;
}

?>
