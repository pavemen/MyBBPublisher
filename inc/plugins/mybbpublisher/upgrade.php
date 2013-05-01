<?php
/**
 * MyBBPublisher Plugin for MyBB - Plugin upgrade file
 * Copyright 2013 CommunityPlugins.com, All Rights Reserved
 *
 * Website: http://www.communityplugins.com
 * Version 3.4.0
 * License: Creative Commons Attribution-NonCommerical ShareAlike 3.0
				http://creativecommons.org/licenses/by-nc-sa/3.0/legalcode
 *
 */
 
    	//update setting labels, etc for older versions

/***********************************************************************
step upgrade from 1.4 series to 1.5 series
***********************************************************************/
		global $mybb, $db;

		if(version_compare($oldver, '1.5.0', '<') && $oldver <> '' && $oldver <> 0)
    	{
			
			//update existing settings
			$pluginsetting = array();
			$pluginsetting[] = array(
				"name"		=> "mybbpublisher_forums",
				"title"		=> "Forums in which New Threads are published",
				"description"	=> "This is a CSV list of FIDs to publish (if above is {yes}) or not to publish (if above is {no}). Set to 0 to include/exclude new threads from all forums."
			);
	
			$pluginsetting[] = array(
				"name"		=> "mybbpublisher_tw_ckey",
				"title"		=> "TWITTER Consumer Key",
				"description"	=> "Twitter data: Consumer key for your registered application created at <a href=\"http://twitter.com/apps/new\" target=\"_blank\">http://dev.twitter.com/apps/new</a> (found at https://dev.twitter.com/apps/&lt;app_id&gt;)."
			);

		
			$pluginsetting[] = array(
				"name"		=> "mybbpublisher_tw_csecret",
				"title"		=> "TWITTER Consumer Secret",
				"description"	=> "Twitter data: Consumer secret for your registered application created at <a href=\"http://twitter.com/apps/new\" target=\"_blank\">http://dev.twitter.com/apps/new</a> (found at https://dev.twitter.com/apps/&lt;app_id&gt;)."
			);

		
			$pluginsetting[] = array(
				"name"		=> "mybbpublisher_tw_token",
				"title"		=> "TWITTER Access Token",
				"description"	=> "Twitter data: Access token for account to which status updates will be posted (found at https://dev.twitter.com/apps/&lt;app_id&gt;/my_token)."
			);

		
			$pluginsetting[] = array(
				"name"		=> "mybbpublisher_tw_secret",
				"title"		=> "TWITTER Access Token Secret",
				"description"	=> "Twitter data: Access token secret for account to which status updates will be posted (found at https://dev.twitter.com/apps/&lt;app_id&gt;/my_token)."
			);    

			$pluginsetting[] = array(
				"name"		=> "mybbpublisher_fb_appid",
				"title"		=> "FACEBOOK Application ID",
				"description"	=> "Facebook data: Application ID for the application you created at <a href=\"http://www.facebook.com/developers/createapp.php\" target=\"_blank\">http://www.facebook.com/developers/createapp.php</a>."
			);

		
			$pluginsetting[] = array(
				"name"		=> "mybbpublisher_fb_apikey",
				"title"		=> "FACEBOOK Application API Key",
				"description"	=> "Facebook data: API Key for the application you created (found at http://www.facebook.com/developers/apps.php?app_id=&lt;app_id&gt;)."
			);

		
			$pluginsetting[] = array(
				"name"		=> "mybbpublisher_fb_secret",
				"title"		=> "FACEBOOK Application Secret",
				"description"	=> "Facebook data: Application Secret for the application you created (found at http://www.facebook.com/developers/apps.php?app_id=&lt;app_id&gt;)."
			);	    
			
			foreach($pluginsetting as $setting)
			{
				$db->update_query("settings", $setting, "name='".$setting['name']."'");
			}
	
			//replace settings
			unset($pluginsetting);
			$pluginsetting = array(
				"name"		=> "mybbpublisher_fb_access_token",
				"title"		=> "FACEBOOK Account Access Token",
				"description"	=> "Facebook data: This is the token required to access your personal account in Facebook.<br /><font color=\"red\">To set/edit this value, use ACP > Tools & Maintenance > MyBB Publisher > Step 2: Get Access Token for your Personal FB Account. </font>",
				"optionscode"	=> "php",
				"value"		=> ""
			);
			
			$db->update_query("settings", $pluginsetting, "name='mybbpublisher_fb_onetimetoken'");

			unset($pluginsetting);
			$pluginsetting = array(
				"name"		=> "mybbpublisher_fb_access_token_page",
				"title"		=> "FACEBOOK Access Token for where to publish",
				"description"	=> "Facebook data: This is your token for the Page/Group/App you manage and want to publish to Facebook. If blank, status will come from your personal account.<br /><font color=\"red\">To set/edit this value, use ACP > Tools & Maintenance > MyBB Publisher > Step 3: Select where to post and specify author.</font>",
				"optionscode"	=> "php",
				"value"		=> ""
			);
	
			$db->update_query("settings", $pluginsetting, "name='mybbpublisher_fb_sessionid'");

			unset($pluginsetting);
			$pluginsetting = array(
				"name"		=> "mybbpublisher_fb_pageid",
				"title"		=> "FACEBOOK Page ID",
				"description"	=> "Facebook data: This is the page for the Page/Group/App you manage and want to publish to Facebook.<br /><font color=\"red\">To set/edit this value, use ACP > Tools & Maintenance > MyBB Publisher > Step 3: Select where to post and specify author.</font>",
				"optionscode"	=> "php",
				"value"		=> ""
			);
	
			$db->update_query("settings", $pluginsetting, "name='mybbpublisher_fb_pageid'");
			
			//insert new
			unset($pluginsetting);
			$gid = mybbpublisher_settings_gid();
			$query = $db->query("select max(disporder) as biggest from ".TABLE_PREFIX."settings where gid=".$gid);
			$biggest = $db->fetch_field($query, "biggest");
			$pluginsetting = array(
				"name"		=> "mybbpublisher_uninstall_fields",
				"title"		=> "Delete status ID fields upon delete?",
				"description"	=> "Do you want to remove existing status update IDs from database during uninstall? Reinstalling will not overwrite existing.",
				"optionscode"	=> "yesno",
				"value"		=> "0",
				"disporder"	=> $biggest + 1,
				"gid"		=> $gid
			);

			$db->insert_query("settings", $pluginsetting);
			
			//remove deprecated
			$db->delete_query("settings", "name='mybbpublisher_fb_postauthor'");
			
			
		}

