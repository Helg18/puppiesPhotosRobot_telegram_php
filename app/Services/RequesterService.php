<?php
namespace App\Services;


use GuzzleHttp\Client;

class RequesterService
{
    public function __construct()
    {
        $this->client = new Client();
    }

    public function getImgLink()
    {
        $response = $this->client->get("https://dog.ceo/api/breeds/image/random");
        $response = $response->getBody()->getContents();

        return json_decode(utf8_encode($response));
    }
}