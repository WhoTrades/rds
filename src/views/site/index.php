<?php
/* @var $this SiteController */
$this->pageTitle=Yii::app()->name;
?>

<h1>Запреты релиза</h1>
<a href="<?=$this->createUrl('createReleaseReject')?>">Создать</a>
<?php $this->widget('bootstrap.widgets.TbGridView', array(
    'id'=>'release-reject-grid',
    'dataProvider'=>$releaseRejectSearchModel->search(),
    'filter'=>$releaseRejectSearchModel,
    'columns'=>array(
        'obj_created',
        'rr_user',
        'rr_comment',
        'project.project_name',
        array(
            'class'=>'CButtonColumn',
            'template' => '{delete}',
            'deleteButtonUrl' => 'Yii::app()->controller->createUrl("deleteReleaseReject",array("id"=>$data->primaryKey))',
        ),
))); ?>
<hr />
<h2>Запрос релиза</h2>
<a href="<?=$this->createUrl('createReleaseRequest')?>">Создать</a>
<?php $this->widget('bootstrap.widgets.TbGridView', array(
    'id'=>'release-request-grid',
    'dataProvider'=>$releaseRequestSearchModel->search(),
    'filter'=>$releaseRequestSearchModel,
    'columns'=>array(
        'obj_created',
        'rr_user',
        'rr_comment',
        'project.project_name',
        array(
            'value' => function(ReleaseRequest $releaseRequest){
                $list = \Build::model()->findAllByAttributes(array(
                    'build_project_obj_id' => $releaseRequest->rr_project_obj_id,
                ), array(
                    'with' => 'worker',
                ));
                foreach ($list as $val) {
                    echo "{$val->worker->worker_name} - {$val->build_status}<br />";
                }
            },
            'type' => 'html',
        ),
        array(
            'class'=>'CButtonColumn',
            'template' => '{delete}',
            'deleteButtonUrl' => 'Yii::app()->controller->createUrl("deleteReleaseRequest",array("id"=>$data->primaryKey))',
        ),
))); ?>

