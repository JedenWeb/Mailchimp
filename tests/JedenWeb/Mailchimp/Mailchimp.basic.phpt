<?php

require __DIR__ . '/../../bootstrap.php';

$mockista = new \Mockista\Registry();

$wrapper = new \Kdyby\Curl\CurlWrapper(NULL);
$wrapper->response = '{"email_address": "test@example.com", "status": "subscribed"}';

$response = new \Kdyby\Curl\Response($wrapper, [
	'Status-Code' => 200,
]);

/** @var \Kdyby\Curl\CurlSender $sender */
$sender = $mockista->create('Kdyby\Curl\CurlSender');
$sender->expects('send')->andReturn($response);

$mailchimp = new \JedenWeb\Mailchimp\Mailchimp('localhost', '123', 'abc', $sender);

\Tester\Assert::null($mailchimp->subscribe('test@example.com'));
