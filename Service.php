<?php

namespace Chat2Desk;

use Exception;
use VoximplantKitIM\Model\MessagingEventMessageType;
use VoximplantKitIM\Model\MessagingIncomingEventType;
use VoximplantKitIM\Model\MessagingIncomingEventTypeClientData;
use VoximplantKitIM\Model\MessagingIncomingEventTypeEventData;
use Ramsey\Uuid\Uuid;
use VoximplantKitIM\Model\MessagingOutgoingChatCloseEventType;
use VoximplantKitIM\Model\MessagingOutgoingNewMessageEventType;
use VoximplantKitIM\ObjectSerializer;
use VoximplantKitIM\VoximplantKitIMClient;

class Service
{
    /** @var VoximplantKitIMClient */
    private $kit;

    /** @var string */
    private $channelUUID;

    /** @var Chat2DeskClient */
    private $chat2Desk;

    /**
     * @var Repository
     */
    private $repository;

    public function __construct(Chat2DeskClient $chat2Desc, Repository $repository, VoximplantKitIMClient $kit, string $channelUUID)
    {
        $this->kit = $kit;
        $this->repository = $repository;
        $this->chat2Desk = $chat2Desc;
        $this->channelUUID = $channelUUID;
    }

    public function login()
    {
        $jwt = $this->kit->botservice->login($this->channelUUID);

        if (!$jwt->getSuccess()) {
            throw new Exception(json_encode($jwt->getResult()));
        }

        $this->kit->getConfig()->setAccessToken($jwt->getResult()->getAccessToken());
    }

    public function handleChat2DeskEvent(string $event)
    {
        $incoming = json_decode($event);

        if (!isset($incoming->type) || $incoming->type !== 'from_client') {
            return;
        }

        $info = [
            'client_id' => $incoming->client_id,
            'channel_id' => $incoming->channel_id,
            'dialog_id' => $incoming->dialog_id,
            'operator_id' => $incoming->operator_id,
        ];

        $this->repository->saveClientConversation($incoming->client_id, $info);

        $event = new MessagingIncomingEventType();
        $client = new MessagingIncomingEventTypeClientData();
        $client->setClientId((string) $incoming->client_id);
        if (!is_null($incoming->client->assigned_name)) {
            $client->setClientDisplayName($incoming->client->assigned_name);
        } elseif (!is_null($incoming->client->name)) {
            $client->setClientDisplayName($incoming->client->name);
        } else {
            $client->setClientDisplayName($incoming->client->phone);
        }

        $event->setClientData($client);
        $event->setEventId(Uuid::uuid4()->toString());
        $event->setEventType(MessagingIncomingEventType::EVENT_TYPE_MESSAGE);

        $message = new MessagingEventMessageType();
        $message->setMessageId((string) $incoming->message_id);
        $message->setText(strip_tags($incoming->text));

        $eventData = (new MessagingIncomingEventTypeEventData())->setMessage($message);
        $event->setEventData($eventData);

        $resp = $this->kit->botservice->sendEvent($event, $this->channelUUID);

        if (!$resp->getSuccess()) {
            throw new Exception(json_encode($resp->getResult()));
        }
    }

    public function handleKitEvent(string $event)
    {
        $eventObj = json_decode($event);
        if ($eventObj->event_type == 'send_message') {
            $kitEvent =  ObjectSerializer::deserialize(json_decode($event), MessagingOutgoingNewMessageEventType::class);

            $info = $this->repository->getClientConversation($kitEvent->getClientData()->getClientId());

            $senderId = $kitEvent->getEventData()->getSenderData()->getSenderId();
            $senderEmail = $kitEvent->getEventData()->getSenderData()->getSenderEmail();

            if ($this->repository->existsUser($senderId)) {
                $operatorId = $this->repository->getOperatorIdByUser($senderId);
            } else {
                $operatorId = $this->chat2Desk->searchContact($senderEmail);
                $this->repository->saveOperatorIdByUser($senderId, $operatorId);
            }

            if ($info->operator_id == null) {
                $this->chat2Desk->openDialog($info->dialog_id, $operatorId);
                $info->operator_id = $operatorId;
                $this->repository->saveClientConversation($info->client_id, $info);
            }

            $this->chat2Desk->reply($kitEvent->getClientData()->getClientId(), $info->channel_id, $operatorId, $kitEvent->getEventData()->getMessage()->getText());
        } elseif ($eventObj->event_type == 'close_conversation') {
            $kitCloseEvent =  ObjectSerializer::deserialize(json_decode($event), MessagingOutgoingChatCloseEventType::class);
            $info = $this->repository->getClientConversation($kitCloseEvent->getClientData()->getClientId());

            $this->chat2Desk->closeDialog($info->dialog_id, $info->operator_id);
        }
    }
}
