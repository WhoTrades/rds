<?php
/** @var $project Project */
use whotrades\rds\models\Project;
use conquer\codemirror\CodemirrorWidget;
use yii\bootstrap\ActiveForm;
use yii\bootstrap\Html;

$this->params['menu'] = array(
    array('label' => Yii::t('rds', 'head_project_management'), 'url' => array('/project/admin')),
);

$project->script_config_local = $project->script_config_local ?: "#!/bin/bash -e\n";
?>
<?php $form = ActiveForm::begin() ?>
<h1><?=Yii::t('rds', 'head_project_local_configuration_deploy', $project->project_name)?></h1>
<div class="row">
    <div class="col-md-6 col-sm-9">
        <?= $form->field($project, 'script_config_local')->widget(
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
                    <li><strong>$configDir</strong> <?=Yii::t('rds', 'help_config_dir')?></li>
                    <li><strong>$servers</strong> <?=Yii::t('rds', 'help_servers')?></li>
                </ul>
                <p><strong><?=Yii::t('rds', 'result')?></strong>: <?=Yii::t('rds', 'help_local_configuration')?></p>
            </div>
        </div>

    </div>
</div>

<?=Html::submitButton('Save', ['class' => 'btn btn-lg btn-primary'])?>
<?php
ActiveForm::end();
