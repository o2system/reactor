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

namespace O2System\Reactor\Models\Sql\DataObjects\Result;

// ------------------------------------------------------------------------

use O2System\Database;
use O2System\Reactor\Models\Sql\Model;
use O2System\Spl\DataStructures\SplArrayObject;
use O2System\Spl\Info\SplClassInfo;

/**
 * Class Row
 *
 * @package O2System\Reactor\Models\Sql\DataObjects
 */
class Row extends SplArrayObject
{
    /**
     * Row::$model
     *
     * @var \O2System\Spl\Info\SplClassInfo
     */
    private $model;

    /**
     * Row::$record
     *
     * @var \O2System\Database\DataObjects\Result\Row\Record
     */
    private $record;

    // ------------------------------------------------------------------------

    /**
     * Row::__construct
     *
     * @param mixed  $row
     * @param Model  $model
     */
    public function __construct($row, Model &$model)
    {
        $this->model = new SplClassInfo($model);
        $this->record = $row->getRecord();

        $hideColumns = [];

        // Visible Columns
        if (count($model->visibleColumns)) {
            $hideColumns = array_diff($row->getColumns(), $model->visibleColumns);
        }

        // Final rebuild row columns
        $model->rebuildRow($row);

        // Hide Columns
        if (count($model->hideColumns)) {
            $hideColumns = array_merge($model->hideColumns);
        }

        if ($row instanceof Database\DataObjects\Result\Row) {
            parent::__construct($row->getArrayCopy());
        } elseif (is_array($row)) {
            parent::__construct($row);
        }

        // Append Columns
        if (count($model->appendColumns)) {
            foreach ($model->appendColumns as $appendColumn) {
                $this->offsetSet($appendColumn, $this->offsetGet($appendColumn));
            }
        }

        // Unset Columns
        foreach ($hideColumns as $column) {
            $this->offsetUnset($column);
        }

        models($this->model->getClass())->row = $this;
    }

    // ------------------------------------------------------------------------

    /**
     * Row::offsetGet
     *
     * @param string $offset
     *
     * @return mixed
     */
    public function offsetGet($offset)
    {
        if ($this->offsetExists($offset)) {
            return parent::offsetGet($offset);
        } elseif(strpos($offset, 'record') !== false) {
            switch ($offset) {
                case 'record':
                    return $this->record;
                    break;
                case 'record_status':
                    return $this->record->status;
                    break;
                case 'record_left':
                    return $this->record->left;
                    break;
                case 'record_right':
                    return $this->record->right;
                    break;
                case 'record_depth':
                    return $this->record->depth;
                    break;
                case 'record_ordering':
                    return $this->record->ordering;
                    break;
                case 'record_create_user':
                    return $this->record->create->user;
                    break;
                case 'record_create_timestamp':
                    return $this->record->create->timestamp;
                    break;
                case 'record_update_user':
                    return $this->record->update->user;
                    break;
                case 'record_update_timestamp':
                    return $this->record->update->timestamp;
                    break;
            }
        } elseif (null !== ($result = $this->__call($offset))) {
            return $result;
        }

        return null;
    }

    // ------------------------------------------------------------------------

