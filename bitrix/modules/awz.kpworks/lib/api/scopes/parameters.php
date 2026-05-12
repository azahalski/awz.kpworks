<?php
namespace Awz\Kpworks\Api\Scopes;

use Awz\Kpworks\Api\Type\Parameters as ParametersType;

class Parameters extends ParametersType {

    public function __construct(array $params = array())
    {
        parent::__construct($params);
    }

}