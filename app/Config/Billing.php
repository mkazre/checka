<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Invoicing defaults (feature: billing). The admin's values persist via
 * setting('Billing.<key>') and are edited on Invoices → Billing Settings.
 * Online card/instant-EFT payment reuses the Paystack keys under Pay.*.
 */
class Billing extends BaseConfig
{
    // --- Company details printed on invoices & statements ---
    public string $companyName    = '';
    public string $companyAddress = '';
    public string $companyPhone   = '';
    public string $companyEmail   = '';
    public string $companyRegNo   = '';
    public string $companyVatNo   = '';

    // --- Tax & numbering ---
    public bool   $vatRegistered     = false;
    public float  $vatRate           = 15.0;
    public string $invoicePrefix     = 'INV-';
    public int    $nextInvoiceNumber = 1001;
    public int    $paymentTermsDays  = 7;
    public float  $defaultInspectionFee = 0;
    public string $defaultChargeDescription = 'Vehicle condition inspection';

    // --- Bank details (EFT) ---
    public string $bankName          = '';
    public string $bankAccountName   = '';
    public string $bankAccountNumber = '';
    public string $bankBranchCode    = '';
    public string $bankAccountType   = '';
    public string $bankSwift         = '';

    // --- Payment options & wording ---
    public bool   $payOnlineEnabled = true;   // show "Pay Now" (Paystack) when Pay.* keys are set
    public bool   $payEftEnabled    = true;   // show bank-transfer details
    public string $paymentInstructions = 'Please use the invoice number as your payment reference.';
    public string $invoiceTerms        = '';

    // --- Monthly statements ---
    public bool $monthlyStatementsEnabled = false;
}
