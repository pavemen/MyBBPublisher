<?php
/**
 * MyBBPublisher Plugin for MyBB
 * Copyright 2011 CommunityPlugins.com, All Rights Reserved
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

/****************************************************************
Plugin bits and ACP modifications - only load if in ACP
****************************************************************/
if(defined("IN_ADMINCP"))
{
	require_once MYBB_ROOT."inc/plugins/mybbpublisher/plugin.php";
}

/****************************************************************
Core plugin functions
****************************************************************/

//add icon to forum description
$plugins->add_hook("build_forumbits_forum", "mybbpublisher_addicon");

//only apply the hooks when user in a group that can publish
$allowed = $intersected = array();
if($mybb->settings['mybbpublisher_allowed_groups'])
{
	$allowed = explode(',', $mybb->settings['mybbpublisher_allowed_groups']);
	$usergroups = explode(',', $mybb->user['usergroup'].','.$mybb->user['additionalgroups']);
	$intersected = array_intersect($allowed, $usergroups);
}

if(count($intersected) || $mybb->settings['mybbpublisher_allowed_groups'] == '0' || $mybb->settings['mybbpublisher_allowed_groups'] == "")
{
	//make option
	$plugins->add_hook("newthread_start", "mybbpublisher_make_option");

	//handle new threads generated via post datahandler
	$plugins->add_hook("datahandler_post_insert_thread_post", "mybbpublisher_newthread",50);

	//handle deleted threads
	$plugins->add_hook("class_moderation_delete_thread_start", "mybbpublisher_delthread");

	//handle editted threads
	$plugins->add_hook("datahandler_post_update_thread", "mybbpublisher_editthread",50);

	//handle moved threads (move single, move multiple, w/ and w/o redirects
	$plugins->add_hook("class_moderation_move_thread_redirect", "mybbpublisher_onmove");
	$plugins->add_hook("class_moderation_move_simple", "mybbpublisher_onmove");
	$plugins->add_hook("class_moderation_move_threads", "mybbpublisher_onmove");

	//handle thread moderation (approval only)
	$plugins->add_hook("class_moderation_approve_threads", "mybbpublisher_approvethreads");

	//handle announcements from ACP
	$plugins->add_hook("admin_forum_announcements_add_commit", "mybbpublisher_newannounce");
	$plugins->add_hook("admin_forum_announcements_edit_commit", "mybbpublisher_newannounce");
	$plugins->add_hook("admin_forum_announcements_delete", "mybbpublisher_delannounce"); //can use same as from ModCP

	//handle announcements from ModCP
	$plugins->add_hook("modcp_do_new_announcement_end", "mybbpublisher_onmodannounce");
	$plugins->add_hook("modcp_do_edit_announcement_end", "mybbpublisher_onmodannounce");
	$plugins->add_hook("modcp_do_delete_announcement", "mybbpublisher_delannounce"); //can use same as from ACP
}


/**
 * Loads the MyBBPublisher core classes and modules on the required pages/actions and gets the list of FIDs that can be published
 *
 */
function mybbpublisher_load() 
{
    global $mybb, $lang, $cache, $usergroups, $publisher, $plugins;

	if($mybb->settings['mybbpublisher_enabled']==1)
	{
		require_once(MYBB_ROOT.'/inc/plugins/mybbpublisher/class_mybbpublisher.php');
		$publisher = new mybbpublisher;
		
		$forums2send = explode(",", $mybb->settings['mybbpublisher_forums']);
		$groupperms = usergroup_permissions('1,2');
		$forumcache = $cache->read('forums');
		
		$forums_can_publish = array();
		foreach($forumcache as $fid => $forum)
		{
			if($forum['type'] == 'f' && $forum['open'] == 1 && $forum['active'] == 1 && $forum['password'] == '')
			{
				$forumpermissions = fetch_forum_permissions($fid, '1,2', $groupperms);
				if($forumpermissions['canview'] == 1 && $forumpermissions['canviewthreads'] == 1)
				{
					if($mybb->settings['mybbpublisher_forums'] != "0" && $mybb->settings['mybbpublisher_forums'] != "")
					{
						if((in_array($fid, $forums2send) && $mybb->settings['mybbpublisher_how'] == 'include') || (!in_array($fid, $forums2send) && $mybb->settings['mybbpublisher_how'] == 'exclude'))
						{
							$forums_can_publish[] = $fid;
						}
					}
					else
					{
						if($mybb->settings['mybbpublisher_how'] == 'include')
						{
							$forums_can_publish[] = $fid;
						}
					}
				}
			}
		}
		
		$publisher->forums_can_publish = $forums_can_publish;
	}
}

