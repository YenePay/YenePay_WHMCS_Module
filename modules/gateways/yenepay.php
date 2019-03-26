<?php

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

function yenepay_MetaData()
{
    return array(
        'DisplayName' => 'YenePay Payment Gateway Module',
        'APIVersion' => '1.1', // Use API Version 1.1
        'DisableLocalCredtCardInput' => true,
        'TokenisedStorage' => false,
    );
}

function gatewaymodule_config()
{
    return array(
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'YenePay',
        ),
        'testMode' => array(
            'FriendlyName' => 'Test Mode',
            'Type' => 'yesno',
            'Description' => 'Tick to enable test mode',
        ),
        'successCallback' => array(
            'Type' => 'System',
            'Value' => 'yenepay_success',
        ),
        'ipnCallback' => array(
            'Type' => 'System',
            'Value' => 'yenepay_ipn',
        ),
        'returnUrl' => array(
            'FriendlyName' => 'Return Url',
            'Type' => 'text',
            'Default' => '',
            'Description' => 'Enter your Return Url here',
        ),
        'sellerCode' => array(
            'FriendlyName' => 'Seller Code',
            'Type' => 'text',
            'Default' => '',
            'Description' => 'Enter your YenePay Seller Code',
        ),
        'pdtKey' => array(
            'FriendlyName' => 'PDT Key',
            'Type' => 'text',
            'Default' => '',
            'Description' => 'Enter your PDT Key here',
        )
    );
}

function yenepay_link($params)
{
    if ($params['testmode'] == "on") {
        $url = 'https://test.yenepay.com/Home/Process/';
	}
	else {
        $url = 'https://www.yenepay.com/checkout/Home/Process/';
	}

    // System Parameters
    $systemUrl = $params['systemurl'];
    $langPayNow = $params['langpaynow'];
    $moduleDisplayName = $params['name'];
    $moduleName = $params['paymentmethod'];
    $successCallback = $params['successCallback'];
    $ipnCallback = $params['ipnCallback'];

    $postfields = array();
    $postfields["Process"] = "Express";
    $postfields["MerchantId"] = $params['sellerCode'];
    $postfields["SuccessUrl"] = $systemUrl . '/modules/gateways/callback/' . $successCallback . '.php';
    $postfields["IPNUrl"] = $systemUrl . '/modules/gateways/callback/' . $ipnCallback . '.php';

    $postfields["ItemId"] = $params['invoiceid'];
    $postfields["MerchantOrderId"] = $params['invoiceid'];
    $postfields["ItemName"] = $params["description"];
    $postfields["UnitPrice"] = $params['amount'];
    $postfields["Quantity"] = 1;    

    $htmlOutput = '<form method="post" action="' . $url . '">';
    foreach ($postfields as $k => $v) {
        $htmlOutput .= '<input type="hidden" name="' . $k . '" value="' . urlencode($v) . '" />';
    }
    $htmlOutput .= '<input type="submit" value="' . $langPayNow . '" />';
    $htmlOutput .= '</form>';

    return $htmlOutput;
}
