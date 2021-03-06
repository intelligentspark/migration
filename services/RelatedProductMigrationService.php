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


class RelatedProductMigrationService extends AbstractConfigfreeMigrationService
{
    /**
     * Return a name for the migration step
     *
     * @return string
     */
    public function getName()
    {
        return $this->trans('service.related_product.service_name');
    }

    /**
     * Return a description what this step is about
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->trans('service.related_product.service_description');
    }

    /**
     * Get SQL commands to migration the database
     *
     * @return array
     */
    public function getMigrationSQL()
    {
        $this->checkMigrationStatus();

        return array_merge(
            $this->renameTable('tl_iso_related_categories', 'tl_iso_related_category'),
            $this->renameTable('tl_iso_related_products', 'tl_iso_related_product')
        );
    }

    /**
     * Execute manual data migration after all the database fields are up-to-date
     */
    public function postMigration()
    {
        // List of product IDs is now comma-separated instead of serialized
        $relations = $this->db->fetchAll("SELECT id, products FROM tl_iso_related_product WHERE products!=''");

        foreach ($relations as $row) {
            $ids = unserialize($row['products']);

            if (!empty($ids) && is_array($ids)) {
                $this->db->update(
                    'tl_iso_related_product',
                    array(
                        'products' => implode(',', $ids)
                    ),
                    array('id' => $row['id'])
                );
            }
        }
    }

    /**
     * Make sure database structure is correct before migration
     *
     * @throws \RuntimeException
     */
    protected function verifyIntegrity()
    {
        $this->dbcheck
            ->tableMustExist('tl_iso_related_categories')
            ->tableMustNotExist('tl_iso_related_category');

        $this->dbcheck
            ->tableMustExist('tl_iso_related_products')
            ->tableMustNotExist('tl_iso_related_product')
            ->columnMustExist('tl_iso_related_products', 'id')
            ->columnMustExist('tl_iso_related_products', 'products');
    }
}
