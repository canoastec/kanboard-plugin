<?php

namespace Kanboard\Plugin\Ctec\Pagination;

use Kanboard\Core\Base;
use Kanboard\Core\Paginator;

/**
 * Class PairProgrammingPagination
 *
 * @package Kanboard\Plugin\Ctec\Pagination
 */
class PairProgrammingPagination extends Base
{
    /**
     * Get dashboard pagination
     *
     * @access public
     * @param  string  $method
     * @param  integer $max
     * @return Paginator
     */
    public function getDashboardPaginator($method, $max)
    {
        $query = $this->db->table('pair_programming')
            ->columns(
                'pair_programming.id',
                'pair_programming.task_id',
                'pair_programming.name',
                'pair_programming.assignee'
            );

        return $this->paginator
            ->setUrl('PairProgrammingController', $method, array('plugin' => 'ctec', 'pagination' => 'pair_programmings'))
            ->setMax($max)
            ->setOrder('pair_programming.id')
            ->setDirection('DESC')
            ->setQuery($query)
            ->calculateOnlyIf($this->request->getStringParam('pagination') === 'pair_programmings');
    }
}
