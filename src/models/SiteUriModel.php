<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\blitz\models;

use Craft;
use craft\base\Model;
use craft\helpers\UrlHelper;
use putyourlightson\blitz\Blitz;
use yii\base\Exception;

/**
 * @property string $url
 * @property bool $isCacheableUri
 */
class SiteUriModel extends Model
{
    // Constants
    // =========================================================================

    /**
     * @const int
     */
    const MAX_URI_LENGTH = 255;

    // Public Properties
    // =========================================================================

    /**
     * @var int
     */
    public $siteId;

    /**
     * @var string
     */
    public $uri;

    // Public Methods
    // =========================================================================

    /**
     * Returns the absolute URL.
     */
    public function getUrl(): string
    {
        $site = Craft::$app->getSites()->getSiteById($this->siteId);

        if ($site === null) {
            return '';
        }

        return $site->getBaseUrl().$this->uri;
    }

    /**
     * Returns whether the URI is cacheable.
     *
     * @return bool
     */
    public function getIsCacheableUri(): bool
    {
        // Ignore URIs that contain index.php
        if (strpos($this->uri, 'index.php') !== false) {
            Blitz::$plugin->debug('Page not cached because the URL contains `index.php`.');

            return false;
        }

        // Ignore URIs that are longer than the max URI length
        if (strlen($this->uri) > self::MAX_URI_LENGTH) {
            Blitz::$plugin->debug('Page not cached because it exceeds the max URL length of {max} characters.', [
                'max' => self::MAX_URI_LENGTH
            ]);

            return false;
        }

        // Excluded URI patterns take priority
        if ($this->_matchesUriPatterns(Blitz::$plugin->settings->excludedUriPatterns)) {
            Blitz::$plugin->debug('Page not cached because it matches an excluded URI pattern.');

            return false;
        }

        if (!$this->_matchesUriPatterns(Blitz::$plugin->settings->includedUriPatterns)) {
            Blitz::$plugin->debug('Page not cached because it does not match an included URI pattern.');

            return false;
        }

        return true;
    }

    // Private Methods
    // =========================================================================

    /**
     * Returns true if the URI matches a set of patterns.
     *
     * @param array|string $siteUriPatterns
     *
     * @return bool
     */
    private function _matchesUriPatterns($siteUriPatterns): bool
    {
        if (!is_array($siteUriPatterns)) {
            return false;
        }

        foreach ($siteUriPatterns as $siteUriPattern) {
            // Don't proceed if site is not empty and does not match the provided site ID
            if (!empty($siteUriPattern['siteId']) && $siteUriPattern['siteId'] != $this->siteId) {
                continue;
            }

            $uriPattern = $siteUriPattern['uriPattern'];

            // Replace a blank string with the homepage
            if ($uriPattern == '') {
                $uriPattern = '^$';
            }

            // Replace "*" with 0 or more characters as otherwise it'll throw an error
            if ($uriPattern == '*') {
                $uriPattern = '.*';
            }

            // Trim slashes
            $uriPattern = trim($uriPattern, '/');

            // Escape hash symbols
            $uriPattern = str_replace('#', '\#', $uriPattern);

            if (preg_match('#'.$uriPattern.'#', trim($this->uri, '/'))) {
                return true;
            }
        }

        return false;
    }
}
