<?php
namespace app\components\import;
interface ImporterInterface
{
    //Должен возвращать структуру array('success' => true,'data' => 1231,'error' => '')
    public function engine($supplier);
  
}