/**
 * Adds icon to specified forums
 * @var array forum details
 */
function mybbpublisher_addicon(&$forum) 
{
    global $publisher;

	if(!is_object($publisher))
	{
		mybbpublisher_load();
	}
	
	if(is_object($publisher))
	{
		if(in_array($forum['fid'], $publisher->forums_can_publish))
		{
			$forum['description'] = $publisher->forum_icons.$forum['description'];
		}
	}
	
	return $forum;
}

/**
 * Generate posting option for publishing
 * 
 */
function mybbpublisher_make_option() 
{
    global $mybb, $mybbpublisher_option, $lang;

	$mybbpublisher_option = '';
	if($mybb->settings['mybbpublisher_method'] == 'ondemand')
	{
		$lang->load('mybbpublisher');
		if($mybb->input['postoptions']['publish'] == 1)
		{
			$checked = " checked=\"checked\"";
		}
		$mybbpublisher_option = '<br /><label><input type="checkbox" class="checkbox" name="postoptions[publish]" value="1" tabindex="8" '.$checked.' /> '.$lang->mybbpublisher_option.'</label>';
	}
}

/**
 * Gets the username and avatar of the post author, or the default image
 * @var int User ID
 * @return array username and avatar path or false if nothing
 */
function mybbpublisher_get_user($uid=0)
{
	global $mybb, $db;
	
	if($uid)
	{
		$avatar = false;
		$query = $db->simple_select('users', 'username, avatar', "uid={$uid}");
		$result = $db->fetch_array($query);
		$avatar = $result['avatar'];
		if(substr($avatar, 0, 2) == './')
		{
			$avatar = str_ireplace('./', $mybb->settings['bburl'].'/', $avatar);
		}
		if(substr($avatar, 0, 4) != 'http' && $avatar != '')
		{
			$avatar = $mybb->settings['bburl'].'/'.$avatar;
		}
		if($avatar == '' && $mybb->settings['mybbpublisher_default_image'] != '')
		{
			$avatar = $mybb->settings['mybbpublisher_default_image'];
		}
		return array('username' => $result['username'], 'avatar' => $avatar);
	}
	return false;
}

/**
 * Gets the shortened URL, if any
 * @var string URL
 * @return string shortened URl or empty string if no result
 */
function mybbpublisher_get_url($url='')
{
	global $mybb;

	if($url != '' && $mybb->settings['mybbpublisher_shorten_service'] != 'none' && $mybb->settings['mybbpublisher_shorten_user'] != '' && $mybb->settings['mybbpublisher_shorten_key'] != '')
	{
		if($mybb->settings['mybbpublisher_shorten_service'] == 'bit')
		{
			require_once MYBB_ROOT.'/inc/plugins/mybbpublisher/lib/bitly.php';
			$bitly = new bitly($mybb->settings['mybbpublisher_shorten_user'], $mybb->settings['mybbpublisher_shorten_key']);
			$shorturl = $bitly->shorten($url);
		}
		
		if($mybb->settings['mybbpublisher_shorten_service'] == 'adf')
		{
			require_once MYBB_ROOT.'/inc/plugins/mybbpublisher/lib/adfly.php';
			$shorturl = get_adf_ly_url($url, $mybb->settings['mybbpublisher_shorten_user'], $mybb->settings['mybbpublisher_shorten_key']);
		}
		
		if($url == $shorturl)
		{
			$shorturl = '';
		}
	}

	return $shorturl;
}


