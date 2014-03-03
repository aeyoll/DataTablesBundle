<?php
namespace Brown298\DataTablesBundle\Model\DataTable;

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class AbstractQueryBuilderDataTable
 *
 * @package Brown298\DataTablesBundle\Model\DataTable
 * @author  John Brown <brown.john@gmail.com>
 */
abstract class AbstractQueryBuilderDataTable extends AbstractDataTable implements DataTableInterface
{
    /**
     * @var QueryBuilder
     */
    protected $queryBuilder = null;

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $em;

    /**
     * getData
     *
     * override this function to return a raw data array
     *
     * @param Request $request
     * @param null    $dataFormatter
     *
     * @return JsonResponse
     */
    public function getData(Request $request, $dataFormatter = null)
    {
        $this->queryBuilder = $this->getQueryBuilder($request);
        if ($this->queryBuilder == null) {
            return null;
        }

        return $this->getDataByQueryBuilder($request, $this->queryBuilder, $dataFormatter);
    }

    /**
     * getDataByQueryBuilder
     *
     * uses a query builder to get the required data
     *
     * @param Request      $request
     * @param QueryBuilder $qb
     * @param null         $dataFormatter
     *
     * @return JsonResponse
     */
    protected function getDataByQueryBuilder(Request $request, QueryBuilder $qb, $dataFormatter = null)
    {
        $service = $this->container->get('data_tables.service');

        // logger is optional
        if ($this->container->has('logger')) {
            $logger  = $this->container->get('logger');
            $service->setLogger($logger);
        }

        if ($service->getRequest() == null) {
            $service->setRequest($request);
        }

        $service->setQueryBuilder($qb);
        if ($service->getColumns() == null || count($service->getColumns()) != count($this->columns)) {
            $service->setColumns($this->columns);
        }

        return $this->execute($service, $dataFormatter);
    }


    /**
     * execute
     *
     * @param $service
     * @param $formatter
     */
    public function execute($service, $formatter)
    {
        return $service->process($formatter, false);
    }

    /**
     * setQueryBuilder
     *
     * @param QueryBuilder $qb
     */
    public function setQueryBuilder(QueryBuilder $qb)
    {
        $this->queryBuilder = $qb;
    }

    /**
     * getQueryBuilder
     *
     * override this function to return a query builder
     *
     * @param Request $request
     *
     * @return QueryBuilder|null
     */
    public function getQueryBuilder(Request $request)
    {
        return $this->queryBuilder;
    }

    /**
     * @param EntityManager $em
     */
    public function setEm(EntityManager $em = null)
    {
        $this->em = $em;
    }

    /**
     * @return \Brown298\DtTestBundle\Model\Doctrine\ORM\EntityManager
     */
    public function getEm()
    {
        return $this->em;
    }
} 