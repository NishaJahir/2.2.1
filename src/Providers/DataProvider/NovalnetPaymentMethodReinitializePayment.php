<?php
/**
 * This module is used for real time processing of
 * Novalnet payment module of customers.
 * This free contribution made by request.
 * 
 * If you have found this script useful a small
 * recommendation as well as a comment on merchant form
 * would be greatly appreciated.
 *
 * @author       Novalnet AG
 * @copyright(C) Novalnet 
 * All rights reserved. https://www.novalnet.de/payment-plugins/kostenlos/lizenz
 */

namespace Novalnet\Providers\DataProvider;

use Plenty\Plugin\Templates\Twig;
use Novalnet\Helper\PaymentHelper;
use Novalnet\Services\PaymentService;
use Plenty\Plugin\ConfigRepository;
use Plenty\Modules\Basket\Contracts\BasketRepositoryContract;
use Plenty\Modules\Frontend\Session\Storage\Contracts\FrontendSessionStorageFactoryContract;
use Plenty\Modules\Payment\Contracts\PaymentRepositoryContract;
use Plenty\Modules\Account\Address\Contracts\AddressRepositoryContract;

class NovalnetPaymentMethodReinitializePayment
{
  
  public function call(Twig $twig, $arg):string
  {
    $order = $arg[0];
    $paymentHelper = pluginApp(PaymentHelper::class);
    $paymentService = pluginApp(PaymentService::class);
    $config = pluginApp(ConfigRepository::class);
    $basketRepository = pluginApp(BasketRepositoryContract::class);
    $addressRepository = pluginApp(AddressRepositoryContract::class);
    $paymentRepository = pluginApp(PaymentRepositoryContract::class);
    $sessionStorage = pluginApp(FrontendSessionStorageFactoryContract::class);
    
    
    // Get payment method Id and status
    foreach($order['properties'] as $property) {
        if($property['typeId'] == 3)
        {
            $mopId = $property['value'];
        }
    }
    $paymentService->logger('order', $order);
  
      // Changed payment method key
       $paymentKey = $paymentHelper->getPaymentKeyByMop($mopId);
       $name = trim($config->get('Novalnet.' . strtolower($paymentKey) . '_payment_name'));
       $paymentName = ($name ? $name : $paymentHelper->getTranslatedText(strtolower($paymentKey)));
      // Get the orderamount from order object if the basket amount is empty
       $orderAmount = $paymentHelper->ConvertAmountToSmallerUnit($order['amounts'][0]['invoiceTotal']);
      // Form the payment request data
      $serverRequestData = $paymentService->getRequestParameters($basketRepository->load(), $paymentKey, false, $orderAmount, $order['billingAddress'], $order['deliveryAddress']);
 
       $sessionStorage->getPlugin()->setValue('nnOrderNo', $order['id']);
       $sessionStorage->getPlugin()->setValue('mop', $mopId);
       $sessionStorage->getPlugin()->setValue('paymentKey', $paymentKey);
       
       // Set the request param for redirection payments
      if ($paymentService->isRedirectPayment($paymentKey, false)) {
         $sessionStorage->getPlugin()->setValue('nnPaymentData', $serverRequestData['data']);
         $sessionStorage->getPlugin()->setValue('nnPaymentUrl', $serverRequestData['url']);
      } else { // Set the request param for direct payments
          $sessionStorage->getPlugin()->setValue('nnPaymentData', $serverRequestData);
      }
       
      if ($paymentKey == 'NOVALNET_CC') {
         $ccFormDetails = $paymentService->getCreditCardAuthenticationCallData($basketRepository->load(), $paymentKey, $orderAmount, $order['billingAddress'], $order['deliveryAddress']);
         $ccCustomFields = $paymentService->getCcFormFields();
      }
    
     // Get company and birthday values
      $basket = $basketRepository->load();            
      $billingAddressId = !empty($basket->customerInvoiceAddressId) ? $basket->customerInvoiceAddressId : $order['billingAddress']['id'];
      $address = !empty($order['billingAddress']) ? $order['billingAddress'] : $addressRepository->findAddressById($billingAddressId);
      foreach ($address->options as $option) {
        if ($option->typeId == 9) {
            $birthday = $option->value;
        }
      }  

      // Set guarantee status
      $guarantee_status = $paymentService->getGuaranteeStatus($basketRepository->load(), $paymentKey, $orderAmount, $order['billingAddress'], $order['deliveryAddress']);
      $show_birthday = (empty($address->companyName) && empty($birthday)) ? $guarantee_status : '';

      if ($guarantee_status == 'guarantee' && $show_birthday == '') {
        $sessionStorage->getPlugin()->setValue('nnProcessb2bGuarantee', $guarantee_status);
      }
    
       
       // If the Novalnet payments are rejected do the reinitialize payment
      
          return $twig->render('Novalnet::NovalnetPaymentMethodReinitializePayment', [
            'order' => $order, 
            'paymentMethodId' => $mopId,
            'paymentKey' => $paymentKey,
            'isRedirectPayment' => $paymentService->isRedirectPayment($paymentKey, false),
            'redirectUrl' => $paymentService->getRedirectPaymentUrl(),
            'reinit' => 1,
            'nnPaymentProcessUrl' => $paymentService->getProcessPaymentUrl(),
            'paymentMopKey'     =>  $paymentKey,
            'paymentName' => $paymentName,
            'ccFormDetails'  => !empty($ccFormDetails) ? $ccFormDetails : '',
            'ccCustomFields' => !empty($ccCustomFields) ? $ccCustomFields : '',
            'endcustomername'=> $serverRequestData['data']['first_name'] . ' ' . $serverRequestData['data']['last_name'],
            'nnGuaranteeStatus' => $show_birthday,
            'orderAmount' => $orderAmount,
            'billingAddressId' => $order['billingAddress']['id'],
            'shippingAddressId' => $order['deliveryAddress']['id']
          ]);
       
  }
}
