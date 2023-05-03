<?php
namespace Fw\EmailNotification\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;


class Data extends AbstractHelper
{
    /**
     * Get admin email addresses from configuration
     *
     * @param int|null $storeId
     * @return array
     */
    public function getAdminEmails($storeId = null)
    {
        $configPath = 'email/review_approval_display/fromemailaddress';
        return $this->scopeConfig->getValue($configPath, ScopeInterface::SCOPE_STORE, $storeId);
    }
    
    
    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function getEmailApprovalReviewStatus($storeId = null)
    {
        return (bool)$this->scopeConfig->getValue('email/review_approval_display/scope');
    }


    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function getAdminReviewStatus($storeId = null)
    {
        $configPath = 'email/general/scope';
        return $this->scopeConfig->getValue($configPath, ScopeInterface::SCOPE_STORE, $storeId);
    }


        /**
     * @param null $storeId
     * Add Email address whol will receive an email address on Review Submission
      * @return mixed
     */ 
    public function addEmailAddressestoReceive($storeId = null)
    {
        $configPath = 'email/general/addemailaddress';
        return $this->scopeConfig->getValue($configPath, ScopeInterface::SCOPE_STORE, $storeId);
    }

            /**
     * @param null $storeId
     * Get Value of Template in Magento2
      * @return mixed
     */
    public function getAdminApprovalTemplateId($storeId = null)
    {
        $configPath = 'email/review_approval_display/approvetemplateid';
        return  $this->scopeConfig->getValue($configPath, ScopeInterface::SCOPE_STORE, $storeId);
    }

    public function getAdminEmailReceiveTemplateId($storeId = null)
    {
        $configPath =  'email/general/templateid';
        return  $this->scopeConfig->getValue($configPath, ScopeInterface::SCOPE_STORE, $storeId);
    }
}
