<?php
/**
 * Twitter Module for MyBBPublisher Plugin for MyBB
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
 $pub_services['twitter'] = '1.5'; //(lowercase, no spaces, no punctuation) must match $service_name below and base filename of this file

 class pub_twitter
 {
 	/**
	 * The simple service name (lowercase, no spaces, no punctuation)
	 * @var string
	 */
	public $service_name = 'twitter';

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

			if(array_key_exists('debug', $params))
			{
				$publisher->debug = $params['debug'];
			}

			if(array_key_exists('ckey', $params))
			{
				$this->settings['ckey'] = $params['ckey'];
			}

			if(array_key_exists('csecret', $params))
			{
				$this->settings['csecret'] = $params['csecret'];
			}

			if(array_key_exists('token', $params))
			{
				$this->settings['token'] = $params['token'];
			}

			if(array_key_exists('tsecret', $params))
			{
				$this->settings['tsecret'] = $params['tsecret'];
			}

			if(array_key_exists('type', $params))
			{
				$this->settings['type'] = $params['type'];
			}

			if(array_key_exists('forums', $params))
			{
				$this->settings['forums'] = $params['forums'];
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

		if($this->settings['ckey'] != "" && $this->settings['csecret'] != "" && $this->settings['token'] != "" && $this->settings['tsecret'] != "")
		{
			$this->acp_actions[1] = array('verify_creds'=>$this->lang['step1']);
			$this->acp_actions[2] = array('rate_limit'=>$this->lang['step2']);
			$this->acp_actions[3] = array('recent'=>$this->lang['step3']);
			$this->acp_actions[4] = array('test_post'=>$this->lang['step4']);
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
			array_walk($mybb->input['forums'], 'intval');
			$this->settings['enabled'] = $db->escape_string($mybb->input['enabled']);
			$this->settings['ckey'] = $db->escape_string($mybb->input['ckey']);
			$this->settings['csecret'] = $db->escape_string($mybb->input['csecret']);
			$this->settings['token'] = $db->escape_string($mybb->input['token']);
			$this->settings['tsecret'] = $db->escape_string($mybb->input['tsecret']);
			$this->settings['type'] = $db->escape_string($mybb->input['type']);
			$this->settings['icon'] = $db->escape_string(str_replace("\\", "/", $mybb->input['icon']));
			$this->settings['tags'] = $db->escape_string($mybb->input['tags']);
			$this->settings['forums'] = $mybb->input['forums'];

			$publisher->save_settings($this->service_name, $this->settings);

			flash_message($this->lang['setting_success'], 'success');
			admin_redirect("index.php?module=tools-mybbpublisher&service=".$this->service_name);
		}
		else
		{
			//be sure form has params 4, 5 and 6 and the sixth is TRUE. this makes the form output to a capturable variable
			//then capture $form_container->end() and all the $form->function() lines into a variable
			$form = new Form("index.php?module=tools-mybbpublisher&amp;service=".$this->service_name."&amp;action=settings", "post", "settings", 0, "", true);

			$form_container = new FormContainer($this->lang['service_name']." ".$lang->mybbpublisher_settings);

			$row_options = array();
			$row_options[] = $form->generate_check_box("enabled", 1, $this->lang['setting_enable_desc'], array("checked" => $this->settings['enabled']));
			$form_container->output_row($this->lang['setting_enable'], '', '<div class="group_settings_bit">'.implode('</div><div class="group_settings_bit">', $row_options).'</div>');

			$posttypes['link'] = $this->lang['setting_type_link'];
			$posttypes['photo'] = $this->lang['setting_type_photo'];

			$form_container->output_row($this->lang['setting_type'], $this->lang['setting_type_desc'], $form->generate_select_box('type', $posttypes, $this->settings['type'], array('id' => 'type')), 'type');

			$form_container->output_row($this->lang['setting_tags'], $this->lang['setting_tags_desc'], $form->generate_text_box('tags', $this->settings['tags'], array('id' => 'tags')), 'tags');

			$form_container->output_row($this->lang['setting_ckey'], $this->lang['setting_ckey_desc'], $form->generate_text_box('ckey', $this->settings['ckey'], array('id' => 'ckey')), 'ckey');
			$form_container->output_row($this->lang['setting_csecret'], $this->lang['setting_csecret_desc'], $form->generate_text_box('csecret', $this->settings['csecret'], array('id' => 'csecret')), 'csecret');
			$form_container->output_row($this->lang['setting_token'], $this->lang['setting_token_desc'], $form->generate_text_box('token', $this->settings['token'], array('id' => 'token')), 'token');
			$form_container->output_row($this->lang['setting_tsecret'], $this->lang['setting_tsecret_desc'], $form->generate_text_box('tsecret', $this->settings['tsecret'], array('id' => 'tsecret')), 'tsecret');
			$form_container->output_row($this->lang['setting_icon'], $this->lang['setting_icon_desc'], $form->generate_text_box('icon', $this->settings['icon'], array('id' => 'icon')), 'icon');
			$form_container->output_row($this->lang['setting_forums'], $this->lang['setting_forums_desc'], $form->generate_forum_select('forums[]', $this->settings['forums'], array('multiple'=>1, 'size'=>'15')));

			//make sure you pass $returnable as TRUE
			$output .= $form_container->end(true);

			$buttons[] = $form->generate_submit_button($lang->mybbpublisher_save);
			$output .= $form->output_submit_wrapper($buttons);

			$output .= $form->end();

			//return the initial form opening tag and then the rest of the form
			return $form->construct_return.$output;
		}
	}



