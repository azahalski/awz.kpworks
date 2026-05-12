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

Loc::loadMessages(__FILE__);

class AppParamsTable extends Entity\DataManager {
    public static function getFilePath()
    {
        return __FILE__;
    }

    public static function getTableName()
    {
        return 'b_awz_kpworks_custom_appparams';
        /*
        CREATE TABLE IF NOT EXISTS `b_awz_kpworks_custom_appparams` (
        `ID` int(18) NOT NULL AUTO_INCREMENT,
        `ACTIVE` varchar(1) NOT NULL,
        `NAME` varchar(64) NOT NULL,
        `SORT` int(4) NOT NULL,
        `PARAMS` longtext NOT NULL,
        `PORTAL` varchar(65) NOT NULL,
        `APP` varchar(65) NOT NULL,
        `DATE_ADD` datetime NOT NULL,
        PRIMARY KEY (`ID`),
        index IX_PORTAL_APP (PORTAL,APP)
        ) AUTO_INCREMENT=1;
        */
    }
    public static function getMap()
    {
        return [
            new Entity\IntegerField('ID', [
                    'primary' => true,
                    'autocomplete' => true,
                    'title'=>Loc::getMessage('AWZ_BXAPI_CUSTOM_APPPARAMS_ENTITY_FIELD_ID')
                ]
            ),
            new Entity\BooleanField('ACTIVE', array(
                    'required' => true,
                    'title'=>Loc::getMessage('AWZ_BXAPI_CUSTOM_APPPARAMS_ENTITY_FIELD_ACTIVE'),
                    'values'=>['N','Y']
                )
            ),
            new Entity\StringField('NAME', array(
                    'required' => true,
                    'title'=>Loc::getMessage('AWZ_BXAPI_CUSTOM_APPPARAMS_ENTITY_FIELD_NAME')
                )
            ),
            new Entity\IntegerField('SORT', array(
                    'required' => true,
                    'title'=>Loc::getMessage('AWZ_BXAPI_CUSTOM_APPPARAMS_ENTITY_FIELD_SORT')
                )
            ),
            new Entity\StringField('PORTAL', array(
                    'required' => true,
                    'title'=>Loc::getMessage('AWZ_BXAPI_CUSTOM_APPPARAMS_ENTITY_FIELD_PORTAL')
                )
            ),
            new Entity\StringField('APP', array(
                    'required' => true,
                    'title'=>Loc::getMessage('AWZ_BXAPI_CUSTOM_APPPARAMS_ENTITY_FIELD_APP')
                )
            ),
            new Entity\StringField('PARAMS', array(
                    'required' => true,
                    'title'=>Loc::getMessage('AWZ_BXAPI_CUSTOM_APPPARAMS_ENTITY_FIELD_PARAMS'),
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
                    'title'=>Loc::getMessage('AWZ_BXAPI_CUSTOM_APPPARAMS_ENTITY_FIELD_DATE_ADD')
                )
            ),
        ];
    }
}