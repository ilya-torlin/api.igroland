<?php

namespace app\components\report\reportPartEngine;

class WorksDoneReportPartEngine implements \app\components\report\ReportPartEngineInterface
{
     public function getAlert(){
      return false;
    }
    public function render($data){       
        $params = array('content' => $data);
        $html = \Yii::$app->view->renderFile('@app/views/report/worksdone/main.php', $params);
        return $html;
    }
}

