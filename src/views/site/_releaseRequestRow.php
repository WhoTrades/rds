<?php return array(
    'obj_id',
    'obj_created',
    'rr_user',
    [
        'name' => 'rr_comment',
        'value' => function(ReleaseRequest $releaseRequest){
            echo strip_tags($releaseRequest->rr_comment)."<br />";

            if ($releaseRequest->isInstalledStatus()) {
                echo "<a href='".Yii::app()->createUrl('/jira/gotoJiraTicketsByReleaseRequest', ['id' => $releaseRequest->obj_id])."' target='_blank'>Тикеты</a><br />";
            }
            echo "<a href='/site/commits/$releaseRequest->obj_id' onclick=\"popup('test', this.href, {id: {$releaseRequest->obj_id}}); return false;\">Комиты</button>";
        }

    ],
    array(
        'value' => function(ReleaseRequest $releaseRequest){
            $map = array(
                ReleaseRequest::STATUS_NEW => array(TbHtml::ICON_TIME, 'Ожидает сборки', 'black'),
                ReleaseRequest::STATUS_FAILED => array(TbHtml::ICON_REMOVE, 'Не собралось', 'red'),
                ReleaseRequest::STATUS_INSTALLED => array(TbHtml::ICON_OK, 'Установлено', 'black'),
                ReleaseRequest::STATUS_USING=> array(TbHtml::ICON_REFRESH, 'Активируем', 'orange'),
                ReleaseRequest::STATUS_CODES=> array(TbHtml::ICON_TIME, 'Ждем ввода кодов', 'orange'),
                ReleaseRequest::STATUS_USED=> array(TbHtml::ICON_OK, 'Активная версия', '#32cd32'),
                ReleaseRequest::STATUS_USED_ATTEMPT=> array(TbHtml::ICON_TIME, 'Временная версия', 'blue'),
                ReleaseRequest::STATUS_OLD=> array(TbHtml::ICON_TIME, 'Старая версия', 'grey'),
                ReleaseRequest::STATUS_CANCELLING=> array(TbHtml::ICON_REFRESH, 'Отменяем...', 'orange'),
                ReleaseRequest::STATUS_CANCELLED=> array(TbHtml::ICON_OK, 'Отменено', 'red'),
            );
            list($icon, $text, $color) = $map[$releaseRequest->rr_status];
            echo "<span title='{$text}' style='color: ".$color."'>".
                    TbHtml::icon($icon).
                    " {$releaseRequest->rr_status}</span><hr />";

            $result = array();
            foreach ($releaseRequest->builds as $val) {
                /** @var $val Build */
                $map = array(
                    Build::STATUS_FAILED => array(TbHtml::ICON_EXCLAMATION_SIGN, 'Не собралось', 'red'),
                    Build::STATUS_BUILDING => array(TbHtml::ICON_REFRESH, 'Собирается', 'orange'),
                    Build::STATUS_NEW => array(TbHtml::ICON_TIME, 'Ожидает сборки', 'black'),
                    Build::STATUS_BUILT => array(TbHtml::ICON_UPLOAD, 'Раскладывается по серверам', 'orange'),
                    Build::STATUS_INSTALLED => array(TbHtml::ICON_OK, 'Скопировано на сервер', 'black'),
                    Build::STATUS_USED=> array(TbHtml::ICON_OK, 'Установлено', '#32cd32'),
                    Build::STATUS_CANCELLED=> array(TbHtml::ICON_BAN_CIRCLE, 'Отменено', 'red'),
                    Build::STATUS_PREPROD_USING=> array(TbHtml::ICON_REFRESH, 'Устанавливаем на preprod', 'orange'),
                    Build::STATUS_PREPROD_MIGRATIONS=> array(TbHtml::ICON_REFRESH, 'Устанавливаем на preprod', 'orange'),
                );
                list($icon, $text, $color) = $map[$val->build_status];
                $result[] =  "<a href='".Yii::app()->createUrl('build/view', array('id' => $val->obj_id))."' title='{$text}' style='color: $color'>".
                TbHtml::icon($icon)
                ."{$val->worker->worker_name} - {$val->build_status} {$val->project->project_name} {$val->build_version}</a>";

                if ($val->build_status == Build::STATUS_BUILDING) {
                    $info = $val->getProgressbarInfo();
                    if ($info) {
                        list($percent, $currentKey) = $info;
                        $result[] = '
                            <div class="progress progress-'.$val->obj_id.'" style="margin: 0; width: 250px;">
                            <div class="progress-bar" style="width: '.$percent.'%;white-space:nowrap; color:#FFA500; padding-left: 5px">
                                <b>'.sprintf("%.2f", $percent).'%:</b> '.$currentKey.'
                            </div>
                            </div>';
                    }
                }

                if ($text = $val->determineHumanReadableError()) {
                    $result[] = TbHtml::alert(TbHTML::ALERT_COLOR_WARNING, $text, ['closeText' => false]);
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
        'value' => function(ReleaseRequest $releaseRequest, $rowIndex, TbDataColumn $column){
            /** @var $provider ReleaseRequestSearchDataProvider*/
            $provider = $column->grid->dataProvider;

            if ($releaseRequest->canBeUsed()) {

            $currentUsed = $provider->getCurrentUsedReleaseRequest($releaseRequest->rr_project_obj_id);

            if ($currentUsed && $currentUsed->getCronConfigCleaned() != $releaseRequest->getCronConfigCleaned()) {
                $diffStat = Yii::app()->diffStat->getDiffStat($currentUsed->getCronConfigCleaned(), $releaseRequest->getCronConfigCleaned());
                $diffStat = preg_replace('~\++~', '<span style="color: #32cd32">$0</span>', $diffStat);
                $diffStat = preg_replace('~\-+~', '<span style="color: red">$0</span>', $diffStat);
                echo "<a href='".Yii::app()->createUrl('/diff/index/', ['id1' => $releaseRequest->obj_id, 'id2' => $currentUsed->obj_id])."'>CRON changed<br />$diffStat</a><br />";
            }

            if ($releaseRequest->hardMigrations) {
                echo "<a href='".Yii::app()->createUrl('hardMigration/index', ['HardMigration[migration_release_request_obj_id]'=>$releaseRequest->obj_id])."'>Show hard migrations (".count($releaseRequest->hardMigrations).")</a><br />";
            }

            if ($releaseRequest->rr_new_migration_count) {
                if ($releaseRequest->rr_migration_status == \ReleaseRequest::MIGRATION_STATUS_UPDATING) {
                    return "updating migrations";
                } elseif ($releaseRequest->rr_migration_status == \ReleaseRequest::MIGRATION_STATUS_FAILED) {
                    echo "updating migrations failed<br />";
                    $widget = Yii::app()->getWidgetFactory()->createWidget(Yii::app(), 'yiistrap.widgets.TbModal', array(
                        'id' => 'release-request-migration-error-'.$releaseRequest->obj_id,
                        'header' => 'Errors of migration applying',
                        'content' => "<pre>$releaseRequest->rr_migration_error</pre>",
                        'footer' => array(
                            TbHtml::button('Close', array('data-dismiss' => 'modal')),
                        ),
                    ));
                    $widget->init();
                    $widget->run();

                    echo '<a href="" style="info" data-toggle="modal" data-target="#release-request-migration-error-'.$releaseRequest->obj_id.'" onclick="return false;">view error</a> | ';
                    echo "<a href='".Yii::app()->createUrl('/use/migrate', array('id' => $releaseRequest->obj_id, 'type' => 'pre'))."' class='ajax-url'>Retry</a><br />";
                    return;
                } else {
                    $text =
                        "<a href='".Yii::app()->createUrl('/use/migrate', array('id' => $releaseRequest->obj_id, 'type' => 'pre'))."' class='ajax-url'>RUN pre migrations</a><br />".
                        "<a href='#' onclick=\"$('#migrations-{$releaseRequest->obj_id}').toggle('fast'); return false;\">show pre migrations</a>
                        <div id='migrations-{$releaseRequest->obj_id}' style='display: none'>";
                    foreach (json_decode($releaseRequest->rr_new_migrations) as $migration) {
                        $text .= "<a href='http://fisheye:8080/whotrades/file/HEAD/".urlencode("migration/{$releaseRequest->project->project_name}/$migration.php")."'>$migration</a><br />";
                    }
                    $text .= "</div>";

                    return $text;
                }
            } else {
                return "<a href='".Yii::app()->createUrl('/use/create', array('id' => $releaseRequest->obj_id))."' --data-id='$releaseRequest->obj_id' class='use-button'>USE</a>";
            }
            } elseif ($releaseRequest->rr_status == \ReleaseRequest::STATUS_CODES) {
                return "<a href='".Yii::app()->createUrl('/use/index', array('id' => $releaseRequest->obj_id))."' onclick='showForm($releaseRequest->obj_id); return false;'>Enter codes</a>";
            } elseif ($releaseRequest->rr_status == \ReleaseRequest::STATUS_USED_ATTEMPT) {
                return "<a href='".Yii::app()->createUrl('/use/fixAttempt', array('id' => $releaseRequest->obj_id))."' class='ajax-url'>Make stable</a>";
            } elseif ($releaseRequest->rr_status == \ReleaseRequest::STATUS_USED && $releaseRequest->rr_old_version) {
                $oldReleaseRequest = $releaseRequest->getOldReleaseRequest($releaseRequest->rr_project_obj_id, $releaseRequest->rr_old_version);
                if ($oldReleaseRequest && $oldReleaseRequest->canBeUsed()) {
                    return "<a href='".Yii::app()->createUrl('/use/create', array('id' => $oldReleaseRequest->obj_id))."' class='use-button'>Revert to $releaseRequest->rr_old_version</a>";
                }
            }

            return null;
        },
        'type' => 'raw'
    ),
    array(
        'class'=>'yiistrap.widgets.TbButtonColumn',
        'template' => '{delete}',
        'deleteButtonUrl' => 'Yii::app()->controller->createUrl("deleteReleaseRequest",array("id"=>$data->primaryKey))',
    ),
);