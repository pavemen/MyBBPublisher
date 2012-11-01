<?php
/**
 * MyBBPublisher Plugin for MyBB - Plugin functions
 * Copyright 2012 CommunityPlugins.com, All Rights Reserved
 *
 * Website: http://www.communityplugins.com
 * Version 3.1.0
 * License: Creative Commons Attribution-NonCommerical ShareAlike 3.0
				http://creativecommons.org/licenses/by-nc-sa/3.0/legalcode
 *
 */
 
// Disallow direct access to this file for security reasons DO NOT REMOVE
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.<br />");
}

$plugins->add_hook("admin_tools_menu", "mybbpublisher_admin_nav");
$plugins->add_hook("admin_tools_action_handler", "mybbpublisher_action_handler");
$plugins->add_hook("admin_load", "mybbpublisher_admin");

/****************************************************************
Plugin code
****************************************************************/
function mybbpublisher_info()
{
	return array(
		"name"		=> "MyBBpublisher",
		"description"	=> "Publishes selected MyBB content to several social media sites.",
		"website"	=> "http://www.communityplugins.com",
		"author"	=> "CommunityPlugins.com",
		"authorsite"	=> "http://www.communityplugins.com",
		"version"	=> "3.1.0",
		"guid" 		=> "7c8931b891567c13d1e05534ac50dd52",
		"compatibility" => "16*"
	);
}

