<?php

namespace app\widgets;

use Yii;
use yii\helpers\Html;

class ReCaptchaWidget extends \yii\bootstrap\InputWidget
{
    public $widgetOptions;
    public $siteKey;
    public static $id_itereator = 0;
    public $defaul_id = "g-re-captcha";
    public static $first_captcha = true;

    private function getCapName()
    {
        if (!empty($this->widgetOptions['id'])) {
            return $this->widgetOptions['id'];
        } else {
            if (!self::$id_itereator) {
                self::$id_itereator++;
                return $this->defaul_id;
            } else {
                return $this->defaul_id . "-" . self::$id_itereator++;
            }
        }
    }

    private function getVarNameById($id)
    {
        return str_replace("-", "_", $id);
    }

    public function init()
    {
        
    }

    public function run()
    {


        $view = $this->view;

        $inputName = Html::getInputName($this->model, $this->attribute);
        $inputId = Html::getInputId($this->model, $this->attribute);

        $view->registerJsFile(
                '//www.google.com/recaptcha/api.js?onload=callBack_recap&render=explicit', [
            'async' => true,
            'defer' => true,
            'position' => \yii\web\View::POS_HEAD,
        ]);

        $callback = '$("#' . $inputId . '").val(response)';
        $expiredCallback = '$("#' . $inputId . '").val("")';

        // если капча вызывается впервые, то подготавлеваем некоторые глобальные 
        // переменные, через которые в дальнейшем удут строится мультикапчки
        // (если потребуется)
        if (self::$first_captcha) {
            $view->registerJs("
                function resetReCaptcha(cap) {
                    grecaptcha.reset(cap);
                }
                var captcha_funcs = {};
                var captcha_vars = {};
                var callBack_recap = function () {
                    for (var code in captcha_funcs) {
                        eval('captcha_funcs.'+code+'();');
                    }
                }
                ", \yii\web\View::POS_HEAD);
        }
        $idCap = $this->getCapName();
        $varName = $this->getVarNameById($idCap);
        $view->registerJs("
            captcha_funcs." . $varName . " = function () {
                    captcha_vars." . $varName . " = grecaptcha.render(
                            '{$idCap}', {
                                'sitekey': '{$this->siteKey}',
                                'theme': 'light',
                                'callback': function(response){{$callback}},
                                'expired-callback': function(){{$expiredCallback}},
                    });
                };
            ", \yii\web\View::POS_HEAD);

        echo Html::input('text', $inputName, null, ['id' => $inputId, 'type' => 'hidden']);
        echo Html::tag('div', "", ['class' => 'google-recaptcha-wraper', 'id' => $idCap, 'data' => ['sitekey' => $this->siteKey, 'reset-func' => "resetReCaptcha(captcha_vars.".$varName."); " . $expiredCallback ]]);

        self::$first_captcha = false;
    }

}
