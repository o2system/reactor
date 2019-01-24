<?php
/**
 * This file is part of the O2System PHP Framework package.
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
use O2System\Spl\Datastructures\SplArrayObject;
use O2System\Spl\Info\SplClassInfo;

/**
 * Class Row
 *
 * @package O2System\Reactor\Models\Sql\DataObjects
 */
class Row extends SplArrayObject
{
    /**
     * Row Model Instance
     *
     * @var Model
     */
    private $model;

    /**
     * Row::__construct
     *
     * @param Database\DataObjects\Result\Row|array $row
     * @param Model                                 $model
     */
    public function __construct($row, Model &$model)
    {
        $this->model = new SplClassInfo($model);

        if ( ! models()->has($this->model->getParameter())) {
            models()->register($this->model->getParameter(), $model);
        }

        if ($row instanceof Database\DataObjects\Result\Row) {
            parent::__construct($row->getArrayCopy());
        } elseif (is_array($row)) {
            parent::__construct($row);
        }

        models($this->model->getParameter())->row = $this;
    }

    // ------------------------------------------------------------------------

    public function offsetGet($offset)
    {
        if (null !== ($result = $this->__call($offset))) {
            return $result;
        }

        return parent::offsetGet($offset);
    }

    // ------------------------------------------------------------------------

    public function __call($method, $args = [])
    {
        $model = models($this->model->getParameter());

        if (method_exists($model, $method)) {
            $model->row = $this;

            return call_user_func_array([&$model, $method], $args);
        }

        return null;
    }
}