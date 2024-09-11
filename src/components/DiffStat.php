<?php

namespace whotrades\rds\components;

final class DiffStat extends \yii\base\BaseObject
{
    /**
     * @param string $text1
     * @param string $text2
     * @return mixed|string
     */
    public function getDiffStat($text1, $text2)
    {
        $filename1 = \Yii::$app->runtimePath . "/" . md5($text1) . ".txt";
        $filename2 = \Yii::$app->runtimePath . "/" . md5($text2) . ".txt";
        if (!file_exists($filename1)) {
            file_put_contents($filename1, $text1);
        }
        if (!file_exists($filename2)) {
            file_put_contents($filename2, $text2);
        }
        $command = "diff $filename1 $filename2|diffstat";
        exec($command, $output, $returnVar);

        if ($returnVar == 0) {
            return str_replace('unknown | ', '', reset($output));
        } else {
            return '';
        }
    }
}
