<?php
/**
 * MyBBPublisher Plugin for MyBB - Task File
 * Copyright 2013 CommunityPlugins.com, All Rights Reserved
 *
 * Website: http://www.communityplugins.com
 * Version 3.4.0
 * License: Creative Commons Attribution-NonCommerical ShareAlike 3.0
				http://creativecommons.org/licenses/by-nc-sa/3.0/legalcode
 * File: <MYBB_ROOT>\inc\tasks\mybbpublisher.php
 *
 */

function task_mybbpublisher($task)
{
	global $mybb, $db, $publisher, $facebook;

	if(!is_object($pubisher))
	{
		require_once(MYBB_ROOT.'/inc/plugins/mybbpublisher/class_mybbpublisher.php');
		$publisher = new mybbpublisher;
	}
	
	if(!is_object($facebook))
	{
		$facebook = new pub_facebook;
	}
	
	$facebook_settings = $publisher->settings[$facebook->service_name];
	
	//if expire time not set or is within 5 days
	if($facebook_settings['enabled'] == 1 && $facebook_settings['appid'] != '' && $facebook_settings['secret'] != '' && ($facebook_settings['expires'] == '' || ($facebook_settings['expires'] - TIME_NOW) <= (60*60*24*5)))
	{	
		//exchange for long term token (possible to get same token back though)
		$params =  array('client_id'	=> $facebook_settings['appid'],                        
						'client_secret' => $facebook_settings['secret'],
						'grant_type'	=> 'fb_exchange_token',
						'fb_exchange_token' => $facebook_settings['token']
						);

		$fb_results = $publisher->curl($facebook->api_url.'oauth/access_token', $params);

		$fb_results = str_ireplace('access_token=', '', $fb_results);
		$fb_results2 = explode("&expires=", $fb_results);

		$check = json_decode($fb_results2[0], true);
		if(is_array($check))
		{
			add_task_log($task, "Error connecting to Facebook: ".$fb_results);
		}
		else
		{
			$facebook->settings = $publisher->settings[$facebook->service_name];
			$facebook->settings['token'] = $db->escape_string($fb_results2[0]);
			if($fb_results2[1])
			{
				$fb_results2[1] = $db->escape_string($fb_results2[1]);
				$facebook->settings['expires'] = $fb_results2[1];
				add_task_log($task, "Updated MyBBPublisher Token/Expire to: ".$fb_results2[1]);
			}
			else
			{
				add_task_log($task, "Updated MyBBPublisher Token/Expire - Nothing to update");
			}
			$publisher->save_settings($facebook->service_name, $facebook->settings);
		}
	}
	else
	{
		add_task_log($task, "Facebook not setup, skipping.");
	}
}
?>
