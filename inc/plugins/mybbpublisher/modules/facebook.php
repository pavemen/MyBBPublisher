<?php
/**
 * Facebook Module for MyBBPublisher Plugin for MyBB
 * Copyright 2012 CommunityPlugins.com, All Rights Reserved
 *
 * Website: http://www.communityplugins.com
 * License: Creative Commons Attribution-NonCommerical ShareAlike 3.0
				http://creativecommons.org/licenses/by-nc-sa/3.0/legalcode
 *
 */

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

 // Disallow direct access to this file for security reasons DO NOT REMOVE
 if(!defined("IN_MYBB"))
 {
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.<br />");
 }

 global $pub_services;
 $pub_services['facebook'] = '1.2'; //(lowercase, no spaces, no punctuation) must match $service_name below and base filename of this file


 class pub_facebook
 {

	/**
	 * The simple service name (lowercase, no spaces, no punctuation)
	 * @var string
	 */
	public $service_name = 'facebook';

	/**
	 * This URL to the Facebook Graph API (with trailing)
	 * @var int
	 */
	public $api_url = "https://graph.facebook.com/";

	/**
	 * Array of actions for the ACP
	 * @var string
	 */
	public $acp_actions;

	/**
	 * This modules errors
	 * @var array
	 */
	public $errors = array();

	/**
	 * This modules language vars
	 * @var array
	 */
	public $lang;



// ****** Default module functions ******


	/**
	 * Constructor of class.
	 * @var array
	 * @return pub_facebook
	 */
	function __construct($params='')
	{
		global $mybb, $publisher;

		$this->settings = $publisher->settings[$this->service_name];

		//allow overrides
		if(is_array($params))
		{
			if(array_key_exists('enabled', $params))
			{
				$this->settings['enabled'] = $params['enabled'];
			}

			if(array_key_exists('api_url', $params))
			{
				$this->settings['api_url'] = $params['api_url'];
			}

			if(array_key_exists('debug', $params))
			{
				$publisher->debug = $params['debug'];
			}

			if(array_key_exists('token', $params))
			{
				$this->settings['token'] = $params['token'];
			}

			if(array_key_exists('page', $params))
			{
				$this->settings['page'] = $params['page'];
			}

			if(array_key_exists('page_token', $params))
			{
				$this->settings['page_token'] = $params['page_token'];
			}

			if(array_key_exists('appid', $params))
			{
				$this->settings['appid'] = $params['appid'];
			}

			if(array_key_exists('secret', $params))
			{
				$this->settings['secret'] = $params['secret'];
			}

			if(array_key_exists('album_id', $params))
			{
				$this->settings['album_id'] = $params['album_id'];
			}

			if(array_key_exists('type', $params))
			{
				$this->settings['type'] = $params['type'];
			}
		}

		$publisher->load_lang($this->service_name, $this->lang);
		if(defined('IN_ADMINCP'))
		{
			$this->get_acp_actions();
		}
	}

	/**
	 * Get the services this module supports
	 *
	 * @var array list of services to show in the ACP
	 * @return string
	 */
	function get_acp_actions()
	{
		global $lang;

		//$this->acp_actions[0] = array('settings'=>$lang->mybbpublisher_settings);
		if($this->settings['appid'] != "" && $this->settings['secret'] != "")
		{
			$this->acp_actions[1] = array('verify_app_id'=>$this->lang['step1']);
			$this->acp_actions[2] = array('verify_creds'=>$this->lang['step2']);
			$this->acp_actions[3] = array('verify_page'=>$this->lang['step3']);
			$this->acp_actions[4] = array('test_post'=>$this->lang['step4']);

			ksort($this->acp_actions);
		}
	}

	/**
	 * Modifies $mybb->input to handle incoming requests when required
	 * or directs actions to appropriate function in the module
	 * $output is not required to be generated.
	 *
	 * @var array MyBB Input object
	 *
	 */
	function process_incoming(&$input)
	{
		$output = '';
		if($input['service'] == $this->service_name && isset($input['action']))
		{
			if(method_exists($this, $input['action']))
			{
				$output .= $this->$input['action']();
			}
		}
		return $output;
	}

	/**
	 * Handles settings for this module
	 *
	 * @var array MyBB Input object
	 *
	 */
	function settings()
	{
		global $mybb, $db, $cache, $lang, $publisher;

		$this->settings = $publisher->settings[$this->service_name];

		if($mybb->request_method == 'post')
		{
			$this->settings['enabled'] = $db->escape_string($mybb->input['enabled']);
			$this->settings['appid'] = trim($db->escape_string($mybb->input['appid']));
			$this->settings['secret'] = trim($db->escape_string($mybb->input['secret']));
			$this->settings['album_id'] = $db->escape_string($mybb->input['album_id']);
			$this->settings['type'] = $db->escape_string($mybb->input['type']);
			$this->settings['icon'] = $db->escape_string(str_replace("\\", "/", $mybb->input['icon']));

			$publisher->save_settings($this->service_name, $this->settings);

			flash_message($this->lang['setting_success'], 'success');
			admin_redirect("index.php?module=tools-mybbpublisher&service=".$this->service_name);
		}
		else
		{
			$albums = array();
			if($this->settings['page'] != '' && $this->settings['page_token'] != '')
			{
				$fb_url = $this->api_url.'/'.$this->settings['page'].'/albums?access_token='.$this->settings['page_token'];
				$fb_results = json_decode(@file_get_contents($fb_url));
				if(count($fb_results))
				{
					foreach($fb_results->data as $key => $album)
					{
						$albums[$album->id] = $album->name.' ('.$album->privacy.')';
					}
				}
				if(count($albums))
				{
					$album_desc = $this->lang['setting_type_photo_upload_to'];
				}
				else
				{
					$album_desc = $this->lang['setting_type_photo_no_albums'];
				}
			}
			else
			{
				$album_desc = $this->lang['setting_type_photo_no_list'];
			}

			//be sure form has params 4, 5 and 6 and the sixth is TRUE. this makes the form output to a capturable variable
			//then capture $form_container->end() and all the $form->function() lines into a variable
			$form = new Form("index.php?module=tools-mybbpublisher&amp;service=".$this->service_name."&amp;action=settings", "post", "settings", 0, "", true);
			$form_container = new FormContainer($this->lang['service_name']." ".$lang->mybbpublisher_settings);

			$row_options = array();
			$row_options[] = $form->generate_check_box("enabled", 1, $this->lang['setting_enable_desc'], array("checked" => $this->settings['enabled']));
			$form_container->output_row($this->lang['setting_enable'], '', '<div class="group_settings_bit">'.implode('</div><div class="group_settings_bit">', $row_options).'</div>');

			$form_container->output_row($this->lang['setting_app_id'], $this->lang['setting_app_id_desc'], $form->generate_text_box('appid', $this->settings['appid'], array('id' => 'appid')), 'appid');
			$form_container->output_row($this->lang['setting_app_secret'], $this->lang['setting_app_secret_desc'], $form->generate_text_box('secret', $this->settings['secret'], array('id' => 'secret')), 'secret');

			$posttypes['link'] = $this->lang['setting_type_link'];
			$posttypes['photo'] = $this->lang['setting_type_photo'];

			$form_container->output_row($this->lang['setting_type'], $this->lang['setting_type_desc'], $form->generate_select_box('type', $posttypes, $this->settings['type'], array('id' => 'type')), 'type');

			if(count($albums))
			{
				$form_container->output_row($this->lang['setting_type_photo_albums'], $album_desc, $form->generate_select_box('album_id', $albums, $this->settings['album_id'], array('id' => 'album_id')), 'album_id', array(),array('id' => 'row_album'));
			}
			else
			{
				$form_container->output_row($this->lang['setting_type_photo_albums'], $album_desc , '', 'album_id', array(),array('id' => 'row_album'));
			}

			$form_container->output_row($this->lang['setting_icon'], $this->lang['setting_icon_desc'], $form->generate_text_box('icon', $this->settings['icon'], array('id' => 'icon')), 'icon');

			//make sure you pass $returnable as TRUE
			$output .= $form_container->end(true);

			$buttons[] = $form->generate_submit_button($lang->mybbpublisher_save);
			$output .= $form->output_submit_wrapper($buttons);

			$output .= $form->end();

			$output .= '<script type="text/javascript" src="./jscripts/peeker.js"></script>
	<script type="text/javascript">Event.observe(window, "load", function() {var peeker = new Peeker($("type"), $("row_album"), /photo/, false);});
	</script>';
			//return the initial form opening tag and then the rest of the form
			return $form->construct_return.$output;
		}
	}


// ****** Facebook core hook functions ******

	/**
	 * Post a new status update
	 * @var Array array of the thread contents to be published
	 * @return Array array of the ID of the post and the UID of the author from Facebook
	 */
	function do_post($content)
	{
		global $mybb,$db,$lang,$publisher;

		if(!is_array($content))
		{
			if($publisher->debug) mybbpublisher_log($this->service_name, 'Content is not an array');
			$this->errors[] = 'Content is not an array';
			return false;
		}

		if($this->settings['enabled'] && $this->settings['page_token'] != "")
		{
			if($publisher->debug) mybbpublisher_log($this->service_name, $content);

			if($content['message'] == '' && $content['title'] == '')
			{
				if($publisher->debug) mybbpublisher_log($this->service_name, 'Content['.$content['id'].'] does not contain a message');
				$this->errors[$content['id']] = 'Content['.$content['id'].'] does not contain a message';
			}

			if(!$this->errors[$content['id']])
			{
				if($this->settings['type'] == 'link' || $content['imagepath'] == '' || !file_exists($content['imagepath']))
				{
					//set page we are posting to
					$url = $this->api_url.$this->settings['page'].'/feed';

					$attachment =  array('access_token'	=> $this->settings['page_token'],
										'name'			=> $content['title'],
										'link' 			=> ($content['shortlink'] ? $content['shortlink'] : $content['link']),
										'description'	=> $lang->sprintf($this->lang['posted_by_link'], $content['author'], $content['message']),
										);

					if($content['avatar'])
					{
						$attachment['picture'] = $content['avatar'];
					}
				}
				elseif($this->settings['type'] == 'photo')
				{
		    		$url = $this->api_url.'/'.$this->settings['album_id'].'/photos?access_token='.$this->settings['page_token'];

					$attachment =  array('message' => $lang->sprintf($this->lang['posted_by_photo'], $content['author'], $content['title'], ($content['shortlink'] ? $content['shortlink'] : $content['link'])),
									basename($content['imagepath']) => "@".realpath($content['imagepath']),
									);
				}
				else
				{
					$this->errors[$content['id']] = array('action'=>'post', 'result'=>$lang->mybbpublisher_invalid_type, 'input'=>$attachment);
					mybbpublisher_log($this->service_name, $lang->mybbpublisher_invalid_type);
					return false;
				}

				$result = json_decode($publisher->curl($url, $attachment), true);

				if($publisher->debug) mybbpublisher_log($this->service_name, array('action'=>'post', 'result'=>$result, 'input'=>$attachment));

				if($result['id'])
				{
					$this->errors = array();
					return array('id'=>$db->escape_string($result['id']), 'uid'=>$db->escape_string($page_id));
				}
				else
				{
					$this->errors[$content['id']] = array('action'=>'post', 'result'=>$result, 'input'=>$attachment);
				}
			}
		}
		return false;
	}


	/**
	 * Delete an existing status update
	 * @var string the service's ID of the update to be removed
	 * @return boolean
	 */
	function delete_post($id=0)
	{
		$this->errors = false;
		if($id)
		{
			$result = @file_get_contents($this->api_url.$id.'?access_token='.$this->settings['token'].'&method=delete');

			if($publisher->debug) mybbpublisher_log($this->service_name, array('action'=>'delete', 'result'=>$result));

			if($result != "true")
			{
				$this->errors[$id] = array('action'=>'delete', 'result'=>$result);
				return false;
			}
			else
			{
				return true;
			}
		}
		return false;
	}

// ****** Admin functions ******

	/**
	 * Verifies the App ID setting
	 * @return string result from Facebook AppID query
	 */
	function verify_app_id()
	{
		$output = '<h3>'.$this->lang['step1_results'].'</h3>';
		if($this->settings['appid'] != "")
		{
			$output .= $this->lang['appid_check'].'<br /><br />';
			$fb_results = json_decode(@file_get_contents($this->api_url.$this->settings['appid']));

			$output .= 'Name: '.$fb_results->name.'<br />';
			$output .= 'Description: '.$fb_results->description.'<br />';
			$output .= 'Application Link: '.$fb_results->link.'<br />';
		}
		else
		{
			$output .= $this->lang['missing_appid'];
		}
		return $output;
	}


	/**
	 * Verifies the credentials for Facebook and obtains long term access tokens
	 * @return string result of Facebook verification and token exchange
	 */
	function verify_creds()
	{
		global $mybb, $config, $db, $publisher;
		if($this->settings['appid'] != "" && $this->settings['secret'] != "")
		{
			//put this here since both calls to the redirect_uri must be equal or code verification fails
			$redirect_uri = $mybb->settings['bburl'].'/'.$config['admin_dir'].'/index.php?module=tools-mybbpublisher&service='.$this->service_name.'&action=verify_creds&do=from_facebook';

			//this is step two, return from permission request and hopefully have CODE
			if($mybb->input['do'] == 'from_facebook')
			{
				$output = '<h3>'.$this->lang['step2_results'].'</h3>';
				if($mybb->input['code']) //if code exists, user gave permission
				{
					$params =  array('client_id'	=> $this->settings['appid'],
									'redirect_uri'	=> $redirect_uri,
									'client_secret' => $this->settings['secret'],
									'code'			=> $mybb->input['code'],
									);

					$fb_results = $publisher->curl($this->api_url.'oauth/access_token', $params);

					//since we have code, get default access token, possibly short-term
					$fb_results = str_ireplace('access_token=', '', $fb_results);
					$fb_results2 = explode("&expires=", $fb_results);
					//exchange for long term token (possible to get same token back though)
					$params =  array('client_id'	=> $this->settings['appid'],
									'client_secret' => $this->settings['secret'],
									'grant_type'	=> 'fb_exchange_token',
									'fb_exchange_token' => $fb_results2[0]
									);

					$fb_results = $publisher->curl($this->api_url.'oauth/access_token', $params);

					$fb_results = str_ireplace('access_token=', '', $fb_results);
					$fb_results2 = explode("&expires=", $fb_results);

					$output .= $this->lang['token_success'].'<br /><br />';
					$output .= $this->lang['token'].": ".$fb_results2[0].'<br />'.$this->lang['expires'].": ".$fb_results2[1];
					$output .= '<br /><br />'.$this->lang['token_saved'].'<br /><br />';

					$this->settings = $publisher->settings[$this->service_name];
					$this->settings['token'] = $db->escape_string($fb_results2[0]);
					if($fb_results2[1])
					{
						$this->settings['expires'] = $db->escape_string($fb_results2[1]);
					}
					$publisher->save_settings($this->service_name, $this->settings);

				}
				elseif($mybb->input['error_reason']) //else user denied
				{
					$output .= 'Error reason: '.$mybb->input['error_reason'].'<br />';
					$output .= 'Error description: '.$mybb->input['error_description'].'<br />';
				}
				else
				{
					$output .= $this->lang['error'];
				}
			}

			//this is step one, request permissions and get CODE
			else
			{
				$output = '<h3>'.$this->lang['step2'].'</h3>';

				$fb_url = 'https://www.facebook.com/dialog/oauth?client_id='.$this->settings['appid'].'&redirect_uri='.rawurlencode($redirect_uri).'&scope=manage_pages,publish_stream,read_stream,user_photos,user_groups';

				//redirect to facebook
				$output .= '<script type="text/javascript">
				<!--
				window.location = "'.$fb_url.'"
				//-->
				</script>';
			}
		}
		else
		{
			$output .= $this->lang['missing_creds'];
		}
		return $output;
	}

	/**
	 * Displays list of Facebook content user can manage and stores the where to post and account to post as
	 * @return string result of selection
	 */
	function verify_page()
	{
		global $mybb, $config, $db, $publisher;

		if($this->settings['appid'] != "" && $this->settings['secret'] != "")
		{
			//this is step two, replace the access token
			if((int)$mybb->input['step'] == 2)
			{
				$output = '<h3>'.$this->lang['step3_results'].'</h3>';

				if(isset($mybb->input['id'])) //posting to page/group/app
				{
					if(isset($mybb->input['access_token'])) //as page/group/app
					{
						$access_token = $mybb->input['access_token'];
					}
					else //as you
					{
						$access_token = $this->settings['token'];
					}

					$post_to_id = $mybb->input['id'];
				}
				//posting to own wall as yourself
				else
				{
					$access_token = $this->settings['token'];
					$post_to_id = 'me';
				}

				//update where to post and token used to post
				$this->settings['page'] = $db->escape_string($post_to_id);
				$this->settings['page_token'] = $db->escape_string($access_token);

				$publisher->save_settings($this->service_name, $this->settings);

				$output .= $this->lang['manage_success'];
			}

			//this is step one, get and list accounts/groups user can manage
			else
			{
				$output = '<h3>'.$this->lang['step3'].'</h3><br />';

				$fb_url = $this->api_url.'me?access_token='.$this->settings['token'];

				//since we have code, get access token
				$fb_me = json_decode(@file_get_contents($fb_url));

				$next_url = $mybb->settings['bburl'].'/'.$config['admin_dir'].'/index.php?module=tools-mybbpublisher&service='.$this->service_name.'&action=verify_page&step=2';

				//get accounts
				$fb_url = $this->api_url.'/me/accounts?access_token='.$this->settings['token'];
				$fb_results = json_decode(@file_get_contents($fb_url));

				if(is_array($fb_results->data))
				{
					$output .= '<strong>'.$this->lang['pages_apps'].':</strong><br /><br />';
					
					//list each page/group/app and give option as that page or as self
					foreach($fb_results->data as $account)
					{
						$output .= $this->lang['name'].': '.$account->name.' -- '.$this->lang['id'].': ' .$account->id.' -- '.$this->lang['categorized'].': ' .$account->category;
						$output .= '<br />&nbsp;&nbsp;<a href="'.$next_url.'&id='.$account->id.'&access_token='.$account->access_token.'">'.$this->lang['post_to'].$account->name.$this->lang['as'].$account->name.'</a>';
						$output .= '<br />&nbsp;&nbsp;<a href="'.$next_url.'&id='.$account->id.'">'.$this->lang['post_to'].$account->name.$this->lang['as'].$fb_me->name.'</a>';
						$output .= '<br /><br />';
					}
				}
				else
				{
					$output .= '<br />'.$this->lang['manage_error'];
					$output .= '<br /><br />';
				}

				//get groups
				$fb_url = $this->api_url.'/me/groups?access_token='.$this->settings['token'];
				$fb_results = json_decode(@file_get_contents($fb_url));

				if(is_array($fb_results->data))
				{
					$output .= '<strong>'.$this->lang['groups'].':</strong><br /><br />';

					//list each page/group/app and give option as that page or as self
					foreach($fb_results->data as $group)
					{
						$output .= $this->lang['name'].': '.$group->name.' -- '.$this->lang['id'].': ' .$group->id;
						$output .= '<br />&nbsp;&nbsp;<a href="'.$next_url.'&id='.$group->id.'">'.$this->lang['post_to'].$group->name.$this->lang['as'].$fb_me->name.'</a>';
						$output .= '<br /><br />';
					}
				}
				else
				{
					$output .= '<br />'.$this->lang['manage_error'];
					$output .= '<br /><br />';
				}

				//always option for own wall as own self
				$output .= '<strong>'.$this->lang['wall'].':</strong><br /><br />';
				$output .= '&nbsp;&nbsp;'.$this->lang['manage_own'].' <a href="'.$next_url.'">'.$fb_me->name.'</a><br /><br />';
			}
		}
		else
		{
			$output .= $this->lang['missing_creds'];
		}

		return $output;
	}


	/**
	 * Creates and deletes test post
	 * @return string result of selection
	 */
	function test_post()
	{
		global $mybb, $config, $db, $publisher;

		if($mybb->input['do'] == "delete")
		{
			$output = '<h3>'.$this->lang['step4_results'].'</h3>';

			$result = $this->delete_post($mybb->input['fb_id']);

			$output .= $this->lang['test_return'].'<br />';
			if(!$result)
			{
				$output .= $this->lang['test_failed'].$this->errors[$mybb->input['fb_id']]['result'];
			}
			else
			{
				$output .= $this->lang['test_success'].$result;
			}
		}
		else
		{
			$output = '<h3>'.$this->lang['step4'].'</h3>';

			if($this->settings['type'] == 'link')
			{
				$content =  array('title'	=> $mybb->settings['bbname'],
								'link' 	=> $mybb->settings['bburl'],
								'message'	=> $this->lang['test_message'],
								'avatar' => $publisher->default_image
								);
			}
			elseif($this->settings['type'] == 'photo')
			{
		   		$fb_url = $this->api_url.'/'.$this->settings['album_id'].'/photos?access_token='.$this->settings['page_token'];
				$content =  array('title' => "Test image",
								'link' => $mybb->settings['bburl'],
								'imagepath' => str_replace('//', '/', str_replace($mybb->settings['bburl'], MYBB_ROOT, $publisher->default_image)),
								);
			}

			$content['id'] = 1; //test TID
			$testout = $this->do_post($content);

			$output .= $this->lang['test_return'].': '.$testout['id'].'<br />';

			if($testout['id'] == "" || !isset($testout['id']))
			{
				$output .= $this->lang['test_failed2'].'<br /><br />';
				$output .= $this->errors[0];
			}
			else
			{
				$output .= '<br />'.$this->lang['test_made'].'<a href="'.$mybb->settings['bburl'].'/'.$config['admin_dir'].'/index.php?module=tools-mybbpublisher&service='.$this->service_name.'&action=test_post&do=delete&fb_id='.$testout['id'].'">'.$this->lang['test_delete'].'</a>';
			}
		}
		return $output;
	}
 }
?>
