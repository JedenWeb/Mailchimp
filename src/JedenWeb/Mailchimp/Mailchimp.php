<?php

namespace JedenWeb\Mailchimp;

use Kdyby\Curl\BadStatusException;
use Kdyby\Curl\CurlException;
use Kdyby\Curl\CurlSender;
use Kdyby\Curl\Request;
use Nette\Diagnostics\Debugger;
use Nette\Utils\Json;

/**
 * @author Pavel Jurásek
 */
class Mailchimp
{

	/** @var string */
	private $url;

	/** @var string */
	private $apiKey;

	/** @var string */
	private $listId;

	/** @var CurlSender */
	private $sender;


	public function __construct($url, $apiKey, $listId, CurlSender $sender)
	{
		$this->url = rtrim($url, '/');
		$this->apiKey = $apiKey;
		$this->listId = $listId;
		$this->sender = $sender;

		$this->sender->headers['Content-Type'] = 'application/json';
	}


	/**
	 * @param string $email
	 *
	 * @return bool|NULL
	 */
	public function subscribe($email)
	{
		$status = $this->getStatus($email);

		$data = ['email_address' => $email, 'status' => 'subscribed'];

		if ($status === FALSE) {
			$response = $this->call(Request::POST, "/lists/$this->listId/members", $data);

			return $response->getCode() === 200;
		} else if ($status !== 'subscribed') {
			$response = $this->call(Request::PATCH, "/lists/$this->listId/members/".md5($email), [
				'status' => 'subscribed',
			]);

			return $response->getCode() === 200;
		}
	}


	/**
	 * @param string $email
	 *
	 * @return string|FALSE
	 */
	private function getStatus($email)
	{
		$response = $this->call(Request::GET, "/lists/$this->listId/members/".md5($email));

		if (!$response || $response->getCode() === 404) {
			return FALSE;
		}

		return Json::decode($response->getResponse())->status;
	}


	/**
	 * @param string $method
	 * @param string $endpoint
	 * @param array $data
	 *
	 * @return \Kdyby\Curl\Response|NULL
	 */
	private function call($method, $endpoint, $data = [])
	{
		$request = $this->createRequest($endpoint);
		$data = Json::encode($data);

		try {
			if ($method === Request::GET) {
				$response = $request->get($data);
			} else {
				$request->post = $data;
				$request->setMethod($method);

				$response = $request->send();
			}
		} catch (CurlException $e) {
			if ($e instanceof BadStatusException) {
				$response = $e->getResponse();

				if ($response->getCode() !== 404) {
					Debugger::log($e);
					return NULL;
				}
			} else {
				throw $e;
			}
		}

		return $response;
	}


	/**
	 * @param string $endpoint
	 *
	 * @return Request
	 */
	private function createRequest($endpoint)
	{
		$request = new Request($this->url.$endpoint);
		$request->setSender($this->sender);
		$request->headers['Authorization'] = 'apikey '. $this->apiKey;
		return $request;
	}

}
