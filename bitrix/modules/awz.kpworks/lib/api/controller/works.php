<?php
namespace Awz\Kpworks\Api\Controller;

use Awz\Kpworks\Api\Scopes\Controller;
use Awz\Kpworks\Custom\WorkAppLogTable;
use Awz\Kpworks\Helper;
use Bitrix\Main\Diag;
use Bitrix\Main\Error;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Main\Application;
use Bitrix\Main\Web\Json;
use Psr\Log\LogLevel;
use Bitrix\Crm\Service;
use Awz\Kpworks\Access\AccessController;
use Awz\Kpworks\Access\Custom\ActionDictionary;

Loc::loadMessages(__FILE__);

class Works extends Controller
{
    public static $lastAddId = 0;

    public function configureActions()
    {
        $config = [
            'savehook'=>[
                'prefilters' => [
                    new \Bitrix\Main\Engine\ActionFilter\Authentication()
                ]
            ],
            'listhook'=>[
                'prefilters' => [
                    new \Bitrix\Main\Engine\ActionFilter\Authentication()
                ]
            ]
        ];
        return $config;
    }

    public static function calcKeys($value=[]){
        $newValue = [];
        $keyCnt = 0;
        foreach($value as $k=>$v){
            $realK = '';
            if($k==0) {
                $realK = '0';
            }else{
                $realK = '0__'.$keyCnt;
                $keyCnt++;
            }
            $newValue[$realK] = $v;
        }
        return $newValue;
    }

    public static function getSearch($value, $type){
        if(!$value) return [];
        if(!Loader::includeModule('crm')) return [];
        $logger = \Bitrix\Main\Diag\Logger::create('AwzKpworksWorks', [null]);
        $logger?->debug(
            "[getSearch] - {type} - {value}\n",
            [
                'value' => $value,
                'type' => $type
            ]
        );
        $filter = [];
        if($type == 'contact_expert_json'){
            try{
                $filter = Json::decode($value);
            }catch (\Exception $e){
                return [];
            }
        }
        if($type == 'company_expert_json'){
            try{
                $filter = Json::decode($value);
            }catch (\Exception $e){
                return [];
            }
        }
        if($type == 'works_expert_json'){
            try{
                $filter = Json::decode($value[2]);
            }catch (\Exception $e){
                return [];
            }
        }

        $logger?->debug(
            "[getSearch] - {filter}\n",
            [
                'filter' => $filter
            ]
        );

        static $searchData = [];
        $key = md5(serialize($value).$type);
        if(isset($searchData[$key])) {
            return $searchData[$key];
        }
        $searchData[$key] = [];

        if(in_array($type, ['contact_email','contact_email2'])){
            $contactsData = \Bitrix\Crm\ContactTable::getList([
                'filter' => ['=EMAIL' => $value], // Поиск по Email
                'order'  => ['ID' => 'DESC'],
                'select' => ['ID']
            ])->fetchAll();
            if (!empty($contactsData)) {
                foreach ($contactsData as $contactData) {
                    $searchData[$key][] = $contactData['ID'];
                }
            }
        }
        elseif(in_array($type, ['contact_phone'])){
            $contactsData = \Bitrix\Crm\ContactTable::getList([
                'filter'=>[
                    'PHONE'=>['+'.$value, $value, preg_replace('/([^0-9])/', ' ', $value)],
                    'HAS_PHONE'=>'Y'
                ],
                'order'  => ['ID' => 'DESC'],
                'select' => ['ID']
            ])->fetchAll();
            if (!empty($contactsData)) {
                foreach ($contactsData as $contactData) {
                    $searchData[$key][] = $contactData['ID'];
                }
            }
        }
        elseif($type == 'contact_expert_json'){
            $params = [
                'filter'=>$filter,
                'order'=>['ID'=>'DESC'],
                'select'=>['ID','PHONE']
            ];
            if(isset($filter['filter']) || isset($filter['order']) || isset($filter['limit'])){
                $params = $filter;
            }
            $logger?->debug(
                "[getSearch] - {params}\n",
                [
                    'params' => Json::encode($params)
                ]
            );

            $contactsData = \Bitrix\Crm\ContactTable::getList($params)->fetchAll();
            if (!empty($contactsData)) {
                foreach ($contactsData as $contactData) {
                    $searchData[$key][] = $contactData['ID'];
                }
            }
        }
        elseif($type == 'company_expert_json'){
            $params = [
                'filter'=>$filter,
                'order'=>['ID'=>'DESC'],
                'select'=>['ID','PHONE']
            ];
            if(isset($filter['filter']) || isset($filter['order']) || isset($filter['limit'])){
                $params = $filter;
            }
            $logger?->debug(
                "[getSearch] - {params}\n",
                [
                    'params' => Json::encode($params)
                ]
            );
            $contactsData = \Bitrix\Crm\CompanyTable::getList($params)->fetchAll();
            if (!empty($contactsData)) {
                foreach ($contactsData as $contactData) {
                    $searchData[$key][] = $contactData['ID'];
                }
            }
        }
        elseif(in_array($type,['works_expert_json'])){
            $params = [
                'filter'=>$filter,
                'order'=>['ID'=>'DESC'],
                'select'=>['ID','SETTINGS','DIRECTION','SUBJECT'],
                //'limit'=>50
            ];
            if(isset($filter['filter']) || isset($filter['order']) || isset($filter['limit'])){
                $params = $filter;
            }
            //unset($params['filter']['SUBJECT']);

            $logger?->debug(
                "[getSearch] - {params}\n",
                [
                    'params' => Json::encode($params)
                ]
            );

            $dbRes = \CCrmActivity::GetList(
                $params['order'],
                $params['filter'],
                false, // Группировка
                ['nPageSize'=>50], // Навигация
                [],
                ['CHECK_PERMISSIONS' => 'N'] // 'N' - если нужно искать по всем делам, игнорируя права текущего юзера
            );

            $worksData = [];
            while ($activity = $dbRes->Fetch()) {
                $worksData[] = $activity;
            }

            if(!empty($worksData)){
                foreach($worksData as $workData){

                    $emailsFrom = self::getEmailFrom($workData);
                    $emailFrom = !empty($emailsFrom) ? $emailsFrom[0] : '';
                    $emailsTo = self::getEmailTo($workData);
                    $emailTo = !empty($emailsTo) ? $emailsTo[0] : '';

                    $logger?->debug(
                        "[emails] - {emailFrom} - {emailTo} - {workData}\n",
                        [
                            'emailFrom' => $emailFrom,
                            'emailTo' => $emailTo,
                            'workData'=>$workData
                        ]
                    );
                    if(
                        ($emailFrom == $value[0] && $emailTo == $value[1]) ||
                        ($emailFrom == $value[1] && $emailTo == $value[0])
                    )
                    {
                        $bindings = \CCrmActivity::GetBindings($workData['ID']);
                        foreach ($bindings as $binding) {

                            $ownerTypeId = (int)($binding['OWNER_TYPE_ID'] ?? $binding['entityTypeId']);
                            $ownerId = (int)($binding['OWNER_ID'] ?? $binding['entityId']);

                            // Проверяем сущности, у которых могут быть финальные стадии
                            $typesWithStages = [
                                \CCrmOwnerType::Deal,     // 2 - Сделка
                                \CCrmOwnerType::Lead,     // 1 - Лид
                                \CCrmOwnerType::Order,    // 14 - Заказ
                            ];

                            // Добавляем проверку на динамические смарт-процессы (их ID обычно > 1000)
                            if (in_array($ownerTypeId, $typesWithStages) || \CCrmOwnerType::isPossibleDynamicTypeId($ownerTypeId)) {

                                // Получаем фабрику для данной сущности через современное CRM API
                                $factory = \Bitrix\Crm\Service\Container::getInstance()->getFactory($ownerTypeId);

                                if ($factory && $factory->isStagesSupported()) {
                                    // Быстро запрашиваем только стадию элемента
                                    $item = $factory->getItem($ownerId, ['STAGE_ID']);

                                    if ($item) {
                                        $stageId = $item->getStageId();
                                        // Получаем семантику стадии (Success, Failure, Process)
                                        $semanticId = $factory->getStageSemantics($stageId);

                                        // Если стадия финальная (успех или провал) — игнорируем этот биндинг
                                        if (\Bitrix\Crm\PhaseSemantics::isFinal($semanticId)) {
                                            continue;
                                        }
                                    }
                                }
                            }

                            $entityRow = [
                                'entityTypeId'=>$ownerTypeId,
                                'entityId'=>$ownerId
                            ];
                            $searchData[$key][] = $entityRow;
                        }
                        break;
                    }
                }
            }
        }
        return $searchData[$key];
    }

