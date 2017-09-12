<?php
/** @var $project Project */
use app\models\Project;
use conquer\codemirror\CodemirrorWidget;
use yii\bootstrap\ActiveForm;
use yii\bootstrap\Alert;
use yii\bootstrap\Html;

$this->params['menu'] = array(
    array('label' => 'Проекты', 'url' => array('/project/admin')),
);

$project->script_deploy = $project->script_deploy ?: "#!/bin/bash -e\n";
?>
<?php $form = ActiveForm::begin() ?>
<h1>Настройка заливки проекта <?=$project->project_name?> на сервера</h1>
<div class="row">
    <div class="col-md-6 col-sm-9">
        <?=Alert::widget([
            'options' => [
                'class' => 'alert-info',
            ],
            'body' => "F11 - полноэкранный режим редактора, Esc - выход",
        ])?>
        <?= $form->field($project, 'script_deploy')->widget(
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
                </ul>
                <p><strong>Результат работы</strong>: Данный скрипт должен загрузить пакет проекта на все сервера. В случае ошибки - завершиться с exit-code != 0</p>
            </div>
        </div>

    </div>
</div>
<?php
echo Html::submitButton('Save', ['class' => 'btn btn-lg btn-primary']);
ActiveForm::end();
