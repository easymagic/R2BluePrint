<?php 
 $_template_store_ = array();
 $_current_template_name_ = '';
 $__data_store__ = array();
 
 $__middle_wares__ = array();
 
 $__registered_Routes__ = array();


 class CoreApp{

 }

 
 function RegisterMiddleWare($classString){
   global $__middle_wares__;
   $__middle_wares__[] = $classString;
 }

 function RunMiddleWares($args=array()){
  global $__middle_wares__;
  foreach ($__middle_wares__ as $k=>$middleware){
    EvaluateStringClass($middleware,$args);
  }
 }

 $__CoreApp__ = new CoreApp;

 

 function RegisterRoute($method,$route,$ctrl){
    global $__registered_Routes__;
    
    $route = CleanRouteString($route);

    $r = explode('/', $route);
    $len = count($r);
    $node = $r[0];
    $salt = $node . ',' . $len . ',' . $method;
    if (!isset($__registered_Routes__[$salt])){
        $__registered_Routes__[$salt] = array(
          'routes'=>array(),
          'duplicates'=>array(),
          'controller'=>array()
        );
    }

    if (!in_array($route, $__registered_Routes__[$salt]['duplicates'])){
      $__registered_Routes__[$salt]['duplicates'][] = $route;
      $__registered_Routes__[$salt]['routes'][] = $r;
      $__registered_Routes__[$salt]['controller'][] = $ctrl;
    }
    

 }

 function CleanRouteString($in_Route){
   if (substr($in_Route, 0,1) == '/'){
      $in_Route = substr($in_Route, 1);
   }
   
   if (substr($in_Route, strlen($in_Route) - 1,1) == '/'){
      $in_Route = substr($in_Route, 0,strlen($in_Route) - 1);
   }
   return $in_Route;
 }


 function MatchRoute($method,$in_Route){
   global $__registered_Routes__;

   $in_Route = CleanRouteString($in_Route);
   
   $route_Collection = explode('/', $in_Route);

   $len = count($route_Collection);
   $node = $route_Collection[0];
   $salt = $node . ',' . $len . ',' . $method;
   $track = -1;
   $matched = false;
   $args = array();

   if (isset($__registered_Routes__[$salt])){


     foreach ($__registered_Routes__[$salt]['routes'] as $k=>$v){
       
         $checksum_Length = array();
         $checksum_Original_Length = sizeof($v);

       foreach ($v as $kk=>$vv){

         foreach ($route_Collection as $k1=>$v1){
          
           if ($vv == '(arg)'){
             $checksum_Length[] = 1;  
             $args[] = $route_Collection[$kk];
             
             break;
           }else if ($vv == $v1){
             
             $checksum_Length[] = 1;
             
             break;
           }

         }

         if ($checksum_Original_Length == sizeof($checksum_Length)){
           $track = $k;
           $matched = true;
           break;
         }

       }

     }

     if ($matched){
       // print_r($__registered_Routes__[$salt]['routes'][$track]);
       // echo $__registered_Routes__[$salt]['controller'][$track];
       // echo 'Args:';
       // print_r($args);
       EvaluateController($__registered_Routes__[$salt]['controller'][$track],$args);

     }else{
       throw new Exception("404 Matched Page Not Found!");
     } 
     

   }else{
     throw new Exception("404 Page Not Found!");
   }


 }

 function LoadClass($obj,$cls){

   if (file_exists($cls . '.php')){

     require_once($cls . '.php');

     $r = explode('/', $cls);
     $cls = end($r);

     $clsObj = new $cls();

     if (is_object($obj)){
       
       $obj->$cls = $clsObj;

     }

   }

 }

 function EvaluateClass($obj,$method,$args=array()){
    if (is_object($obj) && method_exists($obj, $method)){
      return call_user_func_array(array($obj,$method), $args);
    }else{
      return '';
    }
 }

 //RunMiddleWares

 function EvaluateStringClass($classString,$args=array()){
   global $__CoreApp__;
   // echo $classString;
   $r = explode(':',$classString);
   // print_r($r);
   $classString = $r[0];
   $method = 'Index';
   if (isset($r[1])){
    $method = $r[1];
   }
   $name = explode('/', $classString);
   // print_r($name);

   $cls = end($name);
   LoadClass($__CoreApp__,$classString);
   if (isset($__CoreApp__->$cls)){
    echo EvaluateClass($__CoreApp__->$cls,$method,$args); 
   }
 }

 function EvaluateController($classString,$args=array()){
    RunMiddleWares($args);
    EvaluateStringClass($classString,$args);
 }

 
 function TemplateYield($name,$value=''){
  global $_template_store_;
   if (isset($_template_store_[$name])){
      echo $_template_store_[$name];
   }else{
      echo $value;
   }
 }

 function TemplateSectionStart($name){
  global $_current_template_name_;
  ob_start();
  $_current_template_name_ = $name;
 }

 function EndTemplateSection(){
  global $_current_template_name_;
  global $_template_store_;
  $_template_store_[$_current_template_name_] = ob_get_contents();
  ob_end_clean();
 }


 function TemplateExtend($__template__,$__data__=array()){
    global $__data_store__;
    foreach ($__data__ as $_k_=>$_v_){
      $__data_store__[$_k_] = $_v_;
    }

   $__file__ = '@views/' . $__template__ . '.php'; 
   if (file_exists($__file__)){
    extract($__data_store__);
    require($__file__);
   }
 }

 function View($template,$__data__=array()){
  $__r__ = '';
  ob_start();
  TemplateExtend($template,$__data__);
  $__r__ = ob_get_contents();
  ob_end_clean();
  return $__r__;
 }


 // MatchRoute('/hello1');

 // MatchRoute('/hello2/');

 // MatchRoute('hello3/');

RegisterRoute('get','/hello','Foo:Index');
RegisterRoute('get','/hello/(arg)','Foo:Get');
RegisterRoute('post','/hello','Foo:Index');
RegisterRoute('get','/hello/(arg)/edit','Foo:Edit');
RegisterRoute('post','/hello/(arg)/edit/verb','Foo:EditPost');

// RegisterMiddleWare('MiddleWare:PreAction');

 try {
   
   MatchRoute('post','/hello/23/edit/verb');

   // $r = View('template2',array(
   //  'version'=>'View version 1.0.1'
   // ));

   // echo $r;

 } catch (Exception $e) {
   
   echo $e->getMessage();

 }

