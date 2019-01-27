<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\blitz\drivers\purgers;

use Craft;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;
use putyourlightson\blitz\helpers\SiteUriHelper;
use putyourlightson\blitz\models\SiteUriModel;

/**
 * @property mixed $settingsHtml
 */
class CloudflarePurger extends BaseCachePurger
{
    // Constants
    // =========================================================================

    const API_ENDPOINT = 'https://api.cloudflare.com/client/v4/';

    // Properties
    // =========================================================================

    /**
     * @var string
     */
    public $email;

    /**
     * @var string
     */
    public $apiKey;

    /**
     * @var string
     */
    public $zoneId;

    // Static
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function displayName(): string
    {
        return Craft::t('blitz', 'Cloudflare Purger');
    }

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function attributeLabels(): array
    {
        return [
            'apiKey' => Craft::t('blitz', 'API Key'),
            'zoneId' => Craft::t('blitz', 'Zone ID'),
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['apiKey', 'email', 'zoneId'], 'required'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function purge(SiteUriModel $siteUri)
    {
        $this->_sendRequest('delete', 'purge_cache', [
            'files' => [$siteUri->getUrl()]
        ]);
    }

    /**
     * @inheritdoc
     */
    public function purgeUris(array $siteUris)
    {
        $this->_sendRequest('delete', 'purge_cache', [
            'files' => SiteUriHelper::getUrls($siteUris)
        ]);
    }

    /**
     * @inheritdoc
     */
    public function purgeAll()
    {
        $this->_sendRequest('delete', 'purge_cache', [
            'purge_everything' => true
        ]);
    }

    /**
     * @inheritdoc
     */
    public function test(): bool
    {
        $response = $this->_sendRequest('get');

        if (!$response) {
            return false;
        }

        return $response->getStatusCode() == 200;
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml()
    {
        return Craft::$app->getView()->renderTemplate('blitz/_drivers/purgers/cloudflare/settings', [
            'purger' => $this,
        ]);
    }

    // Private Methods
    // =========================================================================

    /**
     * Sends a request to the API.
     *
     * @param string $method
     * @param string|null $action
     * @param array|null $params
     *
     * @return ResponseInterface|string
     */
    private function _sendRequest(string $method, string $action = '', array $params = [])
    {
        $response = '';

        $client = Craft::createGuzzleClient();

        try {
            $response = $client->request(
                $method,
                self::API_ENDPOINT.'zones/'.$this->zoneId.'/'.$action,
                [
                    'headers'  => [
                        'Content-Type' => 'application/json',
                        'X-Auth-Email' => $this->email,
                        'X-Auth-Key'   => $this->apiKey,
                    ],
                    'json' => $params,
                ]
            );
        }
        catch (BadResponseException $e) { }
        catch (GuzzleException $e) { }

        return $response;
    }
}