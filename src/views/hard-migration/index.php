<?php
/**
 * @var $this yii\web\View
 * @var $model app\models\HardMigration
 */

?>
<h1>Управление тяжелыми миграциями</h1>
<p> Можно использовать дополнительные операторы сравнения (<b>&lt;</b>, <b>&lt;=</b>, <b>&gt;</b>, <b>&gt;=</b>, <b>&lt;&gt;</b> or <b>=</b>) </p>

<?php
\yii\widgets\Pjax::begin(['id' => 'hard-migration-grid-pjax-container']);
echo $this->render('_hardMigrationGrid', ['dataProvider' => $model->search($model->attributes), 'filterModel' => $model, 'model' => $model]);
\yii\widgets\Pjax::end();
?>


<script type="text/javascript">
    //an: Если не сделать обновление грида после загрузки страницы, но мы потеряем события, которые произошли после генерации страницы и до подписки на websockets.
    // А такое случается часто, когда мы заказываем сборку
    document.onload.push(function(){
        setTimeout(function(){
            $.pjax.reload('#hard-migration-grid-pjax-container');
        }, 1);
        webSocketSubscribe('hardMigrationChanged', function(event){
            console.log('Hard migration ' + event.rr_id + ' updated');

            var trId = '.hard-migration-' + event.rr_id,
                html = $(event.html).find(trId).html();

            $(trId).html(html);
        });
        webSocketSubscribe('migrationProgressbarChanged', function(event){
            $('.progress-'+event.migration+' .progress-bar').css({width: event.percent+'%'});
            var html = '<b>'+(event.percent.toFixed(2).toString())+'%:</b> '+(event.key);
            $('.progress-'+event.migration+' .progress-bar').html(html);
            $('.progress-action-'+event.migration).html(event.key);
        });
    });
</script>