/***********************************************************************
step upgrade from 1.5 series to 1.6 series
***********************************************************************/
    	if(version_compare($oldver, '1.6.0', '<') && $oldver <> '' && $oldver <> 0)
    	{
			//insert/replace settings for Adf.ly support (combined with Bit.ly)
			
			//get disporder of current bit.ly
			$query = $db->simple_select("settings", "gid, disporder", "name='mybbpublisher_bitly_user'");
			$result = $db->fetch_array($query);
			
			//push them down one
			$db->query("update ".TABLE_PREFIX."settings set disporder = disporder + 1 where gid=".$result['gid']." and disporder >= ".$result['disporder']);

			//insert new above the old bitly position
			unset($pluginsetting);
			$pluginsetting = array(
				"name"		=> "mybbpublisher_shorten_service",
				"title"		=> "Which URL Shortening Service do you want to use?",
				"description"	=> "Select which URL shortening service to use. This option does not get used unless the access information is provided below.",
				"optionscode"	=> "radio\r\nbit=Bit.ly\r\nadf=Adf.ly",
				"value"		=> "bit",
				"disporder"	=> $result['disporder'],
				"gid"		=> $result['gid']
			);

			$db->insert_query("settings", $pluginsetting);
			
			//rename and reset existing Bit.ly settings
			unset($pluginsetting);
			$pluginsetting = array(
				"name"		=> "mybbpublisher_shorten_user",
				"title"		=> "Bit.ly Username or Adf.ly User ID for shortening service",
				"description"	=> "This is the username or user ID for the URL shortening service you want to use.",
				"optionscode"	=> "text",
				"value"		=> $mybb->settings['mybbpublisher_bitly_user']
			);
			
			$db->update_query("settings", $pluginsetting, "name='mybbpublisher_bitly_user'");

			unset($pluginsetting);
			$pluginsetting = array(
				"name"		=> "mybbpublisher_shorten_key",
				"title"		=> "Bit.ly API Key or Adf.ly API Key",
				"description"	=> "This is your API Key for the URL shortening service you want to use.",
				"optionscode"	=> "text",
				"value" => $mybb->settings['mybbpublisher_bitly_apikey']
			);
	
			$db->update_query("settings", $pluginsetting, "name='mybbpublisher_bitly_apikey'");
			
			unset($pluginsetting);
			$pluginsetting = array(
				"name"		=> "mybbpublisher_shorten_tw",
				"title"		=> "Shorten URLs in Twitter updates?",
				"description"	=> "If posting status updates to Twitter, do you want to shorten URLs back to your forum?"
			);
			
			$db->update_query("settings", $pluginsetting, "name='mybbpublisher_bitly_tw'");

			unset($pluginsetting);
			$pluginsetting = array(
				"name"		=> "mybbpublisher_shorten_fb",
				"title"		=> "Shorten URLs in Facebook updates?",
				"description"	=> "If posting status updates to Facebook, do you want to shorten URLs back to your forum?"
			);
	
			$db->update_query("settings", $pluginsetting, "name='mybbpublisher_bitly_fb'");
			
			//insert new from previous upgrade if it is not present for some reason
			if(!array_key_exists('mybbpublisher_uninstall_fields', $mybb->settings))
			{
				unset($pluginsetting);
				$query = $db->query("select max(disporder) as biggest from ".TABLE_PREFIX."settings where gid=".$result['gid']);
				$biggest = $db->fetch_field($query, "biggest");
				$pluginsetting = array(
					"name"		=> "mybbpublisher_uninstall_fields",
					"title"		=> "Delete status ID fields upon delete?",
					"description"	=> "Do you want to remove existing status update IDs from database during uninstall? Reinstalling will not overwrite existing.",
					"optionscode"	=> "yesno",
					"value"		=> "0",
					"disporder"	=> $biggest + 1,
					"gid"		=> $result['gid']
				);

				$db->insert_query("settings", $pluginsetting);
			}
		}
	
