<?php
namespace app\components\report;
interface ReportPartEngineInterface
{
    public function render($data);
    
    public function getAlert();
}