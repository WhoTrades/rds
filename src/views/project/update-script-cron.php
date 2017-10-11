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

$project->script_cron = $project->script_cron ?: "#!/bin/bash -e\n";
?>
<?php $form = ActiveForm::begin() ?>
<h1>Настройка кронов проекта <?=$project->project_name?></h1>
<div class="row">
    <div class="col-md-6 col-sm-9">
        <?= $form->field($project, 'script_cron')->widget(
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
                </ul>
                <p><strong>Результат работы</strong>: Данный скрипт должен вывести в stdout крон конфиг проекта. В случае ошибки - завершиться с exit-code != 0</p>
            </div>
        </div>

    </div>
</div>


<?php
echo Html::submitButton('Save', ['class' => 'btn btn-lg btn-primary']);
ActiveForm::end();