/***********************************************************************
step upgrade from 1.6/7 series to 1.8 series
***********************************************************************/
    	if(version_compare($oldver, '1.8.0', '<') && $oldver <> '' && $oldver <> 0)
    	{
			//nothing applicable to upgrade
		}
		
    	if(version_compare($oldver, '1.8.1', '<') && $oldver <> '' && $oldver <> 0)
    	{
			$pscom_plugins = $cache->read('pscom_plugins');
			if(!$pscom_plugins)
			{
				$db->delete_query("datacache", "title='pscom_plugins'");
				if(method_exists($cache, 'delete'))
				{
					$cache->delete("pscom_plugins");
				}
			}
	
			$cfcom_plugins = $cache->read('cfcom_plugins');
			if(!$cfcom_plugins)
			{
				$db->delete_query("datacache", "title='cfcom_plugins'");
				if(method_exists($cache, 'delete'))
				{
					$cache->delete("cfcom_plugins");
				}
			}
			
			$db->delete_query("datacache", "title='mybbpublisher_errors_fb'");
			$db->delete_query("datacache", "title='mybbpublisher_errors_tw'");
			if(method_exists($cache, 'delete'))
			{
				$cache->delete("mybbpublisher_errors_fb");
				$cache->delete("mybbpublisher_errors_tw");
			}
			
		}
		
