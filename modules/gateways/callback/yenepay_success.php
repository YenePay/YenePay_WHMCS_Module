<?php

require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/../../../includes/gatewayfunctions.php';
require_once __DIR__ . '/../../../includes/invoicefunctions.php';

require_once(__DIR__ .'/../../sdk/CheckoutHelper.php');
require_once(__DIR__ .'/../../sdk/Models/PDT.php');

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

$pdtToken = $gatewayParams['pdtKey'];
$pdtRequestType = "PDT";
$pdtModel = new PDT($pdtToken);
$pdtModel->setUseSandbox($useSandbox);
$invoiceId = "";
$transactionId = "";
		
if(isset($_GET["TransactionId"])){
    $transactionId = $json_data["TransactionId"];
	$pdtModel->setTransactionId($transactionId);
}
if(isset($_GET["MerchantOrderId"])){
    $invoiceId = $_GET["MerchantOrderId"];
	$pdtModel->setMerchantOrderId($invoiceId);
}

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



$transactionStatus = "Failure";
$success = false;	

$helper = new CheckoutHelper();
$result = $helper->RequestPDT($pdtModel);

if($result['result'] == "SUCCESS"){
	$order_status = $result['Status'];
    if($order_status == "Paid")
    {
        $invoiceId = $result["MerchantOrderId"];
        $transactionId = $result["TransactionId"];
        $paymentAmount = $result["TotalAmount"];
        $paymentFee = 0;

        $transactionStatus = "Success";
        $success = true;
    }
}
else{
	$success = false;
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
logTransaction($gatewayParams['name'], $_GET, $transactionStatus);

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

header("Location: " . $gatewayParams['returnUrl']);
}