/**
 * Process new threads for publishing (from hook)
 * @var array Thread contents
 */
function mybbpublisher_newthread(&$threadbyref) 
{
	global $mybb;

	if(($mybb->input['postoptions']['publish'] == 1 && $mybb->settings['mybbpublisher_method'] == 'ondemand') || $mybb->settings['mybbpublisher_method'] == 'always')
	{
		mybbpublisher_do_newthread($threadbyref->post_insert_data);
	}
}

/**
 * Process new threads for publishing (do actual publish)
 * @var array Thread contents
 */
function mybbpublisher_do_newthread($mythread) 
{
	global $mybb, $db, $publisher;
	
	if($mythread['visible']==1 && $mybb->settings['mybbpublisher_onnewthread']==1)
	{
		if(!is_object($publisher))
		{
			mybbpublisher_load();
		}
		
		if(is_object($publisher))
		{
			//if posting from a draft, fid and tid not in post_insert_data, add it
			if($mythread['tid']=="" || $mythread['fid']=="")
			{
				$thread = get_thread( (int)$mybb->input['tid']);
				$mythread['tid'] = (int)$thread['tid'];
				$mythread['fid'] = (int)$thread['fid'];
			}
		
			if(in_array($mythread['fid'], $publisher->forums_can_publish))
			{
				$content = array();
		
				$user = mybbpublisher_get_user($mythread['uid']);

				//load basics	
				$content = array();
				$content['title'] = strip_tags($mythread['subject']);
				$content['message'] = substr(strip_tags($mythread['message']), 0, $mybb->settings['mybbpublisher_max_chars']);
				$content['link'] = $mybb->settings['bburl'] ."/" . get_thread_link($mythread['tid']);
				$content['shortlink'] = mybbpublisher_get_url($content['link']);
				$content['author'] = $user['username'];
				$content['avatar'] = $user['avatar'];
				$content['authorlink'] = $mybb->settings['bburl'] ."/" . get_profile_link($mythread['uid']);
	
				//check if we have an image attachment and load content if we do
				if($mythread['posthash'])
				{
					$mythread['posthash'] = $db->escape_string($mythread['posthash']);
					
					//support for xthreads attachments
					if(defined('XTHREADS_VERSION'))
					{
						$query = $db->simple_select("xtattachments", "*", "posthash='{$mythread['posthash']}' AND uploadmime LIKE 'image%'", array("order_by" => "aid", "limit"=>1));
						while($row = $db->fetch_array($query))
						{
							$content['imageurl'] = $mybb->settings['bburl'].'/'.str_replace('./', '', $mybb->settings['uploadspath']).'/xthreads_ul/'.$row['indir'].'/file_'.$row['aid'].'_'.$row['attachname'];
							$content['imagepath'] = MYBB_ROOT.'/'.str_replace('./', '', $mybb->settings['uploadspath']).'/xthreads_ul/'.$row['indir'].'/file_'.$row['aid'].'_'.$row['attachname'];
						}
					}
					
					if($content['imageurl'] != '')
					{
						$query = $db->simple_select("attachments", "*", "posthash='{$mythread['posthash']}' AND filetype LIKE 'image%'", array("order_by" => "aid", "limit"=>1));
						while($row = $db->fetch_array($query))
						{
							$content['imageurl'] = $mybb->settings['bburl'].'/'.str_replace('./', '', $mybb->settings['uploadspath']).'/'.$row['attachname'];
							$content['imagepath'] = MYBB_ROOT.'/'.str_replace('./', '', $mybb->settings['uploadspath']).'/'.$row['attachname'];
						}
					}
				}

				$result = array();
			
				//run through enabled modules and post content
				foreach($publisher->services as $service => $version)
				{
					//if the service is enabled
					if($publisher->settings[$service]['enabled'])
					{
						//create new class object for it
						$classname = 'pub_'.$service;
						if(!is_object($$service))
						{
							global $$service;
							$$service = new $classname;
						}

						//and post the content
						$result[$service] = $$service->do_post($content);
					}
				}

				//store ids so we can edit/delete updates later
				if(count($result))
				{
					$db->update_query('threads', array('publish_ids'=>serialize($result)), 'tid='.$mythread['tid']);
				}
			}
		}
	}
}

