<?php

namespace app\controllers;

use yii;
use yii\web\Controller;
use app\components\WhoisHelper;

class CronController extends Controller {

     public function actionHostingalert() {
        $panicDays = array( 30, 10, 1);
        set_time_limit(6000);
        ini_set('memory_limit', '500M');
        \Yii::$app->response->format = \yii\web\Response::FORMAT_HTML;
        $projects = \app\models\Project::find()
                ->where(['not', ['hosting_login' => null]])
                ->andWhere(['not', ['hosting_pass' => null]])
                ->with('users')
                ->all();
        foreach ($projects as $project) {
            $res =  \app\components\TimewebHelper::execute($project->hosting_login,$project->hosting_pass);
           
            if ($res != 'error') {
                $expdays = $res;
                echo $project->name . ' - ' . $expdays . '<br>';
                if (in_array($expdays, $panicDays)) {
                    //Тут оповещение о окончании хостинга
                   $emails = array('info@praweb.ru');
                    //$emails = array('sosnin@praweb.ru');
                    foreach ($project->users as $user) {
                          if ($user->role_id === \app\models\UserRole::ACCOUNTANT){
                             $emails[] = $user->email;
                          }
                    }
                    if (!empty($project->client_email)){
                         $emails[] = $project->client_email;
                    }
                    
              
               var_dump($emails);
                    \Yii::$app->mailer->compose('hostingAlert', array('project' => $project, 'expdays' => $expdays))
                            ->setFrom(\Yii::$app->params['adminEmail'])
                            ->setTo($emails)
                            ->setSubject(' Подходит срок окончания оплаченного времени хостинга по проекту ' . $project->name)
                            ->send();
                }
            } else {
                echo $project->name . ' - Не удалось определеить <br>';
            }
        }
        die();
    }

    
    public function actionDomainalert() {
        $panicDays = array( 30, 5, 1);
        set_time_limit(600);
        ini_set('memory_limit', '500M');
        \Yii::$app->response->format = \yii\web\Response::FORMAT_HTML;
        $projects = \app\models\Project::find()->where(['on_seo' => 1])->andWhere(['not', ['url' => null]])->with('users')->all();
        foreach ($projects as $project) {
            $res = WhoisHelper::execute($project->url);
            if ($res) {
                $exptime = strtotime($res);
                $expdays = round(($exptime - time()) / 84600);
                //echo $project->url . ' - ' . $expdays . '<br>';
                if (in_array($expdays, $panicDays)) {
                    //Тут оповещение о окончании домена
                    $emails = array('info@praweb.ru');
                    foreach ($project->users as $user) {
                          if ($user->role_id === \app\models\UserRole::ACCOUNTANT){
                              $emails[] = $user->email;
                          }
                    }
                    if (!empty($project->client_email)){
                         $emails[] = $project->client_email;
                    }
                    
               echo $project->url . ' - Не удалось определеить <br> Отправляем пользователям - <br>';
               var_dump($emails);
                    \Yii::$app->mailer->compose('domainAlert', array('project' => $project, 'expdays' => $expdays))
                            ->setFrom(\Yii::$app->params['adminEmail'])
                            ->setTo($emails)
                            ->setSubject(' Подходит срок окончания регистрации домена по проекту ' . $project->name)
                            ->send();
                }
            } else {
                echo $project->url . ' - Не удалось определеить <br>';
            }
        }
        die();
    }

    public function actionWorktimealert() {
        set_time_limit(600);
        ini_set('memory_limit', '500M');
        \Yii::$app->response->format = \yii\web\Response::FORMAT_HTML;
        $alerts = \app\models\Worktimealert::find()->all();


        foreach ($alerts as $alert) {
            $currentDayNum = date('d');
            if ($currentDayNum >= $alert->day) {
                $month = date('m');
                $year = date('Y');
            } else {
                $finish = date('Y-m-' . $alert->day);
                $month = date('m', strtotime($finish . ' -1 month'));
                $year = date('Y', strtotime($finish . ' -1 month'));
            }
            

            $res = $alert->isActiveInThisMonth();
            if (is_array($res)) {
                //Проверяем срабатывали ли уже тревогу этого уровня в этом месяце
                $activealert = \app\models\ActiveWorktimealert::find()->where(['worktimealert_id' => $alert->id,'worktimealert_level_id' => $res['level'], 'month' => intval($month), 'year' => intval($year)])->one();
            if ($activealert)
                continue;
                
                
                
                $activealert = new \app\models\ActiveWorktimealert();
                $activealert->month = $month;
                $activealert->year = $year;
                $activealert->worktimealert_id = $alert->id;
                $activealert->comment = "На текущий момент - " . date('d.m.Y') . ' - потрачено ' . $res['sum'];
                $activealert->worktimealert_level_id = $res['level'];
                $activealert->save();
                //Оповещение
                if ($res['level'] == 1){
                     $emails = array('roman@praweb.ru', 'solokhin@praweb.ru', 'maya@praweb.ru');
                     $subject = 'Превышение максимального времени по проекту ' . $alert->project->name;
                } else {
                     $emails = array( 'maya@praweb.ru');
                     $subject = 'Предупреждение о приближении максимального времени по проекту ' . $alert->project->name;
                }
                foreach ($alert->project->users as $user) {
                          if ($user->role_id === \app\models\UserRole::SEO){
                             $emails[] = $user->email;
                          }
                    }
                    
                
                \Yii::$app->mailer->compose('alertForAdmin', array('sum' => $res['sum'],'level' =>  $res['level'],'alert' => $alert))
                        ->setFrom(\Yii::$app->params['adminEmail'])
                        ->setTo($emails)
                       // ->setTo(['sosnin@praweb.ru'])
                        ->setSubject($subject)
                        ->send();
            }
        }
        die();
    }

