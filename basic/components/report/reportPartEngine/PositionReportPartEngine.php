<?php

namespace app\components\report\reportPartEngine;

// родительский класс для получения позиций из базы по сервису Allposition
class PositionReportPartEngine
{
    public function getPostition($id, $start, $end, $engine){
         // получаем позиции поисковых запросов у у проекта $id у системы поиска $engine за даты со $start по $end
         $currentPositions = \app\models\PositionValue::find()->select(['sengine.name_se AS search','query.query AS squery','position_value.query_id AS idquery','position_value.value AS position_value','position.date AS position_date','position_value.position_id AS idposition'])
                                                       ->leftJoin('sengine', 'sengine.id = position_value.sengine_id')
                                                       ->leftJoin('query', 'query.id = position_value.query_id')
                                                       ->leftJoin('position', 'position.id = position_value.position_id')
                                                       ->where(['sengine.id' => $engine])
                                                       ->andWhere(['position.project_id' => $id])
                                                       ->andWhere(['>=', 'position.date', $start])
                                                       ->andWhere(['<=', 'position.date', $end])
                                                       ->orderBy(['position.date' => SORT_DESC])
                                                       ->asArray()
                                                       ->all();
          return $currentPositions;
     }


      public function getPostitionExactly($id, $start, $end, $engine){
         // получаем позиции поисковых запросов у у проекта $id у системы поиска $engine за даты  $start и $end
         $currentPositions = \app\models\PositionValue::find()->select(['sengine.name_se AS search','query.query AS squery','position_value.query_id AS idquery','position_value.value AS position_value','position.date AS position_date','position_value.position_id AS idposition'])
                                                       ->leftJoin('sengine', 'sengine.id = position_value.sengine_id')
                                                       ->leftJoin('query', 'query.id = position_value.query_id')
                                                       ->leftJoin('position', 'position.id = position_value.position_id')
                                                       ->where(['sengine.id' => $engine])
                                                       ->andWhere(['position.project_id' => $id])
                                                       ->andWhere(['>=', 'position.date', $start])
                                                       ->andWhere(['<=', 'position.date', $end])
                                                       ->orderBy(['position.date' => SORT_DESC])
                                                       ->asArray()
                                                       ->all();
          return $currentPositions;
     }
     public function getReportPartSettings($id){
          // получаем настройки для текущей части отчета
          $currentCriteries = \app\models\ReportPartReport::find()->where(['id' => $id])->one();
          // декодируем данные из настроек части отчета
          $settings = json_decode($currentCriteries->settings,true);
          return $settings;
     }
}
