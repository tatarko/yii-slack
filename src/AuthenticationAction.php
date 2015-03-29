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
use CAction;
use CEvent;
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
 * @category Connections
 * @package  YiiSlack
 * @author   Tomáš Tatarko <tomas@tatarko.sk>
 * @license  http://choosealicense.com/licenses/mit/ MIT
 * @link     https://github.com/tatarko/yii-slack Official repository
 */
class AuthenticationAction extends CAction
{
    /**
     * Names of the application component to use
     * @var string
     */
    public $componentId = 'slack';

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
        if (!$component instanceof ApplicationComponent) {
            throw new CException('Invalid instance of Slack component', 0);
        }
        return $component;
    }

    /**
     * Authentication Action
     *
     * @param string $code  Code used for getting access_token from Slack
     * @param string $error Responsed error message
     *
     * @return void
     */
    public function run($code = null, $error = null)
    {
        if ($code) {
            $this->catchCode($code);
        } elseif ($error) {
            $this->onAuthError(
                new CEvent(
                    $this,
                    new CException("slack error: $error", 0)
                )
            );
        } else {
            $this->controller->redirect(
                'https://slack.com/oauth/authorize?' . http_build_query(
                    [
                        'client_id' => $this->getSlackComponent()->appId,
                        'redirect_uri' => $this->controller->createAbsoluteUrl(
                            $this->id
                        ),
                        'scope' => implode(',', $this->scopes),
                        'state' => Yii::app()->session->sessionID,
                    ]
                )
            );
        }
    }

    /**
     * Fetchs access token and stores it into user's state
     *
     * @param string $code Request code returned from Slack
     *
     * @return void This method is used only internally
     */
    protected function catchCode($code)
    {
        try {
            $slack = $this->getSlackComponent();
            $response = $slack->get(
                'oauth.access',
                [
                    'client_id' => $slack->appId,
                    'client_secret' => $slack->appSecret,
                    'code' => $code,
                    'redirect_uri' => $this->controller->createAbsoluteUrl(
                        $this->id
                    ),
                ]
            );

            if (isset($response['access_token'])) {
                Yii::app()->user->setState(
                    $slack->tokenStateName,
                    $response['access_token']
                );
                $this->onAuthSuccess(new CEvent($this, $response));
            } else {
                $this->onAuthError(
                    new CEVent(
                        $this,
                        new CException('Missing access token in API response', 0)
                    )
                );
            }
        } catch(TransferException $ex) {
            $this->onAuthError(new CEvent($this, $ex));
        }
    }

    /**
     * Event triggered after successful authetification
     *
     * @param \CEvent $event Instance of event to be passed
     *
     * @return void Internally used for raising event
     */
    public function onAuthSuccess($event)
    {
        Yii::log(json_encode($event->params), CLogger::LEVEL_INFO, 'slack');
        $this->raiseEvent('onAuthSuccess', $event);
        $this->controller->redirect(Yii::app()->homeUrl);
    }

    /**
     * Event triggered after unsuccessful authetification
     *
     * @param \CEvent $event Instance of event to be passed
     *
     * @return void Internally used for raising event
     */
    public function onAuthError($event)
    {
        Yii::log((string)$event->params, CLogger::LEVEL_ERROR, 'slack');
        $this->raiseEvent('onAuthError', $event);
        $this->controller->redirect(Yii::app()->homeUrl);
    }
}