/***********************************************************************
step upgrade from 1.8 series to 2.0 series
***********************************************************************/
    	if(version_compare($oldver, '2.0.0', '<') && $oldver <> '' && $oldver <> 0)
    	{

			//get disporder of current onmove
			$query = $db->simple_select("settings", "gid, disporder", "name='mybbpublisher_onmove'");
			$result = $db->fetch_array($query);
			
			//push them down one
			$db->query("update ".TABLE_PREFIX."settings set disporder = disporder + 1 where gid=".$result['gid']." and disporder >= ".$result['disporder']);

			//insert new token expires setting
			if(!array_key_exists('mybbpublisher_default_image', $mybb->settings))
			{
				unset($pluginsetting);
				$pluginsetting = array(
					"name"		=> "mybbpublisher_default_image",
					"title"		=> "Default image.",
					"description"	=> "This is the URL to the image (avatar size) to use when posting to Facebook when the poster has no avatar, or when posting announcements.",
					"optionscode"	=> "text",
					"value"		=> $mybb->settings['bburl']."/images/mybbpublisher_default.png",
					"disporder"	=> $result['disporder'],
					"gid"		=> $result['gid']
				);
				$db->insert_query("settings", $pluginsetting);
			}
			
			//get disporder of current access token
			$query = $db->simple_select("settings", "gid, disporder", "name='mybbpublisher_fb_access_token'");
			$result = $db->fetch_array($query);
			
			//push them down one
			$db->query("update ".TABLE_PREFIX."settings set disporder = disporder + 1 where gid=".$result['gid']." and disporder >= ".$result['disporder']);

			//insert new token expires setting
			if(!array_key_exists('mybbpublisher_fb_access_token_expires', $mybb->settings))
			{
				unset($pluginsetting);
				$pluginsetting = array(
					"name"		=> "mybbpublisher_fb_access_token_expires",
					"title"		=> "FACEBOOK Access Token expiration time",
					"description"	=> "Facebook data: This is the expires time of your access token.<br /><font color=\"red\">To reset this value, use ACP > Tools & Maintenance > MyBB Publisher > Step 2: Get Access Token for your Personal FB Account.</font>",
					"optionscode"	=> "php",
					"value"		=> "",
					"disporder"	=> $result['disporder'],
					"gid"		=> $result['gid']
				);
				$db->insert_query("settings", $pluginsetting);
			}

			//insert new debug setting at bottom
			if(!array_key_exists('mybbpublisher_debug', $mybb->settings))
			{
				unset($pluginsetting);
				$query = $db->query("select max(gid) as gid, max(disporder) as biggest from ".TABLE_PREFIX."settings where name like 'mybbpublisher_%'");
				$result = $db->fetch_array($query);		
				$pluginsetting = array(
					"name"		=> "mybbpublisher_debug",
					"title"		=> "Enable debug mode?",
					"description"	=> "Enabling this setting will save available content returned from the publishing site to aid in you debugging your settings. All output will be placed in the cached error logs, accessible from the ACP > Tools and Maintenance > MyBB Publisher menu.",
					"optionscode"	=> "yesno",
					"value"		=> "0",
					"disporder"	=> $result['biggest'] + 1,
					"gid"		=> $result['gid']
				);

				$db->insert_query("settings", $pluginsetting);
			}
			
			//remove deprecated
			$db->delete_query("settings", "name='mybbpublisher_shorten_fb'");
			
			//Add Task
			include(MYBB_ROOT.'inc/functions_task.php');
	
			$new_task = array(
				"title" => $db->escape_string('MyBBPublisher'),
				"description" => $db->escape_string('Retrieves new access token from Facebook for MyBB Publisher.'),
				"file" => $db->escape_string('mybbpublisher'),
				"minute" => $db->escape_string('9'),
				"hour" => $db->escape_string('5'),
				"day" => $db->escape_string('3'),
				"month" => $db->escape_string('*'),
				"weekday" => $db->escape_string('*'),
				"enabled" => 1,
				"logging" => 1
			);
	
			$new_task['nextrun'] = fetch_next_run($new_task);
			$tid = $db->insert_query("tasks", $new_task);
			$plugins->run_hooks("admin_tools_tasks_add_commit");
			$cache->update_tasks();	
		}		

    	if(version_compare($oldver, '2.0.2', '<') && $oldver <> '' && $oldver <> 0)
    	{
			//nothing to do with 2.0.1 and 2.0.2
		}	

