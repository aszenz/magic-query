<?php

namespace SQLParser\Node;
use Doctrine\DBAL\Connection;
use Mouf\Database\MagicQueryException;

/**
 * This class represents an In operation in an SQL expression.
 *
 * @author David Négrier <d.negrier@thecodingmachine.com>
 */
abstract class AbstractInListOperator extends AbstractTwoOperandsOperator
{
    protected function getSql(array $parameters = array(), Connection $dbConnection = null, $indent = 0, $conditionsMode = self::CONDITION_APPLY, bool $extrapolateParameters = true)
    {
        $rightOperand = $this->getRightOperand();

        $rightOperand = $this->refactorParameterToExpression($rightOperand);

        $this->setRightOperand($rightOperand);

        $parameterNode = $this->getParameter($rightOperand);

        if ($parameterNode !== null) {
            if (!isset($parameters[$parameterNode->getName()])) {
                throw new MagicQueryException("Missing parameter '" . $parameterNode->getName() . "' for 'IN' operand.");
            }
            if ($parameters[$parameterNode->getName()] === []) {
                return "FALSE";
            }
        }

        return parent::getSql($parameters, $dbConnection, $indent, $conditionsMode, $extrapolateParameters);
    }

    protected function refactorParameterToExpression(NodeInterface $rightOperand): NodeInterface
    {
        if ($rightOperand instanceof Parameter) {
            $expression = new Expression();
            $expression->setSubTree([$rightOperand]);
            $expression->setBrackets(true);
            return $expression;
        }
        return $rightOperand;
    }

    protected function getParameter(NodeInterface $operand): ?Parameter
    {
        if (!$operand instanceof Expression) {
            return null;
        }
        $subtree = $operand->getSubTree();
        if (!isset($subtree[0])) {
            return null;
        }
        if ($subtree[0] instanceof Parameter) {
            return $subtree[0];
        }
        return null;
    }
}
