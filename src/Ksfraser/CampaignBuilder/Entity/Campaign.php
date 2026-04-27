<?php
/**
 * Campaign Entity
 *
 * Represents a marketing campaign with workflow nodes
 *
 * @package Ksfraser\CampaignBuilder\Entity
 * @author KSFII
 * @copyright Copyright (c) 2025
 * @license MIT
 */

declare(strict_types=1);

namespace Ksfraser\CampaignBuilder\Entity;

use DateTime;
use DateTimeInterface;
use JsonSerializable;

/**
 * Campaign - Marketing automation campaign with workflow
 */
class Campaign implements JsonSerializable
{
    private string $id;
    private string $name;
    private string $description;
    private string $status;
    private DateTime $createdAt;
    private ?DateTime $updatedAt;
    private ?DateTime $startsAt;
    private ?DateTime $endsAt;
    private array $nodes;
    private array $triggers;
    private bool $isActive;

    public function __construct(
        string $id,
        string $name,
        string $description = ''
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->description = $description;
        $this->status = 'draft';
        $this->createdAt = new DateTime();
        $this->updatedAt = new DateTime();
        $this->nodes = [];
        $this->triggers = [];
        $this->isActive = false;
    }

    /**
     * Get unique campaign ID
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Get campaign name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set campaign name
     */
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Get description
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Set description
     */
    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Get status
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * Set status
     */
    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    /**
     * Check if campaign is active
     */
    public function isActive(): bool
    {
        return $this->isActive && $this->status === 'active';
    }

    /**
     * Activate campaign
     */
    public function activate(): self
    {
        $this->isActive = true;
        $this->status = 'active';
        return $this;
    }

    /**
     * Deactivate campaign
     */
    public function deactivate(): self
    {
        $this->isActive = false;
        $this->status = 'paused';
        return $this;
    }

    /**
     * Get creation timestamp
     */
    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    /**
     * Get last update timestamp
     */
    public function getUpdatedAt(): ?DateTime
    {
        return $this->updatedAt;
    }

    /**
     * Get scheduled start
     */
    public function getStartsAt(): ?DateTime
    {
        return $this->startsAt;
    }

    /**
     * Set scheduled start
     */
    public function setStartsAt(?DateTime $startsAt): self
    {
        $this->startsAt = $startsAt;
        return $this;
    }

    /**
     * Get scheduled end
     */
    public function getEndsAt(): ?DateTime
    {
        return $this->endsAt;
    }

    /**
     * Set scheduled end
     */
    public function setEndsAt(?DateTime $endsAt): self
    {
        $this->endsAt = $endsAt;
        return $this;
    }

    /**
     * Add workflow node
     */
    public function addNode(CampaignNode $node): self
    {
        $this->nodes[$node->getId()] = $node;
        $this->updatedAt = new DateTime();
        return $this;
    }

    /**
     * Remove node
     */
    public function removeNode(string $nodeId): self
    {
        unset($this->nodes[$nodeId]);
        $this->updatedAt = new DateTime();
        return $this;
    }

    /**
     * Get all nodes
     */
    public function getNodes(): array
    {
        return $this->nodes;
    }

    /**
     * Get node by ID
     */
    public function getNode(string $nodeId): ?CampaignNode
    {
        return $this->nodes[$nodeId] ?? null;
    }

    /**
     * Check if node exists
     */
    public function hasNode(string $nodeId): bool
    {
        return isset($this->nodes[$nodeId]);
    }

    /**
     * Get node count
     */
    public function getNodeCount(): int
    {
        return count($this->nodes);
    }

    /**
     * Add trigger
     */
    public function addTrigger(CampaignTrigger $trigger): self
    {
        $this->triggers[$trigger->getId()] = $trigger;
        return $this;
    }

    /**
     * Get triggers
     */
    public function getTriggers(): array
    {
        return $this->triggers;
    }

    /**
     * Check if campaign has valid triggers
     */
    public function hasTriggers(): bool
    {
        return !empty($this->triggers);
    }

    /**
     * Check if campaign is scheduled
     */
    public function isScheduled(): bool
    {
        return $this->startsAt !== null && $this->startsAt > new DateTime();
    }

    /**
     * Check if campaign is expired
     */
    public function isExpired(): bool
    {
        return $this->endsAt !== null && $this->endsAt < new DateTime();
    }

    /**
     * Get entry points (nodes with no incoming connections)
     */
    public function getEntryPoints(): array
    {
        $entryIds = [];
        foreach ($this->nodes as $node) {
            if ($node->getType() === 'trigger') {
                $entryIds[] = $node->getId();
            }
        }
        return array_filter($this->nodes, fn($n) => in_array($n->getId(), $entryIds));
    }

    /**
     * Validate campaign for execution
     */
    public function validate(): array
    {
        $errors = [];

        if (empty($this->name)) {
            $errors[] = 'Campaign name is required';
        }

        if (empty($this->nodes)) {
            $errors[] = 'At least one workflow node is required';
        }

        if (!$this->hasTriggers()) {
            $errors[] = 'At least one trigger is required';
        }

        foreach ($this->nodes as $node) {
            $nodeErrors = $node->validate();
            $errors = array_merge($errors, $nodeErrors);
        }

        if ($this->hasCycle()) {
            $errors[] = 'Campaign contains an infinite loop';
        }

        return $errors;
    }

    /**
     * Check for infinite loops
     */
    public function hasCycle(): bool
    {
        $visited = [];
        $recursionStack = [];

        $hasCycle = function(string $nodeId) use (&$visited, &$recursionStack, &$hasCycle): bool {
            if (isset($recursionStack[$nodeId])) {
                return true;
            }

            if (isset($visited[$nodeId])) {
                return false;
            }

            $visited[$nodeId] = true;
            $recursionStack[$nodeId] = true;

            $node = $this->getNode($nodeId);
            if ($node) {
                foreach ($node->getOutcomes() as $outcome) {
                    if ($hasCycle($outcome->getTargetNodeId())) {
                        return true;
                    }
                }
            }

            unset($recursionStack[$nodeId]);
            return false;
        };

        foreach (array_keys($this->nodes) as $nodeId) {
            if ($hasCycle($nodeId)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Serialize to JSON
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status,
            'is_active' => $this->isActive,
            'created_at' => $this->createdAt->format(DateTimeInterface::ATOM),
            'updated_at' => $this->updatedAt?->format(DateTimeInterface::ATOM),
            'starts_at' => $this->startsAt?->format(DateTimeInterface::ATOM),
            'ends_at' => $this->endsAt?->format(DateTimeInterface::ATOM),
            'nodes' => array_map(fn($n) => $n->jsonSerialize(), $this->nodes),
            'triggers' => array_map(fn($t) => $t->jsonSerialize(), $this->triggers),
        ];
    }

    /**
     * Create from array data
     */
    public static function fromArray(array $data): self
    {
        $campaign = new self(
            $data['id'],
            $data['name'],
            $data['description'] ?? ''
        );

        if (isset($data['status'])) {
            $campaign->setStatus($data['status']);
        }

        if (isset($data['starts_at'])) {
            $campaign->setStartsAt(new DateTime($data['starts_at']));
        }

        if (isset($data['ends_at'])) {
            $campaign->setEndsAt(new DateTime($data['ends_at']));
        }

        if (isset($data['is_active']) && $data['is_active']) {
            $campaign->activate();
        }

        return $campaign;
    }
}