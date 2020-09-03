<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\MediaGalleryUi\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\PatchVersionInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * Patch is mechanism, that allows to do atomic upgrade data changes
 */
class AddMediaGalleryPermissions implements
    DataPatchInterface,
    PatchVersionInterface
{
    /**
     * @var ModuleDataSetupInterface $moduleDataSetup
     */
    private $moduleDataSetup;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(ModuleDataSetupInterface $moduleDataSetup)
    {
        $this->moduleDataSetup = $moduleDataSetup;
    }

    /**
     * Add child resources permissions for user roles with Magento_Cms::media_gallery permission
     */
    public function apply(): void
    {
        $tableName = $this->moduleDataSetup->getTable('authorization_rule');
        $connection = $this->moduleDataSetup->getConnection();

        if (!$tableName) {
            return;
        }

        $select = $connection->select()
            ->from($tableName, ['role_id'])
            ->where('resource_id = "Magento_Cms::media_gallery"');

        $connection->insertMultiple($tableName, $this->getInsertData($connection->fetchCol($select)));
    }

    /**
     * Retrieve data to insert to authorization_rule table based on role ids
     *
     * @param array $roleIds
     * @return array
     */
    private function getInsertData(array $roleIds): array
    {
        $newResources = [
            'Magento_MediaGalleryUiApi::insert_assets',
            'Magento_MediaGalleryUiApi::upload_assets',
            'Magento_MediaGalleryUiApi::edit_assets',
            'Magento_MediaGalleryUiApi::delete_assets',
            'Magento_MediaGalleryUiApi::create_folder',
            'Magento_MediaGalleryUiApi::delete_folder'
        ];

        $data = [];

        foreach ($roleIds as $roleId) {
            foreach ($newResources as $resourceId) {
                $data[] = [
                    'role_id' => $roleId,
                    'resource_id' => $resourceId,
                    'permission' => 'allow'
                ];
            }
        }
    }

    /**
     * @inheritdoc
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies()
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public static function getVersion()
    {
        return '2.4.2';
    }
}
