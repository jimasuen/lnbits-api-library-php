<?php

/**
 * Lnbits api library in php
 * 
 */


class LnbitsApi
{

    private $endpoint;
    private $walletid;
    private $adminkey;
    private $invoicekey;

    public function __construct($endpoint, $walletid, $adminkey, $invoicekey)
    {
        $this->endpoint = $endpoint;
        $this->walletid = $walletid;
        $this->adminkey = $adminkey;
        $this->invoicekey = $invoicekey;
    }


    // Get wallet details

    function wallet_details()
    {
        $url = $this->endpoint . "/api/v1/wallet";
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $headers = array(
            "X-Api-Key: " . $this->invoicekey,
        );

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($code == 200) {
            return $response;
        } else {
            return "Error " . $code;
        }

        curl_close($ch);
    }

    //Create invoice (incoming)

    /**
     * 
     * unit parameter can be any of the following:
     * sat, usd, etc.
     * 
     * Default value of $unit is sat
     */

    function create_invoice($amount, $memo, $unit = "sat", $webhook = null)
    {
        $url = $this->endpoint . "/api/v1/payments";
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $headers = array(
            "X-Api-Key: " . $this->invoicekey,
            "Content-type: application/json",
        );

        $data = array(
            "out" => false,
            "amount" => $amount,
            "memo" => $memo,
            "unit" => $unit,
            "webhook" => $webhook
        );

        $data = json_encode($data);

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        $response = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($code == 201) {
            return $response;
        } else {
            return "Error " . $code;
        }

        curl_close($ch);
    }

    //Pay an invoice (outgoing)

    function pay_invoice($bolt11)
    {
        $url = $this->endpoint . "/api/v1/payments";
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $headers = array(
            "X-Api-Key: " . $this->adminkey,
            "Content-type: application/json",
        );

        $data = array(
            "out" => true,
            "bolt11" => $bolt11
        );

        $data = json_encode($data);

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        $response = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($code == 201) {
            return $response;
        } else {
            return "Error " . $code;
        }

        curl_close($ch);
    }

    // Pay lightning address (custom)
    /**
     * 
     * $amount is to be written in millisats (amount of sats * 1000)
     * 
     * Function returns JSON object
     * 
     * {"success_action":"xyz",
     * "payment_hash":"xyz",
     * "checking_id":"xyz"}
     */

    function pay_lightning_address($lnaddress, $amount, $memo = "")
    {

        //first, retrieve the metadata and callback url from lightning address

        $ln = explode("@", $lnaddress);

        $lnadd = "https://" . $ln[1] . "/.well-known/lnurlp/" . $ln[0];

        $lnch = curl_init($lnadd);
        curl_setopt($lnch, CURLOPT_RETURNTRANSFER, true);

        $lnresp = curl_exec($lnch);
        curl_close($lnch);

        $lnobj = json_decode($lnresp);

        $metadata = $lnobj->metadata;
        $callback = $lnobj->callback;

        curl_close($lnch);

        // create the description hash using the metadata
        $description_hash = hash('sha256', $metadata);

        //continue with processing payment

        $url = $this->endpoint . "/api/v1/payments/lnurl";
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $headers = array(
            "X-Api-Key: " . $this->adminkey,
            "Content-type: application/json",
        );

        $data = array(
            "description_hash" => $description_hash,
            "callback" => $callback,
            "amount" => $amount,
            "comment" => $memo,
            "description" =>  $memo
        );

        $data = json_encode($data);

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        $response = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($code == 200) {
            return $response;
        } else {
            return "Error " . $code;
        }

        curl_close($ch);
    }

    // Decode an invoice

    /**
     * 
     * $invoice can be bolt11 or lnurl
     */

    function decode_invoice($invoice)
    {
        $url = $this->endpoint . "/api/v1/payments/decode";
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $headers = array(
            "X-Api-Key: " . $this->invoicekey,
            "Content-type: application/json",
        );

        $data = array(
            "data" => $invoice
        );

        $data = json_encode($data);

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        $response = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($code == 200) {
            return $response;
        } else {
            return "Error " . $code;
        }

        curl_close($ch);
    }

    // Check invoice

    function check_invoice($payment_hash)
    {
        $url = $this->endpoint . "/api/v1/payments/" . $payment_hash;
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $headers = array(
            "X-Api-Key: " . $this->invoicekey,
            "Content-type: application/json",
        );

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($code == 200) {
            return $response;
        } else {
            return "Error " . $code;
        }

        curl_close($ch);
    }


    // Get list of payments

    /**
     * $direction = asc or desc
     * 
     * Function returns an array
     */

    function payments_list($limit, $offset, $direction, $memo = null)
    {

        $url = $this->endpoint . "/api/v1/payments?limit=$limit&offset=$offset&direction=$direction" . (($memo == null) ? "" : "&memo=$memo");
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $headers = array(
            "X-Api-Key: " . $this->invoicekey,
            "Content-type: application/json",
        );

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($code == 200) {
            return json_decode($response);
        } else {
            return "Error " . $code;
        }

        curl_close($ch);
    }


    //Conversion of sat to fiat and fiat to sat

    function convert_values($from, $amount, $to)
    {
        $url = $this->endpoint . "/api/v1/conversion";

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $headers = array(
            "Content-type: application/json",
        );

        $data = array(
            "from" => $from,
            "amount" => $amount,
            "to" => $to
        );

        $data = json_encode($data);

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        $response = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($code == 200) {
            return $response;
        } else {
            return "Error " . $code;
        }
    }


    // Create QR code

    /**
     * You can use this function to create a QR code out of the parameter passed into the function.
     * Use case would be to create a QR code for LNURL or invoice
     */

    function create_qr($data)
    {
        $url = $this->endpoint . "/api/v1/qrcode/" . $data;
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($code == 200) {
            return $response;
        } else {
            return "Error " . $code;
        }

        curl_close($ch);
    }
}
 