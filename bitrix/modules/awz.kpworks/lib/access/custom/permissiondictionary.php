<?php
namespace Awz\Kpworks\Access\Custom;

use Awz\Kpworks\Access\Permission;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class PermissionDictionary
    extends Permission\PermissionDictionary
{
    /*awz.gen start - !!!nodelete*/
	public const MODULE_SETT_VIEW = "96";
	public const MODULE_SETT_EDIT = "97";
	public const MODULE_RIGHT_VIEW = "98";
	public const MODULE_RIGHT_EDIT = "99";
	public const SETT_VIEW = "20";
	public const SETT_EDIT = "21";
	public const SETT_ACTIVED = "22";
	public const SETT_DEACTIVED = "23";
	public const SETT_DELETE = "24";
	public const LOGGER_VIEW = "30";
	/*awz.gen end - !!!nodelete*/
}