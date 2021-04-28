<?php

namespace Chat2Desk;

use Exception;
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;

class Chat2DeskClient
{
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    public function searchContact($email) {
        $res = $this->client->request('get', '/v1/operators', [
            'query' => [
                'email' => $email
            ]
        ]);

        $rjson = json_decode($res->getBody()->getContents());
        if ($res->getStatusCode() === 200) {
            return $rjson->data[0]->id;
        }

        return null;
    }

    public function reply($clientId, $channelId, $operatorId, $text) {
        $response = $this->client->request('post', '/v1/messages', [
            'json' => [
                'client_id' => intval($clientId),
                'text' => $text,
                'type' => 'to_client',
                'channel_id' => intval($channelId),
                'operator_id' => intval($operatorId),
                'transport' => 'widget'
            ]
        ]);
        return $this->parseResponse($response);
    }

    public function closeRequest($clientId) {
        $response = $this->client->request('put', '/v1/requests/close', [
            'json' => [
                'client_id' => intval($clientId)
            ]
        ]);

        return $this->parseResponse($response);
    }

    public function closeDialog($dialogId, $operatorId = null) {
        $response = $this->client->request('put', '/v1/dialogs/'.$dialogId, [
            'json' => [
                'operator_id' => $operatorId ? (int) $operatorId : null,
                'state' => 'closed',
            ]
        ]);

        return $this->parseResponse($response);
    }

    public function openDialog($dialogId, $operatorId = null) {
        $response = $this->client->request('put', '/v1/dialogs/'.$dialogId, [
            'json' => [
                'operator_id' => $operatorId ? (int) $operatorId : null,
                'state' => 'open',
            ]
        ]);

        return $this->parseResponse($response);
    }

    protected function parseResponse(ResponseInterface $response): array
    {
        $responseArray = json_decode($response->getBody()->getContents(), true);

        if ($response->getStatusCode() !== 200) {
            throw new Exception($response->getReasonPhrase(), $response->getStatusCode());
        }

        return $responseArray;
    }
}
