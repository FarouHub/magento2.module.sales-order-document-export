<?php

namespace Lightweight\SalesOrderDocumentExport\Cron;

use Exception;
use Lightweight\SalesOrderDocumentExport\Helper\Data;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\FilterGroupBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Area;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\State;
use Magento\Framework\Backup\Exception\NotEnoughPermissions;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\Write;
use Magento\Framework\Validator\Exception as ValidatorException;
use Psr\Log\LoggerInterface;

/**
 * Performs scheduled export.
 */
abstract class ExportPdfAbstract
{

    /** @var string */
    protected $_type = '';

    /**
     * Error messages
     *
     * @var array
     */
    protected $_errors = [];

    /**
     * Backup data
     *
     * @var Data
     */
    protected $_exportHelper = null;

    /**
     * @var LoggerInterface
     */
    protected $_logger;

    /**
     * Filesystem facade
     *
     * @var Filesystem
     */
    protected $_filesystem;

    /**
     * @var string
     */
    protected $_exportDir;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $_searchCriteriaBuilder;

    /**
     * @var FilterBuilder
     */
    protected $_filterBuilder;

    /**
     * @var FilterGroupBuilder
     */
    protected $_filterGroupBuilder;

    /**
     * @var State
     */
    protected $_appState;

    /**
     * @var null
     */
    protected $_repositoryFactory;

    /**
     * @var null
     */
    protected $_pdf;

    /**
     * ExportPdfAbstract constructor.
     *
     * @param State                 $appState
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param FilterBuilder         $filterBuilder
     * @param FilterGroupBuilder    $filterGroupBuilder
     * @param Data                  $exportHelper
     * @param LoggerInterface       $logger
     * @param Filesystem            $filesystem
     * @param null                  $pdf
     * @param null                  $repositoryFactory
     */
    public function __construct(
        State $appState,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        FilterBuilder $filterBuilder,
        FilterGroupBuilder $filterGroupBuilder,
        Data $exportHelper,
        LoggerInterface $logger,
        Filesystem $filesystem,
        $pdf = null,
        $repositoryFactory = null
    ) {
        $this->_appState              = $appState;
        $this->_searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->_filterBuilder         = $filterBuilder;
        $this->_filterGroupBuilder    = $filterGroupBuilder;
        $this->_exportHelper          = $exportHelper;
        $this->_logger                = $logger;
        $this->_filesystem            = $filesystem;
        $this->_pdf                   = $pdf;
        $this->_repositoryFactory     = $repositoryFactory;
    }

    /**
     * Set path to directory where exports are stored and create the directory if it doesn't exist
     *
     * @param string $exportDir
     *
     * @return $this
     * @throws NotEnoughPermissions
     */
    public function setExportDir($exportDir)
    {
        if (!is_dir($exportDir)) {
            $success = mkdir($exportDir, 0755, true);
            if (!$success) {
                throw new NotEnoughPermissions(__('Export directory could not be created'));
            }
        }

        if (!is_writable($exportDir)) {
            throw new NotEnoughPermissions(__('Export directory is not writeable'));
        }

        $this->_exportDir = $exportDir;

        return $this;
    }

    /**
     * Get path to directory where exports are stored
     *
     * @return string
     */
    public function getExportDir()
    {
        return $this->_exportDir;
    }

    /**
     * Export PDF
     *
     * @return $this
     */
    public function execute()
    {
        if (!$this->_exportHelper->isExportEnabled($this->_type)) {
            return $this;
        }

        try {
            //SET CURRENT AREA
            $this->_appState->setAreaCode(Area::AREA_ADMINHTML);
        } catch (LocalizedException $e) {
            // Area is already set
        }

        $this->_errors = [];
        try {
            $exportPath = $this->_exportHelper->getExportPath($this->_type);
            if (!$exportPath) {
                throw new ValidatorException(__('Export path is not set'));
            }
            $this->setExportDir($exportPath);

            $directory            = $this->_filesystem->getDirectoryReadByPath($exportPath);
            $alreadyExportedArray = $directory->search('*.pdf');
            foreach ($alreadyExportedArray as &$filename) {
                $filename = str_replace('.pdf', '', $filename);
            }

            if ($alreadyExportedArray) {
                $filter = $this->_filterBuilder
                    ->setField('increment_id')
                    ->setValue($alreadyExportedArray)
                    ->setConditionType('nin')
                    ->create();

                $filterGroup = $this->_filterGroupBuilder
                    ->addFilter($filter)
                    ->create();

                $searchCriteria = $this->_searchCriteriaBuilder
                    ->setFilterGroups([$filterGroup])
                    ->setPageSize(10)
                    ->create();
            } else {
                $searchCriteria = $this->_searchCriteriaBuilder->setFilterGroups([])->create();
            }

            $repository     = $this->_repositoryFactory->create();
            $repoCollection = $repository->getList($searchCriteria);
            /** @var Write $writeHandle */
            $writeHandle = $this->_filesystem->getDirectoryWrite(DirectoryList::TMP);

            foreach ($repoCollection as $item) {
                $pdf         = $this->_pdf->getPdf([$item]);
                $tmpFilepath = $this->_type . DIRECTORY_SEPARATOR . $item->getData('increment_id') . '.pdf';
                $writeHandle->writeFile($tmpFilepath, $pdf->render());
                $absPath = $writeHandle->getAbsolutePath($tmpFilepath);
                rename($absPath, $this->getExportDir() . DIRECTORY_SEPARATOR . $item->getData('increment_id') . '.pdf');
            };
        } catch (Exception $e) {
            $this->_errors[] = $e->getMessage();
            $this->_errors[] = $e->getTrace();
            $this->_logger->critical($e->getMessage());
        }

        return $this;
    }
}