// ****** Twitter core hook functions ******

	/**
	 * Post a new status update
	 * @var Array array of the thread contents to be published
	 * @return Array array of the ID of the post from Twitter
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

		if($this->settings['enabled'] && $this->settings['ckey'] != "" && $this->settings['csecret'] != "" && $this->settings['token'] != "" && $this->settings['tsecret'] != "")
		{
			if($publisher->debug) mybbpublisher_log($this->service_name, $content);

			if($content['message'] == '' && $content['title'] == '')
			{
				if($publisher->debug) mybbpublisher_log($this->service_name, 'Content['.$content['id'].'] does not contain a message');
				$this->errors[$content['id']] = 'Content['.$content['id'].'] does not contain a message';
			}

			if(!$this->errors[$content['id']])
			{
				//create Twitter object using oAuth
				require_once MYBB_ROOT.'/inc/plugins/mybbpublisher/lib/tmhOAuth.php';
				$thmConfig = array('consumer_key'        => $this->settings['ckey'],
								   'consumer_secret'     => $this->settings['csecret'],
								   'user_token'          => $this->settings['token'],
								   'user_secret'         => $this->settings['tsecret']
								   );
				$tmhOAuth = new tmhOAuth($thmConfig);

				//shorten the overall status length, considering we want to keep the link intact
				$chars = strlen($this->lang['at'].($content['shortlink'] ? $content['shortlink'] : $content['link']));
				$status = substr($content['title'], 0, max(0, 140-$chars));

				$status .= $this->lang['at'].($content['shortlink'] ? $content['shortlink'] : $content['link']);

				//apply hash tags if we have leftover space for the status, attempt to add them all from left to right adding whatever fits
				if(trim($this->settings['tags']) != '')
				{
					$tags = explode(',', trim($this->settings['tags']));
					foreach($tags as $tag)
					{
						$tag = trim($tag);
						if(strlen($status) + strlen($tag) + 2 <= 140)
						{
							$status .= ' #'.$tag;
						}
					}
				}

				if($this->settings['type'] == 'link' || $content['imagepath'] == '' || !file_exists($content['imagepath']))
				{
					$attachment =  array('status' => $status);

					$tmhOAuth->request('POST', $tmhOAuth->url('1.1/statuses/update'), $attachment);
				}
				elseif($this->settings['type'] == 'photo')
				{
					$attachment =  array('status' => $status,
										 'media[]' => "@{$content['imagepath']}",
										);

					$tmhOAuth->request('POST', $tmhOAuth->url('1.1/statuses/update_with_media'), $attachment, true, true);
				}
				else
				{
					$this->errors[$content['id']] = array('action'=>'post', 'result'=>$lang->mybbpublisher_invalid_type, 'input'=>$attachment);
					mybbpublisher_log($this->service_name, $lang->mybbpublisher_invalid_type);
					return false;
				}

				$result = json_decode($tmhOAuth->response['response'], true);
				if($publisher->debug) mybbpublisher_log($this->service_name, $tmhOAuth);

				if($tmhOAuth->response['code'] == '200')
				{
					$this->errors = array();
					return array('id'=>$db->escape_string($result['id_str']));
				}
				else
				{
					$this->errors[$content['id']] = array('action'=>'post', 'result'=>$result['error'], 'input'=>$attachment);
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
		global $db;

		$this->errors = false;
		if($id)
		{
			//create Twitter object using oAuth
			require_once MYBB_ROOT.'/inc/plugins/mybbpublisher/lib/tmhOAuth.php';
			$thmConfig = array('consumer_key'        => $this->settings['ckey'],
							   'consumer_secret'     => $this->settings['csecret'],
							   'user_token'          => $this->settings['token'],
							   'user_secret'         => $this->settings['tsecret']
							   );
			$tmhOAuth = new tmhOAuth($thmConfig);
			$tmhOAuth->request('POST', $tmhOAuth->url('1.1/statuses/destroy/'.$id));

			$result = json_decode($tmhOAuth->response['response'], true);

			if($publisher->debug) mybbpublisher_log($this->service_name, $tmhOAuth);

			if($tmhOAuth->response['code'] == '200')
			{
				$this->errors = array();
				return true;
			}
			else
			{
				$this->errors[$content['id']] = array('action'=>'post', 'result'=>$result['error'], 'input'=>$attachment);
			}
		}
		return false;
	}

// ****** Admin functions ******

	/**
	 * Verifies the credentials for Twitter
	 * @return string result of Twitter verification
	 */
	function verify_creds()
	{
		global $mybb, $config, $db, $publisher;
		if($this->settings['ckey'] != "" && $this->settings['csecret'] != "" && $this->settings['token'] != "" && $this->settings['tsecret'] != "")
		{
			$output = '<h3>'.$this->lang['step1_results'].'</h3>';

			//create Twitter object using oAuth
			require_once MYBB_ROOT.'/inc/plugins/mybbpublisher/lib/tmhOAuth.php';
			$thmConfig = array('consumer_key'        => $this->settings['ckey'],
					           'consumer_secret'     => $this->settings['csecret'],
						       'user_token'          => $this->settings['token'],
					           'user_secret'         => $this->settings['tsecret'],
					           'host'				 => 'api.twitter.com'
					           );
			$tmhOAuth = new tmhOAuth($thmConfig);

			$tmhOAuth->request('GET', $tmhOAuth->url('1.1/account/verify_credentials'));
			$result = json_decode($tmhOAuth->response['response'],true);

			$output .= $this->lang['creds_check'].'<br /><br />';

			if ($tmhOAuth->response['code'] == 200)
			{
				$output .= $this->lang['id'].': '.$result['id'].'<br />';
				$output .= $this->lang['name'].': '.$result['name'].'<br />';
				$output .= $this->lang['description'].': '.$result['description'].'<br />';
				$output .= $this->lang['url'].': '.$result['url'].'<br />';
			}
			else
			{
				$output .= $this->lang['error_info'].'<br /><br />';
				$output .= $this->lang['error'].': '.$result['error'].'<br />';
			}
		}
		else
		{
			$output .= $this->lang['missing_creds'];
		}
		return $output;
	}

	/**
	 * Checks the current rate limit for Twitter
	 * @return string result of Twitter limit check
	 */
	function rate_limit()
	{
		global $mybb, $config, $db, $publisher;
		
		$output .= "This feature is not currently applicable to Twitter's REST API v1.1";
		return $output;
		
		if($this->settings['ckey'] != "" && $this->settings['csecret'] != "" && $this->settings['token'] != "" && $this->settings['tsecret'] != "")
		{
			$output = '<h3>'.$this->lang['step2_results'].'</h3>';

			//create Twitter object using oAuth
			require_once MYBB_ROOT.'/inc/plugins/mybbpublisher/lib/tmhOAuth.php';
			$thmConfig = array('consumer_key'        => $this->settings['ckey'],
					           'consumer_secret'     => $this->settings['csecret'],
						       'user_token'          => $this->settings['token'],
					           'user_secret'         => $this->settings['tsecret'],
					           'host'				 => 'api.twitter.com'
					           );
			$tmhOAuth = new tmhOAuth($thmConfig);

			$tmhOAuth->request('GET', $tmhOAuth->url('1.1/account/rate_limit_status'));
			$result = json_decode($tmhOAuth->response['response'],true);

			$output .= $this->lang['rate_limit'].'<br /><br />';

			if ($tmhOAuth->response['code'] == 200)
			{
				$output .= '<h4>'.$this->lang['text_limit'].'</h4><br />';
				$output .= $this->lang['remaining_hits'].': '.$result['remaining_hits'].'<br />';
				$output .= $this->lang['hourly_limit'].': '.$result['hourly_limit'].'<br />';
				$output .= $this->lang['reset_time'].': '.$result['reset_time'].'<br />';

				if(is_array($result['photos']))
				{
					$output .= '<h4>'.$this->lang['photo_limit'].'</h4><br />';
					$output .= $this->lang['remaining_hits'].': '.$result['photos']['remaining_hits'].'<br />';
					$output .= $this->lang['daily_limit'].': '.$result['photos']['daily_limit'].'<br />';
					$output .= $this->lang['reset_time'].': '.$result['photos']['reset_time'].'<br />';
				}
			}
			else
			{
				$output .= $this->lang['error_info'].'<br /><br />';
				$output .= $this->lang['error'].': '.$result['error'].'<br />';
			}
		}
		else
		{
			$output .= $this->lang['missing_creds'];
		}
		return $output;
	}

	/**
	 * Displays the recent tweets
	 * @return string result of Twitter recent tweets
	 */
	function recent()
	{
		global $mybb, $config, $db, $publisher;
		if($this->settings['ckey'] != "" && $this->settings['csecret'] != "" && $this->settings['token'] != "" && $this->settings['tsecret'] != "")
		{
			$output = '<h3>'.$this->lang['step3_results'].'</h3>';

			//create Twitter object using oAuth
			require_once MYBB_ROOT.'/inc/plugins/mybbpublisher/lib/tmhOAuth.php';
			$thmConfig = array('consumer_key'        => $this->settings['ckey'],
					           'consumer_secret'     => $this->settings['csecret'],
						       'user_token'          => $this->settings['token'],
					           'user_secret'         => $this->settings['tsecret'],
					           'host'				 => 'api.twitter.com'
					           );
			$tmhOAuth = new tmhOAuth($thmConfig);

			$tmhOAuth->request('GET', $tmhOAuth->url('1.1/statuses/user_timeline'), array('count'=>5));
			$result = json_decode($tmhOAuth->response['response'],true);

			$output .= $this->lang['recent'].'<br /><br />';

			if ($tmhOAuth->response['code'] == 200)
			{
				if(count($result))
				{
					foreach($result as $key => $status)
					{
						$output .= $this->lang['created'].': '.$status['created_at'].'<br />';
						$output .= $this->lang['text'].': '.$status['text'].'<br /><br />';
						$output .= $this->lang['id'].': '.$status['id'].'<br /><br />';
					}
				}
				else
				{
					$output .= $this->lang['empty'];
				}
			}
			else
			{
				$output .= $this->lang['error_info'].'<br /><br />';
				$output .= $this->lang['error'].': '.$result['error'].'<br />';
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
		global $mybb, $config, $db;

		if($mybb->input['do'] == "delete")
		{
			$output = '<h3>'.$this->lang['step4_results'].'</h3>';

			$result = $this->delete_post($mybb->input['tw_id']);

			$output .= $this->lang['test_return'].'<br />';
			if(!$result)
			{
				$output .= $this->lang['test_failed'].$this->errors[$mybb->input['tw_id']]['result'];
			}
			else
			{
				$output .= $this->lang['test_success'];
			}
		}
		else
		{
			$output = '<h3>'.$this->lang['step4_result'].'</h3>';

			$content = array('title'=> $this->lang['test_message'],
							'link' 	=> $mybb->settings['bburl']
							);

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
				$output .= '<br />'.$this->lang['test_made'].'<a href="'.$mybb->settings['bburl'].'/'.$config['admin_dir'].'/index.php?module=tools-mybbpublisher&service='.$this->service_name.'&action=test_post&do=delete&tw_id='.$testout['id'].'">'.$this->lang['test_delete'].'</a>';
			}
		}
		return $output;
	}
 }
 ?>
