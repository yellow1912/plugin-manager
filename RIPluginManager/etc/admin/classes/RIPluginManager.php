<?php
/**
 * This class provides methods for managing modules
 *
 */
class RIPluginManager {
	protected $config;
	protected $message_stack;
	protected $sql_patcher;
	public function init(&$config, $sql_patcher){
		$this->config = $config;
		$this->sql_patcher = $sql_patcher;
	}
	
	/**
	 * returns a list of the current available modules on the local server
	 *
	 * @return array
	 */
	public function listModules(){
		$path = $this->config->plugins__path;
		return array_keys(RIFile::getStructure($path, 1, 'folder'));
	}

	/**
	 * installs the selected module
	 *
	 * @param string $plugin
	 * @return bool true (or throw an exception)
	 */
	public function install($plugin){
		// get the installed list shall we?
		$installed = $this->config->RIPluginManager__installed;
		if(!is_array($installed)) $installed = array();
		if(key_exists($plugin, $installed))
			throw new Exception('This plugin has already been installed');
		
		// load the info
		if(!RIFile::exists($this->config->plugins__path."$plugin/config/info.yaml"))
			throw new Exception('The package is missing config/info.yaml');
			
		$info = $this->config->{"{$plugin}__info"};
		
		// check dependecies, init local config if needed
		$this->preProcess($plugin, $info, $installed);
				
		// attempting to run the patches if any
		list($error, $message) = $this->applyPatches($info['history'], $plugin, $installed, isset($info['pages'])? $info['pages'] : null);
		if($error) throw new Exception($message);
		return true;
	}
	
	/**
	 * updates a module
	 *
	 * @param string $plugin
	 * @return bool true (or throw an exception)
	 */
	public function update($plugin){
		// we will have to check if we need update at all 
		$installed = $this->config->RIPluginManager__installed;
		if(!is_array($installed)) $installed = array();
		if(!key_exists($plugin, $installed))
			throw new Exception('This plugin has not been installed');
		
		// load the info
		if(!RIFile::exists($this->config->plugins__path."$plugin/config/info.yaml"))
			throw new Exception('The package is missing config/info.yaml');
			
		$info = $this->config->{"{$plugin}__info"};
		
		// check if this has been installed or not
		if($info['version'] == $installed($plugin))	
			throw new Exception('This plugin is already using the latest version available on this local server');
			
		// check dependecies, init local config if needed
		$this->preProcess($plugin, $info, $installed);
		
		// we find the patches we need to apply
		$found = false;
		$patches = array();
		foreach ($info['history'] as $version => $patch){
			if($version == $installed[$plugin]['version'])
				$found = true;
			if($found)
				$patches[$version] = $patch;
		}
		
		list($error, $message) = $this->applyPatches($patches, $plugin, $installed, isset($info['pages']) ? $info['pages'] : null);
		if($error) throw new Exception($message);
		return true;
	}
	
	public function uninstall($plugin){
		// get the installed list shall we?
		$installed = $this->config->RIPluginManager__installed;
		if(!is_array($installed)) $installed = array();
		if(key_exists($plugin, $installed)){
			
			// uninstallation is easy, first we try to run the uninstall.sql if any
			$patch = $this->config->plugins__path."$plugin/patches/uninstall.sql";
			if(RIFile::exists($patch))
				if(!$this->sql_patcher->execute_sql_file($patch))
					throw new Exception("failed to execute $patch");
			
			// then we run the uninstall.php if any
			$patch = $this->config->plugins__path."$plugin/patches/uninstall.php";
			$error = false;
			if(RIFile::exists($patch)){
				require($patch);
				if($error === true) throw new Exception("failed to execute $patch");
			}
			
			// then we will remove the module from the installed list, we also remove page mapping if any
			unset($installed[$plugin]);
			$this->config->RIPluginManager__installed = $installed;
			
			$pages = $this->config->{"{$plugin}__pages"};
			if(is_array($pages)){
				$page_map = $this->config->RIPluginManager__pages;
				foreach ($page_map as $page => $page_plugin)
					if($page_plugin == $plugin) unset($page_map[$page]);
				$this->config->RIPluginManager__pages = $page_map;
			}
			$this->config->save("RIPluginManager", "local_config");
		}
		// we may want to display the final uninstallation note here
	}
	
