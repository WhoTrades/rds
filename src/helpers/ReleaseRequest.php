<?php
declare(strict_types=1);

namespace whotrades\rds\helpers;

use whotrades\rds\services\strategies\CronConfigProcessingStrategyInterface;
use Yii;
use yii\bootstrap\BaseHtml;
use yii\helpers\Url;
use \whotrades\rds\models\ReleaseRequest as ReleaseRequestModel;

class ReleaseRequest
{
    /**
     * Returns array with 2 elements:
     * - array of buttons
     * - array of messages
     *
     * @param ReleaseRequestModel $releaseRequest
     * @param CronConfigProcessingStrategyInterface $cronConfigProcessor
     *
     * @return array|array[]
     */
    public static function getButtonsAndMessages(ReleaseRequestModel $releaseRequest, CronConfigProcessingStrategyInterface $cronConfigProcessor): array
    {
        $buttons = [];
        $messages = [];

        if ($releaseRequest->isDeleted()) {
            $messages = [Yii::t('rds', 'release_deleted')];
            return [$buttons, $messages];
        }

        if ($releaseRequest->showCronDiff()) {
            /** @var $currentUsed ReleaseRequestModel */
            $currentUsed = ReleaseRequestModel::find()->where(
                [
                    'rr_status' => ReleaseRequestModel::getUsedStatuses(),
                    'rr_project_obj_id' => $releaseRequest->rr_project_obj_id,
                ]
            )->one();

            $currentCron = $currentUsed ? $cronConfigProcessor->process($currentUsed->rr_cron_config) : '';
            $newCron = $cronConfigProcessor->process($releaseRequest->rr_cron_config);

            if ($currentUsed && $currentCron != $newCron) {
                $diffStat = Yii::$app->diffStat->getDiffStat($currentCron, $newCron);
                $diffStat = preg_replace('~\++~', '<span style="color: #32cd32">$0</span>', $diffStat);
                $diffStat = preg_replace('~\-+~', '<span style="color: red">$0</span>', $diffStat);
                $messages[] = Html::aTargetBlank(Url::to(['/diff/index/', 'id1' => $releaseRequest->obj_id, 'id2' => $currentUsed->obj_id]), Yii::t('rds', 'cron_changed')) . '<br />' . $diffStat;
            }
        }

        if ($releaseRequest->showInstallationErrors()) {
            $messages[] = Html::a(Yii::t('rds/errors', 'deploy_error'), '#', [
                'style' => 'info',
                'data' => ['toggle' => 'modal', 'target' => '#release-request-install-error-' . $releaseRequest->obj_id, 'onclick' => "return false;"],
            ]);
        }

        if ($releaseRequest->showActivationErrors()) {
            $messages[] = Html::a(Yii::t('rds/errors', 'activation_error'), '#', [
                'style' => 'info',
                'data' => ['toggle' => 'modal', 'target' => '#release-request-use-error-' . $releaseRequest->obj_id, 'onclick' => "return false;"],
            ]);
        }

        if ($releaseRequest->canBeRecreated()) {
            $buttons[] = Html::a(BaseHtml::icon('repeat') . ' ' . Yii::t('rds', 'btn_rebuild'), Url::to(['/site/recreate-release', 'id' => $releaseRequest->obj_id]), ['class' => 'ajax-url btn btn-default']);

            if ($releaseRequest->rr_status === ReleaseRequestModel::STATUS_FAILED) {
                return [$buttons, $messages];
            }
        }

        if ($releaseRequest->shouldBeInstalled()) {
            $buttons[] = Html::a(Yii::t('rds', 'btn_deploy'), Url::to(['/site/install-release', 'id' => $releaseRequest->obj_id]), ['class' => 'install-button btn btn-primary', 'data-id' => $releaseRequest->obj_id]);
            return [$buttons, $messages];
        }

        if ($releaseRequest->shouldBeMigrated()) {
            if ($releaseRequest->rr_migration_status == ReleaseRequestModel::MIGRATION_STATUS_UP) {
                $messages[] = "Wrong migration status";
                return [$buttons, $messages];
            } elseif ($releaseRequest->rr_migration_status == ReleaseRequestModel::MIGRATION_STATUS_UPDATING) {
                $messages[] = "Updating migrations...";
                return [$buttons, $messages];
            } elseif ($releaseRequest->rr_migration_status == ReleaseRequestModel::MIGRATION_STATUS_FAILED) {
                $messages[] = "updating migrations failed";

                $messages[] = Html::a('View error', '#', [
                    'style' => 'info',
                    'data' => ['toggle' => 'modal', 'target' => '#release-request-migration-error-' . $releaseRequest->obj_id, 'onclick' => "return false;"],
                ]);

                $buttons[] = Html::a('Retry migration', ['/use/migrate', 'id' => $releaseRequest->obj_id], ['class' => 'ajax-url btn btn-primary']);

                return [$buttons, $messages];
            } else {
                $buttons[] = Html::a(Yii::t('rds', 'btn_run_pre_migrations'), Url::to(['/use/migrate', 'id' => $releaseRequest->obj_id]), ['class' => 'ajax-url btn btn-primary']);

                if (!empty($releaseRequest->rr_new_migrations)) {
                    $migrations = Html::a(Yii::t('rds', 'btn_view_pre_migrations'), '#', ['onclick' => '$(\'#migrations-' . $releaseRequest->obj_id . '\').toggle(\'fast\'); return false;']);
                    $migrations .= "<div id='migrations-{$releaseRequest->obj_id}' style='display: none'>";
                    // ag: @see whotrades\rds\models\MigrationBase::getNameForUrl()
                    $getNameForUrl = function ($migrationName) {
                        return str_replace('\\', '/', $migrationName);
                    };
                    foreach (json_decode($releaseRequest->rr_new_migrations) as $migration) {
                        $migrations .= Html::a($migration, $releaseRequest->project->getMigrationUrl($getNameForUrl($migration), \whotrades\rds\models\Migration::TYPE_PRE));
                    }
                    $migrations .= "</div>";
                    $messages[] = $migrations;
                }
                return [$buttons, $messages];
            }
        }

        if ($releaseRequest->canBeUsed()) {
            if ($releaseRequest->isChild()) {
                $messages[] = 'It is a child';
                return [$buttons, $messages];
            }

            $buttons[] = Html::a(Yii::t('rds', 'btn_activate_release'), Url::to(['/use/create', 'id' => $releaseRequest->obj_id]), ['class' => 'use-button btn btn-primary']);
            return [$buttons, $messages];
        }

        if ($releaseRequest->canBeReverted()) {
            if ($releaseRequest->isChild()) {
                $messages[] = "Prev version is {$releaseRequest->rr_old_version}";
                return [$buttons, $messages];
            }

            $buttons[] = Html::a(Yii::t('rds', 'btn_revert_release', ['version' => $releaseRequest->rr_old_version]), Url::to(['/use/revert', 'id' => $releaseRequest->obj_id]), ['class' => 'use-button btn btn-warning']);

            return [$buttons, $messages];
        }

        if (!$releaseRequest->canBeUsedChildren()) {
            $messages[] = 'Waiting for children...';

            return [$buttons, $messages];
        }

        return [$buttons, $messages];
    }

}