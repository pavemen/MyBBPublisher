<?php
/**
 * Tumblr Module for MyBBPublisher Plugin for MyBB
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
 $pub_services['tumblr'] = '1.1'; //(lowercase, no spaces, no punctuation) must match $service_name below and base filename of this file
 
 class pub_tumblr
 {
 	/**
	 * The simple service name (lowercase, no spaces, no punctuation)
	 * @var string 
	 */
	public $service_name = 'tumblr';

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

			if(array_key_exists('ckey', $params))
			{
				$this->settings['ckey'] = $params['ckey'];
			}		

			if(array_key_exists('debug', $params))
			{
				$publisher->debug = $params['debug'];
			}		

			if(array_key_exists('csecret', $params))
			{
				$this->settings['csecret'] = $params['csecret'];
			}		

			if(array_key_exists('hostname', $params))
			{
				$this->settings['hostname'] = $params['hostname'];
			}		

			if(array_key_exists('secret', $params))
			{
				$this->settings['secret'] = $params['secret'];
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

		if($this->settings['ckey'] != "" && $this->settings['csecret'] != "" && $this->settings['hostname'] != "" )
		{
			$this->acp_actions[1] = array('verify_creds'=>$this->lang['step1']);
			$this->acp_actions[2] = array('request_auth'=>$this->lang['step2']);
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
			$this->settings['enabled'] = $db->escape_string($mybb->input['enabled']);
			$this->settings['ckey'] = $db->escape_string($mybb->input['ckey']);
			$this->settings['csecret'] = $db->escape_string($mybb->input['csecret']);
			$this->settings['hostname'] = $db->escape_string($mybb->input['hostname']);
			$this->settings['type'] = $db->escape_string($mybb->input['type']);
			$this->settings['icon'] = $db->escape_string(str_replace("\\", "/", $mybb->input['icon']));
			$this->settings['tags'] = $db->escape_string($mybb->input['tags']);
			$this->settings['state'] = $db->escape_string($mybb->input['state']);
			
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

			$form_container->output_row($this->lang['setting_hostname'], $this->lang['setting_hostname_desc'], $form->generate_text_box('hostname', $this->settings['hostname'], array('id' => 'hostname')), 'hostname');
			
			$posttypes['link'] = $this->lang['setting_type_link'];
			$posttypes['photo'] = $this->lang['setting_type_photo'];
			
			$form_container->output_row($this->lang['setting_type'], $this->lang['setting_type_desc'], $form->generate_select_box('type', $posttypes, $this->settings['type'], array('id' => 'type')), 'type');

			$states['published'] = $this->lang['setting_state_published'];
			$states['draft'] = $this->lang['setting_state_draft'];
			$states['queue'] = $this->lang['setting_state_queue'];
			$states['private'] = $this->lang['setting_state_private'];
			
			$form_container->output_row($this->lang['setting_state'], $this->lang['setting_state_desc'], $form->generate_select_box('state', $states, $this->settings['state'], array('id' => 'state')), 'state');

			$form_container->output_row($this->lang['setting_tags'], $this->lang['setting_tags_desc'], $form->generate_text_box('tags', $this->settings['tags'], array('id' => 'tags')), 'tags');
					
			$form_container->output_row($this->lang['setting_ckey'], $this->lang['setting_ckey_desc'], $form->generate_text_box('ckey', $this->settings['ckey'], array('id' => 'ckey')), 'ckey');
			$form_container->output_row($this->lang['setting_csecret'], $this->lang['setting_csecret_desc'], $form->generate_text_box('csecret', $this->settings['csecret'], array('id' => 'csecret')), 'csecret');

			$form_container->output_row($this->lang['setting_icon'], $this->lang['setting_icon_desc'], $form->generate_text_box('icon', $this->settings['icon'], array('id' => 'icon')), 'icon');

			//make sure you pass $returnable as TRUE
			$output .= $form_container->end(true);

			$buttons[] = $form->generate_submit_button($lang->mybbpublisher_save);
			$output .= $form->output_submit_wrapper($buttons);

			$output .= $form->end();			
			
			//return the initial form opening tag and then the rest of the form 
			return $form->construct_return.$output;	
		}	
	}	
	

	
