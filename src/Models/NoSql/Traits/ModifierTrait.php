<?php
/**
 * This file is part of the O2System Framework package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @author         Steeve Andrian Salim
 * @copyright      Copyright (c) Steeve Andrian Salim
 */

// ------------------------------------------------------------------------

namespace O2System\Reactor\Models\NoSql\Traits;

// ------------------------------------------------------------------------

/**
 * Class TraitModifier
 *
 * @package O2System\Reactor\Models\NoSql\Traits
 */
trait ModifierTrait
{
    /**
     * ModifierTrait::insert
     *
     * Method to input data as well as equipping the data in accordance with the fields
     * in the destination database collection.
     *
     *
     * @param   array   $sets       Array of Input Data
     * @param   string $collection  Table Name
     *
     * @return mixed
     */
    public function insert(array $sets, $collection = null)
    {
        $collection = isset($collection) ? $collection : $this->collection;

        if (method_exists($this, 'insertRecordSets')) {
            $this->insertRecordSets($sets);
        }

        if (method_exists($this, 'beforeInsert')) {
            $this->beforeInsert($sets);
        }

        if ($this->qb->collection($collection)->insert($sets)) {
            if (method_exists($this, 'afterInsert')) {
                return $this->afterInsert();
            }

            return true;
        }

        return false;
    }

    // ------------------------------------------------------------------------

    /**
     * ModifierTrait::update
     *
     * Method to update data as well as equipping the data in accordance with the fields
     * in the destination database collection.
     *
     * @param   string $collection Table Name
     * @param   array  $sets       Array of Update Data
     *
     * @return bool|\O2System\Database\DataObjects\Result
     */
    public function update(array $sets, $where = [], $collection = null)
    {
        $collection = isset($collection) ? $collection : $this->collection;
        $primaryKey = isset($this->primaryKey) ? $this->primaryKey : 'id';

        if (empty($where)) {
            if (empty($this->primaryKeys)) {
                $where[ $primaryKey ] = $sets[ $primaryKey ];
            } else {
                foreach ($this->primaryKeys as $primaryKey) {
                    $where[ $primaryKey ] = $sets[ $primaryKey ];
                }
            }
        }

        // Reset Primary Keys
        $this->primaryKey = 'id';
        $this->primaryKeys = [];

        if (method_exists($this, 'updateRecordSets')) {
            $this->updateRecordSets($sets);
        }

        if (method_exists($this, 'beforeUpdate')) {
            $sets = $this->beforeUpdate($sets);
        }

        if ($this->qb->collection($collection)->update($sets, $where)) {

            if (method_exists($this, 'afterUpdate')) {
                return $this->afterUpdate();
            }

            return true;
        }

        return false;
    }

    // ------------------------------------------------------------------------

    /**
     * @param array $sets
     * @return bool
     */
    public function insertMany(array $sets)
    {
        $collection = isset($collection) ? $collection : $this->collection;

        if (method_exists($this, 'insertRecordSets')) {
            foreach ($sets as $set) {
                $this->insertRecordSets($set);
            }
        }

        if (method_exists($this, 'beforeInsertMany')) {
            $this->beforeInsertMany($sets);
        }

        if ($this->qb->collection($collection)->insertBatch($sets)) {
            if (method_exists($this, 'afterInsertMany')) {
                return $this->afterInsertMany();
            }

            return true;
        }

        return false;
    }

    // ------------------------------------------------------------------------

    /**
     * @param array $sets
     * @return bool
     */
    public function updateMany(array $sets)
    {
        $collection = isset($collection) ? $collection : $this->collection;
        $primaryKey = isset($this->primaryKey) ? $this->primaryKey : 'id';

        $where = [];
        if (empty($this->primaryKeys)) {
            $where[ $primaryKey ] = $sets[ $primaryKey ];
            $this->qb->where($primaryKey, $sets[ $primaryKey ]);
        } else {
            foreach ($this->primaryKeys as $primaryKey) {
                $where[ $primaryKey ] = $sets[ $primaryKey ];
            }
        }

        // Reset Primary Keys
        $this->primaryKey = 'id';
        $this->primaryKeys = [];

        if (method_exists($this, 'updateRecordSets')) {
            foreach ($sets as $set) {
                $this->updateRecordSets($set);
            }
        }

        if (method_exists($this, 'beforeUpdateMany')) {
            $this->beforeUpdateMany($sets);
        }

        if ($this->qb->collection($collection)->updateBatch($sets, $where)) {
            if (method_exists($this, 'afterUpdateMany')) {
                return $this->afterUpdateMany();
            }

            return true;
        }

        return false;
    }

