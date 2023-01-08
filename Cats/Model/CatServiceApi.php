<?php

declare(strict_types=1);

namespace Karaev\Cats\Model;

use Magento\Framework\HTTP\Client\Curl;

/**
 * Class CatServiceApi
 * @package Karaev\Cats\Model
 */
class CatServiceApi
{
    private const CAT_SERVICE_API_DOMAIN = 'https://cataas.com';
    private const CAT_RANDOM_SERVICE_API = 'cat';
    private const CAT_SAYS_RANDOM_SERVICE_API = 'says';

    private Curl $curl;

    /**
     * @param Curl $curl
     */
    public function __construct(Curl $curl) {
        $this->curl = $curl;
    }

    /**
     * @return string
     */
    public function getRandomCatApiUrl(): string
    {
        return self::CAT_SERVICE_API_DOMAIN . DIRECTORY_SEPARATOR . self::CAT_RANDOM_SERVICE_API;
    }

    /**
     * @return string
     */
    public function getRandomCatSaysApiUrl(): string
    {
        return self::CAT_SERVICE_API_DOMAIN . DIRECTORY_SEPARATOR .
            self::CAT_RANDOM_SERVICE_API . DIRECTORY_SEPARATOR .
            self::CAT_SAYS_RANDOM_SERVICE_API;
    }

    /**
     * @return string
     */
    public function getRandomCatPicture(): string
    {
        return $this->sendGetRequest($this->getRandomCatApiUrl());
    }

    /**
     * @param string $phrase
     * @return string
     */
    public function getRandomCatPictureWithPhrase(string $phrase): string
    {
        $url = $this->getApiUrlWithPhraseParameter($phrase);

        return $this->sendGetRequest($url);
    }

    /**
     * @param string $url
     * @return string
     */
    public function sendGetRequest(string $url): string
    {
        $this->curl->get($url);

        return $this->curl->getBody();
    }

    /**
     * @param string $phraseParameter
     * @return string
     */
    public function getApiUrlWithPhraseParameter(string $phraseParameter): string
    {
        return $this->getRandomCatSaysApiUrl() . DIRECTORY_SEPARATOR . $phraseParameter;
    }
}
