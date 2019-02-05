<?php
/**
 * Created by PhpStorm.
 * User: Auoor
 * Date: 2019/2/5
 * Time: 14:47
 */

namespace think;

require_once __DIR__ . '/../vendor/topthink/framework/base.php';

/** @var App $app */
$app = Container::get('app');
$app->path(__DIR__);
$app->initialize();