    // ------------------------------------------------------------------------

    /**
     * Trash many rows from the database collection based on sets of ids.
     *
     * @param array $ids
     *
     * @return mixed
     */
    public function trashMany(array $ids)
    {
        $affectedRows = [];

        foreach ($ids as $id) {
            $affectedRows[ $id ] = $this->trash($id);
        }

        return $affectedRows;
    }

    // ------------------------------------------------------------------------

    /**
     * trash
     *
     * @param      $id
     * @param null $collection
     *
     * @return array|bool
     */
    public function trash($id, $collection = null)
    {
        $collection = isset($collection) ? $collection : $this->collection;
        $primaryKey = isset($this->primaryKey) ? $this->primaryKey : 'id';

        $sets = [];
        $where = [];

        if (empty($this->primaryKeys)) {
            $where[ $primaryKey ] = $id;
            $sets[ $primaryKey ] = $id;
        } elseif (is_array($id)) {
            foreach ($this->primaryKeys as $primaryKey) {
                $where[ $primaryKey ] = $sets[ $primaryKey ];
                $sets[ $primaryKey ] = $id[ $primaryKey ];
            }
        } else {
            foreach ($this->primaryKeys as $primaryKey) {
                $where[ $primaryKey ] = $sets[ $primaryKey ];
            }

            $sets[ reset($this->primaryKeys) ] = $id;
        }

        // Reset Primary Keys
        $this->primaryKey = 'id';
        $this->primaryKeys = [];

        if (method_exists($this, 'updateRecordSets')) {
            $this->setRecordStatus('TRASH');
            $this->updateRecordSets($sets);
        }

        if (method_exists($this, 'beforeTrash')) {
            $this->beforeTrash($sets);
        }

        if ($this->qb->collection($collection)->update($sets, $where)) {
            if (method_exists($this, 'afterTrash')) {
                return $this->afterTrash();
            }

            return true;
        }

        return false;
    }

    // ------------------------------------------------------------------------

    /**
     * @param array $ids
     * @param array $where
     * @param null $collection
     * @return array
     */
    public function trashManyBy(array $ids, $where = [], $collection = null)
    {
        $affectedRows = [];

        foreach ($ids as $id) {
            $affectedRows[ $id ] = $this->trashBy($id, $where, $collection);
        }

        return $affectedRows;
    }

    // ------------------------------------------------------------------------

    /**
     * @param $id
     * @param array $where
     * @param null $collection
     * @return array|bool
     */
    public function trashBy($id, array $where = [], $collection = null)
    {
        $this->qb->where($where);

        return $this->trash($id, $collection);
    }

    // ------------------------------------------------------------------------

    /**
     * @param array $ids
     * @param bool $force
     * @param null $collection
     * @return array
     */
    public function deleteMany(array $ids, $force = false, $collection = null)
    {
        $affectedRows = [];

        foreach ($ids as $id) {
            $affectedRows[ $id ] = $this->delete($id, $force, $collection);
        }

        return $affectedRows;
    }

    // ------------------------------------------------------------------------

    /**
     * @param $id
     * @param bool $force
     * @param null $collection
     * @return bool
     */
    public function delete($id, $force = false, $collection = null)
    {
        if ((isset($collection) AND is_bool($collection)) OR ! isset($collection)) {
            $collection = $this->collection;
        }

        $primaryKey = isset($this->primaryKey) ? $this->primaryKey : 'id';

        $where = [];
        if (empty($this->primaryKeys)) {
            $where[ $primaryKey ] = $id;
        } elseif (is_array($id)) {
            foreach ($this->primaryKeys as $primaryKey) {
                $where[ $primaryKey ] = $id[ $primaryKey ];
            }
        } else {
            $where[ reset($this->primaryKeys) ] = $id;
        }

        // Reset Primary Keys
        $this->primaryKey = 'id';
        $this->primaryKeys = [];

        if (method_exists($this, 'beforeDelete')) {
            $this->beforeDelete();
        }

        if ($this->qb->collection($collection)->delete($where)) {
            if (method_exists($this, 'afterDelete')) {
                return $this->afterDelete();
            }

            return true;
        }

        return false;
    }

