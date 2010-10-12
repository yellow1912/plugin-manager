<?php

/**
 * This class is a simple class implemented to manage RubikIntegration's plugin
 * It is not meant to be a full fledged manager for Zencart in anycase.
 * 
 * The class allows RubikIntegration's plugin to be placed all in one place for easy installation/uninstallation
 * It also aims to support certain degree of dependency 
 */

/**
 * We have a bunch of core plugins we need to manually init
 */
require(dirname(__FILE__).'/../../RIFile/etc/RIFile.php');
require(dirname(__FILE__).'/../../RICache/etc/RICache.php');
require('RIPluginConfig.php');
RICache::set('cache_path', DIR_FS_CATALOG.'cache/');
RICache::set('cache_level', 4);

class RIPluginProvider{
	
	protected $loaded = array();
	protected $default_language;
	protected $default_template;
	protected $config;
		
	public function __construct($config){
		$this->default_language = DEFAULT_LANGUAGE;
		$this->default_template = 'template_default';
		$this->config = $config;
	}
	
	/**
	 * Use the magic method to allow requesting plugin file in a more user friendly way.
	 *
	 * @param unknown_type $name
	 * @param unknown_type $arguments
	 */
	public function __call($name, $arguments){
		array_unshift($arguments, $name);
		if(empty($arguments[1])) $arguments[1] = $name;
		return call_user_func_array(array($this, "request"), $arguments);
	}
	
	/**
	 * A simple method (for now) to request a plugin
	 *
	 * @param string $plugin_name 
	 * @param string $file_path 
	 * @param bool $return 
	 * @param array $params contains the parameters to be passed to class __construct
	 */
	private function request($plugin_name, $file_path, $return = false, $params = null){
		if(!isset($this->loaded[$plugin_name])){
			$this->loaded[$plugin_name] = array();
		}
		
		if(!isset($this->loaded[$plugin_name][$file_path])){
			require_once($this->config->plugins__path."$plugin_name/etc/$file_path.php");
			$this->loaded[$plugin_name][$file_path] = null;
		}
		
		if($return !== false){
			if($this->loaded[$plugin_name][$file_path] == null){
				$class_name = end(explode("/", $file_path));
				$obj = new $class_name();
				if(!empty($params)) call_user_func_array(array($obj, 'init'), $params); 
				$this->loaded[$plugin_name][$file_path] = $obj;
			}
			return $this->loaded[$plugin_name][$file_path]; // TODO: we should check for the differences in params as well
		}
	}
	
	function loadOpt(){
		
	}
	
	function getOpt(){
		
	}
	
	function setOpt(){
		
	}
	
	public function loadLanguage($plugin, $file, $language = '', $admin = false, $template = ''){
		if($admin){
			$path = $this->config->plugins__path."$plugin/etc/admin/languages/%s/%s";
		}
		else{
			$path = $this->config->plugins__path."$plugin/etc/catalog/languages/%s/%s";
		}
		
		if(empty($language)) $language = $this->default_language;
		
		$languages = ($language == $this->default_language) ? array($this->default_language) : array($language, $this->default_language);
		
		if($template != '') $template =  trim($template, '/').'/';
		$templates = ($template == $this->default_template.'/') ? array($this->default_template.'/') : array($template, $this->default_template.'/');
		
		foreach($templates as $t)
			foreach($languages as $l){
				if(file_exists($file_path = sprintf($path, $language, $template)."{$file}")){
					require($file_path);
					return true;
				}
			}
		
		return false;	
	
	}
}