<?php
/**
 * MyBBPublisher Plugin for MyBB
 * Copyright 2013 CommunityPlugins.com, All Rights Reserved
 *
 * Website: http://www.communityplugins.com
 * Version 3.4.0
 * License: Creative Commons Attribution-NonCommerical ShareAlike 3.0
				http://creativecommons.org/licenses/by-nc-sa/3.0/legalcode
 *
 */

class mybbpublisher
{

	/**
	 * Array of services installed
	 * @var array
	 */
	public $services;

	/**
	 * Enable debug mode
	 * @var boolean
	 */
	public $debug = false;
	
	/**
	 * Default images when avatar is not found
	 * @var string
	 */
	public $default_image;
	
	/**
	 * List of publishable forum IDs
	 * @var array
	 */
	public $forums_can_publish;
	
	/**
	 * HTML of available service icons
	 * @var array
	 */
	public $forum_icons;
	
	/**
	 * Global plugin options
	 * @var array
	 */
	public $settings;
	
	/**
	 * Template for option to publish
	 * @var string
	 */
	public $option_template;
	
	/**
	 * Constructor of class.
	 *  
	 *
	 */
	function __construct()
	{
		global $mybb;
		$this->services = $this->get_services();
		$this->default_image = $mybb->settings['mybbpublisher_default_image'];
		$this->debug = $mybb->settings['mybbpublisher_debug'];		
		$this->settings = unserialize($mybb->settings['mybbpublisher_module_settings']);	
		
		//preload image HTML
		foreach($this->services as $service => $version)
		{
			if($this->settings[$service]['enabled'] && $this->settings[$service]['icon'] != '' && file_exists(MYBB_ROOT.$this->settings[$service]['icon']))
			{
				$this->forum_icons[$service] = '<img src="'.$mybb->settings['bburl'].'/'.$this->settings[$service]['icon'].'" title="'.ucfirst($service).'" alt="'.ucfirst($service).'"/> '.$forum['description'];
			}	
		}
	}
	
	/**
	 * Get the available services (modules)
	 * 
	 * @return array
	 */
	function get_services()
	{
		$pub_services = array();
		$modules = glob(MYBB_ROOT.'/inc/plugins/mybbpublisher/modules/*.php');
		foreach($modules as $module)
		{
   			require_once($module);
   		}
    	return $pub_services;
	}
	
	/**
	 * Load the ACP language vars for this service
	 * 
	 * @return string
	 */
	function load_lang($service_name, &$service_lang)
	{
		global $lang;
		
		$path = MYBB_ROOT."inc/plugins/mybbpublisher/languages";
		$lfile = $path."/".$lang->language."/".$service_name.".lang.php";
		
		if(file_exists($lfile))
		{
			include $lfile;
		}
		elseif(file_exists($path."/english/".$service_name.".lang.php"))
		{
			include $path."/english/".$service_name.".lang.php";
		}
		
		$service_lang = array();
		
		if(is_array($l))
		{
			foreach($l as $key => $val)
			{
				if((empty($this->lang[$key]) || $this->lang[$key] != $val))
				{
					$service_lang[$key] = $val;
				}
			}
		}
	
		return $service_lang;
	}

	/**
	 * Generic cURL function
	 * 
	 * @return string
	 */
	function curl($url, $params)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
		//curl_setopt($ch, CURLOPT_TIMEOUT, 1);
		$result = curl_exec($ch);
		curl_close ($ch);
		return $result;
	}
	
	/**
	 * Simple cURL function to replace file_get_contents
	 * 
	 * @return string
	 */
	function curl_simple($url)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,$url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$result = curl_exec($ch);
		curl_close ($ch);
		return $result;
	}
	
	/**
	 * Simple wrapper to save and rebuild options
	 * 
	 * @var string service name settings apply to
	 * @var array settings to apply
	 * 
	 */
	function save_settings($service_name, $options)
	{
		global $db;
		
		$this->settings[$service_name] = $options;
		$db->update_query("settings", array("value" => serialize($this->settings)), "name='mybbpublisher_module_settings'");
		rebuild_settings();
	}
}



?>
