<?php

namespace App\Observers;

use App\Models\Comms\Signature;
use App\Models\Comms\Template;
use App\Models\Company;
use App\Models\Contact;
use App\Models\ContactsProperties;
use App\Models\LogChanges;
use App\Models\Property;
use App\Models\Task;
use App\Models\User;
use App\Models\Agent\PropertyView;
use Illuminate\Database\Eloquent\Model;

abstract class AbstractObserver
{

    const MAX_LEN_PER_RECORD = 10000;

    protected $notMonitoredChanges = [
        'id',
        'created_at',
        'updated_at',
        'deleted_at',
        'latitude',
        'longitude',
        'listing_updated',
        'use_time',
        'import_id',
        'closest_task_id',
        'creator_id',
        'password',
        'ronas_id',
        'unique_id',
        'ronas_updated_at',
        'tasks_imported_at',

    ];

    public function deleted(Model $model)
    {
        try {
            $className = get_class($model);
            (new LogChanges([
                'entity_type' => $className::ENTITY_ID,
                'entity_id' => $model->id,
                'record_type' => $this->getDeleteTypeByEntity($className),
                'description' => $this->addName($model)
            ]))->save();

            if (method_exists($this, 'deletedFunction')) {
                $this->deletedFunction($model);
            }
        } catch (\Exception $e) {
            \Log::error('Observer [deleted] error: ' . $e->getMessage());
        }
    }


    public function created(Model $model)
    {
        try {
            $className = get_class($model);
            (new LogChanges([
                'entity_type' => $className::ENTITY_ID,
                'entity_id' => $model->id,
                'record_type' => $this->getCreateTypeByEntity($className),
                'description' => $this->addName($model)
            ]))->save();

            if (method_exists($this, 'createdFunction')) {
                $this->createdFunction($model);
            }
        } catch (\Exception $e) {
            \Log::error('Observer [created] error: ' . $e->getMessage());
        }

    }

    public function restored(Model $model)
    {
        try {
            $className = get_class($model);
            (new LogChanges([
                'entity_type' => $className::ENTITY_ID,
                'entity_id' => $model->id,
                'record_type' => $this->getRestoreTypeByEntity($className),
                'description' => $this->addName($model)
            ]))->save();
        } catch (\Exception $e) {
            \Log::error('Observer [restored] error: ' . $e->getMessage());
        }

    }

    public function updating(Model $model)
    {
        try {
            if ($changes = $this->getChanges($model)) {
                $className = get_class($model);
                (new LogChanges([
                    'entity_type' => $className::ENTITY_ID,
                    'entity_id' => $model->id,
                    'record_type' => $this->getChangeTypeByEntity($className),
                    'description' => $changes
                ]))->save();
            }

            if (method_exists($this, 'updatingFunction')) {
                $this->updatingFunction($model, $changes);
            }
        } catch (\Exception $e) {
            \Log::error('Observer [updating] error: ' . $e->getMessage());
        }

    }

    public function saved(Model $model)
    {
        try {
            if (method_exists($this, 'savedFunction')) {
                $this->savedFunction($model);
            }
        } catch (\Exception $e) {
            \Log::error('Observer [saved] error: ' . $e->getMessage());
        }

    }

    private function getCreateTypeByEntity($className)
    {
        switch ($className) {
            case Property::class:
                return LogChanges::PROPERTY_CREATED;
            case Contact::class:
                return LogChanges::CONTACT_CREATED;
            case Company::class:
                return LogChanges::COMPANY_CREATED;
            case User::class:
                return LogChanges::USER_CREATED;
            case Task::class:
                return LogChanges::TASK_CREATED;
            case Signature::class:
                return LogChanges::SIGNATURE_CREATED;
            case Template::class:
                return LogChanges::TEMPLATE_CREATED;
        }

        return false;
    }

    private function getDeleteTypeByEntity($className)
    {
        switch ($className) {
            case Property::class:
                return LogChanges::PROPERTY_DELETED;
            case Contact::class:
                return LogChanges::CONTACT_DELETED;
            case Company::class:
                return LogChanges::COMPANY_DELETED;
            case User::class:
                return LogChanges::USER_DELETED;
            case Task::class:
                return LogChanges::TASK_DELETED;
            case Signature::class:
                return LogChanges::SIGNATURE_DELETED;
            case Template::class:
                return LogChanges::TEMPLATE_DELETED;
        }

        return false;
    }

    private function getRestoreTypeByEntity($className)
    {
        switch ($className) {
            case Property::class:
                return LogChanges::PROPERTY_RESTORED;
            case Contact::class:
                return LogChanges::CONTACT_RESTORED;
            case Company::class:
                return LogChanges::COMPANY_RESTORED;
            case User::class:
                return LogChanges::USER_RESTORED;
            case Task::class:
                return LogChanges::TASK_RESTORED;
            case Signature::class:
                return LogChanges::SIGNATURE_RESTORED;
            case Template::class:
                return LogChanges::TEMPLATE_RESTORED;
        }

        return false;
    }

    private function getChangeTypeByEntity($className)
    {
        switch ($className) {
            case Property::class:
                return LogChanges::PROPERTY_MAIN_FIELDS_CHANGED;
            case Contact::class:
                return LogChanges::CONTACT_MAIN_FIELDS_CHANGED;
            case Company::class:
                return LogChanges::COMPANY_MAIN_FIELDS_CHANGED;
            case User::class:
                return LogChanges::USER_MAIN_FIELDS_CHANGED;
            case Task::class:
                return LogChanges::TASK_MAIN_FIELDS_CHANGED;
            case Signature::class:
                return LogChanges::SIGNATURE_CHANGED;
            case Template::class:
                return LogChanges::TEMPLATE_CHANGED;
            case PropertyView::class:
                return LogChanges::PROPERTY_VIEW_CHANGED;
            case ContactsProperties::class:
                return LogChanges::CONTACT_PROPERTIES_CHANGED;
        }

        return false;
    }

    protected function addName(Model $model, array $initialArray = [], $maxLen = self::MAX_LEN_PER_RECORD)
    {
        $name = isset($model->name) ? $model->name : (isset($model->first_name) ? $model->first_name . ' ' . $model->last_name : null);
        if (mb_strlen($name) > $maxLen) {
            $name = mb_substr($name, 0, $maxLen);
        }
        $result = ['name' => $name];
        if ($initialArray) {
            $result = array_merge_recursive($result, $initialArray);
        }

        return $result;
    }

    /**
     * @param Model $model
     * @param int $maxLen Maximum stored length of each field
     * @return array
     */
    protected function getChanges(Model $model, $maxLen = self::MAX_LEN_PER_RECORD)
    {
        $changes = [];
        foreach ($model->getDirty() as $key => $value) {
            if (!\in_array($key, $this->notMonitoredChanges, 1)) {
                $original = $model->getOriginal($key);

                if (is_numeric($original) AND is_numeric($value) AND !mb_strpos($key, '_id') AND !\in_array($key,['phone', 'clean_phone'], 1)) {
                    // We have to check these values!
                    $originalF = (float)$original;
                    $valueF = (float)$value;
                    if (abs($originalF+$valueF)>0.00001) {
                        if (abs(($valueF-$originalF)/($originalF+$valueF))<0.001) {
                            // These digits are almost the same the same
                            continue;
                        }
                    }
                }

                $changes[$key] = [
                    'old' => mb_strlen($original) > $maxLen ? mb_substr($original, 0, $maxLen) : $original,
                    'new' => mb_strlen($value) > $maxLen ? mb_substr($value, 0, $maxLen) : $value,
                ];
            }
        }
        return $changes;
    }
}