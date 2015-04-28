<?php

/**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * PHP Version 5.4
 *
 * @category Connections
 * @package  YiiSlack
 * @author   Tomáš Tatarko <tomas@tatarko.sk>
 * @license  http://choosealicense.com/licenses/mit/ MIT
 * @link     https://github.com/tatarko/yii-slack Official repository
 */

namespace Tatarko\YiiSlack;

use Yii;
use CApplicationComponent;
use CException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\StateException;

/**
 * Slack application component
 *
 * Yii application component for accessing Slack API
 * via Guzzle connection interface.
 *
 * @category      Connections
 * @package       YiiSlack
 * @author        Tomáš Tatarko <tomas@tatarko.sk>
 * @license       http://choosealicense.com/licenses/mit/ MIT
 * @link          https://github.com/tatarko/yii-slack Official repository
 * @property-read \GuzzleHttp\Client $connection Active Guzzle connection
 * @property-read string $accessToken Access token for Slack API
 * @property-read boolean $isAuthenticated Is current web user authenticated?
 */
class ApplicationComponent extends CApplicationComponent
{
    /**
     * Your application's ID
     * @var string
     */
    public $appId;

    /**
     * Your application's secret code
     * @var string
     */
    public $appSecret;

    /**
     * Name of the user's state to store access token
     * @var string
     */
    public $tokenStateName = 'slack.access.token';

    /**
     * Company's global access token
     * @var string
     */
    public $companyToken;

    /**
     * Instance of Guzzle connection
     * @var \GuzzleHttp\Client
     */
    private $_connection;

    /**
     * Slack Application Component initialization
     *
     * @return void Interface implementation
     */
    public function init()
    {
        $this->_connection = new Client(
            [
            'base_url' => 'https://slack.com/api/',
            ]
        );
        parent::init();
    }

    /**
     * Gets Guzzle connection instance
     *
     * @return \GuzzleHttp\Client
     */
    public function getConnection()
    {
        if (!$this->isInitialized) {
            $this->init();
        }

        return $this->_connection;
    }

    /**
     * Gets access token
     *
     * This method tries to fetch access token from user's state
     * with fallback to default access token of application
     *
     * @return string
     */
    public function getAccessToken()
    {
        try {
            return Yii::app()->user->getState(
                $this->tokenStateName,
                $this->companyToken
            );
        } catch(CException $ex) {
            return $this->companyToken;
        }
    }

    /**
     * Makes GET request to requested API method
     *
     * @param string $method Method name to call
     * @param array  $data   Method input arguments
     *
     * @return array Parsed json from response
     * @throws \GuzzleHttp\Exception\StateException In case that response flag
     * "ok" is not set correctly
     */
    public function get($method, array $data = array())
    {
        $response = $this->getConnection()->get(
            $method, [
                'query' => $data + ['token' => $this->getAccessToken()]
            ]
        )->json();

        if (!isset($response['ok']) || !$response['ok']) {
            throw new StateException('Incorrect response', 0);
        }

        return $response;
    }

    /**
     * Makes POST request to requested API method
     *
     * @param string $method Method name to call
     * @param array  $data   Method input arguments
     *
     * @return array Parsed json from response
     * @throws \GuzzleHttp\Exception\StateException In case that response flag
     * "ok" is not set correctly
     */
    public function post($method, array $data = array())
    {
        $response = $this->getConnection()->post(
            $method, [
                'body' => $data + ['token' => $this->getAccessToken()]
            ]
        )->json();

        if (!isset($response['ok']) || !$response['ok']) {
            throw new StateException('Incorrect response', 0);
        }

        return $response;
    }

    /**
     * Checks if current's web user is authenticated
     *
     * @return boolean
     */
    public function getIsAuthenticated()
    {
        return Yii::app()->user->hasState($this->tokenStateName);
    }
}
