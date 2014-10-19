<?php

/**
 * Isotope eCommerce Migration Tool
 *
 * Copyright (C) 2014 terminal42 gmbh
 *
 * @link       http://isotopeecommerce.org
 * @license    http://opensource.org/licenses/lgpl-3.0.html
 */

namespace Isotope\Migration\Service;


use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\TableDiff;
use Doctrine\DBAL\Types\Type;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\Translation\TranslatorInterface;

class DownloadMigrationService extends AbstractConfigfreeMigrationService
{

    /**
     * @type DatabaseVerificationService
     */
    protected $dbcheck;

    /**
     * @type DbafsService
     */
    protected $dbafs;


    public function __construct(
        AttributeBagInterface $config,
        \Twig_Environment $twig,
        TranslatorInterface $translator,
        Connection $db,
        DatabaseVerificationService $migration_dbcheck,
        DbafsService $migration_dbafs
    ) {
        $this->config = $config;
        $this->twig = $twig;
        $this->translator = $translator;
        $this->db = $db;
        $this->dbcheck = $migration_dbcheck;
        $this->dbafs = $migration_dbafs;
    }

    /**
     * Return a name for the migration step
     *
     * @return string
     */
    public function getName()
    {
        return $this->trans('Downloads');
    }

    /**
     * Return a description what this step is about
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->trans('Migrates product downloads and updates orders with downloads.');
    }

    /**
     * Get SQL commands to migration the database
     *
     * @return array
     */
    public function getMigrationSQL()
    {
        if ($this->getStatus() != MigrationServiceInterface::STATUS_READY) {
            throw new \BadMethodCallException('Migration service is not ready');
        }

        return array_merge(
            $this->dbafs->getMigrateFilePathForUuidSQL('tl_iso_downloads', 'singleSRC'),
            $this->getProductSQL(),
            $this->getCollectionSQL()
        );
    }

    /**
     * Execute manual data migration after all the database fields are up-to-date
     */
    public function postMigration()
    {
        $this->dbafs->migratePathToUuid('tl_iso_download', 'singleSRC');


        // TODO: According to Andy there's a download type field now?

        // TODO: finish implementation
    }

    /**
     * Make sure database structure is correct before migration
     *
     * @throws \RuntimeException
     */
    protected function verifyDatabase()
    {
        $this->dbcheck
            ->tableMustExist('tl_iso_downloads')
            ->tableMustNotExist('tl_iso_download')
            ->columnMustExist('tl_iso_downloads', 'title')
            ->columnMustExist('tl_iso_downloads', 'description')
            ->columnMustNotExist('tl_iso_downloads', 'published');

        $this->dbcheck
            ->tableMustExist('tl_iso_order_downloads')
            ->tableMustNotExist('tl_iso_product_collection_download');

        // TODO: finish implementation
    }

    /**
     * @return array
     */
    private function getProductSQL()
    {
        $tableDiff = new TableDiff('tl_iso_downloads');
        $tableDiff->newName = 'tl_iso_download';

        $column = new Column('published', Type::getType(Type::STRING));
        $column->setFixed(true)->setLength(1)->setNotnull(true)->setDefault('');
        $tableDiff->addedColumns['published'] = $column;

        $sql = $this->db->getDatabasePlatform()->getAlterTableSQL($tableDiff);
        $sql[] = "UPDATE tl_iso_download SET published='1'";

        // TODO: finish implementation

        return $sql;
    }

    /**
     * @return array
     */
    private function getCollectionSQL()
    {
        $tableDiff = new TableDiff('tl_iso_order_downloads');
        $tableDiff->newName = 'tl_iso_product_collection_download';

        // TODO: finish implementation

        return $this->db->getDatabasePlatform()->getAlterTableSQL($tableDiff);
    }

    /**
     * Return a list of to do's or messages for the summary page
     *
     * @return array
     */
    public function getSummaryMessages()
    {
        return array(
            $this->trans('service.download.titleAndDescription')
        );
    }
}
