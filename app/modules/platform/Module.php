<?php

declare(strict_types=1);

namespace app\modules\platform;

use Yii;
use yii\base\Module as BaseModule;

/**
 * 平台总部模块：/platform/*
 */
final class Module extends BaseModule
{
    public $id = 'platform';
    public $defaultRoute = 'overview/index';

    public function init(): void
    {
        parent::init();
        Yii::configure($this, []);
    }
}
