<?php
/* @var $this HardMigrationController */
/* @var $model HardMigration */
$this->breadcrumbs=['Hard Migrations'];
?>
<h1>Управление тяжелыми миграциями</h1>
<p> Можно использовать дополнительные операторы сравнения (<b>&lt;</b>, <b>&lt;=</b>, <b>&gt;</b>, <b>&gt;=</b>, <b>&lt;&gt;</b> or <b>=</b>) </p>

<?php $this->widget('yiistrap.widgets.TbGridView', array(
    'id'=>'hard-migration-grid',
    'dataProvider'=>$model->search(),
    'filter'=>$model,
    'rowCssClassExpression' => function($index, $rr){
        return 'hard-migration-'.str_replace("/", "", $rr->migration_name).'_'.$rr->migration_environment;
    },
    'columns'=>include('_hardMigrationRow.php'),
)); ?>


<script type="text/javascript">
    //an: Если не сделать обновление грида после загрузки страницы, но мы потеряем события, которые произошли после генерации страницы и до подписки на realplexor.
    // А такое случается часто, когда мы заказываем сборку
    $(document).ready(function(){
        setTimeout(function(){
            $.fn.yiiGridView.update("hard-migration-grid");
        }, 1);
    });
</script>

<script>
    realplexor.subscribe('hardMigrationChanged', function(event){
        console.log('Hard migration '+event.rr_id+' updated');
        var html = event.html;
        var trHtmlCode = $(html).find('tr.rowItem').first().html()
        $('.hard-migration-'+event.rr_id).html(trHtmlCode);
    });
    realplexor.subscribe('migrationProgressbarChanged', function(event){
        $('.progress-'+event.migration+' .progress-bar').css({width: event.percent+'%'});
        var html = '<b>'+(event.percent.toFixed(2).toString())+'%:</b> '+(event.key);
        $('.progress-'+event.migration+' .progress-bar').html(html);
        $('.progress-action-'+event.migration).html(event.key);
    });
    realplexor.execute();
</script>