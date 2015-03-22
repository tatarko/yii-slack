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
use CAction;
use CException;
use CLogger;
use GuzzleHttp\Exception\TransferException;

/**
 * Slack authentication action
 *
 * Generic action that can be assigned into any Yii controller and used
 * for authentication. It stores fetched access token in user's state
 * defined in Slack's application component.
 *
 * @author Tomáš Tatarko <tomas@tatarko.sk>
 */
class AuthenticationAction extends CAction
{
    /**
     * Names of the application component to use
     * @var string
     */
    public $componentId = 'slack';

    /**
     * Arguments that should be passed to redirect after authentication
     * @var array
     */
    public $successUrl = ['/'];

    /**
     * Arguments that should be passed to redirect after authentication
     * @var array
     */
    public $errorUrl = ['/'];

    /**
     * List of scopes that user should be requested for
     * @var array
     */
    public $scopes = ['read'];

    /**
     * Gets Slack application component
     *
     * @return ApplicationComponent
     * @throws \CException
     */
    public function getSlackComponent()
    {
        $component = Yii::app()->getComponent($this->componentId);
        if(!$component instanceof ApplicationComponent) {
            throw new CException('Invalid instance of Slack component', 0);
        }
        return $component;
    }

    /**
     * Authentication Action
     *
     * @param string $code Code used for getting access_token from Slack
     * @param string $error Responsed error message
     */
    public function run($code = null, $error = null)
    {
        if($code) {
            $this->catchCode($code);
        } elseif($error) {
            Yii::log($error, $error == 'invalid_code' ? CLogger::LEVEL_WARNING : CLogger::LEVEL_ERROR, 'slack');
            $this->controller->redirect($this->errorUrl);
        }

        $this->controller->redirect(
            'https://slack.com/oauth/authorize?' . http_build_query([
                'client_id' => $this->getSlackComponent()->appId,
                'redirect_uri' => $this->controller->createAbsoluteUrl($this->id),
                'scope' => implode(',', $this->scopes),
                'state' => Yii::app()->session->sessionID,
            ])
        );
    }

    /**
     * Fetchs access token and stores it into user's state
     *
     * @param string $code
     */
    protected function catchCode($code)
    {
        try {
            $slack = $this->getSlackComponent();
            $response = $slack->get('oauth.access', [
                'client_id' => $slack->appId,
                'client_secret' => $slack->appSecret,
                'code' => $code,
                'redirect_uri' => $this->controller->createAbsoluteUrl($this->id),
            ]);
            Yii::app()->user->setState(
                $slack->tokenStateName,
                $response['access_token']
            );
            $this->controller->redirect($this->successUrl);
        } catch(TransferException $ex) {
            Yii::log($ex, CLogger::LEVEL_ERROR, 'slack');
            $this->controller->redirect($this->errorUrl);
        }
    }
}
