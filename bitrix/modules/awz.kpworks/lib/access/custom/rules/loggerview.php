<?php
namespace Awz\Kpworks\Access\Custom\Rules;

use Bitrix\Main\Access\AccessibleItem;
use Awz\Kpworks\Access\Custom\PermissionDictionary;
use Awz\Kpworks\Access\Custom\Helper;

class Loggerview extends \Bitrix\Main\Access\Rule\AbstractRule
{

    public function execute(AccessibleItem $item = null, $params = null): bool
    {
        if ($this->user->isAdmin() && !Helper::ADMIN_DECLINE)
        {
            return true;
        }
        if ($this->user->getPermission(PermissionDictionary::LOGGER_VIEW))
        {
            return true;
        }
        return false;
    }

}