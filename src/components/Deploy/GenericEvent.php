<?php
/**
 * @author Maksim Rodikov
 */
declare(strict_types=1);

namespace whotrades\rds\components\Deploy;

use whotrades\rds\models\Build;
use whotrades\rds\models\Project;
use whotrades\RdsSystem\Message\Base;
use \yii\base\Event as EventBase;

class GenericEvent extends EventBase
{
    /** @var Base */
    public $message;

    /** @var Build */
    public $build;

    /** @var Project */
    public $project;

    /** @var string */
    public $projectOldVersion;
}