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

    public function __construct(
        TransportBuilder $_transportBuilder,
        StoreManagerInterface $storeManager,
        StateInterface $state,
        ManagerInterface $messageManager,
        CustomerRepositoryInterface $_customerRepositoryInterface,
        ProductRepositoryInterface $productRepository,
        Data $datahelper,
    ) {
        $this->_transportBuilder = $_transportBuilder;
        $this->storeManager = $storeManager;
        $this->inlineTranslation = $state;
        $this->messageManager = $messageManager;
        $this->_customerRepositoryInterface = $_customerRepositoryInterface;
        $this->productRepository = $productRepository;
        $this->datahelper = $datahelper;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {   

        try {
            if ($this->datahelper->getEmailApprovalReviewStatus()) {
                // this is an example and you can change template id,fromEmail,toEmail,etc as per your need.
                $review = $observer->getEvent()->getObject();
                $customerId = $review->getCustomerId();
                // Load the customer object
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
                        // template variables pass here
                        $templateVars = [
                            'review_title' => $reviewData["title"],
                            'nickname' => $reviewData["nickname"],
                            'review_summary' => $reviewData["detail"],
                            'productName' => $product["name"],
                            'productImage'=> $product["productImage"],
                            'productPrice'=> $product["special_price"]
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

}