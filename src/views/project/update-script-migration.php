<?php
/** @var $project Project */
use app\models\Project;
use conquer\codemirror\CodemirrorWidget;
use yii\bootstrap\ActiveForm;
use yii\bootstrap\Html;

$this->params['menu'] = array(
    array('label' => 'Проекты', 'url' => array('/project/admin')),
);

$project->script_migration_up = $project->script_migration_up ?: "#!/bin/bash -e\n";
$project->script_migration_new = $project->script_migration_new ?: "#!/bin/bash -e\n";
?>
<?php $form = ActiveForm::begin() ?>
<h1>Настройка миграций проекта <?=$project->project_name?></h1>
<div class="row">
    <div class="col-md-6 col-sm-9">
        <?= $form->field($project, 'script_migration_up')->widget(
            CodemirrorWidget::className(),
            [
                'presetsDir' => '../../protected/assets/preset',
                'preset' => 'bash',
                'options' => ['rows' => 20],
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
                    <li><strong>$type</strong> тип миграции (pre/post)</li>
                    <li><strong>$projectDir</strong> папка с проектов (это будет текущая папка)</li>
                </ul>
                <p><strong>Результат работы</strong>: Данный скрипт должен выполнить все невыполненные миграции. В случае ошибки - завершиться с exit-code != 0</p>
            </div>
        </div>

    </div>
</div>

<div class="row">
    <div class="col-md-6 col-sm-9">
        <?= $form->field($project, 'script_migration_new')->widget(
            CodemirrorWidget::className(),
            [
                'presetsDir' => '../../protected/assets/preset',
                'preset' => 'bash',
                'options' => ['rows' => 20],
            ]
        ) ?>

    </div>
    <div class="col-md-6 col-sm-3">
        <div class="panel panel-info">
            <div class="panel-heading">
                <h3 class="panel-title">Доступные переменные окружения</h3>
            </div>
            <div class="panel-body">
                <ul>
                    <li><strong>$projectName</strong> имя проекта</li>
                    <li><strong>$version</strong> версия сборки</li>
                    <li><strong>$type</strong> тип миграции (pre/post)</li>
                    <li><strong>$projectDir</strong> папка с проектов (это будет текущая папка)</li>
                </ul>
                <p>
                    <strong>Результат работы</strong>: Данный скрипт должен вывести на экран список всех невыполненных миграций (одна строчка == одна миграция).
                    Если вывод пустой - считается что новых миграций нет. В случае ошибки - завершиться с exit-code != 0
                </p>
            </div>
        </div>
    </div>
</div>

<?=Html::submitButton('Save', ['class' => 'btn btn-lg btn-primary'])?>
<?php ActiveForm::end() ?>