    public function actionDownloadpositions() {
        set_time_limit(6000);
        ini_set('memory_limit', '500M');
        \Yii::$app->response->format = \yii\web\Response::FORMAT_HTML;
        $projects = \app\models\Project::find()->where(['on_seo' => 1])->all();
        //$num = file_get_contents(dirname(__FILE__) . '/day.txt');
        // echo $num;
        // file_put_contents(dirname(__FILE__) . '/day.txt', $num + 1);
        $date = time() - 40 * 24 * 60 * 60; 
        $cur_date = date('Y-m-d', $date);
        echo $cur_date;

        foreach ($projects as $project) {
            if (!isset($project->all_positions_id)) {
                echo "<br>";
                echo "Пустой allpositionid " . $project->name;
                continue;
            }

            $dates = Yii::$app->allPositionsHelper::getReportDates($project->all_positions_id);

            if (!is_array($dates))
                continue;
            
            foreach ($dates as $itemDate){
                 if ($itemDate > $cur_date) {
                echo "<br>";
                echo 'Загружаем позиции по ' . $project->url.' дата - '.$itemDate;
                $project->downloadPositions($itemDate,true);
            }
            }

           
        }

        echo "<pre>";

        echo "</pre>";

        die();
    }

    /* public function actionDownloadpositions() { buckup :)
      \Yii::$app->response->format = \yii\web\Response::FORMAT_HTML;
      // $result = Yii::$app->allPositionsHelper::getReportDates(606594);
      $result = Yii::$app->allPositionsHelper::getReport(606594, '2017-09-07', '2017-09-07');
      $projects = \app\models\Project::find()->all();
      $date = time() - 31 * 24 * 60 * 60;
      for ($i = 0; $i < 93; $i++)
      foreach ($projects as $project) {
      if (!isset($project->all_positions_id)) {
      continue;
      }
      $dates = Yii::$app->allPositionsHelper::getReportDates($project->all_positions_id);
      $cur_date = $date + 24 * 60 * 60 * $i;
      if (in_array($cur_date, $dates)) {
      $project->downloadPositions(date('Y-m-d', $date + 24 * 60 * 60 * $i));
      }
      }
      echo "<pre>";

      echo "</pre>";
      die();
      } */

    public function actionGenreport() {
        \Yii::$app->response->format = \yii\web\Response::FORMAT_HTML;
        // получаем день текущей даты
        $day = date('j');
        // выбираем проекты, у которых дата отчета - текущая
        $projects = \app\models\Project::find()->where(['on_seo' => 1])->andWhere(['gen_start_date' => $day])->all();
        // получаем тукещую дату
        $date = date('Y-m-d');
        // получаем день даты с первым 0
        $day_date = date('d');
        // получаем дату +1 месяц и забираем оттуда месяц и год
        $tmp_date = date('Y-m-d', strtotime($date . " +1 month"));
        // получаем месяц и год и прибавляем к ним день (profit)
        $end_date = date('Y-m', strtotime($tmp_date)) . '-' . $day_date;

        foreach ($projects as $project) {
            // вывел метод создания отчета в объект \app\models\Report
            $report = \app\models\Report::createReport($project, null, $date, $end_date);

            // $report = \app\models\Report::find()
            //         ->where(['project_id' => $project->id, 'date' => $date])
            //         ->one();
            // if ($report)
            //     continue;
            // //\Yii::trace(print_r($project,true));
            // $user = \app\models\User::find()
            //         ->leftJoin('user_project', 'user_project.user_id = user.id')
            //         ->where(['user.role_id' => \app\models\UserRole::SEO])
            //         ->andWhere(['user_project.project_id' => $project->id])
            //         ->one();
            // if (!isset($user)) {
            //     $user = \app\models\User::find()
            //             ->leftJoin('user_project', 'user_project.user_id = user.id')
            //             ->where(['user.role_id' => \app\models\UserRole::HEAD])
            //             ->andWhere(['user_project.project_id' => $project->id])
            //             ->one();
            // }
            // if (!isset($user)) {
            //     echo $project->id . '<br>';
            //     var_dump($user);
            //     continue;
            // }
            // //\Yii::trace(print_r($user,true));
            // $report = new \app\models\Report;
            // $report->project_id = $project->id;
            // $report->user_id = $user->id;
            // $report->status_id = 1;
            // $report->date = $date;
            // $report->save();
            // // генерируем части отчета для текущего проекта - (автогенерация отчета)
            // $reportParts = \app\models\ReportPart::find()->asArray()->all();
            // if (isset($project->all_positions_id)) {
            //     foreach ($reportParts as $repPart) {
            //         $rep = new \app\models\ReportPartReport;
            //         $rep->report_id = $report['id'];
            //         $rep->report_part_id = $repPart['id'];
            //         $rep->settings = $repPart['settings'];
            //         $rep->show = 1;
            //         $rep->order = 0;
            //         $rep->save();
            //     }
            // }
        }
    }

}
