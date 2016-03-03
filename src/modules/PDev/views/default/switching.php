<div style="max-width: 600px; margin: auto;">
    <h2>Запрос на переключение ветки создан и будет выполнен в течении минуты</h2>
    <?=TbHtml::alert(
        TbHtml::ALERT_COLOR_WARNING,
        TbHtml::icon(TbHtml::ICON_REFRESH) . " Контур переключается",
        ['closeText' => '']
    )?>
    <script>
        setTimeout(function(){
            document.location = '<?=$this->createUrl('/pdev/')?>';
        }, 15000);
    </script>
</div>