    /**
     * Row::__call
     *
     * @param string $method
     * @param array  $args
     *
     * @return bool|\O2System\Reactor\Models\Sql\DataObjects\Result|\O2System\Reactor\Models\Sql\DataObjects\Result\Row|null
     */
    public function __call($method, $args = [])
    {
        $model = models($this->model->getClass());

        if (method_exists($model, $method)) {
            $model->row = $this;

            if (false !== ($result = call_user_func_array([&$model, $method], $args))) {
                $this->offsetSet($method, $result);

                return $result;
            }
        } elseif (strpos($method, 'Url')) {
            $key = str_replace('Url', '', $method);

            if ($key === $model->uploadedImageKey) {
                if (isset($model->uploadedImageFilePath)) {
                    if (is_file($filePath = $model->uploadedImageFilePath . $this->offsetGet($key))) {
                        return images_url($filePath);
                    } elseif (is_file($filePath = PATH_STORAGE . 'images/default/not-found.jpg')) {
                        return images_url($filePath);
                    } elseif (is_file($filePath = PATH_STORAGE . 'images/default/not-found.png')) {
                        return images_url($filePath);
                    }
                }
            } elseif (in_array($key, $model->uploadedImageKeys)) {
                if (isset($model->uploadedImageFilePath)) {
                    if (is_file($filePath = $model->uploadedImageFilePath . $this->offsetGet($key))) {
                        return images_url($filePath);
                    } elseif (is_file($filePath = PATH_STORAGE . 'images/default/not-found.jpg')) {
                        return images_url($filePath);
                    } elseif (is_file($filePath = PATH_STORAGE . 'images/default/not-found.png')) {
                        return images_url($filePath);
                    }
                }
            } elseif ($key === $model->uploadedFileKey) {
                if (isset($model->uploadedFileFilepath)) {
                    return storage_url($model->uploadedFileFilepath . $this->offsetGet($key));
                }
            } elseif (in_array($key, $model->uploadedFileKeys)) {
                if (isset($model->uploadedFileFilepath)) {
                    return storage_url($model->uploadedFileFilepath . $this->offsetGet($key));
                }
            }
        }

        return null;
    }

    // ------------------------------------------------------------------------

    /**
     * Row::delete
     *
     * @return bool
     * @throws \O2System\Spl\Exceptions\RuntimeException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function delete()
    {
        $model = models($this->model->getClass());

        if(empty($model->primaryKeys)) {
            $primaryKey = isset($model->primaryKey) ? $model->primaryKey : 'id';
            $id = $this->offsetGet($primaryKey);

            return $model->delete($id);
        } else {
            $conditions = [];
            foreach($model->primaryKeys as $primaryKey) {
                $conditions[$primaryKey] = $this->offsetGet($primaryKey);
            }

            return $model->deleteBy($conditions);
        }
    }

    // ------------------------------------------------------------------------

    /**
     * Row::updateRecordStatus
     *
     * @param string $method
     *
     * @return bool
     */
    private function updateRecordStatus($method)
    {
        $model = models($this->model->getClass());

        if(empty($model->primaryKeys)) {
            $primaryKey = isset($model->primaryKey) ? $model->primaryKey : 'id';
            $id = $this->offsetGet($primaryKey);

            return $model->{$method}($id);
        } else {
            $conditions = [];
            foreach($model->primaryKeys as $primaryKey) {
                $conditions[$primaryKey] = $this->offsetGet($primaryKey);
            }

            return $model->$model->{$method . 'By'}($conditions);
        }
    }

    // ------------------------------------------------------------------------

    /**
     * Row::restore
     *
     * @return bool
     * @throws \O2System\Spl\Exceptions\RuntimeException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function restore()
    {
        return $this->updateRecordStatus('restore');
    }

    // ------------------------------------------------------------------------

    /**
     * Row::publish
     *
     * @return bool
     * @throws \O2System\Spl\Exceptions\RuntimeException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function publish()
    {
        return $this->updateRecordStatus('publish');
    }

    // ------------------------------------------------------------------------

    /**
     * Row::unpublish
     *
     * @return bool
     * @throws \O2System\Spl\Exceptions\RuntimeException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function unpublish()
    {
        return $this->updateRecordStatus('unpublish');
    }

    // ------------------------------------------------------------------------

    /**
     * Row::archive
     *
     * @return bool
     * @throws \O2System\Spl\Exceptions\RuntimeException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function archive()
    {
        return $this->updateRecordStatus('archive');
    }

    // ------------------------------------------------------------------------

    /**
     * Row::lock
     *
     * @return bool
     * @throws \O2System\Spl\Exceptions\RuntimeException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function lock()
    {
        return $this->updateRecordStatus('lock');
    }

    // ------------------------------------------------------------------------

    /**
     * Row::softDelete
     *
     * @return bool
     * @throws \O2System\Spl\Exceptions\RuntimeException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function softDelete()
    {
        return $this->updateRecordStatus('softDelete');
    }

    // ------------------------------------------------------------------------

    /**
     * Row::draft
     *
     * @return bool
     * @throws \O2System\Spl\Exceptions\RuntimeException
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function draft()
    {
        return $this->updateRecordStatus('draft');
    }
}