	/**
	 * pre proccessing for upgrade/install
	 *
	 * @param string $plugin
	 * @param array $info
	 * @param array $installed
	 */
	protected function preProcess($plugin, $info, $installed){
		// we have to check for dependency here
		if(isset($info['dependencies']))
			foreach ($info['dependencies'] as $dependency => $version){
				if(!key_exists($dependency, $installed))
					throw new Exception('This plugin requires '.$dependency.'. Please install the required module first');
				if(float($version) > $installed[$dependency])
					throw new Exception('This plugin requires '.$dependency.' version '.$version.' Please upgrade the required module first');
			}
			
		// clone the config file
		if(RIFile::exists($this->config->plugins__path."$plugin/config/config.yaml"))
				if(!RIFile::exists($this->config->plugins__path."$plugin/config/local_config.yaml"))
					if(!RIFile::copy($this->config->plugins__path."$plugin/config/config.yaml", $this->config->plugins__path."$plugin/config/local_config.yaml"))
						throw new Exception('Could not create local config file. Check the permisson for creating/editing local_config.yaml at '.$this->config->plugins__path."$plugin/config/");
		
		if(isset($info['pages']) && ($page_map = $this->config->RIPluginManager__pages) != null)
			foreach ($info['pages'] as $page) {
				if(isset($page_map[$page]) && $page_map[$page] != $plugin)
					throw new Exception('Conflict mapping! Page '.$page.' is currently being used by the plugin '.$page_map[$page]);
			}
	}
	
	/**
	 * applies patches for upgrade/install
	 *
	 * @param array $patches
	 * @param string $plugin
	 * @param array $installed
	 * @param array $pages
	 * @return array(error, message)
	 */
	protected function applyPatches($patches, $plugin, $installed, $pages){
		$applied_patches = array();
		$latest_version = null;
		$error = false;
		$message = '';
		foreach ($patches as $version => $patch){
			if($patch['sql'] !== false && !in_array($patch['sql'], $installed[$plugin]['patches'])) {
				$patch = $this->config->plugins__path."$plugin/patches/".$patch['sql'];
				if(!RIFile::exists($patch)){
					$message = "missing required file ".$patch;
					$error = true;
				}
				elseif(!$this->sql_patcher->execute_sql_file($patch)){
					$message = "failed to execute ".$patch;
					$error = true;
				}
				else $applied_patches[] = $patch['sql'];
			}
			
			if($patch['php'] !== false && !in_array($patch['php'], $installed[$plugin]['patches'])) {
				$patch = $this->config->plugins__path."$plugin/patches/".$patch['php'];
				if(!RIFile::exists($patch)){
					$message = "missing required file ".$patch;
					$error = true;
				}
				else{
					require($patch);
					if(!$error)	$applied_patches[] = $patch['php'];
				}
			}
			
			if(!$error) $latest_version = $version;
			else break;
		}
		
		if(!empty($latest_version)){
			// now we add to the installed list shall we?
			$installed[$plugin]['version'] = $latest_version;
			// update the applied patches
			$installed[$plugin]['patches'] = isset($installed[$plugin]['patches']) ? array_merge($installed[$plugin]['patches'], $applied_patches) : $applied_patches;
			// add the status (for new installation only)
			if(!isset($installed[$plugin]['status'])) $installed[$plugin]['status'] = true;
			// add page mapping
			if(!empty($pages)) 
				$installed[$plugin]['pages'] = isset($installed['pages']) ? array_merge($installed['pages'], $pages) : $pages;
			
			$this->config->RIPluginManager__installed = $installed;
			$this->config->save("RIPluginManager", "local_config");
		}
		
		return array($error, $message);
	}
}
