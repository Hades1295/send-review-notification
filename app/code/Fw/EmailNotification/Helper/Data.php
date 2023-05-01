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
        return explode(',', $this->scopeConfig->getValue($configPath, ScopeInterface::SCOPE_STORE, $storeId));
    }
    
    
    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function getEmailApprovalReviewStatus($storeId = null)
    {
        $configPath = 'email/review_approval_display/scope';
        return explode(',', $this->scopeConfig->getValue($configPath, ScopeInterface::SCOPE_STORE, $storeId));
    }


    /**
     * @param null $storeId
     *
     * @return mixed
     */
    public function getAdminReviewStatus($storeId = null)
    {
        $configPath = 'email/general/scope';
        return explode(',', $this->scopeConfig->getValue($configPath, ScopeInterface::SCOPE_STORE, $storeId));
    }


        /**
     * @param null $storeId
     * Add Email address whol will receive an email address on Review Submission
      * @return mixed
     */ 
    public function addEmailAddresstoReceive($storeId = null)
    {
        $configPath = 'email/general/addemailaddress';
        return explode(',', $this->scopeConfig->getValue($configPath, ScopeInterface::SCOPE_STORE, $storeId));
    }

}
