<?php
/** @var $project Project */
use whotrades\rds\models\Project;
use conquer\codemirror\CodemirrorWidget;
use yii\bootstrap\ActiveForm;
use yii\bootstrap\Html;

$this->params['menu'] = array(
    array('label' => 'Проекты', 'url' => array('/project/admin')),
);

$project->script_build = $project->script_build ?: "#!/bin/bash -e\n";
?>
<?php $form = ActiveForm::begin() ?>
<h1>Настройка сборки проекта <?=$project->project_name?></h1>
<div class="row">
    <div class="col-md-6 col-sm-9">
        <?= $form->field($project, 'script_build')->widget(
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
                    <li><strong>$projectDir</strong> папка, куда нужно положить собранную версию проекта</li>
                    <li><strong>$projectName</strong> имя проекта</li>
                    <li><strong>$version</strong> версия сборки</li>
                </ul>
                <p><strong>
                    Результат работы</strong>: Данный скрипт должен положить готовый для заливки на сервера пакет сборки в папку $projectDir.
                    В случае ошибки - завершиться с exit-code != 0
                </p>
            </div>
        </div>

    </div>
</div>

<?=Html::submitButton('Save', ['class' => 'btn btn-lg btn-primary'])?>
<?php
ActiveForm::end();
