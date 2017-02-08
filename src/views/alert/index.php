<?php /** @var $lamps Lamp[]*/ ?>
<?php foreach ($lamps as $lamp) {?>
    <div style="border: solid 1px #eee; margin-bottom: 20px">
        <?php /** @var $lamp Lamp */?>
        <div class="row" style="margin: 0">
            <div class="col-md-6">
                <h2 style="height: 80px; line-height: 80px; margin: 0">
                    <?=$lamp->lamp_name ?><span style="font-size: 48px; text-align: center">
                        <?=TbHtml::icon(TbHtml::ICON_HEART_EMPTY, [
                            'style' => '',
                            'class' => 'lamp-heart status-' . ($lamp->getLampStatus() ? 'on' : 'off'),
                        ])?>
                    </span>
                </h2>
            </div>
            <div class="col-md-6">
                <?= TbHtml::form(); ?>
                <?php if ($lamp->getLampStatus()) {?>
                    <?= TbHtml::submitButton('Остановить на 10 минут', [ 'name' => "disable[$lamp->obj_id]", 'value' => Lamp::ALERT_WAIT_TIMEOUT ]) ?>
                <?php } elseif (strtotime($lamp->lamp_timeout) > time()) { ?>
                    Остановлена до <?= date('H:i:s', strtotime($lamp->lamp_timeout)) ?>
                    <?= TbHtml::submitButton('Включить', [ 'name' => "disable[$lamp->obj_id]", 'value' => '-1 minutes' ]) ?>
                <?php }?>
                <br />
                <?php $phone = Yii::app()->user->phone;
                if (empty($phone)) {
                    echo TbHtml::alert(TbHtml::ALERT_COLOR_WARNING, 'Для подписки на SMS рассылку нужно указать свой телефон в CRM');
                } else {
                    if ($lamp->isReceiverExists($phone)) {
                        echo TbHtml::submitButton("Отписаться от SMS рассылки ($phone)", [
                            'name' => "remove_receiver[$lamp->obj_id]",
                            'value' => $phone,
                            'color' => TbHtml::BUTTON_COLOR_WARNING,
                        ]);
                    } else {
                        echo TbHtml::submitButton("Подписаться на SMS рассылку ($phone)", [
                            'name' => "add_receiver[$lamp->obj_id]",
                            'value' => $phone,
                            'color' => TbHtml::BUTTON_COLOR_PRIMARY,
                        ]);
                    }
                }?>
                <?=TbHtml::endForm();?>
            </div>
        </div>
        <div class="row" style="margin: 0">
            <?= TbHtml::form() ?>

                <?php
                $alerts = [
                    [
                        'header' => 'Ошибки',
                        'list' => $lamp->getLampErrors(),
                        'icon' => TbHtml::ICON_PAUSE,
                        'ignoreTime' => '+10 years',
                    ],
                    [
                        'header' => 'Игнорируемые',
                        'list' => $lamp->getLampIgnores(),
                        'icon' => TbHtml::ICON_PLAY,
                        'ignoreTime' => '-1 minutes',
                    ],
                ];

                foreach ($alerts as $val) {
                    echo '<div class="col-md-6" style="border: solid 1px #eee; padding-bottom: 20px">';
                    echo '<h3>' . $val['header'] . '</h3>';
                    foreach ($val['list'] as $alert) {
                        /** @var AlertLog $alert */
                        $alertName = $alert->alert_name;

                        if ($alert->hasLink()) {
                            $alertName = TbHtml::link($alertName, $alert->getLink(), ['target' => '_blank']);
                        }

                        echo TbHtml::em(
                            TbHtml::submitButton(TbHtml::icon($val['icon']), [
                                'name' => "ignore[$alert->obj_id]",
                                'value' => $val['ignoreTime'],
                                'size' => "xs",
                                'color' => $alert->alert_status == AlertLog::STATUS_OK ? TbHtml::BUTTON_COLOR_SUCCESS : TbHtml::BUTTON_COLOR_DANGER,
                            ]) . ' ' . $alertName,
                            [
                                'color' => $alert->alert_status == AlertLog::STATUS_OK ? TbHtml::BUTTON_COLOR_SUCCESS : TbHtml::BUTTON_COLOR_DANGER,
                            ]
                        );
                    }
                    if (empty($val['list'])) {
                        echo TbHtml::labelTb('Нет', ['color' => TbHtml::LABEL_COLOR_WARNING]);
                    }
                    echo '</div>';
                }
                ?>
                <?= TbHtml::endForm() ?>
            </td>
        </div>
    </div>
<?php }?>
<style>
    .status-on {
        font-weight: bold;;
        color: #c24a4f;
    }
    .status-off {
        animation: saturation 1.2s infinite linear;
        color: green;
        font-weight: bold;;
    }

    @keyframes saturation {
        0% {
            font-size: 1em;
            margin-top: 0;
        }
        10% {
            margin-top: 0.025em;
            font-size: 1.05em;
        }
        20% {
            margin-top: 0;
            font-size: 1em;
        }
        30% {
            margin-top: 0.025em;
            font-size: 1.05em;
        }
        40% {
            margin-top: 0;
            font-size: 1em;
        }
    }
</style>