    // ------------------------------------------------------------------------

    /**
     * @param array $ids
     * @param array $where
     * @param bool $force
     * @param null $collection
     * @return array
     */
    public function deleteManyBy(array $ids, $where = [], $force = false, $collection = null)
    {
        $affectedRows = [];

        foreach ($ids as $id) {
            $affectedRows[ $id ] = $this->deleteBy($id, $where, $force, $collection);
        }

        return $affectedRows;
    }

    /**
     * @param $id
     * @param array $where
     * @param bool $force
     * @param null $collection
     * @return bool
     */
    public function deleteBy($id, $where = [], $force = false, $collection = null)
    {
        $this->qb->where($where);

        return $this->delete($id, $force, $collection);
    }

    // ------------------------------------------------------------------------

    /**
     * @param $id
     * @param null $collection
     * @return bool
     */
    public function restore($id, $collection = null)
    {
        return $this->publish($id, $collection);
    }

    // ------------------------------------------------------------------------

    /**
     * @param $id
     * @param null $collection
     * @return bool
     */
    public function publish($id, $collection = null)
    {
        $collection = isset($collection) ? $collection : $this->collection;
        $primaryKey = isset($this->primaryKey) ? $this->primaryKey : 'id';

        $sets = [];
        $where = [];

        if (empty($this->primaryKeys)) {
            $where[ $primaryKey ] = $id;
            $sets[ $primaryKey ] = $id;
        } elseif (is_array($id)) {
            foreach ($this->primaryKeys as $primaryKey) {
                $where[ $primaryKey ] = $sets[ $primaryKey ];
                $sets[ $primaryKey ] = $id[ $primaryKey ];
            }
        } else {
            foreach ($this->primaryKeys as $primaryKey) {
                $where[ $primaryKey ] = $sets[ $primaryKey ];
            }

            $sets[ reset($this->primaryKeys) ] = $id;
        }

        // Reset Primary Keys
        $this->primaryKey = 'id';
        $this->primaryKeys = [];

        if (method_exists($this, 'updateRecordSets')) {
            $this->setRecordStatus('PUBLISH');
            $this->updateRecordSets($sets);
        }

        if (method_exists($this, 'beforePublish')) {
            $this->beforePublish($sets);
        }

        if ($this->qb->collection($collection)->update($sets, $where)) {
            if (method_exists($this, 'afterPublish')) {
                return $this->afterPublish();
            }

            return true;
        }

        return false;
    }

    // ------------------------------------------------------------------------

    /**
     * @param $id
     * @param array $where
     * @param null $collection
     * @return bool
     */
    public function restoreBy($id, array $where = [], $collection = null)
    {
        return $this->publishBy($id, $where, $collection);
    }

    // ------------------------------------------------------------------------

    /**
     * @param $id
     * @param array $where
     * @param null $collection
     * @return bool
     */
    public function publishBy($id, array $where = [], $collection = null)
    {
        $this->qb->where($where);

        return $this->publish($id, $collection);
    }

    // ------------------------------------------------------------------------

    /**
     * @param array $ids
     * @param null $collection
     * @return array
     */
    public function restoreMany(array $ids, $collection = null)
    {
        return $this->publishMany($ids, $collection);
    }

    // ------------------------------------------------------------------------

    /**
     * @param array $ids
     * @param null $collection
     * @return array
     */
    public function publishMany(array $ids, $collection = null)
    {
        $affectedRows = [];

        foreach ($ids as $id) {
            $affectedRows[ $id ] = $this->publish($id, $collection);
        }

        return $affectedRows;
    }

    // ------------------------------------------------------------------------

    /**
     * @param array $ids
     * @param array $where
     * @param null $collection
     * @return array
     */
    public function restoreManyBy(array $ids, $where = [], $collection = null)
    {
        return $this->publishManyBy($ids, $where, $collection);
    }

    // ------------------------------------------------------------------------

    /**
     * @param array $ids
     * @param array $where
     * @param null $collection
     * @return array
     */
    public function publishManyBy(array $ids, $where = [], $collection = null)
    {
        $affectedRows = [];

        foreach ($ids as $id) {
            $affectedRows[ $id ] = $this->publishBy($id, $where, $collection);
        }

        return $affectedRows;
    }
}