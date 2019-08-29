<?php

/**
 * expression-types.php.
 *
 *
 * Copyright (c) 2010-2013, Justin Swanhart
 * with contributions by André Rothe <arothe@phosco.info, phosco@gmx.de>
 * and David Négrier <d.negrier@thecodingmachine.com>
 *
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without modification,
 * are permitted provided that the following conditions are met:
 *
 *   * Redistributions of source code must retain the above copyright notice,
 *     this list of conditions and the following disclaimer.
 *   * Redistributions in binary form must reproduce the above copyright notice,
 *     this list of conditions and the following disclaimer in the documentation
 *     and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY
 * EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES
 * OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT
 * SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED
 * TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR
 * BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH
 * DAMAGE.
 */
namespace SQLParser\Query;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Mouf\MoufInstanceDescriptor;
use SQLParser\Node\NodeFactory;
use Mouf\MoufManager;
use SQLParser\Node\NodeInterface;
use SQLParser\Node\Traverser\NodeTraverser;
use SQLParser\Node\Traverser\VisitorInterface;
use SQLParser\Node\UnquotedParameter;

/**
 * This class represents a <code>SELECT</code> query. You can use it to generate a SQL query statement
 * using the <code>toSql</code> method.
 * You can use the <code>QueryResult</code> class if you want to run the query directly.
 *
 * @author David Négrier <d.negrier@thecodingmachine.com>
 * @ExtendedAction {"name":"Generate from SQL", "url":"parseselect/", "default":false}
 * @ExtendedAction {"name":"Test query", "url":"parseselect/tryQuery", "default":false}
 * @Renderer { "smallLogo":"vendor/mouf/database.querywriter/icons/database_go.png" }
 */
class Select implements StatementInterface, NodeInterface
{
    private $distinct;

    /**
     * Returns true if the SELECT is a SELECT DISTINCT.
     *
     * @return bool
     */
    public function getDistinct()
    {
        return $this->distinct;
    }

    /**
     * Sets to true if the SELECT is a SELECT DISTINCT.
     *
     * @param bool $distinct
     */
    public function setDistinct($distinct)
    {
        $this->distinct = $distinct;
    }

    private $columns;

    /**
     * Returns the list of columns for this SQL select.
     *
     * @return NodeInterface[]
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * Sets the list of columns for this SQL select.
     *
     * @param NodeInterface[] $columns
     */
    public function setColumns($columns)
    {
        $this->columns = $columns;
    }

    private $from;

    /**
     * Returns the list of tables for this SQL select.
     *
     * @return NodeInterface[]
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * Sets the list of tables for this SQL select.
     *
     * @param NodeInterface[] $from
     */
    public function setFrom($from)
    {
        $this->from = $from;
    }

    private $where;

    /**
     * Returns the list of where statements.
     *
     * @return NodeInterface[]
     */
    public function getWhere()
    {
        return $this->where;
    }

    /**
     * Sets the list of where statements.
     *
     * @param NodeInterface[]|NodeInterface $where
     */
    public function setWhere($where)
    {
        $this->where = $where;
    }

    private $group;

    /**
     * Returns the list of group statements.
     *
     * @return NodeInterface[]
     */
    public function getGroup()
    {
        return $this->group;
    }

    /**
     * Sets the list of group statements.
     *
     * @param NodeInterface[]|NodeInterface $group
     */
    public function setGroup($group)
    {
        $this->group = $group;
    }

    private $having;

    /**
     * Returns the list of having statements.
     *
     * @return NodeInterface[]|NodeInterface
     */
    public function getHaving()
    {
        return $this->having;
    }

    /**
     * Sets the list of having statements.
     *
     * @param NodeInterface[]|NodeInterface $having
     */
    public function setHaving($having)
    {
        $this->having = $having;
    }

    private $order;

    /**
     * Returns the list of order statements.
     *
     * @return NodeInterface[]|NodeInterface
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * Sets the list of order statements.
     *
     * @param NodeInterface[]|NodeInterface $order
     */
    public function setOrder($order)
    {
        $this->order = $order;
    }

    private $options;

    /**
     * Returns the list of options to be applied just after the "SELECT" keyword.
     *
     * @return string[]
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Sets the list of options to be applied just after the "SELECT" keyword.
     *
     * @param string[] $options
     */
    public function setOptions($options)
    {
        $this->options = $options;
    }

    private $limit;

