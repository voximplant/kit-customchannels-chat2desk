<?php

use Cache\Adapter\Filesystem\FilesystemCachePool;
use Chat2Desk\Chat2DeskClient;
use Chat2Desk\Repository;
use Chat2Desk\Service;
use GuzzleHttp\Client;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Symfony\Component\Dotenv\Dotenv;
use VoximplantKitIM\Configuration;
use VoximplantKitIM\VoximplantKitIMClient;

// Configuration for logging
$logger = new Monolog\Logger('name');
$logger->pushHandler(new StreamHandler(__DIR__ . '/chat2desc.log', Logger::WARNING));
$logger->pushHandler(new StreamHandler('php://stdout', Logger::WARNING));

$dotenv = new Dotenv();
$dotenv->loadEnv(__DIR__.'/.env');

//Cache configuration for store conversations data
$filesystemAdapter = new Local(__DIR__ . '/cache/');
$filesystem        = new Filesystem($filesystemAdapter);
$cache = new FilesystemCachePool($filesystem);

//Configuration for Voximplant Kit API client
$kitConfig = new Configuration();
$kitConfig->setHost($_ENV['KIT_IM_API_URL']);
$kitConfig->setApiKey('domain', $_ENV['KIT_ACCOUNT_NAME']);
$kitConfig->setApiKey('access_token', $_ENV['KIT_API_TOKEN']);

$kit = new VoximplantKitIMClient($kitConfig);

//Configuration for chat2desc API client
$chat2deskToken = $_ENV['CHAT2DESC_API_TOKEN'];
$chat2DescClient = new Chat2DeskClient(new Client([
    'base_uri' => 'https://api.chat2desk.com',
    'timeout'  => 2.0,
    'http_errors' => false,
    'headers' => [
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
        'Authorization' => $chat2deskToken
    ]
]));

// Your custom channel uuid in Voximplant KIT
$channelUuid = $_ENV['KIT_CHANNEL_UUID'];

$service = new Service(
    $chat2DescClient,
    new Repository($cache),
    $kit,
    $channelUuid
);