    public static function onCrmActivityAdd($workId, $arFields){

        $log = \Bitrix\Main\Diag\Logger::create('AwzKpworksWorks', [null]);

        $log?->debug(
            "onCrmActivityAdd {workId}\n",
            ['workId' => $workId]
        );

        $domain = 'default';
        $app = 'default';

        if(!$workId){
            return null;
        }

        $result = new Result();

        $entIdToCodes = [];
        $entCodesToId = [];
        $entCodes = [];
        $entCodesAdd = [];

        $entCodesAr = \Awz\Kpworks\Helper::entityCodes();
        foreach($entCodesAr as $ent){
            $entIdToCodes[$ent['ID']] = $ent['MIN_CODE'];
            $entCodes[$ent['ID']] = $ent['CODE'];
            $entCodesAdd['crm.'.mb_strtolower($ent['CODE']).'.add'] = $ent['ID'];
            $entCodesToId[$ent['MIN_CODE']] = $ent['ID'];
        }

        $r = \Awz\Kpworks\Custom\AppParamsTable::getList([
            'select'=>['*'],
            'filter'=>['=PORTAL'=>$domain,'=APP'=>$app,'=ACTIVE'=>'Y'],
            'order'=>['SORT'=>'ASC', 'ID'=>'DESC']
        ]);
        $rules = $r->fetchAll();
        if(empty($rules)){
            $log?->debug(
                "empty rules {workId}\n",
                ['workId' => $workId]
            );
            return null;
        }

        $workData = [];
        $workBinds = [];

        if (Loader::includeModule('crm')) {
            $workData = [];
            $workBinds = [];

            // 1. Получаем данные об активности
            $dbRes = \CCrmActivity::GetList([], ['ID' => $workId, 'CHECK_PERMISSIONS' => 'N']);
            if ($workData = $dbRes->Fetch()) {

                // Формируем CRM_ID
                $entCodeMin = $entIdToCodes[$workData['OWNER_TYPE_ID']] ?? dechex($workData['OWNER_TYPE_ID']);
                $workData['CRM_ID'] = $entCodeMin . '_' . $workData['OWNER_ID'];

                // 2. Получаем связи (вместо crm.activity.binding.list)
                $workBinds = \CCrmActivity::GetBindings($workId);
                /*[{"ID":"4","OWNER_ID":"11","OWNER_TYPE_ID":"2"}]*/
            }

            $log?->debug(
                "workData {workId} {workData} {workBinds}\n",
                ['workId' => $workId, 'workData'=>Json::encode($workData), 'workBinds'=>Json::encode($workBinds)]
            );
        }

        if(empty($workData)){
            return null;
        }

        $ruleIsNo = ['PROVIDER_ID','DIRECTION','OWNER_TYPE_ID','EMAIL_TO','PHONE','EMAIL_FROM',
            'AUTHOR_ID','RESPONSIBLE_ID',"COMPLETED","SUBJECT_SQ","SUBJECT_MAIN"];

        $emailsFrom = self::getEmailFrom($workData);
        $emailFrom = !empty($emailsFrom) ? $emailsFrom[0] : '';
        $emailsTo = self::getEmailTo($workData);
        $emailTo = !empty($emailsTo) ? $emailsTo[0] : '';

        $phone = '';
        if($workData['PROVIDER_ID'] == 'VOXIMPLANT_CALL'){
            $phone = preg_replace('/([^0-9])/is','',$workData['SUBJECT']);
        }

        $SUBJECT_MAIN = trim(preg_replace('/(re:|fwd:|fw:)/is','',$workData['SUBJECT']));
        $SUBJECT_SQ = '';
        $SUBJECT_SQS = [];
        $MSG_SQS = [];
        if(preg_match_all('/\[([^\]]+)\]/is', $workData['SUBJECT'], $match)){
            foreach($match[1] as $tmp_val){
                if(!$SUBJECT_SQ) $SUBJECT_SQ = $tmp_val;
                $SUBJECT_SQS[] = $tmp_val;
            }
        }
        if(preg_match_all('/\[([^\]]+)\]/is', $workData['DESCRIPTION'], $match)){
            foreach($match[1] as $tmp_val){
                $MSG_SQS[] = $tmp_val;
            }
        }

        $log?->debug(
            "[emails] - {workId} | {emailFrom} - {emailTo} | {phone} | {SUBJECT_SQS} | {MSG_SQS}\n",
            [
                'emailFrom' => $emailFrom,
                'emailTo' => $emailTo,
                'phone' => $phone,
                'SUBJECT_SQS'=>Json::encode($SUBJECT_SQS),
                'MSG_SQS'=>Json::encode($MSG_SQS)
            ]
        );

        $rulesCandidate = [];
        foreach($rules as $rule){
            $activeRule = false;
            $activeAction = false;
            $works = $rule['PARAMS']['works'];
            $actions = $rule['PARAMS']['actions'];
            if(isset($works[0]['aggregator'],$works[0]['controlId'])){
                $variant = 'AND';
                foreach($works as $work){
                    if($work['controlId']=='CondGroup'){
                        $variant = $work['aggregator'];
                    }elseif(in_array($work['controlId'], $ruleIsNo)){
                        if($work['controlId']=='EMAIL_TO'){
                            $tmpVal = $emailTo;
                        }elseif($work['controlId']=='EMAIL_FROM'){
                            $tmpVal = $emailFrom;
                        }elseif($work['controlId']=='PHONE'){
                            $tmpVal = $phone;
                        }elseif($work['controlId']=='SUBJECT_SQ'){
                            $tmpVal = $SUBJECT_SQ;
                        }elseif($work['controlId']=='SUBJECT_MAIN'){
                            $tmpVal = $SUBJECT_MAIN;
                        }else{
                            $tmpVal = $workData[$work['controlId']];
                        }
                        if($tmpVal==$work['value'] && $work['logic']=='Is'){
                            $activeRule = true;
                        }elseif($tmpVal!=$work['value'] && $work['logic']=='No'){
                            $activeRule = true;
                        }elseif($tmpVal && $work['logic']=='Yes'){
                            $activeRule = true;
                        }elseif(!$tmpVal && $work['logic']=='Not'){
                            $activeRule = true;
                        }elseif(mb_strpos($tmpVal,$work['value'])!==false && $work['logic']=='Equals'){
                            $activeRule = true;
                        }elseif(mb_strpos($work['value'],$tmpVal)!==false && $work['logic']=='EqualsIn'){
                            $activeRule = true;
                        }elseif(mb_strpos($tmpVal,$work['value'])===false && $work['logic']=='noEquals'){
                            $activeRule = true;
                        }elseif(mb_strpos($work['value'],$tmpVal)===false && $work['logic']=='noEqualsIn'){
                            $activeRule = true;
                        }else{
                            $activeRule = false;
                        }
                        $log?->debug("[check active] - {workId}|{ruleId}|{work_controlId}|{tmpVal}|{work_logic}|{work_value}|{activeRule}\n",
                            [
                                'tmpVal' => $tmpVal,
                                'work_controlId' => $work['controlId'],
                                'work_value' => $work['value'],
                                'work_logic' => $work['logic'],
                                'activeRule'=>$activeRule ? "1" : "0",
                                'ruleId'=>$rule['ID'],
                                'workId'=>$workId,
                                'domain'=>$domain
                            ]
                        );
                        if(!$activeRule && ($variant == 'AND')) break;
                    }else{
                        $activeRule = false;
                        if($variant == 'AND') break;
                    }
                    if($activeRule && ($variant=='OR')) break;
                }
            }
            if($activeRule){

                $log?->debug("[rule active] - {workId}|{ruleId}\n",
                    [
                        'ruleId'=>$rule['ID'],
                        'workId'=>$workId,
                        'domain'=>$domain
                    ]
                );

                $rulesCandidate[] = $rule;
            }
        }
        $rules = $rulesCandidate;
        $log?->debug(
            "[candidate rules] - {workId} | {rules}\n",
            [
                'workId'=>$workId,
                'rules' => Json::encode($rules),
            ]
        );

        if(empty($rules)) {

            \Awz\Kpworks\Custom\WorkAppLogTable::add([
                'ENTITY_ID'=>'miss',
                'PORTAL'=>$domain,
                'APP'=>$app,
                'DATE_ADD'=>\Bitrix\Main\Type\DateTime::createFromTimestamp(time()),
                'PARAMS'=>[
                    'ents'=>[$workData['CRM_ID']],
                    'type'=>'miss',
                    'workId'=>$workData['ID'],
                    'workSubj'=>$workData['SUBJECT']
                ]
            ]);

            return null;
        }

        $isLog = false;
        $cmds = [];
        $groupLastRuleExists = null;
        foreach($rules as $rule){
            $logEntities = [];
            $logEntities_del = [];
            $logEntities_pb = [];
            $logEntities_unbind = [];
            $logEntities_rest = [];
            $entityEntities = [];
            $actions = $rule['PARAMS']['actions'];
            if($groupLastRuleExists && $groupLastRuleExists['SORT']<200) break;
            if(isset($actions[0]['aggregator'],$actions[0]['controlId'])){
                $variant = 'AND';
                $curEntities = [];
                foreach($actions as $action){

                    $macros = [
                        '#EMAIL_FROM#'=>$emailFrom,
                        '#EMAIL_TO#'=>$emailTo,
                        '#PHONE#'=>$phone,
                        '#SUBJECT_SQ#'=>$SUBJECT_SQ,
                        '#SUBJECT_MAIN#'=>$SUBJECT_MAIN,
                        '#SUBJECT_SQS#'=>Json::encode($SUBJECT_SQS),
                        '#MSG_SQS#'=>Json::encode($MSG_SQS),
                        '#ADD_ID#'=>self::$lastAddId
                    ];
                    foreach($workData as $k=>$v){
                        if(is_string($v)){
                            $macros['#'.$k.'#'] = $v;
                        }
                    }

                    if($action['controlId'] == 'actionCleverMail'){
                        if($workData['PROVIDER_ID'] != 'CRM_EMAIL'){
                            continue;
                        }
                        $action = [
                            'controlStart' => 'actionCleverMail', //Добавить привязку для найденных
                            'controlId' => 'actionAny', //Добавить привязку для найденных
                            'cnt'=>50, //50 сущностей
                            'type'=>'0', // типа любой сущности
                            'search'=>'works_expert_json', //осуществив поиск по привязанным Делам
                            'prop_code'=>'{"SUBJECT":"#SUBJECT_MAIN#","!ID":"#ID#"}', // а сущность в деле по json фильтру
                            'apply'=>'json', //и применить
                            'apply_value'=>'{"id":#CRM_IDS#,"awz_system_type":"1"}' //json фильтр
                        ];
                        if($workData['DIRECTION']==2){ //исх
                            $action['prop_code'] = '{"SUBJECT":"#SUBJECT_MAIN#"}';
                        }
                        //исх
                        if($workData['DIRECTION']==2 && $workData['SUBJECT']==$SUBJECT_MAIN)
                            continue;
                    }

                    $log?->debug(
                        "[action] - {workId} | {action}\n",
                        [
                            'workId'=>$workId,
                            'action' => $action
                        ]
                    );

                    if($action['controlId']=='CondGroup'){
                        $variant = $action['aggregator'];
                    }
                    elseif($action['controlId'] == 'actionBpStart'){
                        $action['elid'] = str_replace(array_keys($macros),array_values($macros),$action['elid']);
                        if($action['value'] && $action['elid']){
                            $action['paramsjson'] = self::replaceJson($action['paramsjson'], $macros);
                            try{
                                $action['paramsjson'] = Json::decode($action['paramsjson']);
                            }catch (\Exception $e){
                                $action['paramsjson'] = [];
                                $log?->debug(
                                    "[json] - {workId} | {json}\n",
                                    [
                                        'workId'=>$workId,
                                        'json' => $action['paramsjson']
                                    ]
                                );
                            }
                            $arBp = explode("|||",$action['value']);
                            $cmds['bp_start'] = [
                                'method'=>'bizproc.workflow.start',
                                'params'=>[
                                    'TEMPLATE_ID'=>$arBp[3],
                                    'DOCUMENT_ID'=>[$arBp[0],$arBp[1],$arBp[2].'_'.$action['elid']],
                                    'PARAMETERS'=>$action['paramsjson']
                                ]
                            ];
                            $logEntities_pb[] = $action['value'].'|||'.$arBp[0].'|||'.$arBp[2].'|||'.$action['elid'];
                            $groupLastRuleExists = $rule;
                            if($variant == 'OR') break;
                        }
                    }
                    elseif($action['controlId'] == 'actionBreak') {
                        if($action['apply']=='Y'){
                            if($action['checker'] == 'v1' && !empty($curEntities))
                            {
                                break;
                            }
                            elseif($action['checker'] == 'v2' && empty($curEntities))
                            {
                                break;
                            }
                        }
                    }
                    elseif($action['controlId'] == 'actionFindEntity') {
                        if($action['checker'] == 'ex' && $action['paramsjson'])
                        {
                            $action['paramsjson'] = self::replaceJson($action['paramsjson'], $macros);
                            $log?->debug(
                                "[actionFindEntity] - {workId} | {json}\n",
                                [
                                    'workId'=>$workId,
                                    'json' => $action['paramsjson']
                                ]
                            );
                            try{
                                $action['paramsjson'] = Json::decode($action['paramsjson']);
                                if(!empty($action['paramsjson']) && isset($action['paramsjson']['limit'])){

                                    $factory = Service\Container::getInstance()->getFactory($action['paramsjson']['entityTypeId']);

                                    if($factory){
                                        $itemsFilter = $action['paramsjson'];
                                        unset($itemsFilter['entityTypeId']);
                                        $items = $factory->getItems($itemsFilter);
                                        foreach ($items as $item) {
                                            // Получаем данные в виде массива
                                            //$rowDel = $item->getData();

                                            $tmpkey = $action['paramsjson']['entityTypeId'].'_'.$item->getId();
                                            $curEntities[$tmpkey] = $item->getId();
                                            $groupLastRuleExists = $rule;

                                            // Или обращаемся к конкретному полю
                                            //echo $item->get('TITLE');
                                        }
                                    }
                                }
                            }
                            catch (\Exception $e){
                                $log?->debug(
                                    "[actionFindEntity] - {workId} | {err}\n",
                                    [
                                        'workId'=>$workId,
                                        'err' => Json::encode($e->getMessage()),
                                    ]
                                );
                            }

                        }
                    }
                    elseif($action['controlId'] == 'actionDeleteEntity'){
                        if($action['apply']=='Y'){
                            $checkDel = false;
                            if(
                                ($action['crm_type_value'] && ($action['crm_type_value']==$workData['OWNER_TYPE_ID']))
                                ||
                                !$action['crm_type_value']
                            ){
                                if($action['checker'] == 'v2')
                                {
                                    $checkDel = true;
                                }
                                elseif($action['checker'] == 'v1')
                                {
                                    $tmpkey = $workData['OWNER_TYPE_ID'].'_'.$workData['OWNER_ID'];
                                    if(empty($entityEntities)){

                                    }elseif(count($entityEntities)==1 && ($entityEntities[0]==$tmpkey)){

                                    }else{
                                        $checkDel = true;
                                    }
                                }
                                elseif($action['checker'] == 'ex' && $action['paramsjson'])
                                {
                                    $action['paramsjson'] = self::replaceJson($action['paramsjson'], $macros);
                                    $log?->debug(
                                        "[actionDeleteEntity] - {workId} | {json}\n",
                                        [
                                            'workId'=>$workId,
                                            'json' => $action['paramsjson']
                                        ]
                                    );
                                    try{
                                        $checkDel = false;
                                        $action['paramsjson'] = Json::decode($action['paramsjson']);
                                        if(!empty($action['paramsjson']) && isset($action['paramsjson']['limit'])){

                                            $factory = Service\Container::getInstance()->getFactory($action['paramsjson']['entityTypeId']);

                                            if($factory){
                                                $itemsFilter = $action['paramsjson'];
                                                unset($itemsFilter['entityTypeId']);
                                                $items = $factory->getItems($itemsFilter);
                                                foreach ($items as $item) {
                                                    // Получаем данные в виде массива
                                                    //$rowDel = $item->getData();

                                                    $tmpkey = $action['paramsjson']['entityTypeId'].'_'.$item->getId();
                                                    $cmds['delete_entity_'.$tmpkey] = [
                                                        'method'=>'crm.item.delete',
                                                        'params'=>[
                                                            'entityTypeId'=>$action['paramsjson']['entityTypeId'],
                                                            'id'=>$item->getId()
                                                        ]
                                                    ];
                                                    $logEntities_del[] = $action['paramsjson']['entityTypeId'].'_'.$item->getId();
                                                    $groupLastRuleExists = $rule;

                                                    // Или обращаемся к конкретному полю
                                                    //echo $item->get('TITLE');
                                                }
                                            }

                                        }
                                    }
                                    catch (\Exception $e){
                                        $checkDel = false;
                                        $log?->debug(
                                            "[actionDeleteEntity] - {workId} | {err}\n",
                                            [
                                                'workId'=>$workId,
                                                'err' => $e->getMessage(),
                                            ]
                                        );
                                    }

                                }
                            }

                            if($checkDel){
                                $cmds['delete_entity'] = [
                                    'method'=>'crm.item.delete',
                                    'params'=>[
                                        'entityTypeId'=>$workData['OWNER_TYPE_ID'],
                                        'id'=>$workData['OWNER_ID']
                                    ]
                                ];
                                $logEntities_del[] = $workData['OWNER_TYPE_ID'].'_'.$workData['OWNER_ID'];
                                $groupLastRuleExists = $rule;
                                if($variant == 'OR') break;
                            }
                        }
                    }
                    elseif($action['controlId'] == 'actionDeleteBind'){
                        if($action['apply']=='Y'){
                            $checkDel = false;
                            if($action['checker'] == 'v2'){
                                $checkDel = true;
                            }elseif($action['checker'] == 'v1'){
                                $tmpkey = $workData['OWNER_TYPE_ID'].'_'.$workData['OWNER_ID'];
                                if(empty($entityEntities)){

                                }elseif(count($entityEntities)==1 && ($entityEntities[0]==$tmpkey)){

                                }else{
                                    $checkDel = true;
                                }
                            }
                            if($checkDel){
                                $bindsResData = $workBinds;
                                foreach($bindsResData as $rowBind){
                                    $tmpkey = $rowBind['OWNER_TYPE_ID'].'_'.$rowBind['OWNER_ID'];
                                    $checkDelRow = false;
                                    if($action['checker'] == 'v1'){
                                        if(!in_array($tmpkey, $entityEntities)){
                                            $checkDelRow = true;
                                        }
                                    }else{
                                        $checkDelRow = true;
                                    }
                                    if($checkDelRow){
                                        $cmds['delete_bind_'.$tmpkey] = [
                                            'method'=>'crm.activity.binding.delete',
                                            'params'=>[
                                                'activityId'=>$workId,
                                                'entityTypeId'=>$rowBind['OWNER_TYPE_ID'],
                                                'entityId'=>$rowBind['OWNER_ID'],
                                            ]
                                        ];
                                        $logEntities_unbind[] = $rowBind['OWNER_TYPE_ID'].'_'.$rowBind['OWNER_ID'];
                                        $groupLastRuleExists = $rule;
                                        if($variant == 'OR') break;
                                    }
                                }
                            }
                        }
                    }
                    elseif($action['controlId'] == 'actionRestCall'){
                        if($action['value']){
                            $action['paramsjson'] = self::replaceJson($action['paramsjson'], $macros);
                            try{
                                $action['paramsjson'] = Json::decode($action['paramsjson']);
                            }catch (\Exception $e){
                                $action['paramsjson'] = [];
                                $log?->debug(
                                    "[json] - {workId} | {json}\n",
                                    [
                                        'workId'=>$workId,
                                        'json' => $action['paramsjson']
                                    ]
                                );
                            }

                            if(substr($action['value'],-4)=='.add'){

                                $findId = 0;
                                $firldsadd = $action['paramsjson']['fields'] ? $action['paramsjson']['fields'] : $action['paramsjson']['FIELDS'];
                                if(!is_array($firldsadd)) $firldsadd = [];
                                if($action['value'] == 'crm.lead.add'){
                                    $errorsBp = [];
                                    $leadObject = new \CCrmLead(false);
                                    $findId = $leadObject->Add(
                                        $firldsadd,
                                        true, // bUpdateSearch: обновлять поисковый индекс
                                        ['REGISTER_SONET_EVENT' => true] // Регистрация события в живой ленте
                                    );
                                    if($findId) {
                                        \CCrmBizProcHelper::AutoStartWorkflows(
                                            \CCrmOwnerType::Lead,
                                            $findId,
                                            \CCrmBizProcEventType::Create,
                                            $errorsBp
                                        );
                                        $starter = new \Bitrix\Crm\Automation\Starter(\CCrmOwnerType::Lead, $findId);
                                        $starter->setUserIdFromCurrent();
                                        $starter->runOnAdd();
                                    }
                                }
                                if($action['value'] == 'crm.deal.add'){
                                    $leadObject = new \CCrmDeal(false);
                                    $errorsBp = [];
                                    $findId = $leadObject->Add(
                                        $firldsadd,
                                        true, // bUpdateSearch: обновлять поисковый индекс
                                        ['REGISTER_SONET_EVENT' => true] // Регистрация события в живой ленте
                                    );
                                    if($findId) {
                                        \CCrmBizProcHelper::AutoStartWorkflows(
                                            \CCrmOwnerType::Deal,
                                            $findId,
                                            \CCrmBizProcEventType::Create,
                                            $errorsBp
                                        );
                                        $starter = new \Bitrix\Crm\Automation\Starter(\CCrmOwnerType::Deal, $findId);
                                        $starter->setUserIdFromCurrent();
                                        $starter->runOnAdd();
                                    }
                                }
                                if($action['value'] == 'crm.item.add'){
                                    $errorsBp = [];
                                    $container = Service\Container::getInstance();
                                    $factory = $container->getFactory($action['paramsjson']['entityTypeId']);
                                    $item = $factory->createItem();
                                    foreach ($firldsadd as $k=>$v){
                                        $item->set($k, $v);
                                    }
                                    $factory->getAddOperation($item)->launch();
                                    $findId = $item->getId();
                                    if($findId){
                                        \CCrmBizProcHelper::AutoStartWorkflows(
                                            (int)$action['paramsjson']['entityTypeId'],
                                            $findId,
                                            \CCrmBizProcEventType::Create,
                                            $errorsBp
                                        );
                                        $starter = new \Bitrix\Crm\Automation\Starter((int)$action['paramsjson']['entityTypeId'], $findId);
                                        $starter->setUserIdFromCurrent();
                                        $starter->runOnAdd();
                                    }
                                }
                                if($findId){
                                    $tmp_type = $entCodesAdd[$action['value']] ?? $action['paramsjson']['entityTypeId'];
                                    $cmds[$tmp_type.'_'.$findId] = [
                                        'method'=>'crm.activity.binding.add',
                                        'params'=>[
                                            'activityId'=>$workId,
                                            'entityTypeId'=>$tmp_type,
                                            'entityId'=>$findId
                                        ]
                                    ];
                                    self::$lastAddId = $findId;
                                }

                            }else{
                                $cmds['rest_'.count($logEntities_rest)] = [
                                    'method'=>$action['value'],
                                    'params'=>$action['paramsjson']
                                ];
                            }

                            $logEntities_rest[] = $action['value'].'|||rest|||'.print_r($action['paramsjson'], true).'|||'.$findId;
                            $groupLastRuleExists = $rule;
                            if($variant == 'OR') break;
                        }
                    }
                    elseif(in_array($action['search'],
                        ['expert','works_subject', 'works_expert_json', 'contact_expert_json','company_expert_json',
                            'contact_email','contact_phone','contact_email2',
                            'entity_email','entity_phone','entity_email2']
                    ))
                    {
                        $rowCnt = intval($action['cnt']);
                        if($rowCnt < 1) continue;
                        $rowCmds = [];

                        $contactIds = [];
                        $entityIds = [];
                        $entityCrmIds = [];
                        $entityRows = [];

                        if($action['search'] == 'works_subject'){
                            $action['search'] = 'works_expert_json';
                            $action['prop_code'] = Json::encode([
                                'SUBJECT'=>'#SUBJECT_MAIN#',
                                '!ID'=>'#ID#'
                            ]);
                        }

                        $log?->debug(
                            "[beforeSearch] - {var1};{var2};{var3};{var4};{var5};{var6}\n",
                            [
                                'var1' => $action['search'],
                                'var2' => $emailFrom,
                                'var3' => $emailTo,
                                'var4' => $phone,
                                'var5' => '',
                                'var6' => $action['prop_code'],
                            ]
                        );


                        if($action['search'] == 'contact_email'){
                            $contactIds = self::getSearch($emailFrom, $action['search']);
                            if(empty($contactIds)) continue;
                        }elseif($action['search'] == 'contact_email2'){
                            $contactIds = self::getSearch($emailTo, $action['search']);
                            if(empty($contactIds)) continue;
                        }elseif($action['search'] == 'contact_phone'){
                            $contactIds = self::getSearch($phone, $action['search']);
                            if(empty($contactIds)) continue;
                        }elseif($action['search'] == 'contact_expert_json'){
                            $action['prop_code'] = self::replaceJson($action['prop_code'], $macros);
                            $contactIds = self::getSearch($action['prop_code'], $action['search']);
                            if(empty($contactIds)) continue;
                        }elseif($action['search'] == 'company_expert_json'){
                            $action['prop_code'] = self::replaceJson($action['prop_code'], $macros);
                            $contactIds = self::getSearch($action['prop_code'], $action['search']);
                            if(empty($contactIds)) continue;
                        }elseif($action['search'] == 'entity_email'){
                            if(empty($action['prop_code'])) continue;
                        }elseif($action['search'] == 'entity_email2'){
                            if(empty($action['prop_code'])) continue;
                        }elseif($action['search'] == 'entity_phone'){
                            if(empty($action['prop_code'])) continue;
                        }elseif($action['search'] == 'works_expert_json'){
                            $action['prop_code'] = self::replaceJson($action['prop_code'], $macros);
                            $log?->debug(
                                "[beforeSearch2] - {var1} - {var2}\n",
                                [
                                    'var1' => $action['prop_code'],
                                    'var2' => $action['search']
                                ]
                            );

                            try{
                                $tmp = Json::decode($action['prop_code']);

                                $entityRows = self::getSearch(
                                    [$emailFrom, $emailTo, $action['prop_code']],
                                    $action['search']
                                );
                                if(empty($entityRows) && $SUBJECT_MAIN!=$workData['SUBJECT']) {
                                    //Меняем тему дела (на оригинал), если не нашло дел
                                    if(isset($action['controlStart'])
                                        && $action['controlStart'] == 'actionCleverMail'){

                                        $isUpdated = \CCrmActivity::Update(
                                            $workId,
                                            ['SUBJECT'=>$SUBJECT_MAIN],
                                            false, // CHECK_PERMISSIONS: N - если обновляем системно (от админа)
                                            false,  // REGISTER_SONET_EVENT: регистрировать ли событие в Живой ленте
                                            ['SKIP_BINDINGS' => false] // Не пропускать обновление связей, если нужно
                                        );
                                        $log?->debug(
                                            "[update theme] - {theme}\n{err}\n",
                                            [
                                                'theme' => $SUBJECT_MAIN,
                                                'err'=>!$isUpdated ? 'Ошибка обновления' : ''
                                            ]
                                        );
                                    }
                                    continue;
                                }

                            }catch (\Exception $e){
                                $log?->debug(
                                    "[beforeSearch2] - {err}\n",
                                    [
                                        'err' => $e->getMessage()
                                    ]
                                );
                            }

                        }else{
                            //$action['search'] == expert
                        }

                        $log?->debug(
                            "[afterSearch] - {var1}\n{var2}\n",
                            [
                                'var1' => $contactIds,
                                'var2' => $entityRows
                            ]
                        );

                        $filter = [];

                        if(!empty($entityRows)){
                            foreach($entityRows as $entityRowsRow){
                                /* {"entityTypeId": 1,"entityId": 123} */
                                if($entityRowsRow['entityTypeId'] == 3)
                                    $contactIds[] = $entityRowsRow['entityId'];
                                if($entityRowsRow['entityTypeId'] == $action['type'])
                                    $entityIds[] = $entityRowsRow['entityId'];

                                $entCodeMin = $entIdToCodes[$entityRowsRow['entityTypeId']] ?? dechex($entityRowsRow['entityTypeId']);
                                $entityCrmIds[] = $entCodeMin.'_'.$entityRowsRow['entityId'];
                            }
                            $filter['id'] = $entityIds;
                        }elseif($action['type'] == 3){
                            $filter = ['id'=>$contactIds];
                        }else{
                            if(!empty($contactIds)){
                                $filter['contactId'] = $contactIds;
                            }
                            if($action['search'] == 'entity_email'){
                                $filter[$action['prop_code']] = $emailFrom;
                            }
                            if($action['search'] == 'entity_email2'){
                                $filter[$action['prop_code']] = $emailTo;
                            }
                            if($action['search'] == 'entity_phone'){
                                $filter[$action['prop_code']] = $phone;
                            }
                        }

                        $macros['#CONTACT_IDS#'] = Json::encode($contactIds);
                        $macros['#EL_IDS#'] = Json::encode($entityIds);
                        $macros['#CRM_IDS#'] = Json::encode($entityCrmIds);
                        $action['apply_value'] = str_replace(array_keys($macros), array_values($macros), $action['apply_value']);

                        //print_r($action['apply_value']);
                        //die();
                        if($action['apply']=='json'){
                            try{
                                $filter = Json::decode($action['apply_value']);
                            }catch (\Exception $e){
                                $log?->debug(
                                    "[json] - {json}\n",
                                    [
                                        'json' => $action['apply_value']
                                    ]
                                );
                                continue;
                            }
                        }

                        $log?->debug(
                            "[rule action] - {date} | {action} | {filter}\n",
                            [
                                'action' => $action,
                                'filter' => Json::encode($filter)
                            ]
                        );

                        if(empty($filter)) continue;

                        $params = [
                            'entityTypeId'=>$action['type'],
                            'filter'=>$filter,
                            'order'=>['id'=>'desc'],
                            'select'=>['id']
                        ];
                        if(isset($filter['filter']) || isset($filter['order']) || isset($filter['limit'])){
                            $params = $filter;
                        }

                        if(isset($params['filter']['id']) && isset($params['filter']['awz_system_type'])){
                            if(empty($params['filter']['id']) && ($params['filter']['awz_system_type']!=1))
                                continue;
                            foreach($params['filter']['id'] as $crmId){
                                $tmp_type = $action['type'];
                                $tmp_id = $item['id'];
                                if(strpos($crmId, '_')!==false){
                                    list($tmp_type, $tmp_id) = explode("_",$crmId);
                                }

                                $tmp_type = $entCodesToId[$tmp_type] ?? hexdec($tmp_type);

                                if(count($rowCmds)<$rowCnt) {
                                    $rowCmds[$tmp_type . '_' . $tmp_id] = [
                                        'method' => 'crm.activity.binding.add',
                                        'params' => [
                                            'activityId' => $workId,
                                            'entityTypeId' => $tmp_type,
                                            'entityId' => $tmp_id
                                        ]
                                    ];
                                    if (!in_array($tmp_type . '_' . $tmp_id, $logEntities)) {
                                        $logEntities[] = $tmp_type . '_' . $tmp_id;
                                        $entityEntities[] = $tmp_type . '_' . $tmp_id;
                                        $groupLastRuleExists = $rule;
                                    }
                                }
                            }
                        }else{

                            $factory = Service\Container::getInstance()->getFactory($params['entityTypeId']);

                            if($factory){
                                $itemsFilter = $params;
                                unset($itemsFilter['entityTypeId']);
                                $items = $factory->getItems($itemsFilter);
                                foreach ($items as $item) {
                                    if(count($rowCmds)<$rowCnt){
                                        $rowCmds[$action['type'].'_'.$item->getId()] = [
                                            'method'=>'crm.activity.binding.add',
                                            'params'=>[
                                                'activityId'=>$workId,
                                                'entityTypeId'=>$action['type'],
                                                'entityId'=>$item->getId()
                                            ]
                                        ];
                                        if(!in_array($action['type'].'_'.$item->getId(),$logEntities)){
                                            $logEntities[] = $action['type'].'_'.$item->getId();
                                            $entityEntities[] = $action['type'].'_'.$item->getId();
                                            $groupLastRuleExists = $rule;
                                        }
                                    }
                                }
                            }
                        }


                        if(count($rowCmds)){
                            foreach($rowCmds as $rowCmdKey=>$rowCmd){
                                $cmds[$rowCmdKey] = $rowCmd;
                            }
                            //if($rule['SORT']<200) break 2;
                            if($variant == 'OR') break;
                        }
                    }
                }

            }


            if(!empty($logEntities)){
                \Awz\Kpworks\Custom\WorkAppLogTable::add([
                    'ENTITY_ID'=>$rule['ID'],
                    'PORTAL'=>$domain,
                    'APP'=>$app,
                    'DATE_ADD'=>\Bitrix\Main\Type\DateTime::createFromTimestamp(time()),
                    'PARAMS'=>[
                        'ents'=>$logEntities,
                        'workId'=>$workData['ID'],
                        'workSubj'=>$workData['SUBJECT']
                    ]
                ]);
                if(!isset($rule['PARAMS']['cnt_rule'])) $rule['PARAMS']['cnt_rule'] = 0;
                $rule['PARAMS']['cnt_rule'] += 1;
                \Awz\Kpworks\Custom\AppParamsTable::update(['ID'=>$rule['ID']],[
                    'PARAMS'=>$rule['PARAMS']
                ]);
                $isLog = true;
            }
            if(!empty($logEntities_del)){
                \Awz\Kpworks\Custom\WorkAppLogTable::add([
                    'ENTITY_ID'=>$rule['ID'],
                    'PORTAL'=>$domain,
                    'APP'=>$app,
                    'DATE_ADD'=>\Bitrix\Main\Type\DateTime::createFromTimestamp(time()),
                    'PARAMS'=>[
                        'ents'=>$logEntities_del,
                        'type'=>'delete',
                        'workId'=>$workData['ID'],
                        'workSubj'=>$workData['SUBJECT']
                    ]
                ]);
                $isLog = true;
            }
            if(!empty($logEntities_unbind)){
                \Awz\Kpworks\Custom\WorkAppLogTable::add([
                    'ENTITY_ID'=>$rule['ID'],
                    'PORTAL'=>$domain,
                    'APP'=>$app,
                    'DATE_ADD'=>\Bitrix\Main\Type\DateTime::createFromTimestamp(time()),
                    'PARAMS'=>[
                        'ents'=>$logEntities_unbind,
                        'type'=>'unbind',
                        'workId'=>$workData['ID'],
                        'workSubj'=>$workData['SUBJECT']
                    ]
                ]);
                $isLog = true;
            }
            if(!empty($logEntities_pb)){
                \Awz\Kpworks\Custom\WorkAppLogTable::add([
                    'ENTITY_ID'=>$rule['ID'],
                    'PORTAL'=>$domain,
                    'APP'=>$app,
                    'DATE_ADD'=>\Bitrix\Main\Type\DateTime::createFromTimestamp(time()),
                    'PARAMS'=>[
                        'ents'=>$logEntities_pb,
                        'type'=>'bp',
                        'workId'=>$workData['ID'],
                        'workSubj'=>$workData['SUBJECT']
                    ]
                ]);
                $isLog = true;
            }
            if(!empty($logEntities_rest)){
                \Awz\Kpworks\Custom\WorkAppLogTable::add([
                    'ENTITY_ID'=>$rule['ID'],
                    'PORTAL'=>$domain,
                    'APP'=>$app,
                    'DATE_ADD'=>\Bitrix\Main\Type\DateTime::createFromTimestamp(time()),
                    'PARAMS'=>[
                        'ents'=>$logEntities_rest,
                        'type'=>'rest',
                        'workId'=>$workData['ID'],
                        'workSubj'=>$workData['SUBJECT']
                    ]
                ]);
                $isLog = true;
            }
        }

        if(!$isLog){
            \Awz\Kpworks\Custom\WorkAppLogTable::add([
                'ENTITY_ID'=>'miss',
                'PORTAL'=>$domain,
                'APP'=>$app,
                'DATE_ADD'=>\Bitrix\Main\Type\DateTime::createFromTimestamp(time()),
                'PARAMS'=>[
                    'ents'=>[$workData['CRM_ID']],
                    'type'=>'miss',
                    'workId'=>$workData['ID'],
                    'workSubj'=>$workData['SUBJECT']
                ]
            ]);
        }

        $log?->debug(
            "[cmds] - {workId} | {date} | {cmds}\n",
            [
                'workId'=>$workId,
                'cmds' => Json::encode($cmds)
            ]
        );
        if(count($cmds)){

            foreach($cmds as $cmdCode=>$cmd){
                /*
                * $cmds['bp_start'] = [
                    'method'=>'bizproc.workflow.start',
                    'params'=>[
                        'TEMPLATE_ID'=>$arBp[3],
                        'DOCUMENT_ID'=>[$arBp[0],$arBp[1],$arBp[2].'_'.$action['elid']],
                        'PARAMETERS'=>$action['paramsjson']
                    ]
                ];
                * */
                if($cmd['method'] == 'bizproc.workflow.start'){
                    if (Loader::includeModule('bizproc') && Loader::includeModule('crm')) {
                        $errors = [];
                        \CBPDocument::StartWorkflow(
                            $cmd['params']['TEMPLATE_ID'],
                            $cmd['params']['DOCUMENT_ID'],
                            $cmd['params']['PARAMETERS'],
                            $errors
                        );
                    }
                }
                /*
                 * $cmds['delete_entity_'.$tmpkey] = [
                        'method'=>'crm.item.delete',
                        'params'=>[
                            'entityTypeId'=>$action['paramsjson']['entityTypeId'],
                            'id'=>$item->getId()
                        ]
                    ];
                 * */
                elseif($cmd['method'] == 'crm.item.delete'){
                    $factory = Service\Container::getInstance()->getFactory($cmd['params']['entityTypeId']);
                    $item = $factory->getItem($cmd['params']['id']);
                    $factory->getDeleteOperation($item)->launch();
                }

                /*
                $cmds['delete_bind_'.$tmpkey] = [
                                            'method'=>'crm.activity.binding.delete',
                                            'params'=>[
                                                'activityId'=>$workId,
                                                'entityTypeId'=>$rowBind['entityTypeId'],
                                                'entityId'=>$rowBind['entityId'],
                                            ]
                                        ];
                 */
                elseif($cmd['method'] == 'crm.activity.binding.delete'){

                    // 1. Получаем текущие связи дела
                    $currentBindings = \CCrmActivity::GetBindings($cmd['params']['activityId']);
                    $newBindings = [];
                    foreach($currentBindings as $bind){
                        if($bind['OWNER_TYPE_ID'] == $cmd['params']['entityTypeId'] &&
                            $bind['OWNER_ID'] === (int)$cmd['params']['entityId']
                        ){
                           continue;
                        }
                        $newBindings[] = $bind;
                    }

                    \CCrmActivity::SaveBindings($cmd['params']['activityId'], $newBindings);

                }
                /*$cmds[$tmp_type.'_'.$findId] = [
                        'method'=>'crm.activity.binding.add',
                        'params'=>[
                            'activityId'=>$workId,
                            'entityTypeId'=>$tmp_type,
                            'entityId'=>$findId
                        ]
                    ];
                */
                elseif($cmd['method'] == 'crm.activity.binding.add'){
                    $currentBindings = \CCrmActivity::GetBindings($cmd['params']['activityId']);
                    $currentBindings[] = [
                        'OWNER_TYPE_ID'=>$cmd['params']['entityTypeId'],
                        'OWNER_ID'=>$cmd['params']['entityId'],
                    ];
                    \CCrmActivity::SaveBindings($cmd['params']['activityId'], $currentBindings);
                    //print_r([$cmd['params']['activityId'], $currentBindings]);
                }
            }


            //$batchRes = $appOb->callBatch($cmds);
        }

        return null;

    }

