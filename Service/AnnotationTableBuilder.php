<?php
namespace Brown298\DataTablesBundle\Service;

use Brown298\DataTablesBundle\MetaData\Table;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Process\Exception\InvalidArgumentException;

/**
 * Class TableBuilder
 * @package Brown298\DataTablesBundle\Service
 * @author  John Brown <brown.john@gmail.com>
 */
class AnnotationTableBuilder implements TableBuilderInterface
{
    /**
     * @var \Doctrine\Common\Annotations\AnnotationReader
     */
    private $reader;

    /**
     * @var Table
     */
    protected $tableAnnotations;

    /**
     * @var array
     */
    protected $args;

    /**
     * @var mixed
     */
    protected $table;

    /**
     * @var array
     */
    protected $columns = array();

    /**
     * @var \Doctrine\ORM\EntityManager
     */
    protected $em;

    /**
     * @param ContainerInterface $container
     * @param EntityManager $em
     * @param AnnotationReader $reader
     * @param Table $table\
     */
    public function __construct(ContainerInterface $container, EntityManager $em, AnnotationReader $reader, Table $table)
    {
        $this->container         = $container;
        $this->reader            = $reader;
        $this->tableAnnotations  = $table;
        $this->em                = $em;
    }

    /**
     * buildMetaData
     */
    protected function buildMetaData()
    {
        $columnArray = array();
        $className   = $this->tableAnnotations->class;
        $refl        = new \ReflectionClass($className);
        $properties  = $refl->getProperties();

        foreach ($properties as $property) {
            $column = $this->reader->getPropertyAnnotation($property, 'Brown298\DataTablesBundle\MetaData\Column');

            if (!empty($column)) {
                if (!isset($column->source)) {
                    throw new InvalidArgumentException('DataTables requires a "source" attribute be provided for a column');
                }

                if (!isset($column->name)) {
                    throw new InvalidArgumentException('DataTables requires a "name" attribute be provided for a column');
                }

                // check for default
                $default = $this->reader->getPropertyAnnotation($property, 'Brown298\DataTablesBundle\MetaData\DefaultSort');
                if (!empty($default)) {
                    $column->defaultSort = true;
                }

                // check for formatting
                $format = $this->reader->getPropertyAnnotation($property, 'Brown298\DataTablesBundle\MetaData\Format');
                if (!empty($format)) {
                    if (!isset($format->dataFields)) {
                        throw new InvalidArgumentException('DataTables requires a "dataFields" attribute be provided for a column formatter');
                    }
                    $column->format = $format;
                }

                $this->columns[] = $column;
                $columnArray[$column->source] = $column->name;
            }
        }

        $this->table->setColumns($columnArray);
        $this->table->setMetaData(
            array(
                'table'   => $this->tableAnnotations,
                'columns' => $this->columns,
            )
        );
    }

    /**
     * creates the table
     */
    protected function buildTable()
    {
        $className = $this->tableAnnotations->class;
        array_shift($this->args);

        if(!empty($this->args)) {
            $ref         = new \ReflectionClass($className);
            $this->table = $ref->newInstanceArgs($this->args);
        } else {
            $this->table = new $className;
        }

        // pass the dependencies in, they can override them later if necessary
        $this->table->setContainer($this->container);
        $this->table->setEm($this->em);
    }

    /**
     * build
     *
     * @param array $args
     * @return mixed
     */
    public function build(array $args = array())
    {
        $this->args = $args;
        $this->buildTable();
        $this->buildMetaData();

        return $this->table;
    }
}