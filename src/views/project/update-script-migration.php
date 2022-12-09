<?php
/** @var $project Project */
use whotrades\rds\models\Project;
use conquer\codemirror\CodemirrorWidget;
use yii\bootstrap\ActiveForm;
use yii\bootstrap\Alert;
use yii\bootstrap\Html;

$this->params['menu'] = array(
    array('label' => Yii::t('rds', 'head_project_management'), 'url' => array('/project/admin')),
);

$project->script_migration_up = $project->script_migration_up ?: "#!/bin/bash -e\n";
$project->script_migration_up_hard = $project->script_migration_up_hard ?: "#!/bin/bash -e\n";
$project->script_migration_new = $project->script_migration_new ?: "#!/bin/bash -e\n";
?>
<?php $form = ActiveForm::begin() ?>
<h1><?=Yii::t('rds', 'head_project_migrations', $project->project_name)?></h1>

<div class="row">
    <div class="col-md-6 col-sm-9">
        <?= $form->field($project, 'script_migration_up')->widget(
            CodemirrorWidget::class,
            [
                'presetsDir' => __DIR__ . '/../../assets/preset',
                'preset' => 'bash',
                'options' => ['rows' => 15],
            ]
        ) ?>

    </div>
    <div class="col-md-6 col-sm-3">
        <div class="panel panel-info">
            <div class="panel-heading">
                <h3 class="panel-title"><?=Yii::t('rds', 'help')?></h3>
            </div>
            <div class="panel-body">
                <h5><?=Yii::t('rds', 'available_env_variables')?></h5>
                <ul>
                    <li><strong>$projectName</strong> <?=Yii::t('rds', 'project_name')?></li>
                    <li><strong>$version</strong> <?=Yii::t('rds', 'version')?></li>
                    <li><strong>$type</strong> <?=Yii::t('rds', 'help_migration_type')?></li>
                    <li><strong>$projectDir</strong> <?=Yii::t('rds', 'help_project_dir')?></li>
                </ul>
                <p><strong><?=Yii::t('rds', 'result')?></strong>: <?=Yii::t('rds', 'help_migration_runner')?></p>
            </div>
        </div>

    </div>
</div>

<div class="row">
    <div class="col-md-6 col-sm-9">
        <?= $form->field($project, 'script_migration_up_hard')->widget(
            CodemirrorWidget::class,
            [
                'presetsDir' => __DIR__ . '/../../assets/preset',
                'preset' => 'bash',
                'options' => ['rows' => 15],
            ]
        ) ?>

    </div>
    <div class="col-md-6 col-sm-3">
        <div class="panel panel-info">
            <div class="panel-heading">
                <h3 class="panel-title"><?=Yii::t('rds', 'help')?></h3>
            </div>
            <div class="panel-body">
                <h5><?=Yii::t('rds', 'available_env_variables')?></h5>
                <ul>
                    <li><strong>$projectName</strong> <?=Yii::t('rds', 'project_name')?></li>
                    <li><strong>$version</strong> <?=Yii::t('rds', 'version')?></li>
                    <li><strong>$type</strong> <?=Yii::t('rds', 'help_migration_type')?></li>
                    <li><strong>$projectDir</strong> <?=Yii::t('rds', 'help_project_dir')?></li>
                </ul>
                <p><strong><?=Yii::t('rds', 'result')?></strong>: <?=Yii::t('rds', 'help_migration_hard_runner')?></p>
            </div>
        </div>

    </div>
</div>

<div class="row">
    <div class="col-md-6 col-sm-9">
        <?=Alert::widget([
            'options' => [
                'class' => 'alert-info',
            ],
            'body' => Yii::t('rds', 'help_code_editor'),
        ])?>
        <?= $form->field($project, 'script_migration_new')->widget(
            CodemirrorWidget::class,
            [
                'presetsDir' => __DIR__ . '/../../assets/preset',
                'preset' => 'bash',
                'options' => ['rows' => 15, 'style' => 'width: 100%'],
            ]
        ) ?>

    </div>
    <div class="col-md-6 col-sm-3">
        <div class="panel panel-info">
            <div class="panel-heading">
                <h3 class="panel-title"><?=Yii::t('rds', 'help')?></h3>
            </div>
            <div class="panel-body">
                <h5><?=Yii::t('rds', 'available_env_variables')?></h5>
                <ul>
                    <li><strong>$projectName</strong> <?=Yii::t('rds', 'project_name')?></li>
                    <li><strong>$version</strong> <?=Yii::t('rds', 'version')?></li>
                    <li><strong>$type</strong> <?=Yii::t('rds', 'help_migration_type')?></li>
                    <li><strong>$projectDir</strong> <?=Yii::t('rds', 'help_project_dir')?></li>
                </ul>
                <p>
                    <strong><?=Yii::t('rds', 'result')?></strong>: <?=Yii::t('rds', 'help_migration_listing')?>
                </p>
            </div>
        </div>
    </div>
</div>

<?=Html::submitButton('Save', ['class' => 'btn btn-lg btn-primary'])?>
<?php ActiveForm::end() ?>
