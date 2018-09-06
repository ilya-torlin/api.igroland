<?php

namespace app\controllers;

use yii;
use yii\rest\Controller;
use app\components;

class UtilController extends Controller {

    public function actionAddpost() {
        $params = \Yii::$app->request->post();
        $postQuery = array();
        $postQuery['text'] = $params['text'];
        $postQuery['userkey'] = "Введите свой пользовательский секретный ключ";
        // домены разделяются пробелами либо запятыми. Данный параметр является необязательным.
        $postQuery['exceptdomain'] = "site1.ru, site2.ru, site3.ru";
        // Раскомментируйте следующую строку, если вы хотите, чтобы результаты проверки текста были по-умолчанию доступны всем пользователям
        //$postQuery['visible'] = "vis_on";
        // Раскомментируйте следующую строку, если вы не хотите сохранять результаты проверки текста в своём архиве проверок
        //$postQuery['copying'] = "noadd";
        // Указывать параметр callback необязательно
        //$postQuery['callback'] = "Введите ваш URL-адрес, который примет наш запрос";
        //$params['text'] = preg_replace ("/[^a-zA-ZА-Яа-я0-9\s]/","",$params['text']);
        $postQuery = http_build_query($params, '', '&');

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://api.text.ru/post');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postQuery);
        $json = curl_exec($ch);
        $errno = curl_errno($ch);

        // если произошла ошибка
        if (!$errno) {
            $resAdd = json_decode($json);
            if (isset($resAdd->text_uid)) {
                $text_uid = $resAdd->text_uid;
                \Yii::trace(print_r($text_uid, true));
            } else {
                $error_code = $resAdd->error_code;
                $error_desc = $resAdd->error_desc;
                \Yii::trace(print_r($error_code, true));
                \Yii::trace(print_r($error_desc, true));
            }
        } else {
            $errmsg = curl_error($ch);
            \Yii::trace(print_r($errmsg, true));
        }

        curl_close($ch);

        if ($errno)
            return (object) array('error' => json_encode($errmsg));

        if (!isset($text_uid))
            return (object) array('error' => $error_code);
        if ($text_uid)
            return array('uid' => $text_uid);
    }

    public function actionGetresultpost() {
        $params = \Yii::$app->request->post();
        $params['jsonvisible'] = "detail";

        $postQuery = http_build_query($params, '', '&');

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'http://api.text.ru/post');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postQuery);
        $json = curl_exec($ch);
        $errno = curl_errno($ch);

        if (!$errno) {
            $resCheck = json_decode($json);
            if (isset($resCheck->text_unique)) {
                $text_unique = $resCheck->text_unique;
                $result_json = $resCheck->result_json;
            } else {
                $error_code = $resCheck->error_code;
                $error_desc = $resCheck->error_desc;
            }
        } else {
            $errmsg = curl_error($ch);
        }

        curl_close($ch);

        if ($errno)
            return (object) array('error' => json_encode($errmsg));

        if (!isset($resCheck->text_unique))
            return (object) array('error' => $error_code);
        if ($resCheck->text_unique)
            return array('res' => $resCheck);
    }

    public function actionGetadvegodata() {
        $params = \Yii::$app->request->post();
        $params['id_lang'] = 0;

        $postQuery = http_build_query($params, '', '&');

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://advego.com/text/seo/');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postQuery);
        $html = curl_exec($ch);
        $errno = curl_errno($ch);

        if (!$errno) {
              \Yii::trace(print_r($html, true));
          
            $html = \app\components\HTMLHelper::str_get_html($html);
            $data = $html->find('#text_check_results .seo_table tbody', 0)->last_child()->last_child()->plaintext;            
        } else {
            $errmsg = curl_error($ch);
        }

        curl_close($ch);

        if ($errno)
            return (object) array('error' => json_encode($errmsg));

        if ($data)
            return array('res' => $data);
    }

    public function behaviors() {

        $behaviors = parent::behaviors();

        // remove authentication filter
        $auth = $behaviors['authenticator'];
        unset($behaviors['authenticator']);

        // add CORS filter
        $behaviors['corsFilter'] = [
            'class' => \yii\filters\Cors::className(),
            'cors' => ['Origin' => ['*']]];

        // re-add authentication filter
        $behaviors['authenticator'] = $auth;
        // avoid authentication on CORS-pre-flight requests (HTTP OPTIONS method)
        $behaviors['authenticator']['except'] = ['options'];

        return $behaviors;
    }

}
