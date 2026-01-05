<?php

namespace Kanboard\Plugin\Ctec\Pagination;

use Kanboard\Core\Base;
use Kanboard\Core\Paginator;

/**
 * Class CodeReviewPagination
 *
 * @package Kanboard\Plugin\Ctec\Pagination
 */
class CodeReviewPagination extends Base
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
        $query = $this->db->table('code_review')
            ->columns(
                'code_review.id',
                'code_review.task_id',
                'code_review.name'
            );

        return $this->paginator
            ->setUrl('CodeReviewController', $method, array('plugin' => 'ctec', 'pagination' => 'code_reviews'))
            ->setMax($max)
            ->setOrder('code_review.id')
            ->setDirection('DESC')
            ->setQuery($query)
            ->calculateOnlyIf($this->request->getStringParam('pagination') === 'code_reviews');
    }
}
