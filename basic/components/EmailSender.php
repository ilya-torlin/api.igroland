<?php

namespace app\components;
use yii;

use yii\base\Component;

class EmailSender extends Component {

     public static function sendTest() {

     $result = Yii::$app->mailer->compose('BasicEmailTemplate')
                                  ->setFrom(['torlin@praweb.ru' => 'Письмо с сайта'])
                                  ->setTo('ilya.torlin@yandex.ru')
                                  ->setSubject('Проверка почты с сайта')
                                  ->attach('/home/admin/web/api.praweb.ru/public_html/assets/files/36/59d1e3ba7b490/388 СЕО Радиант.docx')
                                  ->send();

        return $result;
    }

    public static function send($to, $subject, $file_id) {
        // отправка почты to - кому, subject - тема письма, file_id - id прикрепляемого файла
        // отправка работает через SMTP под почтой torlin@praweb.ru
        // проверяем параметры
        if((!isset($to)) || ($to == ''))
             throw new \yii\web\ForbiddenHttpException(sprintf('Получатель письма не определен'));
        if(($subject == '') || (!isset($subject)))
             throw new \yii\web\ForbiddenHttpException(sprintf('Не указана тема письма'));

        $message = Yii::$app->mailer->compose('BasicEmailTemplate');
        $message->setFrom(['info@praweb.ru' => 'Письмо из личного кабинета Praweb.ru'])
                 ->setTo($to)
                 ->setSubject($subject);

        if((is_int($file_id)) && ($file_id > 0)){
            $file =  \app\models\File::find()->where(['id' => $file_id])->one();
            if(isset($file)){
                 $filepath = $file['path'].$file['url'];
                 \Yii::trace($filepath);
                 $message->attach($filepath);
            }
            else {
                 throw new \yii\web\ForbiddenHttpException(sprintf('Не удалось прикрепить файл для отправки письма'));
            }
        }

        // вызываем отправку
        $message->send();
        return ;
    }

    public static function sendReport($to, $report_id) {
        // отправка почты to - кому, subject - тема письма, file_id - id прикрепляемого файла
        // отправка работает через SMTP под почтой torlin@praweb.ru
        // проверяем параметры
        if((!isset($to)) || ($to == ''))
             throw new \yii\web\ForbiddenHttpException(sprintf('Получатель письма не определен'));
        if(($report_id == '') || (!isset($report_id)))
             throw new \yii\web\ForbiddenHttpException(sprintf('Не указан отчет для отправки'));

        // получаем данные с отчета
        $header = \app\components\report\ReportPartEngineFactory::create('HeaderReportPartEngine');
        $getData = $header->getReportData($report_id);
        $date = $header->getReportDate($getData['report']);
        $getData['report']['date'] = $date;
        \Yii::trace(print_r($getData,true));
        $file_id = $getData['report']['file_id'];
        \Yii::trace('file_id - '.$file_id);

        if((is_int($file_id)) && ($file_id > 0)){
            $file =  \app\models\File::find()->where(['id' => $file_id])->one();
            if(isset($file)){
                 $filepath = $file['path'].$file['url'];
                 \Yii::trace($filepath);
            }
            else {
                 throw new \yii\web\ForbiddenHttpException(sprintf('Не удалось получить сылку на файл отчета'));
            }
        }


        $message = Yii::$app->mailer->compose('ReportEmailTemplate',['project' => $getData['project'], 'report' => $getData['report'], 'user' => $getData['user'], 'file' => $file['url']]);
        $message->setFrom(['torlin@praweb.ru' => 'Письмо из личного кабинета Praweb.ru'])
                 ->setTo($to)
                 ->setSubject('Отчет по SEO продвижению проекта '.$getData['project']['name'].' за '.$date);
        // вызываем отправку
        $message->send();
        return ;
    }

}
