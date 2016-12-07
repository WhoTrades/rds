<?php /** @var $this SystemController */ ?>
<?php /** @var $config RdsDbConfig */ ?>
<div class="container-fluid">
    <h1><?=$this->pageTitle?></h1>
        <?=TbHtml::beginForm('', 'POST')?>
            <?php if ($config->deployment_enabled) {?>
                <?=TbHtml::button(
                    'Отключить деплой проектов/синхронизацию конфигов',
                    ['type' => 'submit', 'class' => 'btn-lg btn-danger', 'name' => 'deployment_enabled', 'value' => 0]
                )?>
            <?php } else {?>
                <?=TbHtml::button(
                    'Включить деплой проектов/синхронизацию конфигов',
                    ['type' => 'submit', 'class' => 'btn-lg btn-success', 'name' => 'deployment_enabled', 'value' => 1]
                )?>
            <?php }?>
        <?=TbHtml::endForm()?>
</div>