    /**
     * @return NodeInterface
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * @param NodeInterface $limit
     */
    public function setLimit($limit)
    {
        $this->limit = $limit;
    }

    private $offset;

    /**
     * @return NodeInterface
     */
    public function getOffset()
    {
        return $this->offset;
    }

    /**
     * @param NodeInterface $offset
     */
    public function setOffset($offset)
    {
        $this->offset = $offset;
    }

    /**
     * @param MoufManager $moufManager
     *
     * @return MoufInstanceDescriptor
     */
    public function toInstanceDescriptor(MoufManager $moufManager)
    {
        $instanceDescriptor = $moufManager->createInstance(get_called_class());
        $instanceDescriptor->getProperty('distinct')->setValue($this->distinct);
        $instanceDescriptor->getProperty('columns')->setValue(NodeFactory::nodeToInstanceDescriptor($this->columns, $moufManager));
        $instanceDescriptor->getProperty('from')->setValue(NodeFactory::nodeToInstanceDescriptor($this->from, $moufManager));
        $instanceDescriptor->getProperty('where')->setValue(NodeFactory::nodeToInstanceDescriptor($this->where, $moufManager));
        $instanceDescriptor->getProperty('group')->setValue(NodeFactory::nodeToInstanceDescriptor($this->group, $moufManager));
        $instanceDescriptor->getProperty('having')->setValue(NodeFactory::nodeToInstanceDescriptor($this->having, $moufManager));
        $instanceDescriptor->getProperty('order')->setValue(NodeFactory::nodeToInstanceDescriptor($this->order, $moufManager));
        $instanceDescriptor->getProperty('offset')->setValue(NodeFactory::nodeToInstanceDescriptor($this->offset, $moufManager));
        $instanceDescriptor->getProperty('limit')->setValue(NodeFactory::nodeToInstanceDescriptor($this->limit, $moufManager));
        $instanceDescriptor->getProperty('options')->setValue($this->options);

        return $instanceDescriptor;
    }

    /**
     * Configure the $instanceDescriptor describing this object (it must already exist as a Mouf instance).
     *
     * @param MoufManager $moufManager
     *
     * @return MoufInstanceDescriptor
     */
    public function overwriteInstanceDescriptor($name, MoufManager $moufManager)
    {
        //$name = $moufManager->findInstanceName($this);
        $instanceDescriptor = $moufManager->getInstanceDescriptor($name);
        $instanceDescriptor->getProperty('distinct')->setValue($this->distinct);
        $instanceDescriptor->getProperty('columns')->setValue(NodeFactory::nodeToInstanceDescriptor($this->columns, $moufManager));
        $instanceDescriptor->getProperty('from')->setValue(NodeFactory::nodeToInstanceDescriptor($this->from, $moufManager));
        $instanceDescriptor->getProperty('where')->setValue(NodeFactory::nodeToInstanceDescriptor($this->where, $moufManager));
        $instanceDescriptor->getProperty('group')->setValue(NodeFactory::nodeToInstanceDescriptor($this->group, $moufManager));
        $instanceDescriptor->getProperty('having')->setValue(NodeFactory::nodeToInstanceDescriptor($this->having, $moufManager));
        $instanceDescriptor->getProperty('order')->setValue(NodeFactory::nodeToInstanceDescriptor($this->order, $moufManager));
        $instanceDescriptor->getProperty('offset')->setValue(NodeFactory::nodeToInstanceDescriptor($this->offset, $moufManager));
        $instanceDescriptor->getProperty('limit')->setValue(NodeFactory::nodeToInstanceDescriptor($this->limit, $moufManager));
        $instanceDescriptor->getProperty('options')->setValue($this->options);

        return $instanceDescriptor;
    }

