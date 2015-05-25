<?php

namespace yiiplus\behaviors;

use yii\db\BaseActiveRecord;
use yii\behaviors\AttributeBehavior;

class CreatedTimeAndIPBehavior extends AttributeBehavior
{
    public $createdTimeAttribute = 'created_at';

    public $createdIPAttribute = 'created_ip';

    public $value;

    public function init()
    {
        parent::init();

        if (empty($this->attributes)) {
            $this->attributes = [
                BaseActiveRecord::EVENT_BEFORE_INSERT => [$this->createdTimeAttribute, $this->createdIPAttribute],
            ];
        }
    }

    /**
     * @inheritdoc
     */
    protected function getValue($event)
    {
        return $this->value !== null ? call_user_func($this->value, $event) : $this->defaultValue($event);
    }

    protected function defaultValue($event)
    {
        return [
            $this->createdTimeAttribute => time(),
            $this->createdIPAttribute => static::getUserIP(),
        ];
    }

    /**
     * @inherit
     */
    public function evaluateAttributes($event)
    {
        if (!empty($this->attributes[$event->name])) {
            $attributes = (array) $this->attributes[$event->name];
            $value = $this->getValue($event);
            foreach ($attributes as $index => $attribute) {
                if (is_string($attribute)) {
                    $this->owner->$attribute = $value[$attribute];
                }
            }
        }
    }

    /**
     * Returns the user IP address.
     * @return string user IP address. Null is returned if the user IP address cannot be detected.
     */
    private static function getUserIP()
    {
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
    }
}