<?php

class DiffController extends Controller
{
    public function actionIndex($id1, $id2)
    {
        $rr1 = ReleaseRequest::model()->findByPk($id1);
        $rr2 = ReleaseRequest::model()->findByPk($id2);

        $this->render('index', array(
            'new' => $rr1,
            'current' => $rr2,
        ));
    }
}
