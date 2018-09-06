<?php

namespace app\components\report\reportPartEngine;

use app\components\report\reportPartEnginePositionReportPartEngine;

// класс для генерации таблиц показателей позиций для текущей поисковой службы
class PositionTableByClasterReportPartEngine extends PositionReportPartEngine implements \app\components\report\ReportPartEngineInterface {

    private $allert = false;

    public function getAlert() {
        return $this->allert;
    }

    public function render($data) {
        // заполняем часть отчета - таблицы по позициям
        // идентификатор службы поиска
        $sengine_id = $data['sengine_id'];
        // идентификатор проекта
        $id = $data['project_id'];
        // идентификатор части отчета reportPartReport
        $report_part_id = $data['report_part_id'];
        // дата начала текущего отчета
        $date_start = $data['date_start'];
        // дата конца текущего отчета
        $date_end = $data['date_end'];
        // задаем порядок
        $params['order'] = $data['order'];
        // получаем текущую поисковую службу
        $params['engine'] = \app\models\Sengine::find()->where(['id' => $sengine_id])->one();
        // получаем список запросов для текущего проекта
        $params['queryArray'] = \app\models\Query::find()->where(['project_id' => $id])->indexBy('id')->all();
        // получаем список запросов для текущего проекта
        $params['queryGroupArray'] = \app\models\QueryGroup::find()->where(['project_id' => $id])->with('queries')->indexBy('id')->all();
        // получаем промежуток дат для вывода данных
        $last_date = \app\models\Position::find()->where(['project_id' => $id])
                ->andWhere(['<=', 'position.date', $date_start])
                ->orderBy(['date' => SORT_DESC])
                ->limit(1)
                ->all();
        if (isset($last_date[0]->date))
            $date_start = $last_date[0]->date;

        \Yii::trace($date_start);
        $params['dateArray'] = \app\models\Position::find()->where(['project_id' => $id])
                ->andWhere(['>=', 'position.date', $date_start])
                ->andWhere(['<=', 'position.date', $date_end])
                ->orderBy(['date' => SORT_ASC])
                //->indexBy('id')
                ->all();

        if (count($params['dateArray']) > 0) {
            $date_end = $params['dateArray'][count($params['dateArray']) - 1]->date;
        }

        $params['dateArray'] = \app\models\Position::find()->where(['project_id' => $id])
                ->andWhere(['IN', 'position.date', array($date_start,$date_end)])
                ->orderBy(['date' => SORT_ASC])
                //->indexBy('id')
                ->all();

        // получаем данныйи и выводим в формате массива - array[id группы запросов][id запроса][id позиции]
        $params['data'] = $this->getPostitionData($id, $date_start, $date_end, $sengine_id, $params['queryArray']);
        // получаем настройки текущей части отчета reportPartReport
        $params['colors'] = $this->getReportPartSettings($report_part_id);  // в json формате
        \Yii::trace(print_r($params['data'], true));
        //Проверяем алерт
        foreach ($params['queryGroupArray'] as $qgroupkey => $qgroupvalue) {
            //Для каждого кластера
            $firstTopTenCount = 0;
            foreach ($params['dateArray'] as $pkey => $pvalue) {
                //Для каждой даты (хотя их на самом деле только две)
                if (!$firstTopTenCount) {
                    $firstTopTenCount = $params['data'][$qgroupkey]['idposition-' . $pvalue['id']]['toptenquerycount'];
                } else {
                    $lastTopTenCount = $params['data'][$qgroupkey]['idposition-' . $pvalue['id']]['toptenquerycount'];
                    if ($firstTopTenCount > 0 && $lastTopTenCount == 0) {
                        if (!$this->allert)
                            $this->allert = array();
                           $this->allert[] = 'По поисковой системе '.$params['engine']->name_se.'/'.$params['engine']->name_region.' по '.$qgroupvalue->group.' позиции в полном составе покинули ТОП 10';
                    }
                }
            }
        }
        $html = \Yii::$app->view->renderFile('@app/views/report/worksdone/positionsByClaster.php', $params);
        return $html;
    }

    public function getPostitionData($id, $start, $end, $engine, $queries) {
        // полученные данные выводим в формате массива - array[id позиции][id запроса]
        $currentPositions = $this->getPostitionExactly($id, $start, $end, $engine);
        $positionsArray = array();
        foreach ($currentPositions as $item) {
            $positionsArray[$queries[$item['idquery']]->query_group_id][$item['idquery']][$item['idposition']] = $item['position_value'];

            if (!isset($positionsArray[$queries[$item['idquery']]->query_group_id]['idposition-' . $item['idposition']])) {
                $positionsArray[$queries[$item['idquery']]->query_group_id]['idposition-' . $item['idposition']] = array('querycount' => 0, 'toptenquerycount' => 0);
            }


            $positionsArray[$queries[$item['idquery']]->query_group_id]['idposition-' . $item['idposition']]['querycount'] += 1;

            if (($item['position_value'] <= 10) && ($item['position_value'] != 0))
                $positionsArray[$queries[$item['idquery']]->query_group_id]['idposition-' . $item['idposition']]['toptenquerycount'] += 1;
        }
        return $positionsArray;
    }

}