/***********************************************************************
step upgrade from 2.0 series to 3.0 series
***********************************************************************/

    	if(version_compare($oldver, '3.0.0', '<') && $oldver <> '' && $oldver <> 0)
    	{
			//get gid for setting group
			$query = $db->simple_select("settinggroups", "gid", "name like 'mybbpublisher'");
			$gid = $db->fetch_field($query, 'gid');

    		//change inclusive setting
			$query = $db->simple_select("settings", "sid, value", "name='mybbpublisher_inclusive'");
			$setting = $db->fetch_array($query);
			
			unset($settingchange);
			$settingchange = array(
				"name"		=> "mybbpublisher_how",
				"title"		=> "Publish:",
				"optionscode"	=> "radio\r\ninclude=From only the listed FIDs\r\nexclude=From all except the listed FIDs",
				"value"		=> ($setting['value'] == 1 ? "include" : "exclude")
			);
			
			$db->update_query("settings", $settingchange, "sid=".$setting['sid']);
			
    		//change forums list setting
			$query = $db->simple_select("settings", "sid", "name='mybbpublisher_forums'");
			$setting = $db->fetch_array($query);
			
			unset($settingchange);
			$settingchange = array(
				"title"		=> "Forums in which New Threads and Announcements are published",
				"description"	=> "This is a CSV list of FIDs to publish or not to publish based on setting above. Global announcements ignore this setting. (\'0\' or blank means everything).<br /><font style=\"color:red;\">Use caution as to avoid adding Private forums or forums with special View Own Thread permissions!</font>"
			);
			
			$db->update_query("settings", $settingchange, "sid=".$setting['sid']);

    		//change onnewthread setting
			$query = $db->simple_select("settings", "sid", "name='mybbpublisher_onnewthread'");
			$setting = $db->fetch_array($query);
			
			unset($settingchange);
			$settingchange = array(
				"title"		=> "Publish New Thread Subjects?",
				"description"	=> "Publish new thread subjects in published forums?",
			);
			
			$db->update_query("settings", $settingchange, "sid=".$setting['sid']);

    		//change onmove setting
			$query = $db->simple_select("settings", "sid", "name='mybbpublisher_onmove'");
			$setting = $db->fetch_array($query);
			
			unset($settingchange);
			$settingchange = array(
				"title"		=> "Publish moved threads?",
				"description"	=> "Publish thread subject if a thread is moved into a published forum? Does not work with Copy, only Move and Move with Redirect",
			);
			
			$db->update_query("settings", $settingchange, "sid=".$setting['sid']);
				
    		//change onmove setting
			$query = $db->simple_select("settings", "sid", "name='mybbpublisher_onmove'");
			$setting = $db->fetch_array($query);
			
			unset($settingchange);
			$settingchange = array(
				"title"		=> "Publish moved threads?",
				"description"	=> "Publish thread subject if a thread is moved into a published forum? Does not work with Copy, only Move and Move with Redirect",
			);
			
			$db->update_query("settings", $settingchange, "sid=".$setting['sid']);

			//insert new allowed groups setting
			if(!array_key_exists('mybbpublisher_allowed_groups', $mybb->settings))
			{
				//push settings down one after enabled
				$db->query("update ".TABLE_PREFIX."settings set disporder = disporder + 1 where gid=".$gid." and disporder > 1");
				
				$pluginsetting = array(
					"name"		=> "mybbpublisher_allowed_groups",
					"title"		=> "Usergroups that will have their posts and announcements published.",
					"description"	=> "This is a CSV list of group IDs that will have their threads published. (\'0\' or blank means all groups, don\'t worry about bots, banned, etc., those are handled by your forum permissions)",
					"optionscode"	=> "text",
					"value"		=> "0",
					"disporder"	=> 2,
					"gid"		=> $gid
				);
				$db->insert_query("settings", $pluginsetting);
			}

			//insert new method setting
			if(!array_key_exists('mybbpublisher_method', $mybb->settings))
			{
				//push settings down one after allowed groups
				$db->query("update ".TABLE_PREFIX."settings set disporder = disporder + 1 where gid=".$gid." and disporder > 2");
				
				$pluginsetting = array(
					"name"		=> "mybbpublisher_method",
					"title"		=> "Option to publish?",
					"description"	=> "Do you want to always publish when a new thread or announcement is posted to a publishable forum, or do you want the user to have the option to publish?",
					"optionscode"	=> "radio\r\nalways=Always publish when allowed\r\nondemand=Give option to publish when allowed",
					"value"		=> "always",
					"disporder"	=> 3,
					"gid"		=> $gid
				);
				$db->insert_query("settings", $pluginsetting);
			}

			//insert new max char setting
			if(!array_key_exists('mybbpublisher_max_chars', $mybb->settings))
			{
				$query = $db->simple_select("settings", "disporder", "name='mybbpublisher_forums'");
				$disporder = $db->fetch_field($query, 'disporder');
				
				//push settings down one after forums
				$db->query("update ".TABLE_PREFIX."settings set disporder = disporder + 1 where gid=".$gid." and disporder > ".$disporder);
				
				$pluginsetting = array(
					"name"		=> "mybbpublisher_max_chars",
					"title"		=> "Maximum characters of message contents for previews.",
					"description"	=> "This is the maximum number of characters to include in descriptions for shared links.",
					"optionscode"	=> "text",
					"value"		=> "100",
					"disporder"	=> ++$disporder,
					"gid"		=> $gid
				);
				$db->insert_query("settings", $pluginsetting);
			}

			//generate new serialized settings from existing settings
			
			$oldsettings = array();
			$query = $db->simple_select('settings', '*', "name like 'mybbpublisher_fb%'");
			while($setting = $db->fetch_array($query))
			{
				switch($setting['name'])
				{
					case 'mybbpublisher_fb_enabled':
						$oldsettings['facebook']['enabled'] = $setting['value'];
						break;
					
					case 'mybbpublisher_fb_appid':
						$oldsettings['facebook']['appid'] = $setting['value'];
						break;

					case 'mybbpublisher_fb_secret':
						$oldsettings['facebook']['secret'] = $setting['value'];
						break;

					case 'mybbpublisher_fb_access_token':
						$oldsettings['facebook']['token'] = $setting['value'];
						break;
						
					case 'mybbpublisher_fb_token_page':
						$oldsettings['facebook']['page_token'] = $setting['value'];
						break;

					case 'mybbpublisher_fb_pageid':
						$oldsettings['facebook']['page'] = $setting['value'];
						break;

					case 'mybbpublisher_fb_icon':
						$oldsettings['facebook']['icon'] = $setting['value'];
						break;

					case 'mybbpublisher_fb_access_token_expires':
						$oldsettings['facebook']['expires'] = $setting['value'];
						break;
				}
			}

			$oldsettings['facebook']['type'] = 'link';
			
			$query = $db->simple_select('settings', '*', "name like 'mybbpublisher_tw%'");
			while($setting = $db->fetch_array($query))
			{
				switch($setting['name'])
				{
					case 'mybbpublisher_tw_enabled':
						$oldsettings['twitter']['enabled'] = $setting['value'];
						break;
					
					case 'mybbpublisher_tw_ckey':
						$oldsettings['twitter']['ckey'] = $setting['value'];
						break;

					case 'mybbpublisher_tw_csecret':
						$oldsettings['twitter']['csecret'] = $setting['value'];
						break;
						
					case 'mybbpublisher_tw_token':
						$oldsettings['twitter']['token'] = $setting['value'];
						break;

					case 'mybbpublisher_tw_tsecret':
						$oldsettings['twitter']['tsecret'] = $setting['value'];
						break;

					case 'mybbpublisher_tw_hashtags':
						$oldsettings['twitter']['tags'] = $setting['value'];
						break;

					case 'mybbpublisher_tw_icon':
						$oldsettings['twitter']['icon'] = $setting['value'];
						break;
				}
			}
			
			$oldsettings['twitter']['type'] = 'link';
			
			
			//insert new module settings setting
			if(!array_key_exists('mybbpublisher_module_settings', $mybb->settings))
			{
				$pluginsetting = array(
					"name"		=> "mybbpublisher_module_settings",
					"title"		=> "Module Settings",
					"description"	=> "This is protected data, storing the various module settings.<br /><font color=\"red\">To set/edit this content, use ACP > Tools & Maintenance > MyBB Publisher options.</font>",
					"optionscode"	=> "php",
					"value"		=> serialize($oldsettings),
					"disporder"	=> 100,
					"gid"		=> $gid
				);
				$db->insert_query("settings", $pluginsetting);
			}

			//remove deprecated
			$db->delete_query("settings", "name in (
				'mybbpublisher_shorten_tw',
				'mybbpublisher_fb_enabled',
				'mybbpublisher_fb_appid',
				'mybbpublisher_fb_apikey',
				'mybbpublisher_fb_secret',
				'mybbpublisher_fb_access_token',
				'mybbpublisher_fb_access_token_page',
				'mybbpublisher_fb_pageid',
				'mybbpublisher_fb_icon',
				'mybbpublisher_fb_access_token_expires',
				'mybbpublisher_tw_enabled',
				'mybbpublisher_tw_username',
				'mybbpublisher_tw_ckey',
				'mybbpublisher_tw_csecret',
				'mybbpublisher_tw_token',
				'mybbpublisher_tw_secret',
				'mybbpublisher_tw_icon',
				'mybbpublisher_tw_hashtags',
				'mybbpublisher_tw_hashlimit'
				)"
			);

			//add new fields
			if(!$db->field_exists('publish_ids', 'announcements'))
			{
				$db->write_query("ALTER TABLE ".TABLE_PREFIX."announcements ADD publish_ids TEXT NULL");
			}
			if(!$db->field_exists('publish_ids', 'threads'))
			{
				$db->write_query("ALTER TABLE ".TABLE_PREFIX."threads ADD publish_ids TEXT NULL");
			}
			
			//populate with existing ids then drop old fields (this is a long way, but its a lot less queries)
			if($db->field_exists('tw_id', 'threads'))
			{
				//create temp table if not already there
				if(!$db->table_exists("threads_mybbpub"))
				{
					$db->query("CREATE TABLE ".TABLE_PREFIX."threads_mybbpub LIKE ".TABLE_PREFIX."threads;");
				}
				
				//empty it
				$db->query("TRUNCATE TABLE ".TABLE_PREFIX."threads_mybbpub;");
				
				//query the existing and create insert query
				$count = 0;
				$insert = "INSERT INTO ".TABLE_PREFIX."threads_mybbpub (tid, publish_ids) VALUES ";
				$query = $db->simple_select('threads', 'tid, tw_id, fb_id, fb_uid', 'tw_id <> 0 or fb_id <> 0');
				while($row = $db->fetch_array($query))
				{
					$newids = array('facebook'=>array('id'=>$row['fb_id'], 'uid'=>$row['fb_uid']),'twitter'=>array('id'=>$row['tw_id']));
					$insert .= "(".$row['tid'].",'".serialize($newids)."'),";
					++$count;
				}
				//drop last comma
				$insert = substr($insert, 0, -1);
				
				//insert into temp table
				if($count)
				{
					$db->query($insert);

					//update main table
					$db->query("UPDATE ".TABLE_PREFIX."threads b, ".TABLE_PREFIX."threads_mybbpub a SET b.publish_ids = a.publish_ids WHERE a.tid = b.tid");
				}
												
				//drop old fields
				$db->write_query("ALTER TABLE ".TABLE_PREFIX."threads DROP tw_id");
				$db->write_query("ALTER TABLE ".TABLE_PREFIX."threads DROP fb_id");
				$db->write_query("ALTER TABLE ".TABLE_PREFIX."threads DROP fb_uid");
				
				//drop temp table
				$db->write_query("DROP TABLE ".TABLE_PREFIX."threads_mybbpub");
			}
			
			if($db->field_exists('tw_id', 'announcements'))
			{
				//create temp table if not already there
				if(!$db->table_exists("announcements_mybbpub"))
				{
					$db->query("CREATE TABLE ".TABLE_PREFIX."announcements_mybbpub LIKE ".TABLE_PREFIX."announcements;");
				}
				
				//empty it
				$db->query("TRUNCATE TABLE ".TABLE_PREFIX."announcements_mybbpub;");
				
				//query the existing and create insert query
				$count = 0;
				$insert = "INSERT INTO ".TABLE_PREFIX."announcements_mybbpub (aid, publish_ids) VALUES ";
				$query = $db->simple_select('announcements', 'aid, tw_id, fb_id, fb_uid', 'tw_id <> 0 or fb_id <> 0');
				while($row = $db->fetch_array($query))
				{
					$newids = array('facebook'=>array('id'=>$row['fb_id'], 'uid'=>$row['fb_uid']),'twitter'=>array('id'=>$row['tw_id']));
					$insert .= "(".$row['aid'].",'".serialize($newids)."'),";
					++$count;
				}
				//drop last comma
				$insert = substr($insert, 0, -1);
				
				//insert into temp table
				if($count)
				{
					$db->query($insert);

					//update main table
					$db->query("UPDATE ".TABLE_PREFIX."announcements b, ".TABLE_PREFIX."announcements_mybbpub a SET b.publish_ids = a.publish_ids WHERE a.aid = b.aid");
				}
												
				//drop old fields
				$db->write_query("ALTER TABLE ".TABLE_PREFIX."announcements DROP tw_id");
				$db->write_query("ALTER TABLE ".TABLE_PREFIX."announcements DROP fb_id");
				$db->write_query("ALTER TABLE ".TABLE_PREFIX."announcements DROP fb_uid");
				
				//drop temp table
				$db->write_query("DROP TABLE ".TABLE_PREFIX."announcements_mybbpub");
			}

		}

		if(version_compare($oldver, '3.4.0', '<') && $oldver <> '' && $oldver <> 0)
    	{
			//migrate forums to publish from global settings to module settings
			$modulesettings = unserialize($mybb->settings['mybbpublisher_module_settings']);
			$mybbpublisher_forums = explode(',', $mybb->settings['mybbpublisher_forums']);
			$mybbpublisher_forums = array_walk($mybbpublisher_forums, 'intval');
			
			foreach($modulesettings as $service => $settings)
			{
				$settings['forums'] = 0;
				if($settings['enabled'] == '1')
				{
					$settings['forums'] = $mybbpublisher_forums;
				}
				$modulesettings[$service] = $settings;
			}

			//update with new module settings
			$settingchange = array(
				"value"		=> serialize($modulesettings)
			);
			$db->update_query("settings", $settingchange, "name='mybbpublisher_module_settings'");

			//remove deprecated
			$db->delete_query("settings", "name in ('mybbpublisher_forums')");

			//clear old error cache, was in _activate and was adding the items if they did not exist before. this gets rid of it permanently
			if($cache->read('mybbpublisher_errors_tw'))
			{
				if(is_object($cache->handler) || is_object($cache->cachehandler))
				{
		    		$cache->delete('mybbpublisher_errors_tw');
		    	}
				$db->delete_query('datacache', 'name="mybbpublisher_errors_tw"');
	    	}

			if($cache->read('mybbpublisher_errors_fb'))
			{
				if(is_object($cache->handler) || is_object($cache->cachehandler))
				{
		    		$cache->delete('mybbpublisher_errors_fb');
		    	}
				$db->delete_query('datacache', 'name="mybbpublisher_errors_fb"');
	    	}

		}		
?>