    /**
     * Renders the object as a SQL string.
     *
     * @param array $parameters
     * @param AbstractPlatform $platform
     * @param int $indent
     * @param int $conditionsMode
     *
     * @param bool $extrapolateParameters
     * @return string
     */
    public function toSql(array $parameters, AbstractPlatform $platform, int $indent = 0, $conditionsMode = self::CONDITION_APPLY, bool $extrapolateParameters = true): ?string
    {
        $sql = 'SELECT ';
        if ($this->distinct) {
            $sql .= 'DISTINCT ';
        }
        if (is_array($this->options)) {
            $sql .= implode(' ', $this->options)."\n";
        }

        if (!empty($this->columns)) {
            $sql .= NodeFactory::toSql($this->columns, $platform, $parameters, ',', false, $indent + 2, $conditionsMode, $extrapolateParameters);
        }

        if (!empty($this->from)) {
            $from = NodeFactory::toSql($this->from, $platform, $parameters, ' ', false, $indent + 2, $conditionsMode, $extrapolateParameters);
            if ($from) {
                $sql .= "\nFROM ".$from;
            }
        }

        if (!empty($this->where)) {
            $where = NodeFactory::toSql($this->where, $platform, $parameters, ' ', false, $indent + 2, $conditionsMode, $extrapolateParameters);
            if ($where) {
                $sql .= "\nWHERE ".$where;
            }
        }

        if (!empty($this->group)) {
            $groupBy = NodeFactory::toSql($this->group, $platform, $parameters, ',', false, $indent + 2, $conditionsMode, $extrapolateParameters);
            if ($groupBy) {
                $sql .= "\nGROUP BY ".$groupBy;
            }
        }

        if (!empty($this->having)) {
            $having = NodeFactory::toSql($this->having, $platform, $parameters, ' ', false, $indent + 2, $conditionsMode, $extrapolateParameters);
            if ($having) {
                $sql .= "\nHAVING ".$having;
            }
        }

        if (!empty($this->order)) {
            $order = NodeFactory::toSql($this->order, $platform, $parameters, ',', false, $indent + 2, $conditionsMode, $extrapolateParameters);
            if ($order) {
                $sql .= "\nORDER BY ".$order;
            }
        }

        if (!empty($this->offset) && empty($this->limit)) {
            throw new \Exception('There is no offset if no limit is provided. An error may have occurred during SQLParsing.');
        } elseif (!empty($this->limit)) {
            $limit = NodeFactory::toSql($this->limit, $platform, $parameters, ',', false, $indent + 2, $conditionsMode, $extrapolateParameters);
            if ($limit === '' || ($extrapolateParameters && substr(trim($limit), 0, 1) == ':')) {
                $limit = null;
            }
            if (!$extrapolateParameters && $this->limit instanceof UnquotedParameter && !isset($parameters[$this->limit->getName()])) {
                $limit = null;
            }
            if (!empty($this->offset)) {
                $offset = NodeFactory::toSql($this->offset, $platform, $parameters, ',', false, $indent + 2, $conditionsMode, $extrapolateParameters);
                if ($offset === '' || ($extrapolateParameters && substr(trim($offset), 0, 1) == ':')) {
                    $offset = null;
                }
                if (!$extrapolateParameters && $this->offset instanceof UnquotedParameter && !isset($parameters[$this->offset->getName()])) {
                    $offset = null;
                }
            } else {
                $offset = null;
            }

            if ($limit === null && $offset !== null) {
                throw new \Exception('There is no offset if no limit is provided. An error may have occurred during SQLParsing.');
            }

            if ($limit !== null) {
                $sql .= "\nLIMIT ";
                if ($offset !== null) {
                    $sql .= $offset.', ';
                }
                $sql .= $limit;
            }
        }

        return $sql;
    }

    /**
     * Walks the tree of nodes, calling the visitor passed in parameter.
     *
     * @param VisitorInterface $visitor
     */
    public function walk(VisitorInterface $visitor)
    {
        $node = $this;
        $result = $visitor->enterNode($node);
        if ($result instanceof NodeInterface) {
            $node = $result;
        }
        if ($result !== NodeTraverser::DONT_TRAVERSE_CHILDREN) {
            $this->walkChildren($this->columns, $visitor);
            $this->walkChildren($this->from, $visitor);
            $this->walkChildren($this->where, $visitor);
            $this->walkChildren($this->group, $visitor);
            $this->walkChildren($this->having, $visitor);
            $this->walkChildren($this->order, $visitor);
        }

        return $visitor->leaveNode($node);
    }

    private function walkChildren(&$children, VisitorInterface $visitor)
    {
        if ($children) {
            if (is_array($children)) {
                foreach ($children as $key => $operand) {
                    if ($operand) {
                        $result2 = $operand->walk($visitor);
                        if ($result2 === NodeTraverser::REMOVE_NODE) {
                            unset($children[$key]);
                        } elseif ($result2 instanceof NodeInterface) {
                            $children[$key] = $result2;
                        }
                    }
                }
            } else {
                $result2 = $children->walk($visitor);
                if ($result2 === NodeTraverser::REMOVE_NODE) {
                    $children = null;
                } elseif ($result2 instanceof NodeInterface) {
                    $children = $result2;
                }
            }
        }
    }
}
