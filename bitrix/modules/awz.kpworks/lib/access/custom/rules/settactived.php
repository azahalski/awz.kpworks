<?php
namespace Awz\Kpworks\Access\Custom\Rules;

use Bitrix\Main\Access\AccessibleItem;
use Awz\Kpworks\Access\Custom\PermissionDictionary;
use Awz\Kpworks\Access\Custom\Helper;

class Settactived extends \Bitrix\Main\Access\Rule\AbstractRule
{

    public function execute(AccessibleItem $item = null, $params = null): bool
    {
        if ($this->user->isAdmin() && !Helper::ADMIN_DECLINE)
        {
            return true;
        }
        if ($this->user->getPermission(PermissionDictionary::SETT_ACTIVED))
        {
            return true;
        }
        return false;
    }

}