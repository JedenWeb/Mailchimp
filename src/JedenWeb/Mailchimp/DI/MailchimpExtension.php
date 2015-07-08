<?php

namespace JedenWeb\Mailchimp\DI;

use JedenWeb\Mailchimp\InvalidStateException;
use Nette\DI\CompilerExtension;

/**
 * @author Pavel Jurásek
 */
class MailchimpExtension extends CompilerExtension
{

	/** @var array */
	private $defaults = [
		'url' => 'https://us11.api.mailchimp.com/3.0/',
	];


	public function loadConfiguration()
	{
		$config = $this->getConfig($this->defaults);
		$this->validate($config);
		$container = $this->getContainerBuilder();

		$container->addDefinition($this->prefix('mailchimp'))
			->setClass('JedenWeb\Mailchimp\Mailchimp', [$config['url'], $config['apiKey'], $config['listId']]);
	}


	/**
	 * @param array $config
	 */
	private function validate(array $config)
	{
		$required = ['url', 'apiKey', 'listId'];

		foreach ($required as $item) {
			if (!array_key_exists($item, $config)) {
				throw new InvalidStateException("Missing configuration option $this->name.$item.");
			}
		}
	}

}
