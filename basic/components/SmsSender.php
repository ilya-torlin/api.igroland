<?php

namespace app\components;

use yii\base\Component;

class SmsSender extends Component {

    public static function send($to, $text) {
        
        $deniedSymbols = array("+", "(", " ", ")", "-");
        $to = str_replace($deniedSymbols, "", $to);

        $get = array('user' => \Yii::$app->params['SMSUSER'],
            'pwd' => \Yii::$app->params['SMSPWD'],
            'sadr' => \Yii::$app->params['SMSSADR'],
            'text' => $text,
            'dadr' => $to);

        $defaults = array(
            CURLOPT_URL => 'http://95.213.129.83/sendsms.php?' . http_build_query($get),
            CURLOPT_HEADER => 0,
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_TIMEOUT => 4
        );
        \Yii::trace('SendSms - text' . $text, __METHOD__);
         \Yii::trace('SendSms - http://95.213.129.83/sendsms.php?' . http_build_query($get), __METHOD__);

        $ch = curl_init();
        curl_setopt_array($ch, ($defaults));
        if (!$result = curl_exec($ch)) {
            trigger_error(curl_error($ch));
        }
        curl_close($ch);
        return $result;
    }

}