/**
 * Process thread deletes (from hook)
 */
function mybbpublisher_delthread($tid) 
{
	global $mybb, $db, $publisher;

	$thread = get_thread((int)$tid);
	mybbpublisher_do_delete($thread);
}

/**
 * Process thread/announcement deletes (do actual delete)
 */
function mybbpublisher_do_delete($mythread) 
{
	global $mybb, $db, $publisher;

	if($mythread['publish_ids'] != '' && $mythread['publish_ids'] != 'N;' )
	{
		if(!is_object($publisher))
		{
			mybbpublisher_load();
		}
	
		if(is_object($publisher))
		{
			//do status updates
			$statuses = unserialize($mythread['publish_ids']);
			
			//run through enabled modules and post content
			foreach($publisher->services as $service => $version)
			{
				//if the service is currently enabled and we have an ID for it
				if($publisher->settings[$service]['enabled'] && array_key_exists($service, $statuses))
				{
					//create new class object for it
					$classname = 'pub_'.$service;
					if(!is_object($$service))
					{
						global $$service;
						$$service = new $classname;
					}
					
					//and delete the content
					$$service->delete_post($statuses[$service]['id']);
				}
			}
		}
	}
}

/**
 * Process thread edits
 * @var array Thread contents
 */
function mybbpublisher_editthread(&$threadbyref) 
{
	global $mybb, $db, $publisher;

	//are we publishing new threads, if not no point in dealing with edits
	if($mybb->settings['mybbpublisher_onnewthread'] == 1)
	{
		//since hook is by ref, put needed data into separate var to avoid issues later
		$mythread = $threadbyref->data;
	
		$thread = get_thread((int)$mythread['tid']);
	
		//visible not part of data
		$mythread['visible'] = $thread['visible'];
	
		//if using quick edit, several items are not supplied
		if($mythread['subject'] == "")
		{
			$mythread['subject'] = $thread['subject'];
			$mythread['uid'] = $thread['uid'];
			$mythread['username'] = $thread['username'];
		}
		
		//make sure the thread was previously published before continuing
		$mythread['publish_ids'] = $thread['publish_ids'];
		if($mythread['publish_ids'] != '' && $mythread['publish_ids'] != 'N;' )
		{
			//remove any old updates
			mybbpublisher_do_delete($mythread);
			$mythread['publish_ids'] = '';
	
			//get the posthash of the first post in the thread so we can grab attachments
			$options = array(
				"order_by" => "dateline",
				"order_dir" => "asc",
				"limit_start" => 0,
				"limit" => 1
			);
			$query = $db->simple_select("posts", "posthash", "tid='".$mythread['tid']."'", $options);
			$first_post_hash = $db->fetch_array($query);
			$mythread['posthash'] = $first_post_hash['posthash'];
	
			//finally publish updates for new thread content
			mybbpublisher_do_newthread($mythread);
		}
	}	
}


/**
 * Process thread approvals
 * @var array array of thread IDs to approve
 */
function mybbpublisher_approvethreads($tids) 
{
    global $mybb, $db;
    
	if($mybb->settings['mybbpublisher_onnewthread']==1 && count($tids) > 0)
	{
		foreach($tids as $tid)
		{
			//get thread info for current TID			
			$mythread = get_thread($tid);
			
			//has it been published before? if so, we need to remove the old statuses
			if($mythread['publish_ids'] != '' && $mythread['publish_ids'] != 'N;' )
			{
				mybbpublisher_do_delete($mythread);
				$mythread['publish_ids'] = '';
			}
				
			//try to publish it
			mybbpublisher_do_newthread($mythread);
		}			
	}
}

