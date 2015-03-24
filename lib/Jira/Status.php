<?php
namespace Jira;

class Status
{
    const STATUS_READY_FOR_DEVELOPMENT = "Ready for Develop";
    const STATUS_IN_PROGRESS = "In Progress";
    const STATUS_IN_CONTINUOUS_INTEGRATION = "Continuous Integration";
    const STATUS_CODE_REVIEW = "Code Review";
    const STATUS_MERGE_TO_DEVELOP = "Merge to develop";
    const STATUS_WAITING_FOR_TEST = "Ready for Test";
    const STATUS_TESTING = "Testing";
    const STATUS_MERGE_TO_STAGING = "Merge to staging";
    const STATUS_READY_FOR_CHECK = "Ready for Check";
    const STATUS_CHECKING = "Checking";
    const STATUS_MERGE_TO_MASTER = "Merge to master";
    const STATUS_READY_FOR_DEPLOYMENT = "Ready for Deployment";
    const STATUS_READY_FOR_ACCEPTANCE = "Ready for Acceptance";
    const STATUS_CLOSED = "Closed";
}
