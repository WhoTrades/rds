<?php
/** @var Migration $model */
/** @var MigrationLogAggregatorUrlInterface $migrationLogAggregatorUrl */

use whotrades\rds\models\Migration;
use whotrades\RdsSystem\Migration\LogAggregatorUrlInterface as MigrationLogAggregatorUrlInterface;

?>

<h1><?=Yii::t('rds', 'head_migrations_management')?></h1>

<?php
\yii\widgets\Pjax::begin(['id' => 'migration-grid-pjax-container']);
echo $this->render(
        '_migrationGrid',
        ['dataProvider' => $model->search($model->attributes), 'filterModel' => $model, 'model' => $model, 'migrationLogAggregatorUrl' => $migrationLogAggregatorUrl]
);
\yii\widgets\Pjax::end();
?>

<script type="text/javascript">
    //an: Если не сделать обновление грида после загрузки страницы, но мы потеряем события, которые произошли после генерации страницы и до подписки на websockets.
    // А такое случается часто, когда мы заказываем сборку
    document.onload.push(function(){
        setTimeout(function(){
            $.pjax.reload('#migration-grid-pjax-container');
        }, 1);
        webSocketSubscribe('migrationUpdated', function(event){
            console.log('Migration ' + event.rr_id + ' updated');

            var trId = '.migration-' + event.rr_id,
                    html = $(event.html).find(trId).html();

            $(trId).html(html);
        });
    });
</script>



