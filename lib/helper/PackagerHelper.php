<?php

require_once __DIR__ . '/../../vendor/packager/Source.php';
require_once __DIR__ . '/../../vendor/jsmin-php/jsmin.php';

class PackagerHelper
{
	static $scripts = array();
	static $runtime_code = array();
	
	static function compile($source)
	{
		$code = Packager::strip_blocks($source->build(), '1.2compat');
		if (sfConfig::get('app_sf_packager_plugin_use_compression')) $code = JSMin::minify($code);
		return $code;
	}
	
	static function shouldCompile($file)
	{
		if (sfConfig::get('app_sf_packager_plugin_compile')) return true;
		if (!file_exists($file)) return true;
		if (sfConfig::get('app_sf_packager_plugin_check_dates') && (time() - filemtime($file)) > 86400) return true;
	}
}

function js($name = '', $requires = array(), $provides = null)
{
	PackagerHelper::$scripts[] = $script = new Source(sfConfig::get('sf_app'));
	if ($name) $script->set_name($name);
	if ($requires) $script->requires($requires);
	if ($provides) $script->provides($provides);
	ob_start();
}

# note(ibolmo): CDATA is kept.
function end_js()
{
	preg_match('/<script[^>]*>([\s\S]*?)<\/script>/i', ob_get_clean(), $matches);
	$source = end(PackagerHelper::$scripts);
	$source->set_code($matches[1]);
	$source->parse();
}

function js_tag()
{
	ob_start();
}

function end_js_tag()
{
	preg_match('/<script[^>]*>([\s\S]*?)<\/script>/i', ob_get_clean(), $matches);
	PackagerHelper::$runtime_code[] = $matches[1];
}

function include_js()
{
	if (empty(PackagerHelper::$scripts)) return;
	
	$packager = Packager::get_instance();
	
	$files = sfFinder::type('any')->name('*package.yml')->name('*package.json')->in(sfConfig::get('sf_lib_dir') . '/js/');
	foreach ($files as $package) $packager->add_package($package);
	
	$source = new Source(sfConfig::get('sf_app'));
	$source->requires(PackagerHelper::$scripts);

	$env = sfContext::getInstance()->getConfiguration()->getEnvironment();
	if (!$env) $env = 'prod';
	$key = sha1(implode('', PackagerHelper::$scripts)).'-'.$env;
	$file = sfConfig::get('sf_web_dir').'/js/cache/'.$key.'.js';
	if (PackagerHelper::shouldCompile($file)) file_put_contents($file, PackagerHelper::compile($source));
	
	echo content_tag('script', '', array('type' => 'text/javascript', 'src' => javascript_path('cache/' . $key)));
	
	foreach (PackagerHelper::$runtime_code as $code){
		if (sfConfig::get('app_sf_packager_plugin_use_compression')) $code = JSMin::minify($code);
		echo content_tag('script', $code);
	}
}


function require_js($module)
{
	$files = sfFinder::type('any')->name($module)->in(sfConfig::get('sf_lib_dir').'/js/');
	if (empty($files)) throw new sfException("Could not find '$module'.");
	foreach ($files as $file) PackagerHelper::$scripts[] = new Source(sfConfig::get('sf_app'), $file);
}