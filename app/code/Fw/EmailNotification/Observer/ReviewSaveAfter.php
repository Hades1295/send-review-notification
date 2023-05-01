<?php
namespace Fw\EmailNotification\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Store\Model\ScopeInterface;

class ReviewSaveAfter implements ObserverInterface
{
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

    /**
     * ReviewSaveAfter constructor.
     * @param TransportBuilder $transportBuilder
     * @param DateTime $dateTime
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        TransportBuilder $transportBuilder,
        DateTime $dateTime,
        StoreManagerInterface $storeManager
    ) {
        $this->_transportBuilder = $transportBuilder;
        $this->_dateTime = $dateTime;
        $this->_storeManager = $storeManager;
    }

    /**
     * Send email to admin after review save.
     *
     * @param Observer $observer
     */
    public function execute(Observer $observer)
    {
        $review = $observer->getData('object');
        $storeId = $this->_storeManager->getStore()->getId();
        $templateOptions = array(
            'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
            'store' => $storeId
        );
        $templateVars = array(
            'review_text' => $review->getData('detail'),
            'rating' => $review->getData('rating'),
            'product_name' => $review->getProduct()->getName()
        );
        $templateId = 'your_custom_template_id'; // replace with your template ID

        // Retrieve admin email addresses from the configuration
        $adminEmails = $this->_scopeConfig->getValue('your_module/general/admin_email', ScopeInterface::SCOPE_STORE);

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
    }
}
