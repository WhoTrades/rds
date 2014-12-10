<?php
class TeamcityJsonController extends Controller
{
    public function filters()
    {
        return [];
    }

    public function actionIndex()
    {

    }

    //an: Находящийся тут код нужно дополнить поллингом teamcity на предмет завершеиня всех билдов
    public function actionBuildComplete($id, $branch, $buildTypeId)
    {
        $tbc = new TeamcityBuildComplete();
        $tbc->attributes = [
            'tbc_build_id'      => $id,
            'tbc_branch'        => $branch,
            'tbc_build_type_id' => $buildTypeId,
        ];
        $tbc->save();

        $this->printJson(['ok' => true]);





    }

    public function printJson($data)
    {
        header("Content-type: text/javascript; charset=utf-8");

        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}