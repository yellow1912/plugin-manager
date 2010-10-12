<?php

// we will have to replace the current zen missing page checking with our own

if(!is_dir(DIR_WS_MODULES .  'pages/' . $_GET['main_page'])){
	// we want to know if the page is registered in one of our module
	if(in_array($_GET['main_page'], $ri_plugin_config->RIPluginManager__pages)){
	  $code_page_directory = $ri_plugin_config->plugins__path . '/catalog/modules/pages/' . $current_page_base;
  	$page_directory = $code_page_directory;
	}
	else{
		if(MISSING_PAGE_CHECK == 'On' || MISSING_PAGE_CHECK == 'true'){
		$_GET['main_page'] = 'index';
		}elseif (MISSING_PAGE_CHECK == 'Page Not Found'){
		header('HTTP/1.1 404 Not Found');
		$_GET['main_page'] = 'page_not_found';
		}
	}
}