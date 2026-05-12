<?php
namespace Awz\Kpworks;

use Bitrix\Main\Error;
use Bitrix\Main\Result;

class Helper {

    /**
     * constants https://dev.1c-bitrix.ru/rest_help/crm/constants.php
     *
     * @return void
     */
    public static function entityCodes(){
        return [
            ['ID'=>1, 'VALUE'=>'Лид', 'CODE'=>'LEAD', 'MIN_CODE'=>'L'],
            ['ID'=>2, 'VALUE'=>'Сделка', 'CODE'=>'DEAL', 'MIN_CODE'=>'D'],
            ['ID'=>3, 'VALUE'=>'Контакт', 'CODE'=>'CONTACT', 'MIN_CODE'=>'C'],
            ['ID'=>4, 'VALUE'=>'Компания', 'CODE'=>'COMPANY', 'MIN_CODE'=>'CO'],
            ['ID'=>5, 'VALUE'=>'Счет (старый)', 'CODE'=>'INVOICE', 'MIN_CODE'=>'I'],
            ['ID'=>7, 'VALUE'=>'Предложение', 'CODE'=>'QUOTE', 'MIN_CODE'=>'Q'],
            ['ID'=>8, 'VALUE'=>'Реквизит', 'CODE'=>'REQUISITE', 'MIN_CODE'=>'RQ'],
            ['ID'=>31, 'VALUE'=>'Счет (новый)', 'CODE'=>'SMART_INVOICE', 'MIN_CODE'=>'SI'],
        ];
    }

    /**
     * html ошибок по тексту и заголовку
     *
     * @param array $errors массив ошибок
     * @param string $title
     * @return string
     */
    public static function errorsHtmlFromText(array $errors, string $title=''){

        $result = new Result();
        foreach($errors as $err)
            $result->addError(new Error($err));

        return self::errorsHtml($result, $title);

    }

    /**
     * html ошибок по объекту результата и заголовку
     *
     * @param Result $result
     * @param string $title
     * @return string
     */
    public static function errorsHtml(Result $result, $title=''){

        if($result->isSuccess()) return '';

        $html = '<div class="center-error-wrap">';
        $html .= '<h2>'.$title.'</h2>';
        $html .= '<div class="tab-content tab-content-list">';
        foreach($result->getErrorMessages() as $message){
            $html .= '<div class="ui-alert ui-alert-danger">'.$message.'</div>';
        }

        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }
}