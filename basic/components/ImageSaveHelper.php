<?php

namespace app\components;

use yii\base\Component;
use yii\imagine\Image;
use Imagine\Gd;
use Imagine\Image\Box;
use Imagine\Image\BoxInterface;
use Yii;

class ImageSaveHelper extends Component {

    private static $extensions = array('jpg', 'JPG', 'jpeg', 'JPEG', 'png', 'PNG', 'gif', 'GIF', 'tiff');

    private static function curl_download($url, $file) {
        $dest_file = fopen($file, "w");
        $resource = curl_init();
        curl_setopt($resource, CURLOPT_URL, $url);
        curl_setopt($resource, CURLOPT_FILE, $dest_file);
        curl_setopt($resource, CURLOPT_HEADER, 0);
        $result = curl_exec($resource);
        $http_code = curl_getinfo($resource, CURLINFO_HTTP_CODE);
        curl_close($resource);
        fclose($dest_file);
        if (!$result)
            return false;
        if ($http_code != 200)
            return false;
        
        if (!file_exists($file)) return false;
        if (filesize($file) < 1024) return false;
        return true;
    }

    private static function generateUniquePath() {
        $data = array();

        $data['baseDirName'] = \Yii::$app->params['path']['saveImagePath'];
        $data['baseDirUrl'] = \Yii::$app->params['path']['saveImageUrl'];


        $data['longdirname'] = uniqid('', true);
        $data['longdirname'] = str_replace('.', '', $data['longdirname']);
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
        return ['name' => $filename, 'path' => $data['baseDirName'] . '/' . $data['dirname'] . '/' . $data['longdirname'] . '/' . $filename, 'link' => $data['baseDirUrl'] . '/' . $data['dirname'] . '/' . $data['longdirname'] . '/' . $filename];
    }
    
    public static function saveFromFilePath($link) {
        
        $file= new \SplFileInfo($link);
        if (!in_array($file->getExtension(), static::$extensions))
            return false;

        $filename = $file->getBaseName() . '.' . $file->getExtension();
        $data = static::generateUniquePath();
        file_put_contents($data['baseDirName'] . '/' . $data['dirname'] . '/' . $data['longdirname'] . '/' . $filename, file_get_contents($link));
        return ['name' => $filename, 'path' => $data['baseDirName'] . '/' . $data['dirname'] . '/' . $data['longdirname'] . '/' . $filename, 'link' => $data['baseDirUrl'] . '/' . $data['dirname'] . '/' . $data['longdirname'] . '/' . $filename];
    }

    public static function compress($filename) {
        try {
            $maxWidth = 800;
            if (filesize($filename) == 0)
                return;
            var_dump($filename);
            $sizes = getimagesize($filename);
            if (!$sizes)
                return;
            if ($sizes[0] <= $maxWidth)
                return;
            $imagine = \yii\imagine\Image::getImagine();
            $imagine = $imagine->open($filename);
            $height = round($sizes[1] * $maxWidth / $sizes[0]);
            $imagine = $imagine->resize(new Box($maxWidth, $height))->save($filename, ['quality' => 85]);
        } catch (Exception $ex) {
            var_dump($ex->message);
        }
    }

    public static function saveFromUrl($url) {
        if (empty($url)) return false;
        // для каждого файла создаем директорию и сохраняем его в директорию, заносим запись в таблицу
        $path = parse_url($url, PHP_URL_PATH);
        $pathinfo = pathinfo($path);
        
        if (!is_array($pathinfo)) return false;
        
        if (!array_key_exists('extension', $pathinfo))  return false;

        if (!in_array($pathinfo['extension'], static::$extensions))
            return false;

        $filename = $pathinfo['basename'];
        $data = static::generateUniquePath();


        if (!static::curl_download($url, $data['baseDirName'] . '/' . $data['dirname'] . '/' . $data['longdirname'] . '/' . $filename)) {
            return false;
        }
        static::compress($data['baseDirName'] . '/' . $data['dirname'] . '/' . $data['longdirname'] . '/' . $filename);
        return array('name' => $filename, 'path' => $data['baseDirName'] . '/' . $data['dirname'] . '/' . $data['longdirname'] . '/' . $filename, 'link' => $data['baseDirUrl'] . '/' . $data['dirname'] . '/' . $data['longdirname'] . '/' . $filename);
    }

    public static function saveFromAutoDetect($link) {
        $findme = '//';
        $pos = strpos($link, $findme);
        if ($pos === false) {
            return static::saveFromFilePath($link);
        } else {
            return static::saveFromUrl($link);
        }
    }

}
