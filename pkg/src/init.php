<?php

namespace Main;

require_once __DIR__ . '/core/library.php';
require_once __DIR__ . '/core/package.php';

return (function (){
	$packages = [];
	foreach (scandir(__DIR__) as $file) {
		if (is_dir($dir = __DIR__ . DIRECTORY_SEPARATOR . $file) && !str_starts_with($file, '.')) {
			if (is_file($init = $dir . DIRECTORY_SEPARATOR . 'init.php')) {
				require_once $init;
			}
			else {
				$packages[] = Package::load($dir, __NAMESPACE__, pathinfo(__FILE__, PATHINFO_EXTENSION))->as($file);
			}
		}
	}
	return new Library($packages);
})();
