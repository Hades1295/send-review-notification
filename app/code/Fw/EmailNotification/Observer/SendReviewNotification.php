<?php

namespace Fw\EmailNotification\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Api\ProductRepositoryInterface; 
use Magento\Framework\Message\ManagerInterface;
use Fw\EmailNotification\Helper\Data;
use Magento\SalesRule\Api\CouponRepositoryInterface;
use Magento\SalesRule\Api\Data\CouponInterfaceFactory;
use Magento\SalesRule\Api\Data\RuleInterfaceFactory;
use Magento\SalesRule\Api\RuleRepositoryInterface;
use Magento\SalesRule\Model\RuleFactory;


class SendReviewNotification implements ObserverInterface {

    /**
     * @var TransportBuilder
     */
    protected $_transportBuilder;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $_customerRepositoryInterface;
    /*
    * @param TransportBuilder $transportBuilder    
    * @param CustomerRepositoryInterface $_customerRepositoryInterface
    */
        /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;
    protected $inlineTranslation ;
    protected $storeManager;
    protected $productRepository; 
    protected $couponRepository;
    protected $couponFactory;
    protected $ruleRepository;
    protected $ruleFactory;

    public function __construct(
        TransportBuilder $_transportBuilder,
        StoreManagerInterface $storeManager,
        StateInterface $state,
        ManagerInterface $messageManager,
        CustomerRepositoryInterface $_customerRepositoryInterface,
        ProductRepositoryInterface $productRepository,
        Data $datahelper,
        CouponRepositoryInterface $couponRepository,
        CouponInterfaceFactory $couponFactory,
        RuleRepositoryInterface $ruleRepository,
        RuleFactory $ruleFactory
    ) {
        $this->_transportBuilder = $_transportBuilder;
        $this->storeManager = $storeManager;
        $this->inlineTranslation = $state;
        $this->messageManager = $messageManager;
        $this->_customerRepositoryInterface = $_customerRepositoryInterface;
        $this->productRepository = $productRepository;
        $this->datahelper = $datahelper;
        $this->couponRepository = $couponRepository;
        $this->couponFactory = $couponFactory;
        $this->ruleRepository = $ruleRepository;
        $this->ruleFactory = $ruleFactory;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {   

        try {
            if ($this->datahelper->getEmailApprovalReviewStatus()) {
                // this is an example and you can change template id,fromEmail,toEmail,etc as per your need.
                $review = $observer->getEvent()->getObject();
                $customerId = $review->getCustomerId();
                // Example: Assign the coupon to the customer
                $customer = $this->_customerRepositoryInterface->getById($customerId);               
                $templateId = $this->datahelper->getAdminApprovalTemplateId(); // template id 
                $fromEmail = $this->datahelper->getAdminEmails();  // sender Email id
                $fromName = 'Fw-Fashion Website';             // sender Name
                $toEmail = $customer->getEmail(); // receiver email id
                if ($review->getStatusId() == \Magento\Review\Model\Review::STATUS_APPROVED && $customerId) {
                    try {
                        $reviewData = $review->getData();
                        $productId = $review->getEntityPkValue();
                        $product = $this->productRepository->getById($productId);
                        $couponCode = $this->generateCouponCode();
                        $ruleId = $this->createSalesRule($customerId,$couponCode,$reviewData);
                        $customer->setCustomAttribute('applied_coupon', $couponCode);
                        // Example: Assign the coupon to the review
                        try {
                            $this->_customerRepositoryInterface->save($customer);
                        } catch (\Exception $e) {
                            $this->messageManager->addExceptionMessage(
                                $e,
                                __('Something went wrong while saving the customer Review and. ')
                            );
                        }
                        // template variables pass here
                        $templateVars = [
                            'review_title' => $reviewData["title"],
                            'nickname' => $reviewData["nickname"],
                            'review_summary'=> $reviewData["detail"],
                            'productName' =>  $product["name"],
                            'productImage'=> $product["productImage"],
                            'productPrice'=> $product["special_price"],
                            'coupon' =>      $couponCode
                        ];
                        $storeId = $this->storeManager->getStore()->getId();
                        
                        $from = ['email' => $fromEmail, 'name' => $fromName];
                        $this->inlineTranslation->suspend();
                        
                        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
                        $templateOptions = [
                            'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                            'store' => $storeId
                        ];
                        $transport = $this->_transportBuilder->setTemplateIdentifier($templateId, $storeScope)
                        ->setTemplateOptions($templateOptions)
                        ->setTemplateVars($templateVars)
                        ->setFrom($from)
                        ->addTo($toEmail)
                        ->getTransport();
                        $transport->sendMessage();
                        $this->inlineTranslation->resume();
                        $this->messageManager->addSuccessMessage(
                            __('Email is being Forworded to Customer')
                        );
                    } catch (\Exception $e) {
                        file_put_contents(BP . '/var/log/observer.log',print_r($e->getMessage(), true) . PHP_EOL, FILE_APPEND);
                        $this->messageManager->addExceptionMessage(
                            $e,
                            __('Something went wrong while updating the product(s) status.')
                        );
                    }
                }    
            }
        } catch (\Exception $e) {
            file_put_contents(BP . '/var/log/observer.log',print_r($e->getMessage(), true) . PHP_EOL, FILE_APPEND);
            $this->messageManager->addExceptionMessage(
                $e,
                __('Something went wrong while updating the product(s) status.')
            );
        }
    }


    protected function generateCouponCode()
    {
        $length = 8; // Length of the coupon code
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'; // Characters allowed in the coupon code
        $code = '';
        
        $charactersLength = strlen($characters);
        for ($i = 0; $i < $length; $i++) {
            $code .= $characters[rand(0, $charactersLength - 1)];
        }
        file_put_contents(BP . '/var/log/observer.log',print_r($code, true) . PHP_EOL, FILE_APPEND);
        return "FWR-".$code;
    }

    protected function createSalesRule($customerId,$couponCode,$reviewData)
    {   
        $rule = $this->ruleFactory->create();
        $coupon['name'] = $reviewData['nickname']." ".'Customer Specific Discount';
        $coupon['desc'] = $reviewData['nickname']." ".'Customer Specific Discount';
        $coupon['start'] = date('Y-m-d');
        $coupon['end'] = '';
        $coupon['max_redemptions'] = 1;
        $coupon['coupon_type'] = \Magento\SalesRule\Model\Rule::COUPON_TYPE_SPECIFIC;
        $coupon['discount_amount'] = 5;
        $coupon['flag_is_free_shipping'] = 'no';
        $coupon['redemptions'] = 1;
        $coupon['code'] = $couponCode;
        $coupon['discount_type'] = \Magento\SalesRule\Model\Rule::BY_FIXED_ACTION;

        $rule->setName($coupon['name'])
        ->setDescription($coupon['desc'])
        ->setFromDate($coupon['start'])
        ->setToDate($coupon['end'])
        ->setUsesPerCustomer($coupon['max_redemptions'])
        ->setCustomerGroupIds([$customerId])
        ->setIsActive(1)
        ->setSimpleAction($coupon['discount_type'])
        ->setCouponType($coupon['coupon_type'])
        ->setDiscountAmount($coupon['discount_amount'])
        ->setDiscountQty(1)
        ->setApplyToShipping($coupon['flag_is_free_shipping'])
        ->setTimesUsed($coupon['redemptions'])
        ->setWebsiteIds(array('1'))
        ->setCouponType(2)
        ->setCouponCode($coupon['code'])
        ->setUsesPerCoupon(NULL);
        
        try {
            $rule->save();
        } catch (\Exception $e) {
            file_put_contents(BP . '/var/log/observer.log',print_r($e->getMessage(), true) . PHP_EOL, FILE_APPEND);
            $this->messageManager->addExceptionMessage(
                $e,
                __('Something went wrong while creating a Coupon for customer')
            );
        }
    }    
}