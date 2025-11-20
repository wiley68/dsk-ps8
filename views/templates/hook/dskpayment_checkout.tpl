{*
    * @File: dskpayment_checkout.tpl
    * @Author: Ilko Ivanov
    * @Author e-mail: ilko.iv@gmail.com
    * @Publisher: Avalon Ltd
    * @Publisher e-mail: home@avalonbg.com
    * @Owner: Банка ДСК
    * @Version: 1.2.0
*}
<div>
    С избора си да финансирате покупката чрез Банка ДСК Вие декларирате, че сте запознат с Информацията относно
    обработването на лични данни на физически лица от Банка ДСК АД.
    <br />
    <a target="_blank"
        href="https://dskbank.bg/docs/default-source/gdpr/%D0%B8%D0%BD%D1%84%D0%BE%D1%80%D0%BC%D0%B0%D1%86%D0%B8%D1%8F-%D0%BE%D1%82%D0%BD%D0%BE%D1%81%D0%BD%D0%BE-%D0%BE%D0%B1%D1%80%D0%B0%D0%B1%D0%BE%D1%82%D0%B2%D0%B0%D0%BD%D0%B5%D1%82%D0%BE-%D0%BD%D0%B0-%D0%BB%D0%B8%D1%87%D0%BD%D0%B8-%D0%B4%D0%B0%D0%BD%D0%BD%D0%B8-%D0%BD%D0%B0-%D1%84%D0%B8%D0%B7%D0%B8%D1%87%D0%B5%D1%81%D0%BA%D0%B8-%D0%BB%D0%B8%D1%86%D0%B0-%D0%BE%D1%82-%D0%B1%D0%B0%D0%BD%D0%BA%D0%B0-%D0%B4%D1%81%D0%BA-%D0%B0%D0%B4-%D0%B8-%D1%81%D1%8A%D0%B3%D0%BB%D0%B0%D1%81%D0%B8%D1%8F-%D0%B7%D0%B0-%D0%BE%D0%B1%D1%80%D0%B0%D0%B1%D0%BE%D1%82%D0%B2%D0%B0%D0%BD%D0%B5-%D0%BD%D0%B0-%D0%BB%D0%B8%D1%87%D0%BD%D0%B8-%D0%B4%D0%B0%D0%BD%D0%BD%D0%B8.pdf">Информация
        относно обработването на лични данни на физически лица от 'Банка ДСК' АД</a>
</div>

{* Скрыта POST форма за изпращане на данните *}
<form id="dskpayment-form" method="POST" action="{$dskapi_validation_url|escape:'html':'UTF-8'}" style="display: none;">
    <input type="hidden" name="token" value="{$dskapi_token|escape:'html':'UTF-8'}" />
    <input type="hidden" name="dskapi_cart_id" value="{$dskapi_cart_id|escape:'html':'UTF-8'}" />
    <input type="hidden" name="dskapi_firstname" value="{$dskapi_firstname|escape:'html':'UTF-8'}" />
    <input type="hidden" name="dskapi_lastname" value="{$dskapi_lastname|escape:'html':'UTF-8'}" />
    <input type="hidden" name="dskapi_phone" value="{$dskapi_phone|escape:'html':'UTF-8'}" />
    <input type="hidden" name="dskapi_email" value="{$dskapi_email|escape:'html':'UTF-8'}" />
    <input type="hidden" name="dskapi_address2" value="{$dskapi_address2|escape:'html':'UTF-8'}" />
    <input type="hidden" name="dskapi_address2city" value="{$dskapi_address2city|escape:'html':'UTF-8'}" />
    <input type="hidden" name="dskapi_address1" value="{$dskapi_address1|escape:'html':'UTF-8'}" />
    <input type="hidden" name="dskapi_address1city" value="{$dskapi_address1city|escape:'html':'UTF-8'}" />
    <input type="hidden" name="dskapi_postcode" value="{$dskapi_postcode|escape:'html':'UTF-8'}" />
    <input type="hidden" name="dskapi_eur" value="{$dskapi_eur|escape:'html':'UTF-8'}" />
</form>