<?php
/**
 * CampaignOutcome Entity
 *
 * Connection between workflow nodes (edges)
 *
 * @package Ksfraser\CampaignBuilder\Entity
 * @author KSFII
 * @license MIT
 */

declare(strict_types=1);

namespace Ksfraser\CampaignBuilder\Entity;

use JsonSerializable;

/**
 * CampaignOutcome - Connection to next node
 */
class CampaignOutcome implements JsonSerializable
{
    private string $id;
    private string $label;
    private string $targetNodeId;
    private ?string $condition;
    private bool $isDefault;

    public function __construct(
        string $id,
        string $targetNodeId,
        string $label = ''
    ) {
        $this->id = $id;
        $this->targetNodeId = $targetNodeId;
        $this->label = $label ?: 'Next';
        $this->condition = null;
        $this->isDefault = false;
    }

    /**
     * Get outcome ID
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Get label
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * Set label
     */
    public function setLabel(string $label): self
    {
        $this->label = $label;
        return $this;
    }

    /**
     * Get target node ID
     */
    public function getTargetNodeId(): string
    {
        return $this->targetNodeId;
    }

    /**
     * Set target node
     */
    public function setTargetNodeId(string $nodeId): self
    {
        $this->targetNodeId = $nodeId;
        return $this;
    }

    /**
     * Get condition (for conditional branches)
     */
    public function getCondition(): ?string
    {
        return $this->condition;
    }

    /**
     * Set condition
     */
    public function setCondition(?string $condition): self
    {
        $this->condition = $condition;
        return $this;
    }

    /**
     * Check if default outcome
     */
    public function isDefault(): bool
    {
        return $this->isDefault;
    }

    /**
     * Set as default
     */
    public function setAsDefault(bool $default = true): self
    {
        $this->isDefault = $default;
        return $this;
    }

    /**
     * Check if should follow this path
     */
    public function shouldFollow(array $contactData): bool
    {
        if ($this->isDefault || !$this->condition) {
            return true;
        }

        $condition = $this->condition;
        $data = $contactData;

        $result = eval('return ' . $condition . ';');
        return $result;
    }

    /**
     * Serialize
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'target_node_id' => $this->targetNodeId,
            'condition' => $this->condition,
            'is_default' => $this->isDefault,
        ];
    }

    /**
     * Create from array
     */
    public static function fromArray(array $data): self
    {
        $outcome = new self(
            $data['id'],
            $data['target_node_id'],
            $data['label'] ?? ''
        );

        if (isset($data['condition'])) {
            $outcome->setCondition($data['condition']);
        }

        if (isset($data['is_default'])) {
            $outcome->setAsDefault($data['is_default']);
        }

        return $outcome;
    }
}