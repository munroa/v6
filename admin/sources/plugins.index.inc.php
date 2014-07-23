<?php
if (!defined('CC_INI_SET')) die('Access Denied');

global $lang, $glob;

$GLOBALS['main']->addTabControl('Installed Plugins', 'plugins');

if (isset($_POST['status'])) {
	$updated = false;
	foreach ($_POST['status'] as $module_name => $status) {
		$module_type = $_POST['type'][$module_name];
		if($GLOBALS['db']->select('CubeCart_modules',false,array('folder' => $module_name))) {
			$GLOBALS['db']->update('CubeCart_modules', array('status' => (int)$status), array('folder' => $module_name), true);
			if ($module_type=='plugins') {
				if ($status) {
					$GLOBALS['hooks']->install($module_name);
				} else {
					$GLOBALS['hooks']->uninstall($module_name);
				}
			}
		} else {
			$GLOBALS['db']->insert('CubeCart_modules', array('status' => (int)$status, 'folder' => $module_name, 'module' => $module_type));
		}
		// Update config
		$GLOBALS['config']->set($module_name, 'status', $status);
		$updated = true;
	}
	if ($updated) {
		$GLOBALS['gui']->setNotify($lang['module']['notify_module_status']);
	}
	$GLOBALS['cache']->clear();
	httpredir('?_g=plugins');
}

if(!$modules = $GLOBALS['cache']->read('module_list')) {
	$module_paths = glob("modules/*/*/config.xml");
	$i=0;
	foreach ($module_paths as $module_path) {
	
		$xml   = new SimpleXMLElement(file_get_contents($module_path));
		
		$basename = (string)basename(str_replace('config.xml','', $module_path));
		$key = trim((string)$xml->info->name.$i);
		
		$config = $GLOBALS['db']->select('CubeCart_modules','*',array('folder' => $basename, 'module' => (string)$xml->info->type));

		$modules[$key] = array(
			'uid' 				=> (string)$xml->info->uid,
			'type' 				=> (string)$xml->info->type,
			'mobile_optimized' 	=> (string)$xml->info->mobile_optimized,
			'name' 				=> (string)$xml->info->name,
			'description' 		=> (string)$xml->info->description,
			'version' 			=> (string)$xml->info->version,
			'minVersion' 		=> (string)$xml->info->minVersion,
			'maxVersion' 		=> (string)$xml->info->maxVersion,
			'creator' 			=> (string)$xml->info->creator,
			'homepage' 			=> (string)$xml->info->homepage,
			'block' 			=> (string)$xml->info->block,
			'basename' 			=> $basename,
			'config'			=> $config[0]
		);
		$i++;
	}
	ksort($modules);
	$GLOBALS['cache']->write($modules, 'module_list');
}
$GLOBALS['smarty']->assign('MODULES',$modules);
$page_content = $GLOBALS['smarty']->fetch('templates/plugins.index.php');