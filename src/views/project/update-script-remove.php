<?php
/** @var $project Project */
use whotrades\rds\models\Project;
use conquer\codemirror\CodemirrorWidget;
use yii\bootstrap\ActiveForm;
use yii\bootstrap\Alert;
use yii\bootstrap\Html;

$this->params['menu'] = array(
    array('label' => 'Проекты', 'url' => array('/project/admin')),
);

$project->script_remove_release = $project->script_remove_release ?: "#!/bin/bash -e\n";
?>
<?php $form = ActiveForm::begin() ?>
<h1>Настройка удаления сборок проекта <?=$project->project_name?></h1>
<div class="row">
    <div class="col-md-6 col-sm-9">
        <?= $form->field($project, 'script_remove_release')->widget(
            CodemirrorWidget::className(),
            [
                'presetsDir' => '../protected/assets/preset',
                'preset' => 'bash',
                'options' => ['rows' => 15],
            ]
        ) ?>

    </div>
    <div class="col-md-6 col-sm-3">
        <div class="panel panel-info">
            <div class="panel-heading">
                <h3 class="panel-title">Справка</h3>
            </div>
            <div class="panel-body">
                <h5>Доступные переменные окружения</h5>
                <ul>
                    <li><strong>$projectName</strong> имя проекта</li>
                    <li><strong>$version</strong> версия сборки</li>
                    <li><strong>$servers</strong> список серверов через пробел, куда заливать проект</li>
                    <li><strong>$projectDir</strong> папка с проектом (это будет текущая папка)</li>
                </ul>
                <p><strong>Результат работы</strong>: Данный скрипт должен удалить данную сборку со всех серверов. В случае ошибки - завершиться с exit-code != 0</p>
            </div>
        </div>

    </div>
</div>

<?=Html::submitButton('Save', ['class' => 'btn btn-lg btn-primary'])?>
<?php ActiveForm::end();
