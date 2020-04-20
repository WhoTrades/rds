<?php
namespace whotrades\rds\widgets;

use yii\base\Widget;
use whotrades\rds\models\Migration;

class PostMigration extends Widget
{
    /**
     * @return string
     */
    public function run()
    {
        if (!\Yii::$app->user->can('developer')) {
            return "";
        }

        return $this->render('@app/views/widgets/PostMigration', [
            'readyPostMigrationsCount' => Migration::getPostMigrationCanBeAppliedCount(),
        ]);
    }
}
