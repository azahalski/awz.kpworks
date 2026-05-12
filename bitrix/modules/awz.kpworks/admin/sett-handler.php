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
    if(!AccessController::can(0, ActionDictionary::ACTION_SETT_VIEW)){
        $pageResult->addError(new Error("Нет прав на просмотр правил"));
    }
    if($pageResult->isSuccess()){
        $smartData = [];
        if (Loader::includeModule('crm')) {
            $typesMap = \Bitrix\Crm\Service\Container::getInstance()->getDynamicTypesMap();
            foreach ($typesMap->getTypesCollection() as $type) {
                $smartData[] = [
                    'entityTypeId'=>$type['ENTITY_TYPE_ID'],
                    'title'=>$type['TITLE']
                ];
            }
        }
        $crmOrSmartIds = [
            '1'=>'Лид',
            '2'=>'Сделка',
            '3'=>'Контакт',
            '4'=>'Компания',
            '31'=>'Счет',
            '7'=>'Предложение',
            '8'=>'Реквизит',
            '14'=>'Заказ'
        ];
        foreach($smartData as $item){
            $crmOrSmartIds[$item['entityTypeId']] = $item['title'];
        }

        //bizproc.workflow.template.list
        $zParams = [
            'ID','MODULE_ID','ENTITY','NAME','PARAMETERS','DOCUMENT_TYPE'
        ];

        $bpList = [];
        if (Loader::includeModule('bizproc')) {
            $dbRes = \Bitrix\Bizproc\WorkflowTemplateTable::getList([
                    'select' => $zParams,
                    'filter' => [
                            '=MODULE_ID' => 'crm', // берем только шаблоны CRM
                        // '=ENTITY' => 'Bitrix\Crm\Model\Dynamic\Entity1000056' // фильтр по конкретному смарт-процессу
                    ]
            ]);

            $templates = $dbRes->fetchAll();
            foreach($templates as $tmpl){
                $bpList[$tmpl['MODULE_ID'].'|||'.$tmpl['ENTITY'].'|||'.$tmpl['DOCUMENT_TYPE'][2].'|||'.$tmpl['ID']] =
                        $tmpl['NAME'].' ['.$tmpl['ENTITY'].']';
            }
        }

        $hookResult = \Awz\Kpworks\Api\Controller\Works::getSettById($request->get('id') ? : 0);

        /*$hookResult = [
                'active'=>'Y',
                'name'=>'Маршрут '.time(),
                'sort'=>500,
                'works'=>[
                        'id'=>'0',
                        'controlId'=>'CondGroup',
                        'values'=>[],
                        'children'=>[]
                ],
                'actions'=>[
                        'id'=>'0',
                        'controlId'=>'CondGroup',
                        'values'=>[],
                        'children'=>[]
                ]
        ];*/
        $params = [];
        if(!isset($hookResult['works']['id'])){
            foreach($hookResult['works'] as $k=>$v){
                $kAr = explode('__',$k);
                if(count($kAr)==1){
                    if(!isset($params[$kAr[0]])) $params[$kAr[0]] = [
                        'id'=>$kAr[0],
                        'controlId'=>$v['controlId'],
                        'values'=>['fLevel'=>$v['aggregator']],
                        'children'=>[]
                    ];
                }elseif(count($kAr)==2){
                    if(!isset($params[$kAr[0]]['children'][$kAr[1]])) $params[$kAr[0]]['children'][$kAr[1]] = [
                        'id'=>$kAr[1],
                        'controlId'=>$v['controlId'],
                        'values'=>['logic'=>$v['logic'],'value'=>$v['value']],
                        'children'=>[]
                    ];
                }
            }
        }else{
            $params[0] = $hookResult['works'];
        }
        $params2 = [];
        if(!isset($hookResult['actions']['id'])){
            foreach($hookResult['actions'] as $k=>$v){
                $kAr = explode('__',$k);
                if(count($kAr)==1){
                    if(!isset($params2[$kAr[0]])) $params2[$kAr[0]] = [
                        'id'=>$kAr[0],
                        'controlId'=>$v['controlId'],
                        'values'=>['fLevel'=>$v['aggregator']],
                        'children'=>[]
                    ];
                }elseif(count($kAr)==2){
                    if(!isset($params2[$kAr[0]]['children'][$kAr[1]])) $params2[$kAr[0]]['children'][$kAr[1]] = [
                        'id'=>$kAr[1],
                        'controlId'=>$v['controlId'],
                        'values'=>$v,
                        'children'=>[]
                    ];
                }
            }
        }else{
            $params2[0] = $hookResult['actions'];
        }

        $curValues = $params[0];
        $curActions = $params2[0];
        $condRules = [];
        $condRules[] = [
            'controlId' => 'CondGroup',
            'group'=>true,
            'label'=>'Группа условий',
            'showIn'=>[],
            'visual'=>[
                'controls'=>['fLevel'],
                'values'=>[
                    ['fLevel'=>'AND'],
                    ['fLevel'=>'OR'],
                ],
                'logic'=>[
                    ['style'=>'condition-logic-and', 'message'=>'И'],
                    ['style'=>'condition-logic-or', 'message'=>'ИЛИ']
                ]
            ],
            'control'=>[
                [
                    'id'=>'fLevel',
                    'name'=>'aggregator',
                    'type'=>'select',
                    'values'=>[
                        'AND'=>'Выполняются все условия',
                        'OR'=>'Выполняется одно из условий'
                    ],
                    'defaultText'=>'...',
                    'defaultValue'=>'AND'
                ]
            ]
        ];
        $condRules[] = [
            'controlgroup'=>true,
            'group'=>false,
            'label'=>'Поля дела',
            'showIn'=>['CondGroup'],
            'children'=>[
                [
                    'controlId'=>'PHONE',
                    'description'=>'Телефон',
                    'group'=>false,
                    'label'=>'Телефон',
                    'showIn'=>['CondGroup'],
                    'control'=>[
                        ['type'=>'prefix','id'=>'prefix','text'=>'Телефон'],
                        [
                            'type'=>'select',
                            'id'=>'logic',
                            'name'=>'logic',
                            'values'=>[
                                'Yes'=>'Заполнено',
                                'Not'=>'Не заполнено',
                                'Equals'=>'Содержит',
                                'noEquals'=>'Не содержит',
                                'EqualsIn'=>'Содержится в',
                                'noEqualsIn'=>'Не содержится в',
                                'Is'=>'Равно',
                                'No'=>'Не равно'
                            ],
                            'defaultValue'=>'Yes'
                        ],
                        [
                            'type'=>'input',
                            'id'=>'value',
                            'name'=>'value',
                            'defaultValue'=>''
                        ],
                    ]
                ],
                [
                    'controlId'=>'EMAIL_TO',
                    'description'=>'Email получателя',
                    'group'=>false,
                    'label'=>'Email получателя',
                    'showIn'=>['CondGroup'],
                    'control'=>[
                        ['type'=>'prefix','id'=>'prefix','text'=>'Email получателя'],
                        [
                            'type'=>'select',
                            'id'=>'logic',
                            'name'=>'logic',
                            'values'=>[
                                'Yes'=>'Заполнено',
                                'Not'=>'Не заполнено',
                                'Equals'=>'Содержит',
                                'noEquals'=>'Не содержит',
                                'EqualsIn'=>'Содержится в',
                                'noEqualsIn'=>'Не содержится в',
                                'Is'=>'Равно',
                                'No'=>'Не равно'
                            ],
                            'defaultValue'=>'Yes'
                        ],
                        [
                            'type'=>'input',
                            'id'=>'value',
                            'name'=>'value',
                            'defaultValue'=>''
                        ],
                    ]
                ],
                [
                    'controlId'=>'EMAIL_FROM',
                    'description'=>'Email отправителя',
                    'group'=>false,
                    'label'=>'Email отправителя',
                    'showIn'=>['CondGroup'],
                    'control'=>[
                        ['type'=>'prefix','id'=>'prefix','text'=>'Email отправителя'],
                        [
                            'type'=>'select',
                            'id'=>'logic',
                            'name'=>'logic',
                            'values'=>[
                                'Yes'=>'Заполнено',
                                'Not'=>'Не заполнено',
                                'Equals'=>'Содержит',
                                'noEquals'=>'Не содержит',
                                'EqualsIn'=>'Содержится в',
                                'noEqualsIn'=>'Не содержится в',
                                'Is'=>'Равно',
                                'No'=>'Не равно'
                            ],
                            'defaultValue'=>'Yes'
                        ],
                        [
                            'type'=>'input',
                            'id'=>'value',
                            'name'=>'value',
                            'defaultValue'=>''
                        ],
                    ]
                ],
                [
                    'controlId'=>'PROVIDER_ID',
                    'description'=>'Провайдер',
                    'group'=>false,
                    'label'=>'Провайдер',
                    'showIn'=>['CondGroup'],
                    'control'=>[
                        ['type'=>'prefix','id'=>'prefix2','text'=>'Провайдер'],
                        [
                            'type'=>'select',
                            'id'=>'logic',
                            'name'=>'logic',
                            'values'=>[
                                'Is'=>'Равен',
                                'No'=>'Не равен'
                            ],
                            'defaultValue'=>'Is'
                        ],
                        [
                            'type'=>'select',
                            'id'=>'value',
                            'name'=>'value',
                            'values'=>[
                                'CRM_EMAIL'=>'Письмо',
                                'CRM_TASKS_TASK'=>'Задача',
                                'CRM_TODO'=>'Пуш действие (TODO)',
                                'VOXIMPLANT_CALL'=>'Звонок',
                                'IMOPENLINES_SESSION'=>'Диалог открытой линии',
                                'CRM_SIGN_DOCUMENT'=>'Подпись документа'
                            ],
                            'defaultValue'=>'CRM_EMAIL'
                        ],
                    ]
                ],
                [
                    'controlId'=>'COMPLETED',
                    'description'=>'Дело выполнено',
                    'group'=>false,
                    'label'=>'Дело выполнено',
                    'showIn'=>['CondGroup'],
                    'control'=>[
                        ['type'=>'prefix','id'=>'prefix2','text'=>'Дело выполнено'],
                        [
                            'type'=>'select',
                            'id'=>'logic',
                            'name'=>'logic',
                            'values'=>[
                                'Is'=>'Равен',
                                'No'=>'Не равен'
                            ],
                            'defaultValue'=>'Is'
                        ],
                        [
                            'type'=>'select',
                            'id'=>'value',
                            'name'=>'value',
                            'values'=>[
                                'Y'=>'Да',
                                'N'=>'Нет',
                            ],
                            'defaultValue'=>'N'
                        ],
                    ]
                ],
                [
                    'controlId'=>'DIRECTION',
                    'description'=>'Вариант провайдера',
                    'group'=>false,
                    'label'=>'Вариант провайдера',
                    'showIn'=>['CondGroup'],
                    'control'=>[
                        ['type'=>'prefix','id'=>'prefix','text'=>'Вариант'],
                        [
                            'type'=>'select',
                            'id'=>'logic',
                            'name'=>'logic',
                            'values'=>[
                                'Is'=>'Равно',
                                'No'=>'Не равно'
                            ],
                            'defaultValue'=>'Is'
                        ],
                        [
                            'type'=>'select',
                            'id'=>'value',
                            'name'=>'value',
                            'values'=>[
                                '1'=>'Входящий',
                                '2'=>'Исходящий'
                            ],
                            'defaultValue'=>'1'
                        ],
                    ]
                ],
                [
                    'controlId'=>'OWNER_TYPE_ID',
                    'description'=>'Сущность',
                    'group'=>false,
                    'label'=>'Сущность',
                    'showIn'=>['CondGroup'],
                    'control'=>[
                        ['type'=>'prefix','id'=>'prefix','text'=>'Сущность'],
                        [
                            'type'=>'select',
                            'id'=>'logic',
                            'name'=>'logic',
                            'values'=>[
                                'Is'=>'Равно',
                                'No'=>'Не равно'
                            ],
                            'defaultValue'=>'Is'
                        ],
                        [
                            'type'=>'select',
                            'id'=>'value',
                            'name'=>'value',
                            'values'=>$crmOrSmartIds,
                            'defaultValue'=>'3'
                        ],
                    ]
                ],
                [
                    'controlId'=>'AUTHOR_ID',
                    'description'=>'Кем создано дело',
                    'group'=>false,
                    'label'=>'Создатель дела',
                    'showIn'=>['CondGroup'],
                    'control'=>[
                        ['type'=>'prefix','id'=>'prefix','text'=>'Создатель дела'],
                        [
                            'type'=>'select',
                            'id'=>'logic',
                            'name'=>'logic',
                            'values'=>[
                                'Yes'=>'Заполнено',
                                'Not'=>'Не заполнено',
                                'Equals'=>'Содержит',
                                'noEquals'=>'Не содержит',
                                'EqualsIn'=>'Содержится в',
                                'noEqualsIn'=>'Не содержится в',
                                'Is'=>'Равно',
                                'No'=>'Не равно'
                            ],
                            'defaultValue'=>'Yes'
                        ],
                        [
                            'type'=>'input',
                            'id'=>'value',
                            'name'=>'value',
                            'defaultValue'=>''
                        ],
                    ]
                ],
                [
                    'controlId'=>'RESPONSIBLE_ID',
                    'description'=>'Ответственный',
                    'group'=>false,
                    'label'=>'Ответственный',
                    'showIn'=>['CondGroup'],
                    'control'=>[
                        ['type'=>'prefix','id'=>'prefix','text'=>'Ответственный'],
                        [
                            'type'=>'select',
                            'id'=>'logic',
                            'name'=>'logic',
                            'values'=>[
                                'Yes'=>'Заполнено',
                                'Not'=>'Не заполнено',
                                'Equals'=>'Содержит',
                                'noEquals'=>'Не содержит',
                                'EqualsIn'=>'Содержится в',
                                'noEqualsIn'=>'Не содержится в',
                                'Is'=>'Равно',
                                'No'=>'Не равно'
                            ],
                            'defaultValue'=>'Yes'
                        ],
                        [
                            'type'=>'input',
                            'id'=>'value',
                            'name'=>'value',
                            'defaultValue'=>''
                        ],
                    ]
                ],
                [
                    'controlId'=>'SUBJECT',
                    'description'=>'Тема',
                    'group'=>false,
                    'label'=>'Тема',
                    'showIn'=>['CondGroup'],
                    'control'=>[
                        ['type'=>'prefix','id'=>'prefix','text'=>'Тема'],
                        [
                            'type'=>'select',
                            'id'=>'logic',
                            'name'=>'logic',
                            'values'=>[
                                'Yes'=>'Заполнено',
                                'Not'=>'Не заполнено',
                                'Equals'=>'Содержит',
                                'noEquals'=>'Не содержит',
                                'EqualsIn'=>'Содержится в',
                                'noEqualsIn'=>'Не содержится в',
                                'Is'=>'Равно',
                                'No'=>'Не равно'
                            ],
                            'defaultValue'=>'Yes'
                        ],
                        [
                            'type'=>'input',
                            'id'=>'value',
                            'name'=>'value',
                            'defaultValue'=>''
                        ],
                    ]
                ],
                [
                    'controlId'=>'SUBJECT_MAIN',
                    'description'=>'Тема без re: и fwd:',
                    'group'=>false,
                    'label'=>'Тема без re: и fwd:',
                    'showIn'=>['CondGroup'],
                    'control'=>[
                        ['type'=>'prefix','id'=>'prefix','text'=>'Тема без re: и fwd:'],
                        [
                            'type'=>'select',
                            'id'=>'logic',
                            'name'=>'logic',
                            'values'=>[
                                'Yes'=>'Заполнено',
                                'Not'=>'Не заполнено',
                                'Equals'=>'Содержит',
                                'noEquals'=>'Не содержит',
                                'EqualsIn'=>'Содержится в',
                                'noEqualsIn'=>'Не содержится в',
                                'Is'=>'Равно',
                                'No'=>'Не равно'
                            ],
                            'defaultValue'=>'Yes'
                        ],
                        [
                            'type'=>'input',
                            'id'=>'value',
                            'name'=>'value',
                            'defaultValue'=>''
                        ],
                    ]
                ],
                [
                    'controlId'=>'SUBJECT_SQ',
                    'description'=>'Первое значение в скобках []',
                    'group'=>false,
                    'label'=>'Первое значение в скобках []',
                    'showIn'=>['CondGroup'],
                    'control'=>[
                        ['type'=>'prefix','id'=>'prefix','text'=>'Первое значение в скобках []'],
                        [
                            'type'=>'select',
                            'id'=>'logic',
                            'name'=>'logic',
                            'values'=>[
                                'Yes'=>'Заполнено',
                                'Not'=>'Не заполнено',
                                'Equals'=>'Содержит',
                                'noEquals'=>'Не содержит',
                                'EqualsIn'=>'Содержится в',
                                'noEqualsIn'=>'Не содержится в',
                                'Is'=>'Равно',
                                'No'=>'Не равно'
                            ],
                            'defaultValue'=>'Yes'
                        ],
                        [
                            'type'=>'input',
                            'id'=>'value',
                            'name'=>'value',
                            'defaultValue'=>''
                        ],
                    ]
                ],
            ]
        ];


        $actionRules = [];
        $actionRules[] = [
            'controlId'=>'CondGroup',
            'defaultText'=>"",
            'group'=>true,
            'label'=>"",
            "showIn"=>[],
            'visual'=>[
                'controls'=>['fLevel'],
                'values'=>[
                    ['fLevel'=>'AND'],
                    ['fLevel'=>'OR'],
                ],
                'logic'=>[
                    ['style'=>'condition-logic-and', 'message'=>'И'],
                    ['style'=>'condition-logic-or', 'message'=>'ИЛИ']
                ]
            ],
            'control'=>[
                "Выполнить ",
                [
                    'id'=>'fLevel',
                    'name'=>'aggregator',
                    'type'=>'select',
                    'values'=>[
                        'AND'=>'все действия',
                        'OR'=>'первое выполнимое действие'
                    ],
                    'defaultText'=>'...',
                    'defaultValue'=>'OR'
                ]
            ]
        ];
        $actionRules[] = [
            'controlId' => 'actionAny',
            'group'=>true,
            'label'=>'Добавить привязку к сущности',
            'defaultText'=>'Привязка к сущности',
            'showIn'=>['CondGroup'],
            //'mess'=>['ADD_CONTROL'=>'','DELETE_CONTROL'=>'','SELECT_CONTROL'=>''],
            'visual'=>[
            ],
            'control'=>[
                'Добавить привязку',
                'для найденных',
                [
                    'type'=>'input',
                    'id'=>'cnt',
                    'name'=>'cnt',
                    'defaultValue'=>'1'
                ],
                'сущности(ей)',
                'типа',
                [
                    'type'=>'select',
                    'id'=>'type',
                    'name'=>'type',
                    'values'=>$crmOrSmartIds,
                    'defaultValue'=>'3'
                ],
                'осуществив поиск ',
                [
                    'type'=>'select',
                    'id'=>'search',
                    'name'=>'search',
                    'values'=>[
                        'expert'=>'как эксперт',
                        'contact_expert_json'=>'по Контакту, а контакт по json фильтру',
                        'company_expert_json'=>'по Компании, а компанию по json фильтру',
                        'contact_phone'=>'по Контакту, а контакт по Телефону',
                        'contact_email'=>'по Контакту, а контакт по Email отправителя',
                        'contact_email2'=>'по Контакту, а контакт по Email получателя',
                        'entity_phone'=>'по Телефону в свойстве с кодом',
                        'entity_email'=>'по Email получателя в свойстве с кодом',
                        'entity_email2'=>'по Email отправителя в свойстве с кодом',
                        'works_subject'=>'по привязанным Делам, а сущность в деле по теме дела без fwd: и re:',
                        'works_expert_json'=>'по привязанным Делам, а сущность в деле по json фильтру'
                        //'contact_email_cmp'=>'Контакта c компанией по Email отправителя',
                        //'contact_email2_cmp'=>'Контакта c компанией по Email получателя',
                        //'contact_email_nocmp'=>'Контакта без компании по Email отправителя',
                        //'contact_email2_nocmp'=>'Контакта без компании по Email получателя'
                    ],
                    'defaultValue'=>'contact_email'
                ],
                '(',
                [
                    'type'=>'input',
                    'id'=>'prop_code',
                    'name'=>'prop_code',
                    'defaultValue'=>''
                ],
                ')',
                'и',
                [
                    'type'=>'select',
                    'id'=>'apply',
                    'name'=>'apply',
                    'values'=>[
                        'empty'=>'довериться приложению',
                        'json'=>'применить JSON фильтр'
                    ],
                    'defaultValue'=>'empty'
                ],
                '(',
                [
                    'type'=>'input',
                    'id'=>'apply_value',
                    'name'=>'apply_value',
                    'defaultValue'=>''
                ],
                ')',
                /*'а, все старые привязки элементов типа сущности',
                [
                    'type'=>'select',
                    'id'=>'bindingsdel',
                    'name'=>'bindingsdel',
                    'values'=>[
                        'yes'=>'удалить безвозвратно',
                        'no'=>'не удалять'
                    ],
                    'defaultValue'=>'no'
                ],*/
            ]
        ];



        $actionRules[] = [
            'controlId' => 'actionDeleteEntity',
            'group'=>true,
            'label'=>'Удалить сущность дела',
            'defaultText'=>'Удалить сущность дела',
            'showIn'=>['CondGroup'],
            //'mess'=>['ADD_CONTROL'=>'','DELETE_CONTROL'=>'','SELECT_CONTROL'=>''],
            'visual'=>[
            ],
            'control'=>[
                'Операция удаления безвозвратна!',
                [
                    'type'=>'select',
                    'id'=>'apply',
                    'name'=>'apply',
                    'values'=>[
                        'Y'=>'Да. Понятно.',
                        'N'=>'Нет. Не удалять.'
                    ],
                    'defaultValue'=>'N'
                ],
                'Удалить сущность CRM',
                [
                    'type'=>'select',
                    'id'=>'checker',
                    'name'=>'checker',
                    'values'=>[
                        'v1'=>'только если есть элемент в правиле выше',
                        'v2'=>'всегда, даже если не найдены элементы в правиле выше',
                        'ex'=>'по json фильтру crm.item.list (limit <= 50 - обязателен)'
                    ],
                    'defaultValue'=>'v1'
                ],
                '(',
                [
                    'type'=>'input',
                    'id'=>'paramsjson',
                    'name'=>'paramsjson',
                    'defaultValue'=>''
                ],
                ')',
                'и если OWNER_TYPE_ID равен',
                [
                    'type'=>'select',
                    'id'=>'crm_type_value',
                    'name'=>'crm_type_value',
                    'values'=>$crmOrSmartIds,
                    'defaultValue'=>'1'
                ],
            ]
        ];

        $actionRules[] = [
            'controlId' => 'actionDeleteBind',
            'group'=>true,
            'label'=>'Удалить старые привязки дела',
            'defaultText'=>'Удалить старые привязки дела',
            'showIn'=>['CondGroup'],
            //'mess'=>['ADD_CONTROL'=>'','DELETE_CONTROL'=>'','SELECT_CONTROL'=>''],
            'visual'=>[
            ],
            'control'=>[
                'Операция удаления безвозвратна!',
                [
                    'type'=>'select',
                    'id'=>'apply',
                    'name'=>'apply',
                    'values'=>[
                        'Y'=>'Да. Понятно.',
                        'N'=>'Нет. Не удалять.'
                    ],
                    'defaultValue'=>'N'
                ],
                'Удалить старые привязки к элементам CRM',
                [
                    'type'=>'select',
                    'id'=>'checker',
                    'name'=>'checker',
                    'values'=>[
                        'v1'=>'только если найден элемент в правиле выше',
                        'v2'=>'всегда, даже если не найдены элементы в правиле выше'
                    ],
                    'defaultValue'=>'v1'
                ]
            ]
        ];


        $actionRules[] = [
            'controlId' => 'actionRestCall',
            'group'=>true,
            'label'=>'Выполнить REST действие от приложения',
            'defaultText'=>'Выполнить REST действие от приложения',
            'showIn'=>['CondGroup'],
            //'mess'=>['ADD_CONTROL'=>'','DELETE_CONTROL'=>'','SELECT_CONTROL'=>''],
            'visual'=>[
            ],
            'control'=>[
                'Выполнить REST метод',
                [
                    'type'=>'input',
                    'id'=>'value',
                    'name'=>'value',
                    'defaultValue'=>'crm.lead.add'
                ],
                'И параметрами в JSON',
                [
                    'type'=>'input',
                    'id'=>'paramsjson',
                    'name'=>'paramsjson',
                    'defaultValue'=>'{}'
                ],
            ]
        ];

        $actionRules[] = [
            'controlId' => 'actionCleverMail',
            'group'=>true,
            'label'=>'Умная привязка писем по темам',
            'defaultText'=>'Умная привязка писем по темам',
            'showIn'=>['CondGroup'],
            //'mess'=>['ADD_CONTROL'=>'','DELETE_CONTROL'=>'','SELECT_CONTROL'=>''],
            'visual'=>[
            ],
            'control'=>[
                'Выполнить умную привязку писем по темам',
                'и довериться',
                'приложению',
                [
                    'type'=>'select',
                    'id'=>'apply',
                    'name'=>'apply',
                    'values'=>[
                        'Y'=>'Да',
                        'N'=>'Нет'
                    ],
                    'defaultValue'=>'N'
                ]
            ]
        ];

        $actionRules[] = [
            'controlId' => 'actionFindEntity',
            'group'=>true,
            'label'=>'Найти элемент по фильтру',
            'defaultText'=>'Найти элемент по фильтру',
            'showIn'=>['CondGroup'],
            //'mess'=>['ADD_CONTROL'=>'','DELETE_CONTROL'=>'','SELECT_CONTROL'=>''],
            'visual'=>[
            ],
            'control'=>[
                'Найти элемент CRM',
                [
                    'type'=>'select',
                    'id'=>'checker',
                    'name'=>'checker',
                    'values'=>[
                        'ex'=>'по json фильтру crm.item.list (limit <= 50 - обязателен)'
                    ],
                    'defaultValue'=>'ex'
                ],
                '(',
                [
                    'type'=>'input',
                    'id'=>'paramsjson',
                    'name'=>'paramsjson',
                    'defaultValue'=>''
                ],
                ')',
                'и если OWNER_TYPE_ID равен',
                [
                    'type'=>'select',
                    'id'=>'crm_type_value',
                    'name'=>'crm_type_value',
                    'values'=>$crmOrSmartIds,
                    'defaultValue'=>'1'
                ],
            ]
        ];

        $actionRules[] = [
            'controlId' => 'actionBreak',
            'group'=>true,
            'label'=>'Прервать выполнение',
            'defaultText'=>'Прервать выполнение',
            'showIn'=>['CondGroup'],
            //'mess'=>['ADD_CONTROL'=>'','DELETE_CONTROL'=>'','SELECT_CONTROL'=>''],
            'visual'=>[
            ],
            'control'=>[
                'Прервать выполнение действий',
                [
                    'type'=>'select',
                    'id'=>'apply',
                    'name'=>'apply',
                    'values'=>[
                        'Y'=>'Да',
                        'N'=>'Нет'
                    ],
                    'defaultValue'=>'N'
                ],
                [
                    'type'=>'select',
                    'id'=>'checker',
                    'name'=>'checker',
                    'values'=>[
                        'v1'=>'если найден элемент в действиях поиска',
                        'v2'=>'если не найден элемент в действиях поиска'
                    ],
                    'defaultValue'=>'v1'
                ],
                'Даже если логика "И"'
            ]
        ];

        if(!empty($bpList)){
            $actionRules[] = [
                'controlId' => 'actionBpStart',
                'group'=>true,
                'label'=>'Запустить Бизнес-процесс',
                'defaultText'=>'Запустить Бизнес-процесс',
                'showIn'=>['CondGroup'],
                //'mess'=>['ADD_CONTROL'=>'','DELETE_CONTROL'=>'','SELECT_CONTROL'=>''],
                'visual'=>[
                ],
                'control'=>[
                    'Запустить Бизнес-процесс',
                    [
                        'type'=>'select',
                        'id'=>'value',
                        'name'=>'value',
                        'values'=>$bpList,
                        'defaultValue'=>''
                    ],
                    'С идентификатором элемента',
                    [
                        'type'=>'input',
                        'id'=>'elid',
                        'name'=>'elid',
                        'defaultValue'=>'#OWNER_ID#'
                    ],
                    'И параметрами запуска в JSON',
                    [
                        'type'=>'input',
                        'id'=>'paramsjson',
                        'name'=>'paramsjson',
                        'defaultValue'=>'{}'
                    ],
                ]
            ];
        }






        //echo'<pre>';print_r($fieldsTo);echo'</pre>';
        //echo'<pre>';print_r($hookResult);echo'</pre>';
        ?>
        <form method="post" id="sett-handler-form">
            <input type="hidden" name="id" value="<?=intval($request->get('id'))?>">
            <div class="container">
                <div class="row">
                    <div class="ui-block-title">
                        <div class="ui-block-title-text h2">Настройка маршрута</div>
                    </div>
                </div>
                <?if(!$request->get('id')){?>
                <div class="row my-2 align-items-center">
                    <div class="col col-2">
                        Предустановленные правила
                    </div>
                    <div class="col col-10">
                        <div class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown">
                            <div class="ui-ctl-after ui-ctl-icon-angle"></div>
                            <select class="ui-ctl-element" name="preset" id="preset">
                                <option value="">Ручная настройка</option>
                                <option value="1">Умная привязка писем по заголовкам</option>
                            </select>
                        </div>
                    </div>
                </div>
                <?}?>
                <div class="row row-no-preset my-2 align-items-center">
                    <div class="col col-2">
                        Активность
                    </div>
                    <div class="col col-5">
                        <label class="ui-ctl ui-ctl-checkbox" for="active">
                            <input type="checkbox" name="active" id="active" class="ui-ctl-element awz-row" value="Y" autocomplete="off"<?if($hookResult['active']=='Y'){?> checked="checked"<?}?>>
                            <div class="ui-ctl-label-text">да</div>
                        </label>
                    </div>
                    <div class="col col-5">
                        <label class="ui-ctl ui-ctl-checkbox" for="delete" style="float: right;">
                            <input type="checkbox" name="delete" id="delete" class="ui-ctl-element awz-row" value="Y" autocomplete="off">
                            <div class="ui-ctl-label-text">удалить правило</div>
                        </label>
                    </div>
                </div>
                <div class="row row-no-preset my-2 align-items-center">
                    <div class="col col-2">
                        Название
                    </div>
                    <div class="col col-10">
                        <input type="text" name="name" class="ui-ctl-element awz-row" value="<?=htmlspecialcharsbx($hookResult['name'])?>" placeholder="">
                    </div>
                </div>
                <div class="row row-no-preset my-2 align-items-center">
                    <div class="col col-2">
                        Сортировка правила
                    </div>
                    <div class="col col-10">
                        <input type="text" name="sort" class="ui-ctl-element awz-row" value="<?=htmlspecialcharsbx($hookResult['sort'])?>" placeholder="">
                    </div>
                </div>
                <div class="row row-no-preset my-2 align-items-center">
                    <div class="col col-2">
                        Правило
                    </div>
                    <div class="col col-10">
                        <div id="worksConditions"></div>
                    </div>
                </div>
                <div class="row row-no-preset my-2 align-items-center">
                    <div class="col col-2">
                        Макросы для JSON фильтра
                    </div>
                    <div class="col col-10">
                        #CONTACT_IDS# - Массив из ид найденных контактов/компаний;
                        #EL_IDS# - Массив из ид найденных элементов crm (int);
                        #CRM_IDS# - Массив из найденных элементов crm (D_300);
                        #CRM_ID# - Элемент crm с дела (D_300);
                        #PHONE# - все числа с названия дела;
                        #SUBJECT_SQ# - первое значение в скобках [] в названии дела;
                        #SUBJECT_SQS# - массив значений в скобках [] в названии дела;
                        #MSG_SQS# - массив значений в скобках [] в описании дела;
                        #EMAIL_FROM# - Email отправителя;
                        #EMAIL_TO# - Email получателя; поля дела, согласно документации <a target="_blank" href="https://apidocs.bitrix24.ru/api-reference/crm/timeline/activities/activity-base/crm-activity-get.html">crm.activity.get</a>
                    </div>
                </div>
                <div class="row row-no-preset my-2 align-items-center">
                    <div class="col col-2">
                        Действие
                    </div>
                    <div class="col col-10">
                        <div id="actionConditions"></div>
                    </div>
                </div>
                <div class="row">
                    <div class="col col-12">
                        <button type="submit" id="awz-save-hook-params" class="ui-btn ui-btn-success ui-btn-icon-success">Сохранить</button>
                    </div>
                </div>

            </div>
        </form>
        <script>
            $(document).ready(function(){
                window.AwzAppInstance = new AwzApp({
                    endpointUrl: 'https://<?=Application::getInstance()->getContext()->getServer()->getHttpHost()?>/bitrix/services/main/ajax.php?action=awz:kpworks.api.works.',
                    noHandlers: true,
                });
                let condRules = <?=\Bitrix\Main\Web\Json::encode($condRules)?>;
                let condRules2 = <?=\Bitrix\Main\Web\Json::encode($actionRules)?>;
                let curValues = <?=\Bitrix\Main\Web\Json::encode($curValues)?>;
                let curValues2 = <?=\Bitrix\Main\Web\Json::encode($curActions)?>;
                function initValues(){
                    initValues.worksTree = new BX.TreeConditions({
                        'parentContainer': 'worksConditions',
                        'form': 'worksConditions',
                        'formName': 'works_conditions',
                        'sepID': '__',
                        'prefix': 'works'
                    },curValues,condRules);
                    initValues.actionTree = new BX.TreeConditions({
                        'parentContainer': 'actionConditions',
                        'form': 'actionConditions',
                        'formName': 'action_conditions',
                        'sepID': '__',
                        'prefix': 'actions',
                        'messTree':{'SELECT_CONTROL':'Выбрать действие','ADD_CONTROL':'Добавить действие'}
                    },curValues2,condRules2);
                }
                initValues();
            });
            $(document).on('submit', '#sett-handler-form',function(e){
                if(!!e) e.preventDefault();
                $.ajax({
                    url: window.AwzAppInstance.endpointUrl+'savehook',
                    data: $(this).serialize(),
                    dataType : "json",
                    type: "POST",
                    success: function (data, textStatus){
                        if(window.awz_helper.check_ok(data)){
                            var msg = window.awz_helper.ok.get_text(data['data']['msg']);
                            $('input[name="id"]').val(data['data']['id']);
                            window.awz_helper.showMessage(msg);
                        }else{
                            var msg = window.awz_helper.errors.get_text(data);
                            window.awz_helper.showMessage(msg);
                        }
                    },
                    error: function (){
                        var msg = window.awz_helper.errors.get_text('внутренняя ошибка сервера');
                        window.awz_helper.showMessage(msg);
                        window.awz_helper.remove_loader();
                    }
                });
            });
        </script>
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