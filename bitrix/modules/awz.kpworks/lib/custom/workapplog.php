<?php

namespace Awz\Kpworks\Custom;

use Awz\Kpworks\Helper;
use Bitrix\Main\Error;
use Bitrix\Main\EventResult;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Entity;
use Bitrix\Main\Result;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Security\Random;

use Bitrix\Main;
use Bitrix\Main\ORM\Query;

Loc::loadMessages(__FILE__);

class WorkAppLogTable extends Entity\DataManager {

    public static function getFilePath()
    {
        return __FILE__;
    }

    public static function getTableName()
    {
        return 'b_awz_kpworks_applog';
        /*
        CREATE TABLE IF NOT EXISTS `b_awz_kpworks_applog` (
        `ID` int(18) NOT NULL AUTO_INCREMENT,
        `ENTITY_ID` varchar(64) NOT NULL,
        `PARAMS` longtext NOT NULL,
        `PORTAL` varchar(64) NOT NULL,
        `APP` varchar(64) NOT NULL,
        `DATE_ADD` datetime NOT NULL,
        PRIMARY KEY (`ID`),
        index IX_PORTAL_APP (PORTAL,APP),
        index IX_PORTAL_APP_ENT (PORTAL,APP,ENTITY_ID)
        ) AUTO_INCREMENT=1;
        */
        /* ALTER TABLE `b_awz_kpworks_applog` ALTER COLUMN ENTITY_ID VARCHAR(64); */
    }
    public static function getMap()
    {
        return [
            new Entity\IntegerField('ID', [
                    'primary' => true,
                    'autocomplete' => true,
                    'title'=>Loc::getMessage('AWZ_BXAPI_CUSTOM_WORKAPP_ENTITY_FIELD_ID')
                ]
            ),
            new Entity\StringField('ENTITY_ID', array(
                    'required' => true,
                    'title'=>Loc::getMessage('AWZ_BXAPI_CUSTOM_WORKAPP_ENTITY_FIELD_ENTITY_ID')
                )
            ),
            new Entity\StringField('PORTAL', array(
                    'required' => true,
                    'title'=>Loc::getMessage('AWZ_BXAPI_CUSTOM_WORKAPP_ENTITY_FIELD_PORTAL')
                )
            ),
            new Entity\StringField('APP', array(
                    'required' => true,
                    'title'=>Loc::getMessage('AWZ_BXAPI_CUSTOM_WORKAPP_ENTITY_FIELD_APP')
                )
            ),
            new Entity\StringField('PARAMS', array(
                    'required' => true,
                    'title'=>Loc::getMessage('AWZ_BXAPI_CUSTOM_WORKAPP_ENTITY_FIELD_PARAMS'),
                    'save_data_modification' => function(){
                        return [
                            function ($value) {
                                return serialize($value);
                            }
                        ];
                    },
                    'fetch_data_modification' => function(){
                        return [
                            function ($value) {
                                return unserialize($value, ["allowed_classes" => false]);
                            }
                        ];
                    },
                )
            ),
            new Entity\DatetimeField('DATE_ADD', array(
                    'required' => true,
                    'title'=>Loc::getMessage('AWZ_BXAPI_CUSTOM_WORKAPP_ENTITY_FIELD_DATE_ADD')
                )
            ),
        ];
    }

    public static function removeOldAgent($day = 30){

        self::deleteByFilter([
            '<DATE_ADD'=>\Bitrix\Main\Type\DateTime::createFromTimestamp(time()-$day*86400)
        ]);

        return "\Awz\Kpworks\Custom\WorkAppLogTable::removeOldAgent(".$day.");";
    }


    public static function deleteByFilter(array $filter)
    {
        $entity = static::getEntity();
        $table = static::getTableName();

        $where = Query\Query::buildFilterSql($entity, $filter);

        if($where <> '')
        {
            $where = ' where ' . $where;
        }
        else
        {
            throw new Main\ArgumentException("Deleting by empty filter is not allowed, use truncate ({$table}).", 'filter');
        }

        $entity->getConnection()->queryExecute("delete from {$table} {$where}");

        static::cleanCache();
    }

}