// ****** Tumblr core hook functions ******

	/**
	 * Post a new status update
	 * @var Array array of the thread contents to be published
	 * @return Array array of the ID of the post from Tumblr
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

		if($this->settings['enabled'] && $this->settings['ckey'] != "" && $this->settings['csecret'] != "" && $this->settings['oauth_token'] && $this->settings['oauth_token_secret'])
		{	
			if($publisher->debug) mybbpublisher_log($this->service_name, $content);

			if($content['message'] == '' && $content['title'] == '')
			{
				if($publisher->debug) mybbpublisher_log($this->service_name, 'Content['.$content['id'].'] does not contain a message');
				$this->errors[$content['id']] = 'Content['.$content['id'].'] does not contain a message';
			}

			if(!$this->errors[$content['id']])
			{
				if($this->settings['type'] == 'link' || $content['imageurl'] == '')
				{
					$attachment =  array('title' => $content['title'],
										'type' => 'link',
										'url' => ($content['shortlink'] ? $content['shortlink'] : $content['link']),
										'description' => $lang->sprintf($this->lang['posted_by_link'], $content['message'], $content['author'], $content['authorlink'])
										);
	
				}
				elseif($this->settings['type'] == 'photo')
				{
					$data = array();
					$data[] = file_get_contents($content['imagepath']);
					$attachment =  array('caption' => $lang->sprintf($this->lang['posted_by_photo'], $content['author'], $content['authorlink'], $content['title'], ($content['shortlink'] ? $content['shortlink'] : $content['link'])),
										'type' => 'photo',
										//'source' => $content['imageurl'],
										'data' => $data,
										'link' => ($content['shortlink'] ? $content['shortlink'] : $content['link']),
										'tags' => $this->settings['tags'],
										'state' => $this->settings['state']
										);
				}
				else
				{
					$this->errors[$content['id']] = array('action'=>'post', 'result'=>$lang->mybbpublisher_invalid_type, 'input'=>$attachment);
					mybbpublisher_log($this->service_name, $lang->mybbpublisher_invalid_type);
					return false;
				}

				require_once MYBB_ROOT.'/inc/plugins/mybbpublisher/lib/tumblroauth.php';
				$tumblr = new TumblrOAuth($this->settings['ckey'], $this->settings['csecret'], $this->settings['oauth_token'], $this->settings['oauth_token_secret']);
		
				$result = $tumblr->post($tumblr->host.'blog/'.$this->settings['hostname'].'/post', $attachment);

				if($publisher->debug) mybbpublisher_log($this->service_name, $result);
			
				if($result->response->id)
				{
					$this->errors = array();
					return array('id'=>$db->escape_string($result->response->id));
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
		global $db;
		
		$this->errors = false;
		if($id)
		{
			require_once MYBB_ROOT.'/inc/plugins/mybbpublisher/lib/tumblroauth.php';
			$tumblr = new TumblrOAuth($this->settings['ckey'], $this->settings['csecret'], $this->settings['oauth_token'], $this->settings['oauth_token_secret']);
		
			$result = $tumblr->post($tumblr->host.'blog/'.$this->settings['hostname'].'/post/delete', array('id'=>$id));

			if($publisher->debug) mybbpublisher_log($this->service_name, $result);
			
			if($result->response->id)
			{
				$this->errors = array();
				return array('id'=>$db->escape_string($result->response->id));
			}
			else
			{
				$this->errors[$content['id']] = array('action'=>'post', 'result'=>$result, 'input'=>$attachment);
			}
				
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
	 * Verifies the credentials for Tumblr
	 * @return string result of Tumblr verification
	 */
	function verify_creds() 
	{
		global $mybb, $config, $db, $publisher;
		if($this->settings['ckey'] != "" && $this->settings['csecret'] != "")
		{	
			$output = '<h3>'.$this->lang['step1_results'].'</h3>';

			//create Tumblr object using oAuth
			require_once MYBB_ROOT.'/inc/plugins/mybbpublisher/lib/tumblroauth.php';
			
			//obtain credentials
			$tumblr = new TumblrOAuth($this->settings['ckey'], $this->settings['csecret']);

			$result = $tumblr->get($tumblr->host.'blog/'.$this->settings['hostname'].'/info?api_key='.$this->settings['ckey']);
		
			$output .= $this->lang['creds_check'].'<br /><br />';

			if($result->meta->status == "200") 
			{
				$output .= $this->lang['name'].': '.$result->response->blog->title.'<br />';
				$output .= $this->lang['description'].': '.$result->response->blog->description.'<br />';
				$output .= $this->lang['url'].': '.$result->response->blog->url.'<br />';
			}
			else
			{
				$output .= $this->lang['error_info'].'<br /><br />';
				$output .= $this->lang['error_code'].': '.$result->meta->status.'<br />';
				$output .= $this->lang['error_msg'].': '.$result->meta->msg.'<br />';
			}			
		}
		else
		{
			$output .= $this->lang['missing_creds'];
		}
		return $output;
	}
	
	
	/**
	 * Requests authorization from Tumblr to the application to get Access Token
	 * @return 
	 */
	function request_auth() 
	{
		global $mybb, $config, $db, $publisher;	
		
		if($this->settings['ckey'] != "" && $this->settings['csecret'] != "")
		{	
			//create Tumblr object using oAuth
			require_once MYBB_ROOT.'/inc/plugins/mybbpublisher/lib/tumblroauth.php';
			$tumblr = new TumblrOAuth($this->settings['ckey'], $this->settings['csecret']);
		
			//if we are not coming back from authorization
			if($mybb->input['oauth_token'] == '' && $mybb->input['oauth_verifier'] == '' && $mybb->input['do'] != 'from_auth')
			{
				$output = '<h3>'.$this->lang['step2'].'</h3><br />';
			
				$token = $tumblr->getRequestToken($mybb->settings['bburl'].'/'.$config['admin_dir'].'/index.php?module=tools-mybbpublisher&service='.$this->service_name.'&action=request_auth&do=from_auth');

				update_admin_session('tumblr_token', $token['oauth_token']);			
				update_admin_session('tumblr_token_secret', $token['oauth_token_secret']);			
				
				$url = $tumblr->getAuthorizeURL($token);
				
				//redirect to tumblr
				$output .= '<script type="text/javascript">
							<!--
								window.location = "'.$url.'"
							//-->
							</script>';
			}
			//if dont have authorization
			elseif($mybb->input['oauth_token'] == '' && $mybb->input['oauth_verifier'] == '' && $mybb->input['do'] == 'from_auth')
			{
				$output .= $this->lang['failed_auth'];
			}	
			//otherwise assume we do		
			else
			{
				$output = '<h3>'.$this->lang['step2_results'].'</h3><br />';
				
				//needed to retreive temporary tokens
				global $admin_session;
				
				$tumblr->token = new OAuthConsumer($admin_session['data']['tumblr_token'], $admin_session['data']['tumblr_token_secret']);
				$access_token = $tumblr->getAccessToken($mybb->input['oauth_verifier']);
				
				$this->settings['oauth_token'] = $db->escape_string($access_token['oauth_token']);
				$this->settings['oauth_token_secret'] = $db->escape_string($access_token['oauth_token_secret']);
			
				$publisher->save_settings($this->service_name, $this->settings);

				$output .= $this->lang['token_success'].'<br /><br />';
				$output .= $this->lang['token'].": ".$access_token['oauth_token'].'<br />'.$this->lang['token_secret'].": ".$access_token['oauth_token_secret'];
				$output .= '<br /><br />'.$this->lang['token_saved'].'<br /><br />';
				
				update_admin_session('tumblr_token', '');			
				update_admin_session('tumblr_token_secret', '');			
			}
		}
		else
		{
			$output .= $this->lang['missing_creds'];
		}
		
		return $output;
	}
	
	
	/**
	 * Displays the recent tumblr posts
	 * @return string result of Tumblr recent tumblr posts
	 */
	function recent() 
	{
		global $mybb, $config, $db, $publisher;
		if($this->settings['ckey'] != "" && $this->settings['csecret'] != "")
		{	
			$output = '<h3>'.$this->lang['step3_results'].'</h3>';

			//create Tumblr object using oAuth
			require_once MYBB_ROOT.'/inc/plugins/mybbpublisher/lib/tumblroauth.php';
			
			//obtain credentials
			$tumblr = new TumblrOAuth($this->settings['ckey'], $this->settings['csecret']);
					
			$result = $tumblr->get($tumblr->host.'blog/'.$this->settings['hostname'].'/posts?limit=5&api_key='.$this->settings['ckey']);

			$output .= $this->lang['recent'].'<br /><br />';

			if($result->meta->status == "200") 
			{
				foreach($result->response->posts as $key => $status)
				{
					$output .= '<strong>'.$this->lang['created'].':</strong> '.$status->date.'<br />';
					$output .= '<strong>'.$this->lang['url'].':</strong> '.$status->post_url.'<br />';
					$output .= '<strong>'.$this->lang['type'].':</strong> '.$status->type.'<br />';
					
					switch($status->type)
					{
						case "text":
							$output .= '<strong>'.$this->lang['title'].':</strong> '.$status->title.'<br />';
							$output .= '<strong>'.$this->lang['body'].':</strong><span style="display:inline-block;border-top-style:dotted;border-bottom-style:dotted;border-width:1px;">'.$status->body.'</span><br />';	
						break;
						
						case "photo":
							$output .= '<strong>'.$this->lang['caption'].':</strong><span style="display:inline-block;border-top-style:dotted;border-bottom-style:dotted;border-width:1px;">'.$status->caption.'</span><br />';
							$photohtml = '';
							foreach($status->photos as $photo)
							{
								$photohtml .= '<img src="'.$photo->alt_sizes[0]->url.'" width="300"><br /><br />';					
							}
							if($photohtml == '') $photohtml = $this->lang['nophotos'];
							$output .= '<strong>'.$this->lang['photos'].':</strong><span style="display:inline-block;border-top-style:dotted;border-bottom-style:dotted;border-width:1px;">'.$photohtml.'</span><br />';
						break;

						case "quote":
							$output .= '<strong>'.$this->lang['text'].':</strong> '.$status->text.'<br />';
							$output .= '<strong>'.$this->lang['source'].':</strong><span style="display:inline-block;border-top-style:dotted;border-bottom-style:dotted;border-width:1px;">'.$status->source.'</span><br />';	
						break;

						case "link":
							$output .= '<strong>'.$this->lang['title'].':</strong> '.$status->title.'<br />';
							$output .= '<strong>'.$this->lang['url'].':</strong> '.$status->url.'<br />';
							$output .= '<strong>'.$this->lang['description'].':</strong><span style="display:inline-block;border-top-style:dotted;border-bottom-style:dotted;border-width:1px;">'.$status->description.'</span><br />';	
						break;
					}
					$output .= '<br />';			
					
				}
			}
			else
			{
				$output .= $this->lang['error_info'].'<br /><br />';
				$output .= $this->lang['error_code'].': '.$result->meta->status.'<br />';
				$output .= $this->lang['error_msg'].': '.$result->meta->msg.'<br />';
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
	
			$result = $this->delete_post($mybb->input['tu_id']);
		
			$output .= $this->lang['test_return'].'<br />';
			if(!$result)
			{
				$output .= $this->lang['test_failed'].$this->errors[$mybb->input['tu_id']]['result'];
			}
			else
			{
				$output .= $this->lang['test_success'];
			}
		}
		else
		{		
			$output = '<h3>'.$this->lang['step4_result'].'</h3>';
			
			if($this->settings['type'] == 'link')
			{
				$content = array('message'=> $this->lang['test_message'],
								'title'=>$this->lang['test_title'],
								'link'=>$mybb->settings['bburl'],
								'author'=>'Test User',
								'authorlink'=>$mybb->settings['bburl'],
								);
			
			}
			elseif($this->settings['type'] == 'photo')
			{		
				$content = array('message'=> $this->lang['test_message'],
								'title'=>$this->lang['test_title'],
								'link'=>$mybb->settings['bburl'],
								'author'=>'Test User',
								'authorlink'=>$mybb->settings['bburl'],
								'imageurl'=>$mybb->settings['bburl'].'/images/mybbpublisher_default.png',
								'imagepath'=>str_replace('//', '/', str_replace($mybb->settings['bburl'], MYBB_ROOT, $publisher->default_image))
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
				$output .= '<br />'.$this->lang['test_made'].'<a href="'.$mybb->settings['bburl'].'/'.$config['admin_dir'].'/index.php?module=tools-mybbpublisher&service='.$this->service_name.'&action=test_post&do=delete&tu_id='.$testout['id'].'">'.$this->lang['test_delete'].'</a>';
			}
		}
		return $output;
	}	
 }
 ?>
