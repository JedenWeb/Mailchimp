<?php

/** @link https://github.com/nette/di/blob/master/tests/bootstrap.php */

if (@!include __DIR__ . '/../vendor/autoload.php') {
	echo 'Install Nette Tester using `composer install`';
	exit(1);
}

Tester\Environment::setup();
date_default_timezone_set('Europe/Prague');

define('TEMP_DIR', __DIR__ . '/tmp/' . getmypid());
@mkdir(dirname(TEMP_DIR)); // @ - directory may already exist
Tester\Helpers::purge(TEMP_DIR);

function test(\Closure $function)
{
	$function();
}

function createContainer($source, $config = NULL)
{
	$class = 'Container' . md5(lcg_value());
	if ($source instanceof Nette\DI\ContainerBuilder) {
		$code = implode('', $source->generateClasses($class));
	} elseif ($source instanceof Nette\DI\Compiler) {
		if (is_string($config)) {
			$loader = new Nette\DI\Config\Loader;
			$config = $loader->load(is_file($config) ? $config : Tester\FileMock::create($config, 'neon'));
		}
		$code = $source->compile((array) $config, $class, 'Nette\DI\Container');
	} else {
		return;
	}
	file_put_contents(TEMP_DIR . '/code.php', "<?php\n\n$code");
	require TEMP_DIR . '/code.php';
	return new $class;
}
