<?php

namespace app\components\report\reportPartEngine;

// класс для генерации заголовка отчета с данными об отчете и менеджере проекта
class HeaderReportPartEngine implements \app\components\report\ReportPartEngineInterface
{
    public function getAlert(){
      return false;
    }
    public function render($data){
         //\Yii::trace(print_r($data,true));
         // заполняем заголовок (данные проекта и данные менеджера)
        $params = $this->getReportData($data['report_id']);
        $params['order'] = 0;
        $arr = array();
        foreach ($data['parts'] as $key => $part) {

             if ($part['show']) {
                  $arr[$key]['order'] = $part['order'];
                  $set = json_decode($part['settings'], true);
                  foreach ($set as $s) {
                       if($s['type'] === 'name')
                         $name = $s['value'];
                  }
                  $arr[$key]['name'] = $name;
             }
        }
       // \Yii::trace(print_r($arr,true));
        $params['parts'] = $arr;
        $params['date'] = $this->getReportDate($params['report']);
        $html = \Yii::$app->view->renderFile('@app/views/report/worksdone/header.php', $params);
        return $html;
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

    public function getReportData($id){
         // получаем текущий отчет, который будем гененировать
         $currentReport = \app\models\Report::find()->where(['id' => $id])->with(['project'])->one();
         if (isset($currentReport))
               $data['report'] = $currentReport;
          // получаем текущий проект, по которому будем делать отчет
         $currentProject = $this->getProjectData($currentReport->project_id);
         if (isset($currentProject))
               $data['project'] = $currentProject;
          //\Yii::trace(print_r($currentProject->users,true));
          // получаем менеджера (Аккаунтер), который ответственноый за проект
         $currentAccauntant = $this->getAccauntantData($currentProject->users);
         if (isset($currentAccauntant))
               $data['user'] = $currentAccauntant;
          return $data;
    }

    public static function getStReportData($id){
         // получаем текущий отчет, который будем гененировать
         $currentReport = \app\models\Report::find()->where(['id' => $id])->with(['project'])->one();
         if (isset($currentReport))
               $data['report'] = $currentReport;
          // получаем текущий проект, по которому будем делать отчет
         $currentProject = $this->getProjectData($currentReport->project_id);
         if (isset($currentProject))
               $data['project'] = $currentProject;
          //\Yii::trace(print_r($currentProject->users,true));
          // получаем менеджера (Аккаунтер), который ответственноый за проект
         $currentAccauntant = $this->getAccauntantData($currentProject->users);
         if (isset($currentAccauntant))
               $data['user'] = $currentAccauntant;
          return $data;
    }

    public function getProjectData($id){
         // получаем текущий проект, по которому будем делать отчет
         $currentProject =\app\models\Project::find()->where(['id' => $id])->with(['users','users.role','yandexAccount'])->one();
         return $currentProject;
    }

    public function getAccauntantData($users){
         // проверяем пользователей, которые привязаны к проекту
         foreach($users as $user){
             // ищем аккаунт-менеджера среди всех
             if($user->id !== 18){
                  if ($user->role_id === \app\models\UserRole::ACCOUNTANT)
                       $account = $user;
                  else if ($user->role_id === \app\models\UserRole::HEAD)
                     $head = $user;
                  else if ($user->role_id === \app\models\UserRole::ROOT)
                     $root = $user;
             }

         }

         if (isset($account)) return $account;
         else if (isset($root)) return $root;
         else if (isset($head)) return $head;
    }
}