/**
 * Process thread moves
 * @var array array of details of move
 */
function mybbpublisher_onmove($arguments)
{
	global $mybb, $db;

	if($mybb->settings['mybbpublisher_onmove']==1)
	{
		//$arguments['new_fid'] only exists if single move from showthread inline moderation
		//$arguments['moveto'] only exists if multiple move from forumdispaly inline moderation
		//since moveto is still a single numeric fid value, go ahead and assign to new_fid so code can be reused
		//same with single move tid is single value, tids is array from multimove. create array to loop so code is simpler
		
		//handle multiple move from forumdisplay
		if(array_key_exists('moveto', $arguments))
		{
			$new_fid = $arguments['moveto'];
		}
		$tids = $arguments['tids'];
		
		//handle single move from showthread
		if(array_key_exists('new_fid', $arguments))
		{
			$new_fid = $arguments['new_fid'];
		}
		
		if(array_key_exists('tid', $arguments))
		{
			$tids[] = $arguments['tid'];
		}
			
		//loop through list of TIDs
		foreach($tids as $tid)
		{
			//make status text  
			$mythread = get_thread($tid);
			
			//overwrite with new forum ID
			$mythread['fid'] = $new_fid;

			//get the message and posthash of the first post in the thread
			$options = array(
				"order_by" => "dateline",
				"order_dir" => "asc",
				"limit_start" => 0,
				"limit" => 1
			);
			$query = $db->simple_select("posts", "message, posthash", "tid='".$mythread['tid']."'", $options);
			$first_post = $db->fetch_array($query);
			$mythread['posthash'] = $first_post['posthash'];
			$mythread['message'] = $first_post['message'];	
			
			//has it been published before? if so, we need to remove the old statuses
			if($mythread['publish_ids'] != '' && $mythread['publish_ids'] != 'N;' )
			{
				mybbpublisher_do_delete($mythread);
				$mythread['publish_ids'] = '';
			}
				
			//try to publish it
			mybbpublisher_do_newthread($mythread);
		}
	}
}

/**
 * Process new threads for publishing (from hook)
 * 
 */
function mybbpublisher_newannounce() 
{
	global $mybb, $db, $aid;

	if($mybb->input['aid'] && $mybb->input['action'] == 'edit')
	{
		$aid =  $mybb->input['aid'];
	}
		
	$aid = (int)$aid;
	
	if($aid)
	{
		$query = $db->simple_select('announcements', '*', 'aid='.$aid);
		while($myannounce = $db->fetch_array($query))
		{
			$user = get_user($myannounce['uid']);
			$myannounce['username'] = $user['username'];

			//has it been published before? if so, we need to remove the old statuses
			if($myannounce['publish_ids'] != '' && $myannounce['publish_ids'] != 'N;' )
			{
				mybbpublisher_do_delete($myannounce);
				$myannounce['publish_ids'] = '';
			}

			mybbpublisher_do_newannounce($myannounce);
		}
	}
}


/**
 * Process new announcements
 * @var array Announcement contents
 */
