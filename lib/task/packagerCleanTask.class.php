<?php

class packagerCleanTask extends sfBaseTask
{
	protected function configure()
	{
 		$this->namespace            = 'packager';
    $this->name                 = 'clean';
    $this->briefDescription     = 'Removes all cached compiled JavaScript';
    $this->detailedDescription  = <<<EOF
The [packager:clean|INFO] task removes all cached compiled JavaScript.
Call it with:

  [php symfony less:compile|INFO]
EOF;
	}
	
	protected function execute($arguments = array(), $options = array())
	{
		$js_cache_dir = sfConfig::get('sf_web_dir') . '/js/cache';
		`rm -f $js_cache_dir/*`;
	}
}
