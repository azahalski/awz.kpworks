<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Application;
use Bitrix\Main\UI\Extension;
use Awz\Kpworks\Access\AccessController;
use Bitrix\Main\UI\Extension as UIExt;

Loc::loadMessages(__FILE__);
global $APPLICATION;
$module_id = "awz.kpworks";
if(!Loader::includeModule($module_id)) return;
Extension::load('ui.sidepanel-content');
$request = Application::getInstance()->getContext()->getRequest();
$APPLICATION->SetTitle(Loc::getMessage('AWZ_KPWORKS_OPT_TITLE'));

\CJSCore::init(["sidepanel","jquery3","catalog_cond"]);
UIExt::load("ui.condition");
UIExt::load("ui.buttons");
UIExt::load("ui.buttons.icons");
UIExt::load("ui.alerts");
UIExt::load("ui.forms");
UIExt::load("ui.layout-form");
UIExt::load('ui.entity-selector');
UIExt::load('ui.tree-conditions');

if($request->get('IFRAME_TYPE')==='SIDE_SLIDER'){
    require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
    require_once('lib/access/include/moduleright.php');
    CMain::finalActions();
    die();
}

if(!AccessController::isViewSettings())
    $APPLICATION->AuthForm(Loc::getMessage("ACCESS_DENIED"));
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");
?>

    <div class="container">
        <div class="row">
            <div class="ui-block-wrapper">
                <div class="ui-block-title">
                    <div class="ui-block-title-text">Маршруты дел</div>
                </div>
            <div class="ui-block-content active">
                <div class="col col-12">
                    <div class="row" id="list_hook" style="display: flex;">

                        <div class="col col-lg-4 awz-marsh-item-add my-2">
                            <div class="row align-items-center border-style-1">
                                <div class="col сol-6 col-lg-6"><b>Новый маршрут</b><br>
                                    <a style="font-size: 13px;" data-user_id="<?=\Bitrix\Main\Engine\CurrentUser::get()?->getId()?>" data-admin="<?=\Bitrix\Main\Engine\CurrentUser::get()?->isAdmin()?>" data-app="" data-domain="" data-signed="" data-page="log-handler" href="#" class="awz-handler-slide">лог обработки</a>
                                </div>
                                <div class="col сol-6 col-lg-6 text-right" id="awz-handler-slide-add">
                                    <a data-user_id="<?=\Bitrix\Main\Engine\CurrentUser::get()?->getId()?>" data-admin="<?=\Bitrix\Main\Engine\CurrentUser::get()?->isAdmin()?>" data-app="" data-domain="" data-signed="" data-page="sett-handler" href="#" class="ui-btn ui-btn-sm ui-btn-success ui-btn-icon-add awz-handler-slide">Добавить</a>
                                </div>
                            </div>
                        </div>

                    </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?
    CJSCore::Init(['jquery3']);
    ?>

    <script>
        <?=file_get_contents($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/awz.kpworks/admin/script.js");?>
    </script>

    <script>

        $(document).ready(function(){
            window.AwzAppInstance = new AwzApp({
                endpointUrl: 'https://<?=Application::getInstance()->getContext()->getServer()->getHttpHost()?>/bitrix/services/main/ajax.php?action=awz:kpworks.api.works.'
            });
        });

    </script>
<style>
    .slide_side_slider .workarea, .placement_REST_APP_URI .workarea, .placement_CRM_DEAL_DETAIL_TAB .workarea
    {padding: 20px;
        box-sizing: border-box;}

    .center-error-wrap {display:block;width:90%;max-width:600px;margin:auto;
        text-align:center;}
    .row-form-currency {border-bottom:1px solid #c6cdd3;margin-bottom:20px;padding-bottom:20px;overflow:hidden;}
    .appWrap {position:relative;min-height:400px;}
    .awz-main-preload {display:block;position:absolute;top:0;left:0;width:100%;
        height:100%;background:#ffffff;opacity:0.85;z-index:1000;opacity:0.6;}
    .awz-main-load {display:block;width:50%;top:50%;margin:auto;
        margin-top:-20px;color:red;font-size:18px;position:relative;
        text-align:center;z-index:1001;
    }
    .awz-main-load span {background:red;color:#ffffff;padding:10px 20px;font-size:18px;border-radius:10px;
        display:inline-block;}
    @media (min-width: 100px){
        .container {width:100%;max-width:2200px;}
    }

    .ui-block-wrapper {
        background-color: #fff;
        padding: 0px;
        margin-bottom: 10px;
        margin-right: 10px;
    }
    /**/
    .ui-block-title {
        padding: 12px;
        border-bottom:1px solid rgba(82, 92, 105, 0.1);
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 15px;
        background: #f1f5fb;
        font-weight:bold;
    }
    .ui-block-title-text {
        font: 18px "Open Sans", "Helvetica Neue", Helvetica, Arial, sans-serif;
        color: #000;
    }

    .ui-block-title-actions-link {
        color: rgba(128, 134, 142, 0.5);
        font:13px/17px "Helvetica Neue", Helvetica, Arial, sans-serif;
        border-bottom:1px dashed;
    }
    /**/

    .ui-block-content {
        display:none;
    }
    .ui-block-content.active {display:block;padding-bottom:0px;}

    .ui-block-field-container {
        margin-bottom: 12px;
    }

    .ui-block-field-title {
        color: rgba(133, 140, 150, 0.7);
        font: 13px/18px "Helvetica Neue", Helvetica, Arial, sans-serif;
        display: block;
    }

    .ui-block-field-content {
        font: 15px/21px "Helvetica Neue", Helvetica, Arial, sans-serif;
        color: #333;
    }

    .ui-block-content-actions {
        padding:14px 0 11px;
    }

    .ui-block-content-actions-link {
        color: rgba(128, 134, 142, 0.5);
        font:13px/17px "Helvetica Neue", Helvetica, Arial, sans-serif;
        border-bottom:1px dashed;
        margin-right: 12px;
    }

    .row-form-currency-th h4 {margin-bottom:0;}
    .row-form-currency-th h4 i.ui-hint {position:relative;top:2px;}
    .row-form-currency-th h4 .ui-hint-icon {
        width: 18px;
        height: 18px;
        margin:0;
    }

    .my-inp-calendar-clt {display:block;width:39px;height:39px;position:absolute;right:0;top:0;}
    .my-inp-calendar {position:relative;}
    .my-inp-calendar-clt img {width:100%;height:100%;opacity:0;}
    .placements-settings {padding:35px 0 10px 0;}
    .placements-settings .row {margin-bottom:15px;}

    .item-map-row {border-top: 1px solid rgba(82, 92, 105, 0.1);padding:15px 0;}
    .bp-load-hide-btn {display:none;}

    #list_hook {justify-content: space-evenly;justify-content: flex-start;    flex-wrap: wrap;
        flex-direction: row;}
    #list_hook .row {
        display: flex;
        flex-direction: row;
        flex-wrap: wrap;
    }
    #list_hook .text-right {text-align: right;}
    #list_hook .text-center {text-align: center;}
    .awz-marsh-item, .awz-marsh-item-add {/*min-width:320px;*/min-height:80px;}
    .border-style-1 {border: 1px solid rgba(82, 92, 105, 0.1);width:100%;}
    .awz-marsh-item > .row, .awz-marsh-item-add > .row  {height:100%;width:100%;}
    .awz-marsh-item-active-N > .row {opacity:0.5;background:#fbd0d04a;}
    .awz-marsh-item-active-Y > .row {background:#f1fbd036;}
    .awz-marsh-item-active a {
        background: #868d95;
        color: #ffffff;
        padding: 0 2px;
        border-radius: 5px;
        min-width: 20px;
        display: inline-block;
        font-size: 12px;
        line-height: 17px;
    }
    .awz-marsh-item-active a:hover {background: #000000;}
    .side-panel-container {background:#ffffff;}
    .workarea > .container {float:left;}

    #char-stat-today {height:360px;}
    #char-stat-yesterday {height:360px;}
    #char-stat-1 {height:360px;}
    #char-stat-2 {height:360px;}
    .col {padding:0.5rem;}
    .col-1 {width:calc(100% * 1 / 12);box-sizing: border-box;}
    .col-2 {width:calc(100% * 2 / 12);box-sizing: border-box;}
    .col-3 {width:calc(100% * 3 / 12);box-sizing: border-box;}
    .col-4 {width:calc(100% * 4 / 12);box-sizing: border-box;}
    .col-5 {width:calc(100% * 5 / 12);box-sizing: border-box;}
    .col-6 {width:calc(100% * 6 / 12);box-sizing: border-box;}
    .col-7 {width:calc(100% * 7 / 12);box-sizing: border-box;}
    .col-8 {width:calc(100% * 8 / 12);box-sizing: border-box;}
    .col-9 {width:calc(100% * 9 / 12);box-sizing: border-box;}
    .col-10 {width:calc(100% * 10 / 12);box-sizing: border-box;}
    .col-11 {width:calc(100% * 11 / 12);box-sizing: border-box;}
    .col-12 {width:calc(100% * 12 / 12);box-sizing: border-box;}
    .col-lg-6 {width:50%;box-sizing: border-box;}
    .col-lg-4 {width:25%;box-sizing: border-box;min-width:360px;}
</style>
<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");