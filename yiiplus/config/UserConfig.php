<?php
/**
 * Created by PhpStorm.
 * User: chendong
 * Date: 15/3/28
 * Time: 下午6:28
 */

namespace yiiplus\config;


use yii\base\Component;
use yii\caching\Cache;
use yii\db\Connection;
use yii\di\Instance;
use yii\db\Query;

class UserConfig extends Component
{
    /**
     * @var Connection|array|string
     */
    public $db = 'db';

    public $table = '{{%user_config}}';

    /**
     * @var Cache|array|string
     */
    public $cache = 'cache';

    public function init()
    {
        parent::init();
        $this->db = Instance::ensure($this->db, Connection::className());
        if ($this->cache !== null) {
            $this->cache = Instance::ensure($this->cache, Cache::className());
        }
    }

    public function get($userID, $key, $defaultValue = null)
    {
        static $values = [];
        $cacheKey = $key . $userID;

        if (isset($values[$cacheKey]))
            return $values[$cacheKey];

        $value = (new Query())
            ->from([$this->table])
            ->select(['value'])
            ->where([
                'key' => $key,
                'user_id' => $userID
            ])->scalar($this->db);

        $values[$cacheKey] = $value === false ? $defaultValue : $value;
        return $values[$cacheKey];
    }

    public function set($userID, $key, $value)
    {
        if ($this->get($userID, $key) === null)
            return $this->add($userID, $key, $value);
        else
            return $this->db->createCommand()->update($this->table,
                ['value' => $value],
                [
                    'key' => $key,
                    'user_id' => $userID
                ]
            )->execute();
    }

    public function add($userID, $key, $value)
    {
        return $this->db->createCommand()->insert($this->table, [
            'user_id' => $userID,
            'key' => $key,
            'value' => $value
        ])->execute();
    }
}