<?php
namespace Fw\EmailNotification\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Catalog\Api\ProductRepositoryInterface; 
use Fw\EmailNotification\Helper\Data;


class ReviewSaveAfter implements ObserverInterface
{   

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $_customerRepositoryInterface;
    /*
    /**
     * @var TransportBuilder
     */
    protected $_transportBuilder;

    /**
     * @var DateTime
     */
    protected $_dateTime;

    /**
     * @var StoreManagerInterface
     */
    protected $_storeManager;
    protected $productRepository; 

    /**
     * ReviewSaveAfter constructor.
     * @param TransportBuilder $transportBuilder
     * @param DateTime $dateTime
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        TransportBuilder $transportBuilder,
        DateTime $dateTime,
        StoreManagerInterface $storeManager,
        ManagerInterface $messageManager,
        CustomerRepositoryInterface $_customerRepositoryInterface,
        ProductRepositoryInterface $productRepository,
        Data $datahelper
    ) {
        $this->_transportBuilder = $transportBuilder;
        $this->_dateTime = $dateTime;
        $this->_storeManager = $storeManager;
        $this->messageManager = $messageManager;
        $this->_customerRepositoryInterface = $_customerRepositoryInterface;
        $this->productRepository = $productRepository;
        $this->datahelper = $datahelper;

    }

    /**
     * Send email to admin after review save.
     *
     * @param Observer $observer
     */ 
    public function execute(Observer $observer)
    {   

        try {
            $review = $observer->getData('object'); 
            $customerId = $review->getCustomerId();
            $customer = $this->_customerRepositoryInterface->getById($customerId);
            $productId = $review->getEntityPkValue();
            $product = $this->productRepository->getById($productId);
            $storeId = $this->_storeManager->getStore()->getId();
            $templateOptions = array(
                'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                'store' => $storeId
            );
            $templateVars = array(
                'review_title' => $review->getData('title'),
                'review_text' => $review->getData('detail'),
                'rating' => $review->getData('rating'),
                'customer_name' =>$review->getData('nickname'),
                'product_name' => $product->getName()
            );
            $templateId =$this->datahelper->getAdminEmailReceiveTemplateId(); // replace with your template ID
            // Retrieve admin email addresses from the configuration
            $adminEmails = $this->datahelper->addEmailAddressestoReceive();
    
            // Check if there are multiple email addresses
            $adminEmails = explode(",", $adminEmails);
    
            // Send email to admin(s)
            foreach($adminEmails as $adminEmail) {
                $transport = $this->_transportBuilder->setTemplateIdentifier($templateId)
                    ->setTemplateOptions($templateOptions)
                    ->setTemplateVars($templateVars)
                    ->setFrom('general')
                    ->addTo($adminEmail)
                    ->getTransport();
                $transport->sendMessage();
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
