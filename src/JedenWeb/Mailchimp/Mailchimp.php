<?php

namespace JedenWeb\Mailchimp;

use Kdyby\Curl\BadStatusException;
use Kdyby\Curl\CurlException;
use Kdyby\Curl\CurlSender;
use Kdyby\Curl\Request;
use Tracy\Debugger;
use Nette\Utils\Json;

/**
 * @author Pavel Jurásek
 */
class Mailchimp
{

	const STATUS_SUBSCRIBED = 'subscribed';
	const STATUS_UNSUBSCRIBED = 'unsubscribed';

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
	 * @param string|NULL $listId
	 *
	 * @return bool|NULL
	 */
	public function subscribe($email, $listId = NULL)
	{
		$listId = $listId ?: $this->listId;
		$status = $this->getStatus($email, $listId);

		$data = ['email_address' => $email, 'status' => self::STATUS_SUBSCRIBED];

		if ($status === FALSE) {
			$response = $this->call(Request::POST, "/lists/$listId/members", $data);

			return $response && $response->getCode() === 200;
		} else if ($status !== self::STATUS_SUBSCRIBED) {
			$response = $this->call(Request::PATCH, "/lists/$listId/members/".md5($email), [
				'status' => self::STATUS_SUBSCRIBED,
			]);

			return $response && $response->getCode() === 200;
		}
	}

	/**
	 * @param string $email
	 * @param string|NULL $listId
	 *
	 * @return bool|NULL
	 */
	public function unsubscribe($email, $listId = NULL)
	{
		$listId = $listId ?: $this->listId;
		$status = $this->getStatus($email, $listId);
		$data = ['email_address' => $email, 'status' => self::STATUS_UNSUBSCRIBED];

		if ($status === FALSE) {	// not in Mailchimp at all, add new entry as ubsubscribed
			$response = $this->call(Request::POST, "/lists/$listId/members", $data);

			return $response && $response->getCode() === 200;
		} else if ($status !== self::STATUS_UNSUBSCRIBED) {	// only update is user is not in the ubsubscribed status
			$response = $this->call(Request::PATCH, "/lists/$listId/members/".md5($email), [
				'status' => self::STATUS_UNSUBSCRIBED,
			]);

			return $response && $response->getCode() === 200;
		}
	}


	/**
	 * @param string $email
	 * @param string $listId
	 *
	 * @return string|FALSE
	 */
	private function getStatus($email, $listId)
	{
		$response = $this->call(Request::GET, "/lists/$listId/members/".md5($email));

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
