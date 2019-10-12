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

namespace O2System\Reactor\Models\Sql\Relations;

// ------------------------------------------------------------------------

use O2System\Database\DataObjects\Result;
use O2System\Reactor\Models\Sql;

/**
 * Class BelongsToManyThrough
 *
 * @package O2System\Reactor\Models\Sql\Relations
 */
class BelongsToManyThrough extends Sql\Relations\Abstracts\AbstractRelation
{
    /**
     * BelongsToManyThrough::getResult
     * 
     * @return array|bool|Result
     */
    public function getResult()
    {
        if ($this->map->currentModel->row instanceof Sql\DataObjects\Result\Row) {
            $criteria = $this->map->currentModel->row->offsetGet($this->map->currentPrimaryKey);
            $field = $this->map->currentTable . '.' . $this->map->currentPrimaryKey;

            $this->map->referenceModel->qb
                ->select([
                    $this->map->referenceTable . '.*',
                ])
                ->join($this->map->currentTable, implode(' = ', [
                    $this->map->currentTable . '.' . $this->map->currentPrimaryKey,
                    $this->map->intermediaryTable . '.' . $this->map->intermediaryCurrentForeignKey,
                ]))
                ->join($this->map->referenceTable, implode(' = ', [
                    $this->map->referenceTable . '.' . $this->map->referencePrimaryKey,
                    $this->map->intermediaryTable . '.' . $this->map->intermediaryReferenceForeignKey,
                ]));

            if ($result = $this->map->intermediaryModel->find($criteria, $field)) {
                return $result;
            }
        }

        return new Result([]);
    }
}