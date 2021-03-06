<?php
/**
 * @copyright Copyright (c) PutYourLightsOn
 */

namespace putyourlightson\blitz\records;

use craft\db\ActiveRecord;
use yii\db\ActiveQueryInterface;

/**
 * @property int $cacheId
 * @property int $queryId
 * @property CacheRecord $cache
 * @property ElementQueryRecord $elementQuery
 */
class ElementQueryCacheRecord extends ActiveRecord
{
    // Public Static Methods
    // =========================================================================

    /**
     * @inheritdoc
     *
     * @return string
     */
    public static function tableName(): string
    {
        return '{{%blitz_elementquerycaches}}';
    }

    /**
     * Returns the associated cache
     *
     * @return ActiveQueryInterface
     */
    public function getCache(): ActiveQueryInterface
    {
        return $this->hasOne(CacheRecord::class, ['id' => 'cacheId']);
    }

    /**
     * Returns the associated element query
     *
     * @return ActiveQueryInterface
     */
    public function getElementQuery(): ActiveQueryInterface
    {
        return $this->hasOne(ElementQueryRecord::class, ['id' => 'queryId']);
    }
}
