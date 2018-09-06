<?php

namespace app\components\report\reportPartEngine;

// класс для генерации подвала отчета со скриптами js
class FooterReportPartEngine implements \app\components\report\ReportPartEngineInterface
{
    public function getAlert(){
      return false;
    }
    public function render($data){
         // заполняем заголовок (данные проекта и данные менеджера)
        $params = $this->getReportData($data['id']);
        $params['hidden'] = $data['hidden'];
        $params['date'] = $this->getReportDate($params['report']);
        if (isset($params)){
             $html = \Yii::$app->view->renderFile('@app/views/report/worksdone/footer.php', $params);
        }
        else {
             throw new \yii\web\ForbiddenHttpException(sprintf('Неудалось сгенерировать футер'));
        }
        return $html;
    }

    public function getReportData($id){
         // получаем текущий проект, к которому генерируем отчет
         $currentReport = \app\models\Report::find()->where(['id' => $id])->with(['project'])->one();
         if (isset($currentReport)){
               $data['report'] = $currentReport;
               return $data;
          }
          return null;
    }

    public function getReportDate($report){
         // получаем дату (старую), разбираем ее на Г Д М
         if (isset($report['date']))
             $days = explode('-',$report['date']);
         // существует дата нового формата, то подменяем дату Г Д М
         if(isset($report['gen_start']))
             $days = explode('-',$report['gen_start']);
         // проверяем есть ли текущя дата (старого формата) или День у даты нового формата равен 1 то период выставляем месяц год
         if (isset($report['date']) || ($days[2] === '01')) {
             $monthes = array('01' => 'Январь', '02' => 'Февраль', '03' => 'Март', '04' => 'Апрель', '05' => 'Май', '06' => 'Июнь', '07' => 'Июль', '08' => 'Август', '09' => 'Сентябрь', '10' => 'Октябрь', '11' => 'Ноябрь', '12' => 'Декабрь');
             $date = $monthes[$days[1]].' '.$days[0];
         }
         // иначе период ставим с "дата" по "дата"
         else{
             $date = $report['gen_start'].' по '. $report['gen_end'];
         }
         return $date;
    }
}
