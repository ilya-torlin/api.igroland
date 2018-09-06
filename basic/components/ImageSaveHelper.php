<?php

namespace app\components;

use yii\base\Component;
use Yii;

class ImageSaveHelper extends Component {

    private static $extensions = array('jpg', 'jpeg', 'png', 'gif', 'tiff');

    private static function curl_download($url, $file) {
        $dest_file = @fopen($file, "w");
        $resource = curl_init();
        curl_setopt($resource, CURLOPT_URL, $url);
        curl_setopt($resource, CURLOPT_FILE, $dest_file);
        curl_setopt($resource, CURLOPT_HEADER, 0);
        curl_exec($resource);
        curl_close($resource);
        fclose($dest_file);
    }

    private static function generateUniquePath() {
        $data = array();
        
        $data['baseDirName'] = \Yii::$app->params['path']['saveImagePath'];
        $data['baseDirUrl'] = \Yii::$app->params['path']['saveImageUrl'];
        
        
        $data['longdirname'] = uniqid();
        $data['dirname'] = substr($data['longdirname'], -3);
        if (!file_exists($data['baseDirName'] . '/' . $data['dirname'])) {
            mkdir($data['baseDirName'] . '/' . $data['dirname'], 0755);
        }
        mkdir($data['baseDirName'] . '/' . $data['dirname'] . '/' . $data['longdirname'], 0755);
        return $data;
    }

    public static function saveFromFile($file) {
        if (!in_array($file->getExtension(), static::$extensions))
               return false;
        
         $filename = $file->getBaseName() . '.' . $file->getExtension();
         $data = static::generateUniquePath();
         $file->saveAs($data['baseDirName'] . '/' . $data['dirname'] . '/' . $data['longdirname'] . '/' . $filename, true);
         return array('name' => $filename, 'path' => $data['baseDirName'] . '/' . $data['dirname'] . '/' . $data['longdirname'] . '/' . $filename, 'link' => $data['baseDirUrl'] . '/' . $data['dirname'] . '/' . $data['longdirname'] . '/' . $filename);

    }

    public static function saveFromUrl($url) {       
        // для каждого файла создаем директорию и сохраняем его в директорию, заносим запись в таблицу
        $path = parse_url($url, PHP_URL_PATH);
        $pathinfo = pathinfo($path);

        if (!in_array($pathinfo['extension'], static::$extensions))
            return false;
        
        $filename = $pathinfo['basename'];
        $data = static::generateUniquePath();
        
        static::curl_download($url, $data['baseDirName'] . '/' . $data['dirname'] . '/' . $data['longdirname'] . '/' . $filename);
        return array('name' => $filename, 'path' => $data['baseDirName'] . '/' . $data['dirname'] . '/' . $data['longdirname'] . '/' . $filename, 'link' => $data['baseDirUrl'] . '/' . $data['dirname'] . '/' . $data['longdirname'] . '/' . $filename);
    }

}
