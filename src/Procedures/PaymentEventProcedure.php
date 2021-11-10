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
 
namespace Novalnet\Procedures;

use Plenty\Modules\EventProcedures\Events\EventProceduresTriggered;
use Plenty\Modules\Order\Models\Order;
use Plenty\Plugin\Log\Loggable;
use Plenty\Modules\Payment\Contracts\PaymentRepositoryContract;
use Novalnet\Services\PaymentService;
use Novalnet\Services\TransactionService;
use Plenty\Modules\Basket\Contracts\BasketRepositoryContract;

/**
 * Class PaymentEventProcedure
 */
class PaymentEventProcedure
{
    use Loggable;
    
    /**
     *
     * @var PaymentService
     */
    private $paymentService;
    
    /**
     *
     * @var Transaction
     */
    private $transaction;
 
   private $basketRepository;
    
    /**
     * Constructor.
     *
     * @param PaymentService $paymentService
     * @param TransactionService $tranactionService
     */
     
    public function __construct(PaymentService $paymentService, TransactionService $tranactionService, BasketRepositoryContract $basketRepository)
    {
        $this->paymentService  = $paymentService;
        $this->transaction     = $tranactionService;
        $this->basketRepository = $basketRepository->load();
    }   
    
    /**
     * @param EventProceduresTriggered $eventTriggered
     */
    public function run(
        EventProceduresTriggered $eventTriggered
    ) {
        /* @var $order Order */
     
        $order = $eventTriggered->getOrder(); 
        $payments = pluginApp(\Plenty\Modules\Payment\Contracts\PaymentRepositoryContract::class);  
        $paymentDetails = $payments->getPaymentsByOrderId($order->id);
       
        $serverRequestData = $this->paymentService->getRequestParameters($this->basketRepository, 'NOVALNET_INVOICE', false, 611, 7, 7);
        $this->getLogger(__METHOD__)->error('order obj', $order);
     $this->getLogger(__METHOD__)->error('request data', $serverRequestData);
    }
}
