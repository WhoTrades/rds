<?php
namespace Jira;

class Transition
{
    const START_PROGRESS    = "Start progress";
    const STOP_PROGRESS     = "Stop progress";
    const FINISH_DEVELOPMENT= "Finish Development";
    const FINISH_INTEGRATION_TESTING= "Finish integration testing";
    const FAILED_INTEGRATION_TESTING= "Failed integration testing";
    const DEPLOYED          = "Deployed";
    const ROLL_BACK         = "Rolled back";
    const MERGED_TO_DEVELOP = "Merged to develop";
    const MERGED_TO_STAGING = "Merged to staging";
    const MERGED_TO_MASTER  = "Merged to master";

    public static $transitionMap = [
        self::START_PROGRESS            => [Status::STATUS_READY_FOR_DEVELOPMENT,       Status::STATUS_IN_PROGRESS],

        self::STOP_PROGRESS             => [Status::STATUS_IN_PROGRESS,                 Status::STATUS_READY_FOR_DEVELOPMENT],
        self::FINISH_DEVELOPMENT        => [Status::STATUS_IN_PROGRESS,                 Status::STATUS_IN_CONTINUOUS_INTEGRATION],

        self::FINISH_INTEGRATION_TESTING=> [Status::STATUS_IN_CONTINUOUS_INTEGRATION,   Status::STATUS_CODE_REVIEW],
        self::FAILED_INTEGRATION_TESTING=> [Status::STATUS_IN_CONTINUOUS_INTEGRATION,   Status::STATUS_IN_PROGRESS],

        self::DEPLOYED                  => [Status::STATUS_READY_FOR_DEPLOYMENT,        Status::STATUS_READY_FOR_ACCEPTANCE],
        self::ROLL_BACK                 => [Status::STATUS_READY_FOR_ACCEPTANCE,        Status::STATUS_READY_FOR_DEPLOYMENT],

        self::MERGED_TO_DEVELOP         => [Status::STATUS_MERGE_TO_DEVELOP,            Status::STATUS_WAITING_FOR_TEST],
        self::MERGED_TO_STAGING         => [Status::STATUS_MERGE_TO_STAGING,            Status::STATUS_READY_FOR_CHECK],
        self::MERGED_TO_MASTER          => [Status::STATUS_MERGE_TO_MASTER,             Status::STATUS_READY_FOR_DEPLOYMENT],
    ];
}