    public function listhookAction(string $domain = 'default', string $app = 'default'){

        if(!AccessController::can(0, ActionDictionary::ACTION_SETT_VIEW)){
            $this->addError(new Error("Нет прав на просмотр списка правил"));
            return [];
        }

        $items = \Awz\Kpworks\Custom\AppParamsTable::getList([
            'select'=>['ID','ACTIVE','NAME','SORT','PARAMS'],
            'filter'=>['=PORTAL'=>$domain,'=APP'=>$app],
            'order'=>['SORT'=>'ASC','ID'=>'DESC']
        ])->fetchAll();

        $itemsFin = [];
        foreach($items as $itm){
            $itemsFin[] = [
                'ID'=>$itm['ID'],
                'ACTIVE'=>$itm['ACTIVE'],
                'NAME'=>$itm['NAME'],
                'SORT'=>$itm['SORT'],
                'CNT'=>isset($itm['PARAMS']['cnt_rule']) ? intval($itm['PARAMS']['cnt_rule']) : 0
            ];
        }


        return $itemsFin;

    }

    public function savehookAction(int $sort, string $name, array $works = [],
                                   array $actions = [], int $id=0, string $active = 'N', string $delete = 'N',
                                   string $preset = '', string $domain = 'default', string $app = 'default')
    {
        if(!AccessController::can(0, ActionDictionary::ACTION_SETT_VIEW)){
            $this->addError(new Error("Нет прав на просмотр правил"));
            return [];
        }

        $preset = (int) $preset;
        if($logger = $this->getLogger()){
            $logger->debug(
                "[request]\n{date}\n{request}\n",
                [
                    'request' => $this->getRequest()
                ]
            );
        }

        if($delete=='Y' && $active=='Y'){
            $this->addError(
                new Error('Нельзя удалить активный маршрут', 100)
            );
            return null;
        }

        if($delete=='Y' && !AccessController::can(0, ActionDictionary::ACTION_SETT_DELETE)){
            $this->addError(new Error("Нет прав на удаление правил"));
            return [];
        }

        $isWrite = ($delete === 'Y' || $preset > 0 || !$id || $id > 0);
        if($isWrite && !AccessController::can(0, ActionDictionary::ACTION_SETT_EDIT)){
            $this->addError(new Error("Нет прав на изменение правил"));
            return null;
        }

        if($preset == 1){
            $check = \Awz\Kpworks\Custom\AppParamsTable::getList([
                'select'=>['ID'],
                'filter'=>['=PORTAL'=>$domain,'=APP'=>$app,'=NAME'=>'awz письма: роутер']
            ]);
            if($check->fetch()){
                $this->addError(
                    new Error('маршрут "awz письма: роутер" уже существует', 100)
                );
                return null;
            }

            $fields = [
                'ACTIVE'=>'Y',
                'NAME'=>'awz письма: роутер',
                'SORT'=>100,
                'PORTAL'=>$domain,
                'APP'=>$app,
                'PARAMS'=>unserialize('a:2:{s:5:"works";a:2:{i:0;a:2:{s:9:"controlId";s:9:"CondGroup";s:10:"aggregator";s:3:"AND";}s:4:"0__0";a:3:{s:9:"controlId";s:11:"PROVIDER_ID";s:5:"logic";s:2:"Is";s:5:"value";s:9:"CRM_EMAIL";}}s:7:"actions";a:3:{i:0;a:2:{s:9:"controlId";s:9:"CondGroup";s:10:"aggregator";s:3:"AND";}s:4:"0__0";a:2:{s:9:"controlId";s:16:"actionCleverMail";s:5:"apply";s:1:"Y";}s:4:"0__1";a:3:{s:9:"controlId";s:16:"actionDeleteBind";s:5:"apply";s:1:"Y";s:7:"checker";s:2:"v1";}}}', ["allowed_classes" => false])
            ];
            $fields2 = [
                'ACTIVE'=>'N',
                'NAME'=>'awz письма: добавление лида',
                'SORT'=>500,
                'PORTAL'=>$domain,
                'APP'=>$app,
                'PARAMS'=>unserialize('a:2:{s:5:"works";a:3:{i:0;a:2:{s:9:"controlId";s:9:"CondGroup";s:10:"aggregator";s:3:"AND";}s:4:"0__0";a:3:{s:9:"controlId";s:11:"PROVIDER_ID";s:5:"logic";s:2:"Is";s:5:"value";s:9:"CRM_EMAIL";}s:4:"0__1";a:3:{s:9:"controlId";s:9:"DIRECTION";s:5:"logic";s:2:"Is";s:5:"value";s:1:"1";}}s:7:"actions";a:5:{i:0;a:2:{s:9:"controlId";s:9:"CondGroup";s:10:"aggregator";s:3:"AND";}s:4:"0__3";a:4:{s:9:"controlId";s:16:"actionFindEntity";s:7:"checker";s:2:"ex";s:10:"paramsjson";s:102:"{"entityTypeId":"#OWNER_TYPE_ID#","limit":"1","filter":{"id":"#OWNER_ID#",">createdTime":"-2minutes"}}";s:14:"crm_type_value";s:1:"1";}s:4:"0__4";a:3:{s:9:"controlId";s:11:"actionBreak";s:5:"apply";s:1:"Y";s:7:"checker";s:2:"v1";}s:4:"0__5";a:3:{s:9:"controlId";s:14:"actionRestCall";s:5:"value";s:12:"crm.lead.add";s:10:"paramsjson";s:122:"{"fields":{"TITLE":"#SUBJECT#","ASSIGNED_BY_ID":"1","EMAIL":[{"TYPE":"WORK","VALUE":"#EMAIL_FROM#"}],"SOURCE_ID":"EMAIL"}}";}s:4:"0__6";a:3:{s:9:"controlId";s:16:"actionDeleteBind";s:5:"apply";s:1:"Y";s:7:"checker";s:2:"v2";}}}', ["allowed_classes" => false])
            ];
            $fields['DATE_ADD'] = \Bitrix\Main\Type\DateTime::createFromTimestamp(time());
            $fields2['DATE_ADD'] = \Bitrix\Main\Type\DateTime::createFromTimestamp(time());
            $r = \Awz\Kpworks\Custom\AppParamsTable::add($fields);
            $r = \Awz\Kpworks\Custom\AppParamsTable::add($fields2);
            if($r->isSuccess()){
                $id = $r->getId();
                $msg = 'Маршрут добавлен.';
                return ['id'=>(int)$id, "msg"=>$msg];
            }else{
                $this->addErrors($r->getErrors());
                return null;
            }
        }


        $fields = [
            'ACTIVE'=>$active=='Y' ? 'Y' : 'N',
            'NAME'=>$name,
            'SORT'=>$sort,
            'PORTAL'=>$domain,
            'APP'=>$app,
            'PARAMS'=>['works'=>$works,'actions'=>$actions]
        ];
        $msg = '';

        if(!$id){
            $fields['DATE_ADD'] = \Bitrix\Main\Type\DateTime::createFromTimestamp(time());
            $r = \Awz\Kpworks\Custom\AppParamsTable::add($fields);
            if($r->isSuccess()){
                $id = $r->getId();
                $msg = 'Маршрут добавлен.';
            }else{
                $this->addErrors($r->getErrors());
                return null;
            }
        }else{
            $check = \Awz\Kpworks\Custom\AppParamsTable::getList([
                'select'=>['ID'],
                'filter'=>['=PORTAL'=>$domain,'=APP'=>$app,'=ID'=>$id]
            ]);
            if(!$check->fetch()){
                $this->addError(
                    new Error('Ошибка в параметрах запроса', 100)
                );
                return null;
            }
            if($delete=='Y'){
                \Awz\Kpworks\Custom\AppParamsTable::delete(['ID'=>$id]);
                $msg = 'Маршрут удален.';
                $id = 0;
            }else{

                /*if($active=='Y' && !AccessController::can(0, ActionDictionary::ACTION_SETT_ACTIVED)){
                    $this->addError(new Error("Нет прав на активацию правил"));
                    return [];
                }

                if($active=='N' && !AccessController::can(0, ActionDictionary::ACTION_SETT_DEACTIVED)){
                    $this->addError(new Error("Нет прав на деактивацию правил"));
                    return [];
                }*/

                if(!AccessController::can(0, ActionDictionary::ACTION_SETT_EDIT)){
                    $this->addError(new Error("Нет прав на редактирование правил"));
                    return null;
                }

                $r = \Awz\Kpworks\Custom\AppParamsTable::update(['ID'=>$id],$fields);
                if(!$r->isSuccess()){
                    $this->addErrors($r->getErrors());
                    return null;
                }else{
                    $msg = 'Маршрут обновлен.';
                }
            }
        }

        return ['id'=>(int)$id, "msg"=>$msg];
    }

