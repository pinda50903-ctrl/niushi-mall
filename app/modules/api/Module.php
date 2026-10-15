<?php

declare(strict_types=1);

namespace app\modules\api;

use Yii;
use yii\base\Module as BaseModule;

/**
 * 用户端模块：/api/*
 */
final class Module extends BaseModule
{
    public $id = 'api';
    public $defaultRoute = 'home/index';

    public function init(): void
    {
        parent::init();
        Yii::configure($this, []);
    }
}
