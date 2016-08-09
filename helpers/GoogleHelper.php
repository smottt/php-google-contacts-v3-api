<?php

namespace rapidweb\googlecontacts\helpers;

abstract class GoogleHelper
{
    private static $accessToken;

    private static function loadConfig()
    {
        return json_decode(
            file_get_contents(__DIR__ . '/../.config.json'),
            true
        );
    }

    public static function setAccessToken(array $accessToken)
    {
        self::$accessToken = $accessToken;
    }

    public static function getClient()
    {
        $client = new \Google_Client(static::loadConfig());

        $client->setApplicationName('Rapid Web Google Contacts API');

        $client->setScopes(array(/*
        'https://apps-apis.google.com/a/feeds/groups/',
        'https://www.googleapis.com/auth/userinfo.email',
        'https://apps-apis.google.com/a/feeds/alias/',
        'https://apps-apis.google.com/a/feeds/user/',*/
        'https://www.google.com/m8/feeds/',
        /*'https://www.google.com/m8/feeds/user/',*/
        ));

        if (self::$accessToken) {
            $client->setAccessToken(self::$accessToken);
        }

        // TODO
//         if (isset($config->refreshToken) && $config->refreshToken) {
//             $client->refreshToken($config->refreshToken);
//         }

        return $client;
    }

    public static function getAuthUrl(\Google_Client $client)
    {
        return $client->createAuthUrl();
    }

    public static function authenticate(\Google_Client $client, $code)
    {
        return $client->fetchAccessTokenWithAuthCode($code);
    }

    public static function getAccessToken(\Google_Client $client)
    {
        return json_encode($client->getAccessToken());
    }

    public static function doRequest($method, $url, $body = null)
    {
        $client = self::getClient()->authorize(new \GuzzleHttp\Client());

        $options = [
            'headers' => [
                'Content-Type' => 'application/atom+xml; charset=UTF-8; type=entry'
            ]
        ];

        if (isset($body)) {
            $options['body'] = $body;
        }

        $response = $client->request($method, $url, $options);

        return $response->getBody()->getContents();
    }
}
