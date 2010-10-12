<?php
/**
 * This class is responsible for loading, setting and getting plugins configurations
 *
 */

require(dirname(__FILE__).'/../../sfYaml/etc/sfYaml.php');

class RIPluginConfig{
	protected $config = array();
	
	public function __construct(){
		 $this->config['plugins']['local_config']['path'] = RI_PLUGINS_PATH;
	}
	
	/**
	 * Allows setting value to the config array
	 *
	 * @param string $plugin
	 * @param string $base
	 * @param string $name
	 * @param string $value
	 */
	public function set($plugin, $base, $name, $value){
		if(!empty($name))
			$this->config[$plugin][$base][$name] = $value;
		elseif(!empty($base))
			$this->config[$plugin][$base] = $value;
		else
			$this->config[$plugin] = $value;
	}
	
	/**
	 * Allows getting value from the config array
	 *
	 * @param string $plugin
	 * @param string $base
	 * @param string $name
	 * @return value
	 */
	public function get($plugin, $base, $name){
		// we may want to remove this autoload for performance purpose? here we assume people rarely every try 
		// to request any setthing that should not be available
		if(!isset($this->config[$plugin][$base]) || !isset($this->config[$plugin][$base])) $this->load($plugin, $base);
		
		if(!isset($this->config[$plugin])) return null;
		
		if(empty($base)) return $this->config[$plugin];
		
		if(empty($name)) return $this->config[$plugin][$base];
		
		return $this->config[$plugin][$base][$name];
	}
	
	/**
	 * magic method for set
	 *
	 * @param string $name
	 * @param string $value
	 */
	public function __set($name, $value){
		list($plugin, $name, $base) = $this->getPartsFromName($name);
		return $this->set($plugin, $base, $name, $value);
	}
	
	/**
	 * magic method for get
	 *
	 * @param string $name
	 */
	public function __get($name){
		list($plugin, $name, $base) = $this->getPartsFromName($name);
		return $this->get($plugin, $base, $name);
	}

	/**
	 * overloading isset
	 *
	 * @param string $name
	 * @return bool true/false
	 */
	public function __isset($name){
		list($plugin, $name, $base) = $this->getPartsFromName($name);
		if(!empty($name))	return isset($this->config[$plugin][$base][$name]);	
		elseif(!empty($base))	return isset($this->config[$plugin][$base]);	
		return isset($this->config[$plugin]);	
	}
	
	/**
	 * get parts from name
	 *
	 * @param string $key
	 * @return array
	 */
	private function getPartsFromName($key){
		$temp = explode("__", $key);
		if(count($temp) == 2 && $temp[1] != 'local_config'){
			$temp[2] = $temp[1];
			$temp[1] = 'local_config';
		}
		list($plugin, $base, $name) = $temp;
		return array($plugin, $name, $base);
	}
	
	/**
	 * Load the configuration from cache or yaml file
	 *	
	 * @param string $plugin
	 * @param string $config
	 */
	public function load($plugin, $config = "local_config"){
		if(!isset($this->config[$plugin][$config])){
			$cache_file = ($plugin.$config);
			if(($data = RICache::read($cache_file, 'plugins')) !== false){
				$data = unserialize($data);
			}elseif(RIFile::exists($this->plugins__path."$plugin/config/$config.yaml")){
				$data = sfYaml::load($this->plugins__path."$plugin/config/$config.yaml");
				RICache::write($cache_file, 'plugins', serialize($data));
			}
			
			$this->config[$plugin][$config] = !isset($this->config[$plugin][$config]) ? $this->config[$plugin][$config] = $data : array_merge( $this->config[$plugin][$config], $data);
		}
		return $this->config[$plugin][$config];
	}
	
	/**
	 * Load multiple configuration
	 *
	 * @param string $plugin
	 * @param array $configs
	 * @return array
	 */
	function loadArray($plugin, $configs = array('info', 'local_config')){
		foreach ($configs as $config){
			$parts = pathinfo($config);
			$this->load($plugin, $parts['filename']);
		}
		return $this->config[$plugin];
	}
	
	/**
	 * Save configuration to yaml and cache files
	 *
	 */
	public function save($plugin, $config = "local_config"){
		if(isset($this->config[$plugin][$config])){
			$data = sfYaml::dump($this->config[$plugin][$config]);
			$cache_file = ($plugin.$config);
			if(RIFile::write($data, $this->plugins__path."$plugin/config/$config.yaml"))
				if(RICache::write($cache_file, "plugins", serialize($this->config[$plugin][$config])))
					return true;
		}
		return false;
	}
}