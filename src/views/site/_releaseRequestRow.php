<?php return array(
    'obj_id',
    'obj_created',
    'rr_user',
    [
        'name' => 'rr_comment',
        'value' => function(ReleaseRequest $releaseRequest){
            echo $releaseRequest->rr_comment."<br />";

            if ($releaseRequest->isInstalledStatus()) {
                echo "<a href='".$this->createUrl('/jira/gotoJiraTicketsByReleaseRequest', ['id' => $releaseRequest->obj_id])."' target='_blank'>Тикеты</a><br />";
            }
        }

    ],
    array(
        'value' => function(ReleaseRequest $releaseRequest){
            $map = array(
                ReleaseRequest::STATUS_NEW => array('time', 'Ожидает сборки', 'black'),
                ReleaseRequest::STATUS_FAILED => array('remove', 'Не собралось', 'red'),
                ReleaseRequest::STATUS_INSTALLED => array('ok', 'Установлено', 'black'),
                ReleaseRequest::STATUS_USING=> array('refresh', 'Активируем', 'orange'),
                ReleaseRequest::STATUS_CODES=> array('time', 'Ждем ввода кодов', 'orange'),
                ReleaseRequest::STATUS_USED=> array('ok', 'Активная версия', '#32cd32'),
                ReleaseRequest::STATUS_USED_ATTEMPT=> array('time', 'Временная версия', 'blue'),
                ReleaseRequest::STATUS_OLD=> array('time', 'Старая версия', 'grey'),
                ReleaseRequest::STATUS_CANCELLING=> array('refresh', 'Отменяем...', 'orange'),
                ReleaseRequest::STATUS_CANCELLED=> array('ok', 'Отменено', 'red'),
            );
            list($icon, $text, $color) = $map[$releaseRequest->rr_status];
            echo "<span title='{$text}' style='color: ".$color."'><span class='icon-$icon'></span>{$releaseRequest->rr_status}</span><hr />";

            $result = array();
            foreach ($releaseRequest->builds as $val) {
                $map = array(
                    Build::STATUS_FAILED => array('remove', 'Не собралось', 'red'),
                    Build::STATUS_BUILDING => array('refresh', 'Собирается', 'orange'),
                    Build::STATUS_NEW => array('time', 'Ожидает сборки', 'black'),
                    Build::STATUS_BUILT => array('upload', 'Раскладывается по серверам', 'orange'),
                    Build::STATUS_INSTALLED => array('ok', 'Скопировано на сервер', 'black'),
                    Build::STATUS_USED=> array('ok', 'Установлено', '#32cd32'),
                    ReleaseRequest::STATUS_CANCELLED=> array('remove', 'Отменено', 'red'),
                );
                list($icon, $text, $color) = $map[$val->build_status];
                $result[] =  "<a href='".$this->createUrl('build/view', array('id' => $val->obj_id))."' title='{$text}' style='color: $color'><span class='icon-$icon'></span>{$val->worker->worker_name} - {$val->build_status} {$val->project->project_name} {$val->build_version}</a>";

                if ($val->build_status == Build::STATUS_BUILDING) {
                    $info = $val->getProgressbarInfo();
                    if ($info) {
                        list($percent, $currentKey) = $info;
                        $result[] = '
                            <div class="progress progress-'.$val->obj_id.'" style="margin: 0; width: 250px;">
                            <div class="bar" role="progressbar"style="width: '.$percent.'%;white-space:nowrap; color:#FFA500; padding-left: 5px">
                                <b>'.sprintf("%.2f", $percent).'%:</b> '.$currentKey.'
                            </div>
                            </div>';
                    }
                }
            }

            return implode("<br />", $result);
            },
        'name' => 'rr_status',
        'filter' => array(
            ReleaseRequest::STATUS_NEW => ReleaseRequest::STATUS_NEW,
            ReleaseRequest::STATUS_FAILED => ReleaseRequest::STATUS_FAILED,
            ReleaseRequest::STATUS_INSTALLED => ReleaseRequest::STATUS_INSTALLED,
            ReleaseRequest::STATUS_USING => ReleaseRequest::STATUS_USING,
            ReleaseRequest::STATUS_CODES => ReleaseRequest::STATUS_CODES,
            ReleaseRequest::STATUS_USED => ReleaseRequest::STATUS_USED,
            ReleaseRequest::STATUS_USED_ATTEMPT => ReleaseRequest::STATUS_USED_ATTEMPT,
            ReleaseRequest::STATUS_OLD => ReleaseRequest::STATUS_OLD,
            ReleaseRequest::STATUS_CANCELLING => ReleaseRequest::STATUS_CANCELLING,
            ReleaseRequest::STATUS_CANCELLED => ReleaseRequest::STATUS_CANCELLED,
        ),
        'type' => 'html',
    ),
    array(
        'name' => 'rr_project_obj_id',
        'value' => function($r){
                return $r->builds[0]->project->project_name;
            },
        'filter' => \Project::model()->forList(),
    ),
    array(
        'name' => 'rr_build_version',
        'value' => function(ReleaseRequest $r){
                if ($r->rr_built_time) {
                    $time = strtotime($r->rr_built_time) - strtotime($r->obj_created);
                    return $r->rr_build_version."<br /><br />Собрано за <b>$time</b> сек.";
                } else {
                    return $r->rr_build_version;
                }
            },
        'type' => 'html',
    ),
    array(
        'value' => function(ReleaseRequest $releaseRequest){


                if ($releaseRequest->canBeUsed()) {
                static $currentUsedCache = [];

                $currentUsed = isset($currentUsedCache[$releaseRequest->rr_project_obj_id])
                    ? $currentUsedCache[$releaseRequest->rr_project_obj_id]
                    : \ReleaseRequest::model()->findByAttributes([
                            'rr_project_obj_id' => $releaseRequest->rr_project_obj_id,
                            'rr_status' => [\ReleaseRequest::STATUS_USED, \ReleaseRequest::STATUS_USED_ATTEMPT],
                        ]);

                if ($currentUsed && $currentUsed->rr_cron_config != $releaseRequest->rr_cron_config) {
                    $diffStat = Yii::app()->diffStat->getDiffStat($currentUsed->rr_cron_config, $releaseRequest->rr_cron_config);
                    $diffStat = preg_replace('~\++~', '<span style="color: #32cd32">$0</span>', $diffStat);
                    $diffStat = preg_replace('~\-+~', '<span style="color: red">$0</span>', $diffStat);
                    echo "<a href='".$this->createUrl('/diff/index/', ['id1' => $releaseRequest->obj_id, 'id2' => $currentUsed->obj_id])."'>
                CRON changed<br />$diffStat
            </a><br />";
                }

                if ($releaseRequest->hardMigrations) {
                    echo "<a href='".$this->createUrl('hardMigration/index', ['HardMigration[migration_release_request_obj_id]'=>$releaseRequest->obj_id])."'>Show hard migrations (".count($releaseRequest->hardMigrations).")</a><br />";
                }

                if ($releaseRequest->rr_new_migration_count) {
                    if ($releaseRequest->rr_migration_status == \ReleaseRequest::MIGRATION_STATUS_UPDATING) {
                        return "updating migrations";
                    } elseif ($releaseRequest->rr_migration_status == \ReleaseRequest::MIGRATION_STATUS_FAILED) {
                        return "updating migrations failed";
                    } else {
                        $text =
                            "<a href='".$this->createUrl('/use/migrate', array('id' => $releaseRequest->obj_id, 'type' => 'pre'))."'>RUN pre migrations</a><br />".
                            "<a href='#' onclick=\"$('#migrations-{$releaseRequest->obj_id}').toggle('fast'); return false;\">show pre migrations</a>
                            <div id='migrations-{$releaseRequest->obj_id}' style='display: none'>";
                        foreach (json_decode($releaseRequest->rr_new_migrations) as $migration) {
                            $text .= "<a href='http://sources:8060/browse/migration-{$releaseRequest->project->project_name}/pre/$migration.php?hb=true'>$migration</a><br />";
                        }
                        $text .= "</div>";

                        return $text;
                    }
                } else {
                    return "<a href='".$this->createUrl('/use/create', array('id' => $releaseRequest->obj_id))."'>USE</a>";
                }
                } elseif ($releaseRequest->rr_status == \ReleaseRequest::STATUS_CODES) {
                    return "<a href='".$this->createUrl('/use/index', array('id' => $releaseRequest->obj_id))."'>Enter codes</a>";
                } elseif ($releaseRequest->rr_status == \ReleaseRequest::STATUS_USED_ATTEMPT) {
                    return "<a href='".$this->createUrl('/use/fixAttempt', array('id' => $releaseRequest->obj_id))."'>Make stable</a>";
                } elseif ($releaseRequest->rr_status == \ReleaseRequest::STATUS_USED && $releaseRequest->rr_old_version) {
                    $oldReleaseRequest = $releaseRequest->getOldReleaseRequest();
                    if ($oldReleaseRequest && $oldReleaseRequest->canBeUsed()) {
                        return "<a href='".$this->createUrl('/use/create', array('id' => $oldReleaseRequest->obj_id))."'>Revert to $releaseRequest->rr_old_version</a>";
                    }
                }

                return null;
            },
        'type' => 'raw'
    ),
    array(
        'class'=>'CButtonColumn',
        'template' => '{delete}',
        'deleteButtonUrl' => 'Yii::app()->controller->createUrl("deleteReleaseRequest",array("id"=>$data->primaryKey))',
    ),
);