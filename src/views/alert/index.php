<?php
/** @var $lamps app\models\Lamp[] */

use app\models\Lamp;
use yii\bootstrap\Html;
use yii\bootstrap\Alert;
use app\models\AlertLog;

?>
<?php foreach ($lamps as $lamp) {?>
    <div style="border: solid 1px #eee; margin-bottom: 20px">
        <?php /** @var $lamp Lamp */?>
        <div class="row" style="margin: 0">
            <div class="col-md-6">
                <h2 style="height: 80px; line-height: 80px; margin: 0">
                    <?=$lamp->lamp_name ?><span style="font-size: 48px; text-align: center">
                        <?= Html::icon('heart-empty', [
                            'style' => '',
                            'class' => 'lamp-heart status-' . ($lamp->getLampStatus() ? 'on' : 'off'),
                        ])?>
                    </span>
                </h2>
            </div>
            <div class="col-md-6">
                <?= Html::beginForm(); ?>
                <?php if ($lamp->getLampStatus()) {?>
                    <?= Html::submitButton('Остановить на 10 минут', ['name' => "disable[$lamp->obj_id]", 'value' => Lamp::ALERT_WAIT_TIMEOUT ]) ?>
                <?php } elseif (strtotime($lamp->lamp_timeout) > time()) { ?>
                    Остановлена до <?= date('H:i:s', strtotime($lamp->lamp_timeout)) ?>
                    <?= Html::submitButton('Включить', [ 'name' => "disable[$lamp->obj_id]", 'value' => '-1 minutes' ]) ?>
                <?php }?>
                <br />
                <?php $phone = \Yii::$app->user->identity->phone;
                if (empty($phone)) {
                    echo Alert::widget(['options' => ['class' => 'alert-warning'], 'body' => 'Для подписки на SMS рассылку нужно указать свой телефон в CRM']);
                } else {
                    if ($lamp->isReceiverExists($phone)) {
                        echo Html::submitButton("Отписаться от SMS рассылки ($phone)", [
                            'name' => "remove_receiver[$lamp->obj_id]",
                            'value' => $phone,
                            'class' => 'btn bth-warning',
                        ]);
                    } else {
                        echo Html::submitButton("Подписаться на SMS рассылку ($phone)", [
                            'name' => "add_receiver[$lamp->obj_id]",
                            'value' => $phone,
                            'class' => 'btn bth-primary',
                        ]);
                    }
                }?>
                <?=Html::endForm();?>
            </div>
        </div>
        <div class="row" style="margin: 0">
            <?= Html::beginForm() ?>

                <?php
                $alerts = [
                    [
                        'header' => 'Ошибки',
                        'list' => $lamp->getLampErrors(),
                        'icon' => 'pause',
                        'ignoreTime' => '+10 years',
                    ],
                    [
                        'header' => 'Игнорируемые',
                        'list' => $lamp->getLampIgnores(),
                        'icon' => 'play',
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
                            $alertName = Html::a($alertName, $alert->getLink(), ['target' => '_blank']);
                        }

                        echo Html::tag(
                            'em',
                            Html::submitButton(Html::icon($val['icon']), [
                                'name' => "ignore[$alert->obj_id]",
                                'value' => $val['ignoreTime'],
                                'size' => "xs",
                                'class' => $alert->alert_status == AlertLog::STATUS_OK ? 'btn btn-success' : 'btn btn-danger',
                            ]) . ' ' . $alertName
                        );
                    }
                    if (empty($val['list'])) {
                        echo '<span class="label label-warning">Нет</span>';
                    }
                    echo '</div>';
                }
                ?>
                <?= Html::endForm() ?>
            </td>
        </div>
    </div>
<?php }?>
<style>
    em {
        display: block;
        width: 100%;
        margin: 10px 0;
    }
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

