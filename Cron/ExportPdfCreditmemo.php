<?php

namespace Lightweight\SalesOrderDocumentExport\Cron;

use Lightweight\SalesOrderDocumentExport\Helper\Data;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\FilterGroupBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\State;
use Magento\Framework\Filesystem;
use Magento\Sales\Model\Order\CreditmemoRepositoryFactory;
use Magento\Sales\Model\Order\Pdf\Creditmemo;
use Psr\Log\LoggerInterface;

/**
 * Performs scheduled pdf export
 */
class ExportPdfCreditmemo extends ExportPdfAbstract
{

    /** @var string */
    protected $_type = 'creditmemo';

    /**
     * ExportPdfCreditmemo constructor.
     *
     * @param Data                        $exportHelper
     * @param Filesystem                  $filesystem
     * @param FilterBuilder               $filterBuilder
     * @param FilterGroupBuilder          $filterGroupBuilder
     * @param LoggerInterface             $logger
     * @param SearchCriteriaBuilder       $searchCriteriaBuilder
     * @param State                       $appState
     * @param Creditmemo                  $pdf
     * @param CreditmemoRepositoryFactory $repositoryFactory
     */
    public function __construct(
        Data $exportHelper,
        Filesystem $filesystem,
        FilterBuilder $filterBuilder,
        FilterGroupBuilder $filterGroupBuilder,
        LoggerInterface $logger,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        State $appState,
        Creditmemo $pdf,
        CreditmemoRepositoryFactory $repositoryFactory
    ) {
        parent::__construct($appState, $searchCriteriaBuilder, $filterBuilder, $filterGroupBuilder, $exportHelper, $logger, $filesystem, $pdf, $repositoryFactory);
    }

}
