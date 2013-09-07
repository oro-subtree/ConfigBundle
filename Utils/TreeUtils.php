<?php

namespace Oro\Bundle\ConfigBundle\Utils;

use Oro\Bundle\ConfigBundle\Config\Tree\GroupNodeDefinition;

class TreeUtils
{
    /**
     * Finds node by name in tree
     * called recursively
     *
     * @param        array  GroupNodeDefinition $node
     * @param string $nodeName
     *
     * @return null|GroupNodeDefinition
     */
    public static function findNodeByName(GroupNodeDefinition $node, $nodeName)
    {
        $resultNode = null;
        /** @var $childNode GroupNodeDefinition */
        foreach ($node as $childNode) {
            if ($childNode->getName() === $nodeName) {
                return $childNode;
            } elseif (!$childNode->isEmpty()) {
                $resultNode = static::findNodeByName($childNode, $nodeName);
            }
        }

        return $resultNode;
    }

    /**
     * Pick nodes for needed level
     * called recursively
     *
     * @param GroupNodeDefinition $node
     * @param int                 $neededLevel
     *
     * @return null|GroupNodeDefinition
     */
    public static function getByNestingLevel(GroupNodeDefinition $node, $neededLevel)
    {
        /** @var $childNode GroupNodeDefinition */
        foreach ($node as $childNode) {
            if ($neededLevel === $childNode->getLevel()) {
                return $childNode;
            } else {
                $node = static::getByNestingLevel($childNode, $neededLevel);
                if ($node !== null) {
                    return $node;
                }
            }
            echo $childNode->getName();
        }

        return null;
    }

    /**
     * Returns first node name if nodes is not empty
     *
     * @param GroupNodeDefinition $node
     *
     * @return null|string
     */
    public static function getFirstNodeName(GroupNodeDefinition $node)
    {
        if (!$node->isEmpty()) {
            $firstNode = $node->first();

            return $firstNode->getName();
        }

        return null;
    }
}
