<?php
/* @var $model HardMigration */

?>
<h1>Управление тяжелыми миграциями</h1>
<p> Можно использовать дополнительные операторы сравнения (<b>&lt;</b>, <b>&lt;=</b>, <b>&gt;</b>, <b>&gt;=</b>, <b>&lt;&gt;</b> or <b>=</b>) </p>

<?= yii\grid\GridView::widget(array(
    'id' => 'hard-migration-grid',
    'dataProvider' => $model->search($model->attributes),
    'options' => ['class' => 'table-responsive'],
    'filterModel' => $model,
    'rowOptions' => function ($model, $key, $index, $grid) {
        return [
            'class' => 'hard-migration-' . str_replace("/", "", $model->migration_name) . '_' . $model->migration_environment,
        ];
    },
    'columns' => include('_hardMigrationRow.php'),
));

?>


<script type="text/javascript">
    //an: Если не сделать обновление грида после загрузки страницы, но мы потеряем события, которые произошли после генерации страницы и до подписки на websockets.
    // А такое случается часто, когда мы заказываем сборку
    document.onload.push(function(){
        setTimeout(function(){
            $.fn.yiiGridView.update("hard-migration-grid");
        }, 1);
        webSocketSubscribe('hardMigrationChanged', function(event){
            console.log('Hard migration '+event.rr_id+' updated');
            var html = event.html;
            var trHtmlCode = $(html).find('tr.rowItem').first().html()
            $('.hard-migration-'+event.rr_id).html(trHtmlCode);
        });
        webSocketSubscribe('migrationProgressbarChanged', function(event){
            $('.progress-'+event.migration+' .progress-bar').css({width: event.percent+'%'});
            var html = '<b>'+(event.percent.toFixed(2).toString())+'%:</b> '+(event.key);
            $('.progress-'+event.migration+' .progress-bar').html(html);
            $('.progress-action-'+event.migration).html(event.key);
        });
    });
</script>