    public static function getSettById(int $id = 0, string $domain = 'default', string $app = 'default'){

        if($id){
            $check = \Awz\Kpworks\Custom\AppParamsTable::getList([
                'select'=>['*'],
                'filter'=>['=PORTAL'=>$domain,'=APP'=>$app,'=ID'=>$id]
            ]);
            if($data = $check->fetch()){
                return [
                    'id'=>$data['ID'],
                    'active'=>$data['ACTIVE'],
                    'name'=>$data['NAME'],
                    'sort'=>$data['SORT'],
                    'works'=>self::calcKeys($data['PARAMS']['works']),
                    'actions'=>self::calcKeys($data['PARAMS']['actions'])
                ];
            }
        }

        if(!$id){
            return [
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
            ];
        }

        return ['id'=>$id];
    }

    public static function findEmail(string $str): array
    {
        $email = [];
        $pattern = '/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]+/ui';
        if($str){
            preg_match_all($pattern, $str, $res);
            if(!empty($res[0])){
                $email = $res[0];
            }
            unset($res);
        }
        return $email;
    }
    public static function getEmailFrom(array $workData): array
    {
        $emailCandidate = [];
        if(!isset($workData['SETTINGS']) || !is_array($workData['SETTINGS']) || empty($workData['SETTINGS']))
            return $emailCandidate;
        if(!isset($workData['SETTINGS']['EMAIL_META']) || !is_array($workData['SETTINGS']['EMAIL_META']) || empty($workData['SETTINGS']['EMAIL_META']))
            return $emailCandidate;
        if(isset($workData['SETTINGS']['EMAIL_META']['from']) && $workData['SETTINGS']['EMAIL_META']['from']){
            $emailCandidate = self::findEmail($workData['SETTINGS']['EMAIL_META']['from']);
        }
        if(empty($emailCandidate) && isset($workData['SETTINGS']['EMAIL_META']['replyTo']) && $workData['SETTINGS']['EMAIL_META']['replyTo']){
            $emailCandidate = self::findEmail($workData['SETTINGS']['EMAIL_META']['replyTo']);
        }
        if(empty($emailCandidate) && isset($workData['SETTINGS']['EMAIL_META']['__email']) && $workData['SETTINGS']['EMAIL_META']['__email']){
            $emailCandidate = self::findEmail($workData['SETTINGS']['EMAIL_META']['__email']);
        }

        return $emailCandidate;
    }