function mybbpublisher_do_newannounce($myannounce)
{
	global $db, $mybb, $publisher;

	if($mybb->settings['mybbpublisher_onannounce']==1)
	{
		if(!is_object($publisher))
		{
			mybbpublisher_load();
		}
		
		if(is_object($publisher))
		{
			if(in_array($myannounce['fid'], $publisher->forums_can_publish) || $myannounce['fid'] == -1)
			{
				$content = array();
	
				$user = mybbpublisher_get_user($myannounce['uid']);
				
				//load basics	
				$content['title'] = strip_tags($myannounce['subject']);
				$content['message'] = substr(strip_tags($myannounce['message']), 0, $mybb->settings['mybbpublisher_max_chars']);
				$content['link'] = $mybb->settings['bburl'] ."/" . get_announcement_link($myannounce['aid']);
				$content['shortlink'] = mybbpublisher_get_url($content['link']);
				$content['author'] = $user['username'];
				$content['avatar'] = $user['avatar'];
				$content['authorlink'] = $mybb->settings['bburl'] ."/" . get_profile_link($myannounce['uid']);
			
				$result = array();
		
				//run through enabled modules and post content
				foreach($publisher->services as $service => $version)
				{
					//if the service is enabled
					if($publisher->settings[$service]['enabled'])
					{
						//create new class object for it
						$classname = 'pub_'.$service;
						if(!is_object($$service))
						{
							global $$service;
							$$service = new $classname;
						}

						//and post the content
						$result[$service] = $$service->do_post($content);
					}
				}

				//store ids so we can edit/delete updates later
				if(count($result))
				{
					$db->update_query('announcements', array('publish_ids'=>serialize($result)), 'aid='.$myannounce['aid']);
				}				
			}
		}
	}
}

/**
 * Process announcement deletes (from hook)
 */
function mybbpublisher_delannounce() 
{
	global $mybb, $db, $publisher;

	$aid = (int)$mybb->input['aid'];
	
	//get existing announcement info since hook called before actual announcement delete 
	$query = $db->simple_select("announcements", "*", "aid=".$aid);
	$result = $db->fetch_array($query);

	mybbpublisher_do_delete($result);
}

/**
 * Process announcement (from ModCP)
 */
function mybbpublisher_onmodannounce()
{
	global $mybb, $db, $aid, $insert_announcement, $update_announcement;
	
	//populated if editting
	if(isset($update_announcement))
	{
		$myannounce = $update_announcement;

		$query = $db->simple_select('announcements', '*', 'aid='.(int)$aid);
		while($myannounce = $db->fetch_array($query))
		{
			//has it been published before? if so, we need to remove the old statuses
			if($myannounce['publish_ids'] != '' && $myannounce['publish_ids'] != 'N;' )
			{
				mybbpublisher_do_delete($myannounce);
				$myannounce['publish_ids'] = '';
			}
		}
	}
	//populated if adding
	if(isset($insert_announcement))
	{
		$myannounce = $insert_announcement;
	}

	$myannounce['aid'] = (int)$aid;

	mybbpublisher_do_newannounce($myannounce);
}



//die('<pre>'.print_r($content, true).'</pre>');
/**
 * $content[] array contains these default keys
 *	title		- the status title (not supported by all services)
 *	message		- the status message/caption
 *	link		- complete URL to thread/announcement
 *	shortlink	- shortened URL to thread/announcement
 *	imageurl	- image URL (depends on service being called if this is needed)
 *	imagepath	- image filepath (depends on service being called if this is needed)
 *	author		- username of poster
 *	authorlink	- URL to poster profile
 *	avatar		- avatar of poster (or default image if no avatar)
 *	
 *	
 */ 		




/**
 * Log errors/messages function
 * @var string name of the service being logged 
 * @var string content of the log entry
 * @var boolean overwrite existing cache contents
 */
function mybbpublisher_log($service, $message, $overwrite=false)
{
	global $cache;
	
	$cachename = 'mybbpublisher_errors_'.$service;
	
	$contents = '';
	if(!$overwrite)
	{
		$contents = $cache->read($cachename);
		$contents .= "\n\n===========================================\n\n";
	}
	
	$contents .= date('D, d M Y H:i:s',TIME_NOW).'\n\n';
	
	//get string rep of the mesage, wahtever the contents	
	ob_start();
	var_dump($message);
	$message = ob_get_clean();
	
	//add to existing if we keep it
	$contents .= '<blockquote>'.$message.'</blockquote>';
	
	//update requested the cache
	$cache->update($cachename, $contents);
}


?>
