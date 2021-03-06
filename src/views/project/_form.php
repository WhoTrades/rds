<?php
/**
 * @var $model Project
 * @var $workers Worker[]
 * @var $form yii\widgets\ActiveForm
 */

use conquer\codemirror\CodemirrorWidget;
use conquer\codemirror\CodemirrorAsset;
use yii\bootstrap\Alert;
use yii\bootstrap\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;
use whotrades\rds\models\Project;
use whotrades\rds\models\Worker;
use kartik\select2\Select2;

?>

<div class="form">

    <?php $form = ActiveForm::begin(['id' => 'project-form']) ?>
        <?php if (count($model->projectConfigs)) { ?>
            <?=Alert::widget([
                'options' => [
                    'class' => 'alert-danger',
                ],
                'body' => Yii::t('rds', 'hint_local_configuration_warning'),
            ])?>
        <?php } ?>
        <p class="note">Fields with <span class="required">*</span> are required.</p>
        <?= $form->field($model, 'project_name') ?>
        <?= $form->field($model, 'project_notification_email') ?>
        <?= $form->field($model, 'project_notification_subject') ?>

        <?= $form->field($model, 'projectserversarray')->widget(Select2::class, [
            'data' => $model->getKnownServers(),
            'options' => ['placeholder' => Yii::t('rds', 'hint_select_servers'), 'multiple' => true],
            'pluginOptions' => [
                'tags' => true,
                'tokenSeparators' => [',', ' '],
                'maximumInputLength' => 30,
            ],
        ]); ?>

        <label><?=Yii::t('rds', 'child_projects')?>:</label>
        <?= Select2::widget([
            'name' => 'child_project_array',
            'value' => $model->getChildProjectIdList(),
            'data' => $model->getKnownProjectsIdNameList(),
            'options' => ['placeholder' => Yii::t('rds', 'hint_select_child_project'), 'multiple' => true],
        ]) ?>

        <h2><?=Yii::t('rds', 'local_configuration')?> (<a href="<?=Url::to(['/project-config/', 'projectId' => $model->obj_id])?>"><?=Yii::t('rds', 'management')?></a>)</h2>
        <?php foreach ($model->projectConfigs as $projectConfig) { ?>
            <h3>
                <?=$projectConfig->pc_filename?>
                <a href="<?=Url::to(['/project-config/update', 'id' => $projectConfig->obj_id])?>">
                    <span class="glyphicon glyphicon-pencil"></span>
                </a>
            </h3>
            <?=$model->getFirstError($projectConfig->pc_filename)
                ? Html::error(
                    $model,
                    $projectConfig->pc_filename,
                    [
                        'class' => 'alert alert-danger',
                        'encode' => false,
                    ]
                )
                : ''
            ?>
            <?=Alert::widget([
                'options' => [
                    'class' => 'alert-info',
                ],
                'body' => Yii::t('rds', 'help_code_editor'),
            ])?>
            <?=CodemirrorWidget::widget([
                'name' => 'project_config[' . $projectConfig->pc_filename . ']',
                'value' => isset($_POST['project_config'][$projectConfig->pc_filename]) ? $_POST['project_config'][$projectConfig->pc_filename] : $projectConfig->pc_content,
                'preset' => 'php',
                'settings' => [
                    'viewportMargin' => 1000000,
                ],
                'options' => [
                    'rows' => 15,
                    'style' => 'width: 100%',
                ],
                'assets' => [
                    CodemirrorAsset::ADDON_DIALOG,
                    CodemirrorAsset::ADDON_SEARCHCURSOR,
                    CodemirrorAsset::ADDON_SEARCH,
                ],
            ]);?>
        <?php } ?>
        <br />
        <?=Yii::t('rds', 'build_with')?>:<br />
        <?php foreach ($workers as $worker) {?>
            <?=Html::checkbox('workers[]', isset($list[$worker->obj_id]), array('id' => $id = uniqid(), 'value' => $worker->obj_id))?>
            <label style="display: inline" for="<?=$id?>"><?=$worker->worker_name?></label>
            <br />
        <?php }?>
        <?php if (count($model->projectConfigs)) { ?>
            <?=Alert::widget([
                'options' => [
                    'class' => 'alert-danger',
                ],
                'body' => Yii::t('rds', 'hint_local_configuration_warning'),
            ])?>
        <?php } ?>
        <br />
        <div class="row buttons">
            <?php echo Html::submitButton($model->isNewRecord ? 'Create' : 'Save', ['class' => 'btn btn-success']); ?>
        </div>
    <?php ActiveForm::end() ?>
</div>