    public static function getEmailTo(array $workData): array
    {
        $emailCandidate = [];
        if(!isset($workData['SETTINGS']) || !is_array($workData['SETTINGS']) || empty($workData['SETTINGS']))
            return $emailCandidate;
        if(!isset($workData['SETTINGS']['EMAIL_META']) || !is_array($workData['SETTINGS']['EMAIL_META']) || empty($workData['SETTINGS']['EMAIL_META']))
            return $emailCandidate;

        if(isset($workData['SETTINGS']['EMAIL_META']['to']) && $workData['SETTINGS']['EMAIL_META']['to']){
            $emailCandidate = self::findEmail($workData['SETTINGS']['EMAIL_META']['to']);
        }
        if(empty($emailCandidate) && isset($workData['SETTINGS']['EMAIL_META']['__email']) && $workData['SETTINGS']['EMAIL_META']['__email']){
            $emailCandidate = self::findEmail($workData['SETTINGS']['EMAIL_META']['__email']);
        }

        return $emailCandidate;
    }

    public static function replaceJson($jsonStr, $macros){
        try{
            $tstStr = str_replace(array_keys($macros), array_values($macros), $jsonStr);
            $tst = Json::decode($tstStr);
            $jsonStr = $tstStr;
        }catch (\Exception $e){
            foreach($macros as $k=>$v){
                $jsonStr = str_replace(':'.$k, ':"'.$k.'"', $jsonStr);
                try{
                    $tst = Json::decode($v);
                    $jsonStr = str_replace('"'.$k.'"', ''.str_replace('"','\"',$v), $jsonStr);
                }catch (\Exception $e){
                    $jsonStr = str_replace('"'.$k.'"', Json::encode($v), $jsonStr);
                }
            }
        }
        return $jsonStr;
    }

}