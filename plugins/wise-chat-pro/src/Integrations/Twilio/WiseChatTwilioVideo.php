<?php

namespace Kainex\WiseChatPro\Integrations\Twilio;

use Exception;
use Kainex\WiseChatPro\DAO\User\WiseChatUsersDAO;
use Kainex\WiseChatPro\Services\ClientSide\WiseChatClientSide;
use Kainex\WiseChatPro\Services\User\WiseChatActions;
use Kainex\WiseChatPro\Services\User\WiseChatAuthentication;
use Kainex\WiseChatPro\WiseChat;
use Kainex\WiseChatPro\WiseChatCrypt;
use Kainex\WiseChatPro\WiseChatOptions;
use Twilio\Jwt\AccessToken;
use Twilio\Jwt\Grants\VideoGrant;
use Twilio\Rest\Client;
use Twilio\Rest\Video\V1\RoomInstance;

/**
 * WiseChat Twilio Video integration class.
 *
 * @author Kainex <contact@kainex.pl>
 */
class WiseChatTwilioVideo {

	const TOKEN_TIMEOUT = 120;

	/** @var WiseChatAuthentication */
	protected $authentication;

	/** @var WiseChatUsersDAO */
	protected $usersDAO;

	/** @var WiseChatActions */
	protected $actions;

	/** @var WiseChatClientSide */
	protected $clientSide;

	/** @var WiseChatOptions */
	protected $options;

	/**
	 * @param WiseChatAuthentication $authentication
	 * @param WiseChatUsersDAO $usersDAO
	 * @param WiseChatActions $actions
	 * @param WiseChatClientSide $clientSide
	 * @param WiseChatOptions $options
	 */
	public function __construct(WiseChatAuthentication $authentication, WiseChatUsersDAO $usersDAO, WiseChatActions $actions, WiseChatClientSide $clientSide, WiseChatOptions $options) {
		$this->authentication = $authentication;
		$this->usersDAO = $usersDAO;
		$this->actions = $actions;
		$this->clientSide = $clientSide;
		$this->options = $options;
		WiseChat::requireComposerClassLoader();
	}

	/**
	 * @param string $encryptedChannelId
	 * @return array
	 * @throws Exception
	 */
	public function startStream($encryptedChannelId) {
		$twilio = $this->getTwilioClient();

		$channelTypeAndId = WiseChatCrypt::decryptFromString($encryptedChannelId);
		if (strpos($channelTypeAndId, 'd|') !== 0) {
			throw new \Exception('Cannot start video calls in non-direct channels');
		}

		$userId = preg_replace('/^d\|/', '' , $channelTypeAndId);
		if (strpos($userId, 'v') === 0) {
			throw new \Exception('User is offline');
		}

		$recipient = $this->usersDAO->get(intval($userId));
		if ($recipient === null) {
			throw new Exception('Video call participant does not exist');
		}
		if ($recipient->getId() === $this->authentication->getUser()->getId()) {
			throw new Exception('Incorrect participant');
		}

		$roomName = $this->getRoomName(array($this->authentication->getUser()->getId(), $recipient->getId()));
		$room = $this->getOrCreateRoom($twilio, $roomName);

        // create an Access Token
        $token = new AccessToken(
            $this->options->getOption('twilio_account_sid', ''),
            $this->options->getOption('twilio_api_key_sid', ''),
            $this->options->getOption('twilio_api_key_secret', ''),
            self::TOKEN_TIMEOUT,
            $this->authentication->getUser()->getId()
        );

        // Grant access to Video
        $grant = new VideoGrant();
        $grant->setRoom($room->sid);
        $token->addGrant($grant);

        $streamId = sha1(uniqid().time());
        $this->actions->publishAction('incomingStream', array(
        	'id' => $streamId,
        	'type' => 'stream.video.twilio.room.go',
        	'room' => $roomName,
	        'channel' => $this->clientSide->getUserAsPlainDirectChannel($this->authentication->getUser(), array('online' => true))
        ), $recipient->getId());

        return array(
        	'id' => $streamId,
        	'token' => $token->toJWT(),
	        'room' => $roomName,
	        'channel' => array('id' => $encryptedChannelId)
        );
	}

	/**
	 * @param $parameters
	 * @return array
	 * @throws Exception
	 */
	public function getToken($parameters) {
		$this->validateConfiguration();

		$roomName = $parameters['room'];
		$this->validateRoom($roomName);

        // Create an Access Token
        $token = new AccessToken(
            $this->options->getOption('twilio_account_sid', ''),
            $this->options->getOption('twilio_api_key_sid', ''),
            $this->options->getOption('twilio_api_key_secret', ''),
            self::TOKEN_TIMEOUT,
            $this->authentication->getUser()->getId()
        );

        // Grant access to Video
        $grant = new VideoGrant();
        $grant->setRoom($roomName);
        $token->addGrant($grant);

        return array(
        	'token' => $token->toJWT(),
	        'room' => $roomName,
	        'channel' => $parameters['channel']
        );
	}

	private function validateConfiguration() {
		if (!$this->options->isOptionEnabled('enable_private_messages', false)) {
			throw new \Exception('Private messages are not allowed');
		}
		if (!$this->options->isOptionEnabled('video_calls_enabled', false)) {
			throw new \Exception('Video calls are not enabled');
		}
		if (!$this->options->getOption('twilio_account_sid', '') || !$this->options->getOption('twilio_api_key_sid', '') ||
			!$this->options->getOption('twilio_api_key_secret', '')) {
			throw new \Exception('Mission Twilio configuration');
		}
	}

	private function validateRoom($room) {
		if (!preg_match('/^tvr_/', $room)) {
			throw new \Exception('Room does not exist (100)');
		}

		$decrypted = explode(',', WiseChatCrypt::decryptFromString(preg_replace('/^tvr_/', '', $room)));
		if (!is_array($decrypted)) {
			throw new \Exception('Room does not exist (101)');
		}
		if (!in_array($this->authentication->getUser()->getId(), $decrypted)) {
			throw new \Exception('Room does not exist (102)');
		}
	}

	/**
	 * @param integer[] $userIDs
	 * @return string
	 * @throws Exception
	 */
	private function getRoomName($userIDs) {
		if (count($userIDs) !== 2) {
			throw new Exception('Cannot create rooms for more than 2 participants');
		}
		sort($userIDs, SORT_NUMERIC);

		return 'tvr_'.WiseChatCrypt::encryptToString(implode(',', $userIDs));
	}

	/**
	 * @return Client
	 * @throws \Twilio\Exceptions\ConfigurationException
	 */
	private function getTwilioClient() {
		$this->validateConfiguration();

		 return new Client($this->options->getOption('twilio_api_key_sid', ''), $this->options->getOption('twilio_api_key_secret', ''), $this->options->getOption('twilio_account_sid', ''));
	}

	/**
	 * @param Client $client
	 * @param string $roomName
	 * @return RoomInstance
	 * @throws \Twilio\Exceptions\TwilioException
	 */
	private function getOrCreateRoom($client, $roomName) {
		$rooms = $client->video->v1->rooms->read([ "uniqueName" => $roomName ], 1);
		if (count($rooms) > 0) {
			return $rooms[0];
		}

		return $client
            ->video
            ->v1
            ->rooms
            ->create([
				'mediaRegion' => $this->options->getOption('twilio_video_room_media_region', 'de1'),
				'uniqueName' => $roomName,
				'type' =>  $this->options->getOption('twilio_video_room_type', 'go')
            ])
        ;
	}

}