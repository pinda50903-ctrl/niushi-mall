<?php

declare(strict_types=1);

namespace app\modules\manage;

use Yii;
use yii\base\Module as BaseModule;

/**
 * 商城管理端模块：/manage/*
 */
final class Module extends BaseModule
{
    public $id = 'manage';
    public $defaultRoute = 'dashboard/index';

    public function init(): void
    {
        parent::init();
        Yii::configure($this, []);
    }
}
