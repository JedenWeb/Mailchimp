<?php

require __DIR__ . '/../../../bootstrap.php';

use JedenWeb\Mailchimp\DI\MailchimpExtension;
use Kdyby\Curl\DI\CurlExtension;
use Nette\DI;

$compiler = new DI\Compiler;
$compiler->addExtension('curl', new CurlExtension);
$compiler->addExtension('mailchimp', new MailchimpExtension);

$loader = new DI\Config\Loader();
$config = $loader->load(__DIR__ . '/files/invalid.neon');

\Tester\Assert::exception(function() use ($compiler, $config) {
	eval($compiler->compile($config, 'Container1', 'Nette\DI\Container'));
}, 'JedenWeb\Mailchimp\InvalidStateException', 'Missing configuration option mailchimp.listId.');


$config = $loader->load(__DIR__ . '/files/valid.neon');

eval($compiler->compile($config, 'Container1', 'Nette\DI\Container'));

/** @var DI\Container $container */
$container = new Container1;

\Tester\Assert::same('JedenWeb\Mailchimp\Mailchimp', get_class($container->getService('mailchimp.mailchimp')));
