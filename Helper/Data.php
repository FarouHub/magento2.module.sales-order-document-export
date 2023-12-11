<?php

namespace Lightweight\SalesOrderDocumentExport\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Backup\Exception\NotEnoughPermissions;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Phrase;
use Magento\Store\Model\ScopeInterface;

/**
 * Class Data
 *
 * @package Lightweight\SalesOrderDocumentExport\Helper
 */
class Data extends AbstractHelper
{
    const XML_PATH_EXPORT_ENABLE = 'sales_pdf/%%TYPE%%/enable_export';
    const XML_PATH_EXPORT_PATH = 'sales_pdf/%%TYPE%%/export_path';

    /**
     * Data constructor.
     *
     * @param Context $context
     */
    public function __construct(
        Context $context
    ) {
        parent::__construct($context);
    }

    /**
     * @param null $exportType
     *
     * @return bool
     */
    public function isExportEnabled($exportType = null)
    {
        $xmlPath = str_replace('%%TYPE%%', strtolower($exportType), self::XML_PATH_EXPORT_ENABLE);

        return $this->scopeConfig->isSetFlag($xmlPath, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @param string $exportType
     *
     * @return mixed
     */
    public function getExportPath($exportType)
    {
        $xmlPath = str_replace('%%TYPE%%', strtolower($exportType), self::XML_PATH_EXPORT_PATH);

        return $this->scopeConfig->getValue($xmlPath, ScopeInterface::SCOPE_STORE);
    }

}