<?php

namespace SQLParser\Node;

use Doctrine\DBAL\Platforms\AbstractPlatform;

/**
 * This class represents an = operation in an SQL expression.
 *
 * @author David Négrier <d.negrier@thecodingmachine.com>
 */
class Equal extends AbstractTwoOperandsOperator
{
    /**
     * Returns the symbol for this operator.
     */
    protected function getOperatorSymbol()
    {
        return '=';
    }

    protected function getSql(array $parameters, AbstractPlatform $platform, $indent = 0, $conditionsMode = self::CONDITION_APPLY, bool $extrapolateParameters = true)
    {
        $rightOperand = $this->getRightOperand();
        if ($rightOperand instanceof Parameter && !isset($parameters[$rightOperand->getName()])) {
            $isNull = true;
        } else {
            $isNull = false;
        }

        $sql = NodeFactory::toSql($this->getLeftOperand(), $platform, $parameters, ' ', false, $indent, $conditionsMode, $extrapolateParameters);
        if ($isNull) {
            $sql .= ' IS null';
        } else {
            $sql .= ' '.$this->getOperatorSymbol().' ';
            $sql .= NodeFactory::toSql($rightOperand, $platform, $parameters, ' ', false, $indent, $conditionsMode, $extrapolateParameters);
        }

        return $sql;
    }
}
