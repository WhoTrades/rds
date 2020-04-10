<?php
/** @var Migration $model */

use whotrades\rds\models\Migration;

?>


<?php
\yii\widgets\Pjax::begin(['id' => 'migration-grid-pjax-container']);
echo $this->render('_migrationGrid', ['dataProvider' => $model->search($model->attributes), 'filterModel' => $model, 'model' => $model]);
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



