<?php

/*
 * This file is part of the YiiSlack package.
 *
 * (c) Tomáš Tatarko <tomas@tatarko.sk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tatarko\YiiSlack;

use Yii;
use CApplicationComponent;
use CException;
use GuzzleHttp\Client;

/**
 * Slack application component
 *
 * Yii application component for accessing Slack API
 * via Guzzle connection interface.
 *
 * @author Tomáš Tatarko <tomas@tatarko.sk>
 * @property-read \GuzzleHttp\Client $connection Active Guzzle connection to Slack API
 * @property-read string $accessToken Access token for Slack API
 * @property-read boolean $isAuthenticated Is current web user authenticated to access Slack API?
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
     */
    public function init()
    {
        $this->_connection = new Client([
            'base_url' => 'https://slack.com/api/',
        ]);
        parent::init();
    }

    /**
     * Gets Guzzle connection instance
     *
     * @return \GuzzleHttp\Client
     * @throws \CException
     */
    public function getConnection()
    {
        if(!$this->isInitialized) {
            throw new CException('Slack application no initialized yet', 0);
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
     * @param array $data Method input arguments
     * @return array Parsed json from response
     */
    public function get($method, array $data = array())
    {
        return $this->getConnection()->get($method, [
            'query' => $data + ['token' => $this->getAccessToken()]
        ])->json();
    }

    /**
     * Makes POST request to requested API method
     *
     * @param string $method Method name to call
     * @param array $data Method input arguments
     * @return array Parsed json from response
     */
    public function post($method, array $data = array())
    {
        return $this->getConnection()->post($method, [
            'query' => $data + ['token' => $this->getAccessToken()]
        ])->json();
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
