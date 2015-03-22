# Yii Slack extension
Yii extension for accessing Slack API in Yii framework via Guzzle.

## Installation

**Yii Slack** is composer library so you can install the latest version with `composer require tatarko/yii-slack`.

## Configuration

To your application's config add following:

```
'components' => array(
	'slack' => array(
		'class' => 'Tatarko\\YiiSlack\\ApplicationComponent',
		'appId' => '', // Your's application ID
		'appSecret' => '', // Your's application secret code
	),
)
```

For OAuth authentication add following method to the controller of your choise:

```
<?php

class SiteController extends Controller
{
	public function actions()
    {
        return array(
            'slack' => array(
                'class' => 'Tatarko\\YiiSlack\\AuthenticationAction',
                'successUrl' => array('site/index'),
                'errorUrl' => array('site/login'),
            ),
        );
    }
}
```

## Usage

For simple OAuth you need just make one link visible to user in any view:

```php
<a href="<?= $this->createUrl('site/slack') ?>">Login with Slack</a>
```

After that you can check if current web user is logged using Slack by calling:

```php
var_dump(Yii::app()->slack->isAuthenticated); // boolean
```

And in case that user is really authenticated you can make API call like:

```php
var_dump(Yii::app()->slack->get('auth.test'));

// prints something like:
// array(2) {
//  'ok' =>
//  bool(true)
//  'url' =>
//  string(25) "https://myteam.slack.com/"
// }
```
For additional arguments use:

```php
Yii::app()->slack->post('channels.create', array('name' => 'mychannel'));
```

For complete list of all available methods and their arguments read official [Slack documentation](https://api.slack.com/methods).