<?php

namespace app\components;

use yii;
use yii\base\Component;
use yii\helpers\ArrayHelper;

class ExcelHelper extends Component {

    public static function formatTime($min) {
        return round($min / 60);
    }

    public static function getExcelByWorkTime($res) {
        //Массив [Человек][Проект - id][Тип работы -id]
        //                                  

        $data = array();
        $users = array();
        $work_types = array();
        $projects = array();

        foreach ($res as $element) {
            //Заполняем справочники пользователей, типов работ, проектов
            if (!array_key_exists($element['user_id'], $users)) {
                $users[$element['user_id']] = $element['user'];
            }
            if (!array_key_exists($element['work_type_id'], $work_types)) {
                $work_types[$element['work_type_id']] = $element['workType'];
            }
            if (!array_key_exists($element['project_id'], $projects)) {
                $projects[$element['project_id']] = $element['project'];
            }
            //Заполняем основную таблицу
            if (!array_key_exists($element['user_id'], $data)) {
                $data[$element['user_id']] = array();
            }

            if (!array_key_exists($element['project_id'], $data[$element['user_id']])) {
                $data[$element['user_id']][$element['project_id']] = array();
            }


            if (!array_key_exists($element['work_type_id'], $data[$element['user_id']][$element['project_id']])) {
                $data[$element['user_id']][$element['project_id']][$element['work_type_id']] = $element['minute'];
            } else {
                $data[$element['user_id']][$element['project_id']][$element['work_type_id']] += $element['minute'];
            }
        }
        $objPHPExcel = new \PHPExcel();
        $a = 1;
        $objPHPExcel->setActiveSheetIndex(0)
                ->setCellValueByColumnAndRow(0, $a, 'Специалист')
                ->setCellValueByColumnAndRow(1, $a, 'Название проекта');
        $i = 2;
        foreach ($work_types as $workTypeElement) {
            $objPHPExcel->setActiveSheetIndex(0)
                    ->setCellValueByColumnAndRow($i, $a, $workTypeElement['name']);
            $i++;
        }
        $objPHPExcel->setActiveSheetIndex(0)
                ->setCellValueByColumnAndRow($i, $a, 'Сумма');

        $a++;
        foreach ($data as $userId => $userElement) {
            foreach ($userElement as $projectId => $projectElement) {
                $objPHPExcel->setActiveSheetIndex(0)
                        ->setCellValueByColumnAndRow(0, $a, $users[$userId]['name'])
                        ->setCellValueByColumnAndRow(1, $a, $projects[$projectId]['name']);
                $i = 2;
                $sumVal = 0;
                foreach ($work_types as $workTypeId => $workTypeElement) {
                    $val = 0;
                    if (array_key_exists($workTypeId, $projectElement)) {
                        $val = $projectElement[$workTypeId];
                    }
                    $sumVal += $val;
                    $objPHPExcel->setActiveSheetIndex(0)
                            ->setCellValueByColumnAndRow($i, $a, ExcelHelper::formatTime($val));
                    $i++;
                }
                $objPHPExcel->setActiveSheetIndex(0)
                        ->setCellValueByColumnAndRow($i, $a, ExcelHelper::formatTime($sumVal));
                $a++;
            }
        }

        $fname = uniqid();
        $filename = '/assets/reports/' . $fname . '.xls';
        $path = $_SERVER['DOCUMENT_ROOT'] . $filename;

        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save($path);
        return $filename;
    }

}
