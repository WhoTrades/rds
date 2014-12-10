<?php
namespace Jira;

class Status
{
    const STATUS_READY_FOR_DEVELOPMENT = "Готово к разработке";
    const STATUS_IN_PROGRESS = "В работе";
    const STATUS_IN_CONTINUOUS_INTEGRATION = "Continuous Integration";
    const STATUS_CODE_REVIEW = "Проверка кода";
    const STATUS_MERGE_TO_DEVELOP = "Merge to develop";
    const STATUS_WAITING_FOR_TEST = "Ожидает тестирование";
    const STATUS_TESTING = "Тестирование";
    const STATUS_MERGE_TO_STAGING = "Merge to staging";
    const STATUS_READY_FOR_CHECK = "Ready for Check";
    const STATUS_CHECKING = "Checking";
    const STATUS_MERGE_TO_MASTER = "Merge to master";
    const STATUS_READY_FOR_DEPLOYMENT = "Готово к выкладке";
    const STATUS_READY_FOR_ACCEPTANCE = "Готово к приемке";
    const STATUS_CLOSED = "Закрыт";
}
