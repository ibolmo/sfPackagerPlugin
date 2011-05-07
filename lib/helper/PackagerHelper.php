<?php

require_once __DIR__ . '/../../vendor/packager/Source.php';

class PackagerHelper
{
	static $scripts = array();
}

function js($name = '', $requires = array(), $provides = null)
{
	PackagerHelper::$scripts[] = $script = new Source(sfConfig::get('sf_app'));
	if ($name) $script->set_name($name);
	if ($requires) $script->requires($requires);
	if ($provides) $script->provides($provides);
}

# note(ibolmo): CDATA is kept.
function end_js()
{
	preg_match('/<script[^>]*>([\s\S]*?)<\/script>/i', ob_get_clean(), $matches);
	$source = end(PackagerHelper::$scripts);
	$source->set_code($matches[1]);
	$source->parse();
}

# todo(ibolmo): May crash and burn, since it's duplicated.
function include_js()
{
	$packager = Packager::get_instance();
	
	$files = sfFinder::type('any')->name('*package.yml')->name('*package.json')->in(sfConfig::get('sf_lib_dir') . '/js/');
	foreach ($files as $package) $packager->add_package($package);
	
	$source = new Source(sfConfig::get('sf_app'));
	$source->requires(PackagerHelper::$scripts);
	# todo(ibolmo): Save to a cached file. Return a content tag to the cached file.
	echo content_tag('script', Packager::strip_blocks($source->build(), '1.2compat'));
}


function require_js($module)
{
	$filepath = sprintf('%s/js/%s.js', sfConfig::get('sf_lib_dir'), $module);
	if (!file_exists($filepath)) throw new sfException("JavaScript Module ($module) Not Found");
	
	PackagerHelper::$scripts[] = new Source(sfConfig::get('sf_app'), $filepath);
}