<?php

namespace app\components;

use yii\base\Component;
use Yii;

class JsonOutputHelper extends Component {

    public static function getError($msgClient,$msgDev=false,$code='err') {
        if (!$msgDev) {
            $msgDev = $msgClient;
        }
         return array('error' => true, 'data' => array('errCode' => $code, 'msgDev' => $msgDev ,'msgClient' => $msgClient));
    }   
     public static function getResult($data) {       
         return array('error' => false, 'data' => $data);
    }  
    
    
    

}