function mybbpublisher_install() 
{
	global $mybb, $db, $plugins, $cache;
    
    //get current version if avail
    $oldver = mybbpublisher_get_cache_version() ;
        
    //start settings install
	$settings_group = array(
		"gid" => "",
		"name" => "MyBBpublisher",
		"title" => "MyBBpublisher",
		"description" => "Settings for the MyBBpublisher feature.",
		"disporder" => "50",
		"isdefault" => "0",
        );
		
	$db->insert_query("settinggroups", $settings_group);
	$gid = $db->insert_id();
	
	$pluginsetting = array();
	
	$disporder = 1;
	
	$pluginsetting[] = array(
		"name"		=> "mybbpublisher_enabled",
		"title"		=> "Enable MyBBpublisher",
		"description"	=> "Master switch for MyBBpublisher.",
		"optionscode"	=> "onoff",
		"value"		=> "0",
		"disporder"	=> $disporder,
		"gid"		=> $gid
	);

	$pluginsetting[] = array(
		"name"		=> "mybbpublisher_allowed_groups",
		"title"		=> "Usergroups that will have their posts and announcements published.",
		"description"	=> "This is a CSV list of group IDs that will have their threads published. (\'0\' or blank means all groups, don\'t worry about bots, banned, etc., those are handled by your forum permissions)",
		"optionscode"	=> "text",
		"value"		=> "0",
		"disporder"	=> ++$disporder,
		"gid"		=> $gid
	);
	
	$pluginsetting[] = array(
		"name"		=> "mybbpublisher_method",
		"title"		=> "Option to publish?",
		"description"	=> "Do you want to always publish when a new thread or announcement is posted to a publishable forum, or do you want the user to have the option to publish?",
		"optionscode"	=> "radio\r\nalways=Always publish when allowed\r\nondemand=Give option to publish when allowed",
		"value"		=> "always",
		"disporder"	=> ++$disporder,
		"gid"		=> $gid
	);
		
	$pluginsetting[] = array(
		"name"		=> "mybbpublisher_how",
		"title"		=> "Publish:",
		"optionscode"	=> "radio\r\ninclude=From only the listed FIDs\r\nexclude=From all except the listed FIDs",
		"value"		=> "include",
		"disporder"	=> ++$disporder,
		"gid"		=> $gid
	);
		
	$pluginsetting[] = array(
		"name"		=> "mybbpublisher_forums",
		"title"		=> "Forums in which New Threads and Announcements are published",
		"description"	=> "This is a CSV list of FIDs to publish or not to publish based on setting above. Global announcements ignore this setting. (\'0\' or blank means everything).<br /><font style=\"color:red;\">Use caution as to avoid adding Private forums or forums with special View Own Thread permissions!</font>",
		"optionscode"	=> "text",
		"value"		=> "0",
		"disporder"	=> ++$disporder,
		"gid"		=> $gid
	);
	
	$pluginsetting[] = array(
		"name"		=> "mybbpublisher_max_chars",
		"title"		=> "Maximum characters of message contents for previews.",
		"description"	=> "This is the maximum number of characters to include in descriptions for shared links.",
		"optionscode"	=> "text",
		"value"		=> "100",
		"disporder"	=> ++$disporder,
		"gid"		=> $gid
	);
	
	$pluginsetting[] = array(
		"name"		=> "mybbpublisher_onnewthread",
		"title"		=> "Publish New Thread Subjects?",
		"description"	=> "Publish new thread subjects in published forums?",
		"optionscode"	=> "yesno",
		"value"		=> "0",
		"disporder"	=> ++$disporder,
		"gid"		=> $gid
	);
	
	$pluginsetting[] = array(
		"name"		=> "mybbpublisher_onannounce",
		"title"		=> "Publish Announcements?",
		"description"	=> "Publish new announcements. Only works for announcements created or editted via the ACP. Does not work with the ModCP.",
		"optionscode"	=> "yesno",
		"value"		=> "0",
		"disporder"	=> ++$disporder,
		"gid"		=> $gid
	);

	
	$pluginsetting[] = array(
		"name"		=> "mybbpublisher_onmove",
		"title"		=> "Publish moved threads?",
		"description"	=> "Publish thread subject if a thread is moved into a published forum? Does not work with Copy, only Move and Move with Redirect",
	    "optionscode" 	=> "yesno",
	    "value" 	=> "0",
		"disporder"	=> ++$disporder,
		"gid"		=> $gid
	);

	$pluginsetting[] = array(
		"name"		=> "mybbpublisher_default_image",
		"title"		=> "Default image",
		"description"	=> "This is the full URL to the image (of avatar size) to use when posting and the author has no avatar, or when posting announcements. Since this image is used on remote sites, the full URL is required.",
		"optionscode"	=> "text",
		"value"		=> $mybb->settings['bburl']."/images/mybbpublisher_default.png",
		"disporder"	=> ++$disporder,
		"gid"		=> $gid
	);
	
	$pluginsetting[] = array(
		"name"		=> "mybbpublisher_shorten_service",
		"title"		=> "Which URL Shortening Service do you want to use?",
		"description"	=> "Select which URL shortening service to use. This option does not get used unless the access information is provided below.",
		"optionscode"	=> "radio\r\nnone=None\r\nbit=Bit.ly\r\nadf=Adf.ly",
		"value"		=> "none",
		"disporder"	=> ++$disporder,
		"gid"		=> $gid
	);
	
	$pluginsetting[] = array(
		"name"		=> "mybbpublisher_shorten_user",
		"title"		=> "Username or User ID for shortening service",
		"description"	=> "This is the username or user ID for the URL shortening service you want to use. Leave blank to not shorten URLs.",
		"optionscode"	=> "text",
		"value"		=> "",
		"disporder"	=> ++$disporder,
		"gid"		=> $gid
	);
	
	$pluginsetting[] = array(
		"name"		=> "mybbpublisher_shorten_key",
		"title"		=> "API Key for the shortening service",
		"description"	=> "This is your API Key for the URL shortening service you want to use. Leave blank to not shorten URLs.",
		"optionscode"	=> "text",
		"value"		=> "",
		"disporder"	=> ++$disporder,
		"gid"		=> $gid
	);	
	
	$pluginsetting[] = array(
		"name"		=> "mybbpublisher_uninstall_fields",
		"title"		=> "Delete status ID fields upon delete?",
		"description"	=> "Do you want to remove existing status update IDs from database during uninstall? Reinstalling will not overwrite existing.",
		"optionscode"	=> "yesno",
		"value"		=> "0",
		"disporder"	=> ++$disporder,
		"gid"		=> $gid
	);

	$pluginsetting[] = array(
		"name"		=> "mybbpublisher_debug",
		"title"		=> "Enable debug mode?",
		"description"	=> "Enabling this setting will save available content returned from the publishing service to aid in you debugging your settings. All output will be placed in the cached error logs, accessible from the ACP > Tools and Maintenance > MyBB Publisher menu.",
		"optionscode"	=> "yesno",
		"value"		=> "0",
		"disporder"	=> ++$disporder,
		"gid"		=> $gid
	);
	
	$pluginsetting[] = array(
		"name"		=> "mybbpublisher_module_settings",
		"title"		=> "Module Settings",
		"description"	=> "This is protected data, storing the various module settings.<br /><font color=\"red\">To set/edit this content, use ACP > Tools & Maintenance > MyBB Publisher options.</font>",
		"optionscode"	=> "php",
		"value"		=> "",
		"disporder"	=> ++$disporder,
		"gid"		=> $gid
	);
	
	reset($pluginsetting);
	foreach($pluginsetting as $setting)
	{
		$db->insert_query("settings", $setting);
	}
	
	rebuild_settings();

	if(!$db->field_exists('publish_ids', 'announcements'))
	{
		$db->write_query("ALTER TABLE ".TABLE_PREFIX."announcements ADD publish_ids TEXT NULL");
	}
	if(!$db->field_exists('publish_ids', 'threads'))
	{
		$db->write_query("ALTER TABLE ".TABLE_PREFIX."threads ADD publish_ids TEXT NULL");
	}
	
/*---- Add Task ---*/
	include('../inc/functions_task.php');
	
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

function mybbpublisher_is_installed() 
{
	global $mybb, $db;
	if(array_key_exists('mybbpublisher_enabled', $mybb->settings))
	{
		return true;
	}
	return false;
}

function mybbpublisher_activate() 
{
	global $db, $cache, $plugins;
    
    	//clear old error cache
    	$cache->update('mybbpublisher_errors_tw',false);
    	$cache->update('mybbpublisher_errors_fb',false);
    
    	//deal with version changes on activate
    	$oldver = mybbpublisher_get_cache_version() ;

	if(file_exists(MYBB_ROOT.'/inc/plugins/mybbpublisher/upgrade.php'))
	{
		require_once MYBB_ROOT.'/inc/plugins/mybbpublisher/upgrade.php';
    	}
	$retval = mybbpublisher_set_cache_version() ;

 	rebuild_settings();
 	
 	//install template edits	
	include MYBB_ROOT."/inc/adminfunctions_templates.php";
	find_replace_templatesets("newthread", "#".preg_quote('{$disablesmilies}')."#i", '{$disablesmilies}{$mybbpublisher_option}');

	$db->update_query("tasks", array("enabled" => 1), "title='".$db->escape_string('MyBBPublisher')."'");
	$plugins->run_hooks("admin_tools_tasks_edit_commit");
	$cache->update_tasks(); 	
}

function mybbpublisher_deactivate() 
{
	global $db, $cache, $plugins;
	
	//remove template edits	
	include MYBB_ROOT."/inc/adminfunctions_templates.php";
	find_replace_templatesets("newthread", "#".preg_quote('{$mybbpublisher_option}')."#i", '', 0);

	$db->update_query("tasks", array("enabled" => 0), "title='".$db->escape_string('MyBBPublisher')."'");
	$plugins->run_hooks("admin_tools_tasks_edit_commit");
	$cache->update_tasks();
}

function mybbpublisher_uninstall() 
{
	global $mybb, $db, $cache, $plugins;
	
	if($mybb->settings['mybbpublisher_uninstall_fields'] == 1)
	{
		$db->write_query("ALTER TABLE ".TABLE_PREFIX."announcements DROP publish_ids");
		$db->write_query("ALTER TABLE ".TABLE_PREFIX."threads DROP publish_ids");
	}
	
	$query = $db->simple_select("settinggroups", "gid", "name='mybbpublisher'");
	$gid = $db->fetch_field($query, 'gid');
	$db->delete_query("settinggroups","gid='".$gid."'");
	$db->delete_query("settings", "name like '%mybbpublisher%'");
	
	rebuild_settings();
    
    	//remove version details
    	$retval = mybbpublisher_unset_cache_version() ;
    
/*---- Remove Task ---*/
	include('../inc/functions_task.php');

	$tid = $db->delete_query("tasks", "title='".$db->escape_string('MyBBPublisher')."'");
	$plugins->run_hooks("admin_tools_tasks_delete_commit");
	$cache->update_tasks();     
}

// ********* Versioning code *************

/**
 * Get MyBBPublisher version from CPCOM cache
 * @return string version number
 */
function mybbpublisher_get_cache_version() 
{
	global $cache, $mybb, $db;

	//get currently installed version, if there is one
	$cpcom_plugins = $cache->read('cpcom_plugins');
	if(is_array($cpcom_plugins))
	{
        return $cpcom_plugins['versions']['mybbpublisher'];
	}
    return 0;
}

/**
 * Update MyBBPublisher version in CPCOM cache
 * @return boolean
 */
function mybbpublisher_set_cache_version() 
{
	global $cache;
	
	//get version from this plugin file
	$mybbpublisher_info = mybbpublisher_info();
    
	//update version cache to latest
	$cpcom_plugins = $cache->read('cpcom_plugins');
	$cpcom_plugins['versions']['mybbpublisher'] = $mybbpublisher_info['version'];
	$cache->update('cpcom_plugins', $cpcom_plugins);

    return true;
}

/**
 * Remove MyBBPublisher from CPCOM cache
 * @return boolean
 */
function mybbpublisher_unset_cache_version() 
{
	global $cache;

	$cpcom_plugins = $cache->read('cpcom_plugins');
	unset($cpcom_plugins['versions']['mybbpublisher']);
	$cache->update('cpcom_plugins', $cpcom_plugins);
    
    return true;
}

// ********* Admin tools testing code *************


/**
 * Add MyBB Publisher to tools menu
 * @var Array array of the existing sub-menus
 * @return Array updated sub-menu
 */
function mybbpublisher_admin_nav(&$sub_menu)
{
	global $mybb, $lang;
	
		end($sub_menu);
		$key = (key($sub_menu))+20;
		
		if(!$key)
		{
			$key = '120';
		}
		
		$sub_menu[$key] = array('id' => 'mybbpublisher', 'title' => 'MyBB Publisher', 'link' => "index.php?module=tools-mybbpublisher&action=tools");
		
		return $sub_menu;
}

/**
 * Add MyBB Publisher to ACP handler
 * @var Array array of exissting actions
 * @return Array updated action array
 */
function mybbpublisher_action_handler(&$action)
{
	$action['mybbpublisher'] = array('active' => 'mybbpublisher', 'file' => '');
	
	return $action;
}

/**
 * Process the actual MyBBPublisher admin page
 * 
 */
function mybbpublisher_admin()
{
	global $mybb, $db, $page, $lang, $cache, $config, $publisher, $plugins;

	if($page->active_action == "mybbpublisher")
	{

		$lang->load('mybbpublisher');
		
		//verify required PHP functions
		$required_pass = true;
		if(!function_exists('curl_init'))
		{
				$output = '<h1><font color="red">'.$lang->mybbpublisher_curl.'</font></h1>';
				$required_pass = false;
		}

		if(!function_exists('json_decode'))
		{
				$output = '<h1><font color="red">'.$lang->mybbpublisher_json.'</font></h1>';
				$required_pass = false;
		}

		//verify clearing logs to avoid accidental deletion
		if($mybb->request_method == "post" && $mybb->input['action'] == 'do_clearlogs')
		{
			// User clicked no
			if($mybb->input['no'])
			{
				admin_redirect("index.php?module=tools-mybbpublisher");
			}
		}		

		//confirm action
		if($mybb->input['action'] == 'clearlogs')
		{
			$page->output_confirm_action("index.php?module=tools-mybbpublisher&action=do_clearlogs", $lang->mybbpublisher_clear_confirm, "MyBB Publisher");
			return;
		}

		$addon = '';
		
		//if main switch off, leave hint for admin
		if(!$mybb->settings['mybbpublisher_enabled'])
		{
			$addon .= ' <br /><span style="font-weight:bold;color:red;">'.$lang->mybbpublisher_not_enabled.'</span>';
		}

		//if in debug mode, leave hint for admin
		if($mybb->settings['mybbpublisher_debug'] == 1)
		{
			$addon .= ' <br /><span style="font-weight:bold;color:blue;">'.$lang->mybbpublisher_debug_enabled.'</span>';
		}

		//start of output
		$page->add_breadcrumb_item("MyBB Publisher".$addon);
		$page->output_header("MyBB Publisher");
		
		//output tests/setup
		if($required_pass)
		{
			$output = "";

			//load main class
			require_once(MYBB_ROOT.'/inc/plugins/mybbpublisher/class_mybbpublisher.php');
			$publisher = new mybbpublisher;
		
			$plugins->run_hooks("mybbpublisher_admin_start", $publisher);
			
			//clear error logs
			if($mybb->input['action'] == "do_clearlogs")
			{
				foreach($publisher->services as $service => $version)
				{
					$db->delete_query("datacache", "title='mybbpublisher_errors_".$service."'");
					if(method_exists($cache, 'delete'))
					{
						$cache->delete("mybbpublisher_errors_".$service);
					}					
				}

				flash_message($lang->mybbpublisher_clearlog_success, 'success');
				admin_redirect("index.php?module=tools-mybbpublisher");
			}		

	        	$sub_tabs['tools'] = array('title' => $lang->mybbpublisher_tools,
                                  'description' => $lang->mybbpublisher_tools_desc,
                                  'link' => 'index.php?module=tools-mybbpublisher&amp;action=tools');

			//show requested error logs
			if($mybb->input['action'] == "showlog")
			{
				$logcache = $cache->read("mybbpublisher_errors_".$mybb->input['service']);
				$output .= '<h3>'.$lang->sprintf($lang->mybbpublisher_showlog, $mybb->input['service']).'</h3>';
				if($logcache)
				{
					$output .= '<pre>';
					$output .= print_r($logcache, true);
					$output .= '</pre>';			
				}
				else
				{
					flash_message($lang->mybbpublisher_log_empty, 'success');
					admin_redirect("index.php?module=tools-mybbpublisher");
				}
			}		

			//main code output if not viewing log
			//if($mybb->input['action'] != 'showlog')
			{
				//show logs table if not requesting a specific service
				if($mybb->input['service'] == '')
				{
					//for each module
					$table = new Table;
					$table->construct_row($table->construct_cell('<a href="index.php?module=tools-mybbpublisher&action=clearlogs">'.$lang->mybbpublisher_clearlog.'</a>'));
					foreach($publisher->services as $service => $version)
					{
						$table->construct_row($table->construct_cell('<a href="index.php?module=tools-mybbpublisher&service='.$service.'&action=showlog">'.$lang->sprintf($lang->mybbpublisher_showlog, ucfirst($service)).'</a>'));
					}
					$output .= $table->output($lang->mybbpublisher_logs.'  - <span class="smalltext">'.$lang->mybbpublisher_logs_desc.'</span>','1','general',true);

					$table = new Table;
					$table->construct_row($table->construct_cell('<a href="http://www.communityplugins.com/forum/showthread.php?tid=244" target="_blank">'.$lang->mybbpublisher_help_create_apps.'</a>'));
					$output .= $table->output($lang->mybbpublisher_help,'1','general',true);
				}

				//for each module
				foreach($publisher->services as $service => $version)
				{
					//create new class object for it
					$classname = 'pub_'.$service;
					$$service = new $classname;
	
					$status_image = '<img src="'.$mybb->settings['bburl'].'/images/invalid.gif" alt="'.$lang->mybbpublisher_module_disabled.'" title="'.$lang->mybbpublisher_module_disabled.'">';
					if($$service->settings['enabled'])
					{
						$status_image = '<img src="'.$mybb->settings['bburl'].'/images/valid.gif" alt="'.$lang->mybbpublisher_module_enabled.'" title="'.$lang->mybbpublisher_module_enabled.'">';
					}
									
					//create a tab for the module
				    	$sub_tabs[$service] = array('title' => $$service->lang['service_name'].' '.$status_image,
                                  		'description' => $lang->sprintf($lang->mybbpublisher_tab_desc, $$service->lang['service_name']),
                                  		'link' => 'index.php?module=tools-mybbpublisher&amp;service='.$service);

					$plugins->run_hooks("mybbpublisher_admin_service_start", $$service);

					//and build the ACP options for each
					if($mybb->input['action'] != 'showlog')
					{
						if($mybb->input['action'] != 'settings' && $mybb->input['service'] == $service)
						{
							$table = new Table;
							$table->construct_cell('<a href="index.php?module=tools-mybbpublisher&service='.$service.'&action=settings">'.$lang->mybbpublisher_settings.'</a><br />');
							$table->construct_row();
							$output .= $table->output($lang->mybbpublisher_module_admin,'1','general',true);
											
							if(count($$service->acp_actions))
							{	
								ksort($$service->acp_actions);
								$table = new Table;
								foreach($$service->acp_actions as $key => $action)
								{
									$table->construct_cell('<a href="index.php?module=tools-mybbpublisher&service='.$service.'&action='.key($action).'">'.$action[key($action)].'</a><br />');
									$table->construct_row();
								}
								$status_image = '<img src="'.$mybb->settings['bburl'].'/images/invalid.gif">';
								if($$service->settings['enabled'])
								{
									$status_image = '<img src="'.$mybb->settings['bburl'].'/images/valid.gif">';
								}
								$output .= $table->output($lang->mybbpublisher_module_setup,'1','general',true);
							}
						}
					
						//then handle any incoming requests for the module
						if(method_exists($$service, 'process_incoming'))
						{
							$output .= $$service->process_incoming($mybb->input);

							$plugins->run_hooks("mybbpublisher_admin_service_input", $$service);
						}
					}
		   		}

				if(!count($publisher->services))
				{
					$table = new Table;
					$table->construct_cell($lang->mybbpublisher_no_modules);
					$table->construct_row();
					$output .= $table->output($lang->mybbpublisher_modules,'1','general',true);
				}
			}
		}

		$plugins->run_hooks("mybbpublisher_admin_end", $publisher);

		if($mybb->input['service'] == "" || $mybb->input['action'] == 'showlog')
		{
			$mybb->input['service'] = 'tools';
		}
		$page->output_nav_tabs($sub_tabs, $mybb->input['service']);
		echo $output;

		$mybbpublisher_info = mybbpublisher_info();
		echo '<p /><div class="smalltext" style="border-top-style:solid;border-top-width:1px;">'.$mybbpublisher_info['name'].' version '.$mybbpublisher_info['version'].' &copy; 2006-'.COPY_YEAR.' <a href="'.$mybbpublisher_info['website'].'">'.$mybbpublisher_info['author'].'</a>.</div>';
		$page->output_footer();
	}
}

?>
