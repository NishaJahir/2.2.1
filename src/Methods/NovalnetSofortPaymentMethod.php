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

namespace Novalnet\Methods;

use Plenty\Plugin\ConfigRepository;
use Plenty\Modules\Payment\Method\Contracts\PaymentMethodService;
use Plenty\Plugin\Application;
use Novalnet\Helper\PaymentHelper;
use Novalnet\Services\PaymentService;
use Plenty\Modules\Basket\Models\Basket;
use Plenty\Modules\Basket\Contracts\BasketRepositoryContract;

/**
 * Class NovalnetSofortPaymentMethod
 *
 * @package Novalnet\Methods
 */
class NovalnetSofortPaymentMethod extends PaymentMethodService
{
    /**
     * @var ConfigRepository
     */
    private $configRepository;

    /**
     * @var PaymentHelper
     */
    private $paymentHelper;
    
     /**
     * @var PaymentService
     */
    private $paymentService;
    
    
    /**
     * @var Basket
     */
    private $basket;
    
    /**
     * NovalnetPaymentMethod constructor.
     *
     * @param ConfigRepository $configRepository
     * @param PaymentHelper $paymentHelper
     * @param PaymentService $paymentService
     */
    public function __construct(ConfigRepository $configRepository,
                                PaymentHelper $paymentHelper,
                                PaymentService $paymentService,
                   BasketRepositoryContract $basket)
    {
        $this->configRepository = $configRepository;
        $this->paymentHelper = $paymentHelper;
        $this->paymentService  = $paymentService;
        $this->basket = $basket->load();
    }

    /**
     * Check the configuration if the payment method is active
     * Return true only if the payment method is active
     *
     * @return bool
     */
    public function isActive():bool
    {
       if ($this->configRepository->get('Novalnet.novalnet_sofort_payment_active') == 'true') {
        
        $active_payment_allowed_country = 'true';
        if ($allowed_country = $this->configRepository->get('Novalnet.novalnet_sofort_allowed_country')) {
        $active_payment_allowed_country  = $this->paymentService->allowedCountries($this->basket, $allowed_country);
        }
        
        $active_payment_minimum_amount = 'true';
        $minimum_amount = trim($this->configRepository->get('Novalnet.novalnet_sofort_minimum_order_amount'));
        if (!empty($minimum_amount) && is_numeric($minimum_amount)) {
        $active_payment_minimum_amount = $this->paymentService->getMinBasketAmount($this->basket, $minimum_amount);
        }
        
        $active_payment_maximum_amount = 'true';
        $maximum_amount = trim($this->configRepository->get('Novalnet.novalnet_sofort_maximum_order_amount'));
        if (!empty($maximum_amount) && is_numeric($maximum_amount)) {
        $active_payment_maximum_amount = $this->paymentService->getMaxBasketAmount($this->basket, $maximum_amount);
        }
        
        
        return (bool)($this->paymentHelper->paymentActive() && $active_payment_allowed_country && $active_payment_minimum_amount && $active_payment_maximum_amount);
        } 
        return false;
    
    }

    /**
     * Get the name of the payment method. The name can be entered in the config.json.
     *
     * @return string
     */
    public function getName():string
    {   
        $name = trim($this->configRepository->get('Novalnet.novalnet_sofort_payment_name'));
        return ($name ? $name : $this->paymentHelper->getTranslatedText('novalnet_sofort'));
    }

    /**
     * Retrieves the icon of the payment. The URL can be entered in the configuration.
     *
     * @return string
     */
    public function getIcon():string
    {
       $logoUrl = $this->configRepository->get('Novalnet.novalnet_sofort_payment_logo');
        if($logoUrl == 'images/banktransfer.png'){
            /** @var Application $app */
            $app = pluginApp(Application::class);
            $logoUrl = $app->getUrlPath('novalnet') .'/images/banktransfer.png';
        } 
        return $logoUrl;
    }

    /**
     * Retrieves the description of the payment. The description can be entered in the configuration.
     *
     * @return string
     */
    public function getDescription():string
    {
        $description = trim($this->configRepository->get('Novalnet.novalnet_sofort_description'));
        return ($description ? $description : sprintf($this->paymentHelper->getTranslatedText('redirectional_payment_description'),$this->paymentHelper->getTranslatedText('novalnet_sofort')));
    }

    /**
     * Check if it is allowed to switch to this payment method
     *
     * @return bool
     */
    public function isSwitchableTo(): bool
    {
        return true;
    }

    /**
     * Check if it is allowed to switch from this payment method
     *
     * @param int $orderId
     * @return bool
     */
    public function isSwitchableFrom($orderId = null): bool
    {
    if($orderId > 0) {
        $tid_status = $this->paymentHelper->getNovalnetTxStatus($orderId);
        if(!empty($tid_status) && !in_array($tid_status, [75, 85, 86, 90, 91, 98, 99, 100])) {
            return true;
        }
        }
        return false;
    }
}