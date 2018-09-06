<?php

namespace app\controllers;

use yii\data\ActiveDataProvider;
use yii\rest\ActiveController;
use yii\web\UploadedFile;
use app\filters\auth\HttpBearerAuth;

class FileController extends ActiveController {

    public $modelClass = 'app\models\File';

    public function actions() {
        $actions = parent::actions();
        unset($actions['create'], $actions['update'], $actions['index'], $actions['view'], $actions['delete']);
        return $actions;
    }



    public function actionDelete($id) {
          if(!\Yii::$app->permissionHelper::checkPermit('file','delete')){
              throw new \yii\web\ForbiddenHttpException(sprintf('Недостаточно прав доступа!'));
          }
          // ищем файл в таблице, удаляем файл и директорию
          $model = \app\models\File::find()->where(['id' => $id])->one();
          unlink($model->path . $model->url);
          rmdir(dirname($model->path . $model->url));
          $model->delete();
    }

    public function actionView($id) {
         if(!\Yii::$app->permissionHelper::checkPermit('file','view')){
            throw new \yii\web\ForbiddenHttpException(sprintf('Недостаточно прав доступа!'));
         }
         $model = \app\models\Project::find()->where(['id' => $id])->with(['files'])->one();
         \Yii::trace(print_r($model,true));
         $fileTypes = \app\models\ProjectFileType::find()->indexBy('id')->all();
         $fileTypesArr = array();
         foreach ($fileTypes as $key => $fileType){
             $fileTypesArr[$key] = $fileType->toArray();
             $fileTypesArr[$key]['files'] = array();
         }

         foreach ($model->projectFiles as $projectFile){
             $fileTypesArr[$projectFile->project_file_type_id]['files'][] = $projectFile->file;
         }

         return (object)$fileTypesArr;
    }

    public function actionSave() {
         if(!\Yii::$app->permissionHelper::checkPermit('file','add')){
            throw new \yii\web\ForbiddenHttpException(sprintf('Недостаточно прав доступа!'));
         }

         // проверка расширений
         $extensions = array('doc','docx','xls','xlsx','jpg','jpeg','pdf','png','txt','rtf');
         // заполняем поля из post
          $params = \Yii::$app->request->post();
          // получаем переданные файлы
          $files = \yii\web\UploadedFile::getInstancesByName('file');
          // заполняем директорию проекта
          $dirName = \Yii::$app->params['path']['defaultPath']. \Yii::$app->params['path']['defaultUrl'];
          // для каждого файла создаем директорию и сохраняем его в директорию, заносим запись в таблицу
          foreach ($files as $file) {
               if(!in_array($file->getExtension(),$extensions)) continue;
               $extDir =  $params['projectId'] .'/'. uniqid() .'/';
               mkdir($dirName.$extDir, 0764);
               $filename = $extDir . $file->getBaseName() .'.'. $file->getExtension();
               // сохраняем файл
               $file->saveAs($dirName . $filename, true);
               $newfile = new \app\models\File;
               // заполняем имя файла
               $newfile->name = $file->getBaseName();
               $newfile->path = $dirName;
               $newfile->url = $filename;
               $newfile->save();
               // создаем связь с проектом
               $projectFile = new \app\models\ProjectFile();
               $projectFile->file_id = $newfile->id;
               $projectFile->project_id = $params['projectId'];
               $projectFile->project_file_type_id = $params['projectFileTypeId'];
               $projectFile->save();

          }
          return $files;
    }

    public function actionGetbyid(){
         $params=\Yii::$app->request->post();
         $model = \app\models\File::find()->where(['id' => $params['id']])->one();
         \Yii::trace(print_r($model,true));
         return '/assets/reports/'.$model['url'];
    }

    public function actionUpdate($id) {
    }

    public function behaviors() {

        $behaviors = parent::behaviors();

        $behaviors['authenticator'] = [
           'class' => HttpBearerAuth::className(),
        ];



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
        $behaviors['authenticator']['except'] = ['options','get'];

        return $behaviors;
    }

}
