<?php

require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/../../../includes/gatewayfunctions.php';
require_once __DIR__ . '/../../../includes/invoicefunctions.php';

require_once(__DIR__ .'/../../sdk/CheckoutHelper.php');
require_once(__DIR__ .'/../../sdk/Models/IPN.php');

$gatewayModuleName = "yenepay";
// Fetch gateway configuration parameters.
$gatewayParams = getGatewayVariables($gatewayModuleName);

// Die if module is not active.
if (!$gatewayParams['type']) {
    die("Module Not Activated");
}

$useSandbox = $gatewayParams['testmode'] == "on" ? true : false;

$ipnModel = new IPN();
$ipnModel->setUseSandbox($useSandbox);

$json_data = json_decode(file_get_contents('php://input'), true);

if(isset($json_data["TotalAmount"]))
	$ipnModel->setTotalAmount($json_data["TotalAmount"]);
if(isset($json_data["BuyerId"]))
	$ipnModel->setBuyerId($json_data["BuyerId"]);
if(isset($json_data["MerchantOrderId"]))
	$ipnModel->setMerchantOrderId($json_data["MerchantOrderId"]);
if(isset($json_data["MerchantId"]))
	$ipnModel->setMerchantId($json_data["MerchantId"]);
if(isset($json_data["MerchantCode"]))
	$ipnModel->setMerchantCode($json_data["MerchantCode"]);
if(isset($json_data["TransactionId"]))
	$ipnModel->setTransactionId($json_data["TransactionId"]);
if(isset($json_data["TransactionCode"]))
	$ipnModel->setTransactionId($json_data["TransactionCode"]);
if(isset($json_data["Status"]))
	$ipnModel->setStatus($json_data["Status"]);
if(isset($json_data["Currency"]))
	$ipnModel->setCurrency($json_data["Currency"]);
if(isset($json_data["Signature"]))
	$ipnModel->setSignature($json_data["Signature"]);

// Retrieve data returned in payment gateway callback
// Varies per payment gateway
$invoiceId = $json_data["MerchantOrderId"];
$transactionId = $json_data["TransactionId"];
$paymentAmount = $json_data["TotalAmount"];
$paymentFee = 0;

/**
 * Validate Callback Invoice ID.
 *
 * Checks invoice ID is a valid invoice number. Note it will count an
 * invoice in any status as valid.
 *
 * Performs a die upon encountering an invalid Invoice ID.
 *
 * Returns a normalised invoice ID.
 *
 * @param int $invoiceId Invoice ID
 * @param string $gatewayName Gateway Name
 */
$invoiceId = checkCbInvoiceID($invoiceId, $gatewayParams['name']);

/**
 * Check Callback Transaction ID.
 *
 * Performs a check for any existing transactions with the same given
 * transaction number.
 *
 * Performs a die upon encountering a duplicate.
 *
 * @param string $transactionId Unique Transaction ID
 */
checkCbTransID($transactionId);


$transactionStatus = 'IPN Authentication Failure';
$success = false;
/**
 * Validate callback authenticity.
 *
 */
 $helper = new CheckoutHelper();
if ($helper->isIPNAuthentic($ipnModel))
{	
    $transactionStatus = 'Success';
    $success = true;
}



/**
 * Log Transaction.
 *
 * Add an entry to the Gateway Log for debugging purposes.
 *
 * The debug data can be a string or an array. In the case of an
 * array it will be
 *
 * @param string $gatewayName        Display label
 * @param string|array $debugData    Data to log
 * @param string $transactionStatus  Status
 */
logTransaction($gatewayParams['name'], $json_data, $transactionStatus);

if ($success) {

    /**
     * Add Invoice Payment.
     *
     * Applies a payment transaction entry to the given invoice ID.
     *
     * @param int $invoiceId         Invoice ID
     * @param string $transactionId  Transaction ID
     * @param float $paymentAmount   Amount paid (defaults to full balance)
     * @param float $paymentFee      Payment fee (optional)
     * @param string $gatewayModule  Gateway module name
     */
    addInvoicePayment(
        $invoiceId,
        $transactionId,
        $paymentAmount,
        $paymentFee,
        $gatewayModuleName
    );

}
