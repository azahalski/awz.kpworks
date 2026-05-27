<?
define("NOT_CHECK_PERMISSIONS", true);
define("STOP_STATISTICS", true);
define("BX_SENDPULL_COUNTER_QUEUE_DISABLE", true);
define('PUBLIC_AJAX_MODE', true);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

use Awz\BxApi\App;use Awz\BxApi\Helper;use Awz\BxApi\TokensTable;use Bitrix\Main\Application;
use Bitrix\Main\Loader;
use Bitrix\Main\Result;
use Bitrix\Main\Error;
use Bitrix\Main\Type\ParameterDictionary;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\UI\Extension as UIExt;
use Awz\Kpworks\Access\AccessController;
use Awz\Kpworks\Access\Custom\ActionDictionary;
if(!Loader::includeModule('awz.kpworks')) return;
$arTreeDescr = array(
        'js' => '/bitrix/js/catalog/core_tree.js',
        'css' => '/bitrix/panel/catalog/catalog_cond.css',
        'lang' => '/bitrix/modules/catalog/lang/ru/js_core_tree.php',
        'rel' => array('core', 'date', 'window')
);
CJSCore::RegisterExt('core_condtree', $arTreeDescr);
$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
?><?if(!$request->get('html')){?><!DOCTYPE html>
<html lang="ru">
<head>
    <?
    CJsCore::init(['jquery3','core','core_condtree']);
    UIExt::load("ui.bootstrap4");
    UIExt::load("ui.condition");
    UIExt::load("ui.buttons");
    UIExt::load("ui.buttons.icons");
    UIExt::load("ui.alerts");
    UIExt::load("ui.forms");
    UIExt::load("ui.layout-form");
    UIExt::load('ui.entity-selector');
    UIExt::load('ui.tree-conditions');
    ?>
    <?$APPLICATION->ShowHead()?>
</head>
<body style="padding:1rem;"><div class="workarea">
    <div class="container"><div class="row"><div class="result-block-messages px-0"></div></div></div>
    <?}?>
    <?php
    $pageResult = new Result();
    if(!AccessController::can(0, ActionDictionary::ACTION_LOGGER_VIEW)){
        $pageResult->addError(new Error("Нет прав на просмотр правил"));
    }
    if($pageResult->isSuccess()){
        $crmOrSmartIds = [
                '1'=>'lead',
                '2'=>'deal',
                '3'=>'contact',
                '4'=>'company',
                '7'=>'quote',
                '14'=>'order'
        ];
        foreach(\Awz\Kpworks\Helper::entityCodes() as $ent){
            $crmOrSmartIds[$ent['MIN_CODE']] = strtolower($ent['CODE']);
        }
        $filter = [];
        $filter['=APP']= 'default';
        $filter['=PORTAL']= 'default';
        if($request->get('id')){
            $filter['=ENTITY_ID'] = $request->get('id');
        }
        $logItems = \Awz\Kpworks\Custom\WorkAppLogTable::getList([
                'select'=>['*'],
                'filter'=>$filter,
                'limit'=>1000,
                'order'=>['ID'=>'DESC']
        ])->fetchAll();






        //echo'<pre>';print_r($fieldsTo);echo'</pre>';
        //echo'<pre>';print_r($hookResult);echo'</pre>';
        ?>
        <div class="container">
            <div class="row">
                <div class="ui-block-title">
                    <div class="ui-block-title-text h2">Лог обработки <?if($request->get('id')){?>маршрута ID: <?=$request->get('id')?><?}else{?> всех маршрутов<?}?></div>
                </div>
            </div>
            <?foreach($logItems as $logItem){
                if(!$logItem['PARAMS']['type']) $logItem['PARAMS']['type'] = 'bind';
                ?>
                <div class="row my-2 pb-1" style="font-size: 12px;border-bottom:1px solid #ededed;">
                    <div class="col col-4">ID: <?=$logItem['ID']?>, <?=$logItem['DATE_ADD']->toString()?></div>
                    <div class="col col-4">[<?=$logItem['PARAMS']['workId']?>] <?=$logItem['PARAMS']['workSubj']?></div>
                    <div class="col col-4">
                        <?
                        foreach($logItem['PARAMS']['ents'] as $ent){
                            if($logItem['PARAMS']['type']=='bp'){
                                $entData = explode('|||',$ent);
                                if($entData[0]=='crm'){
                                    $path = '/'.$entData[0].'/'.mb_strtolower($entData[5]).'/details/'.$entData[6].'/';
                                    if(strpos($entData[2], 'DYNAMIC_')!==false){
                                        $path = '/'.$entData[0].'/type/'.str_replace('DYNAMIC_','',$entData[5]).'/details/'.$entData[6].'/';
                                    }
                                    echo '<a class="awz-handler-slide" target="_blank" href="'.$path.'">'.$entData[5].'_'.$entData[6].'</a>';
                                }else{
                                    echo $ent;
                                }
                            }elseif($logItem['PARAMS']['type']=='rest'){
                                //echo'<pre>';print_r($ent);echo'</pre>';
                                $entData = explode('|||',$ent);
                                echo $entData[0].' - ';
                                echo print_r($entData[2], true);
                                if(isset($crmOrSmartIds[$entData[2]['entityTypeId']])){
                                    $path = '/crm/'.$crmOrSmartIds[$entData[2]['entityTypeId']].'/details/'.$entData[3].'/';
                                    echo '<a class="awz-handler-slide" target="_blank" href="'.$path.'">'.$ent.'</a>';
                                }
                            }else{
                                $entData = explode('_',$ent);
                                $path = '/crm/type/'.$entData[0].'/details/'.$entData[1].'/';
                                if(isset($crmOrSmartIds[$entData[0]])){
                                    $path = '/crm/'.$crmOrSmartIds[$entData[0]].'/details/'.$entData[1].'/';
                                }
                                echo '<a class="awz-handler-slide" target="_blank" href="'.$path.'">'.$ent.'</a>';
                            }


                            echo ' - '.$logItem['PARAMS']['type'].'; ';
                        }
                        ?>
                    </div>
                </div>
            <?}?>
        </div>
        <?
    }else{
        echo \Awz\Kpworks\Helper::errorsHtml($pageResult, 'Ошибка');
    }

    //echo'<pre>';print_r($controller->getErrors());echo'</pre>';
    //echo'<pre>';print_r($hookResult);echo'</pre>';
    ?>
    <script>
        <?=file_get_contents($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/awz.kpworks/admin/script.js");?>
    </script>
    <?if(!$request->get('html')){?>
</div>
</body></html>
<?}?>
<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");