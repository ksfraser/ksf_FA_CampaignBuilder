<?php
/**
 * CampaignNode Entity
 *
 * Represents a node in the campaign workflow (trigger, action, condition, delay)
 *
 * @package Ksfraser\CampaignBuilder\Entity
 * @author KSFII
 * @license MIT
 */

declare(strict_types=1);

namespace Ksfraser\CampaignBuilder\Entity;

use DateTime;
use DateTimeInterface;
use JsonSerializable;

/**
 * CampaignNode - Workflow node (trigger/action/condition/delay)
 */
class CampaignNode implements JsonSerializable
{
    private string $id;
    private string $name;
    private string $type;
    private string $subType;
    private array $config;
    private array $outcomes;
    private int $positionX;
    private int $positionY;
    private ?DateTime $delay;
    private bool $isEnabled;

    public const TYPE_TRIGGER = 'trigger';
    public const TYPE_ACTION = 'action';
    public const TYPE_CONDITION = 'condition';
    public const TYPE_DELAY = 'delay';
    public const TYPE_NOTIFICATION = 'notification';

    public const SUBTYPE_EMAIL = 'email';
    public const SUBTYPE_SCORE = 'score';
    public const SUBTYPE_SEGMENT = 'segment';
    public const SUBTYPE_WEBHOOK = 'webhook';
    public const SUBTYPE_FORM_SUBMIT = 'form_submit';
    public const SUBTYPE_PAGE_VIEW = 'page_view';
    public const SUBTYPE_LINK_CLICK = 'link_click';
    public const SUBTYPE_MANUAL = 'manual';

    public function __construct(
        string $id,
        string $name,
        string $type,
        string $subType = ''
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->type = $type;
        $this->subType = $subType;
        $this->config = [];
        $this->outcomes = [];
        $this->positionX = 0;
        $this->positionY = 0;
        $this->isEnabled = true;
    }

    /**
     * Get node ID
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Get node name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set node name
     */
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Get node type
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Get node sub-type
     */
    public function getSubType(): string
    {
        return $this->subType;
    }

    /**
     * Get configuration
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Set configuration value
     */
    public function setConfig(string $key, $value): self
    {
        $this->config[$key] = $value;
        return $this;
    }

    /**
     * Get config value
     */
    public function getConfigValue(string $key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }

    /**
     * Set all config
     */
    public function setAllConfig(array $config): self
    {
        $this->config = $config;
        return $this;
    }

    /**
     * Add outcome connection
     */
    public function addOutcome(CampaignOutcome $outcome): self
    {
        $this->outcomes[$outcome->getId()] = $outcome;
        return $this;
    }

    /**
     * Remove outcome
     */
    public function removeOutcome(string $outcomeId): self
    {
        unset($this->outcomes[$outcomeId]);
        return $this;
    }

    /**
     * Get all outcomes
     */
    public function getOutcomes(): array
    {
        return $this->outcomes;
    }

    /**
     * Get outcome targets
     */
    public function getTargetNodeIds(): array
    {
        return array_map(fn($o) => $o->getTargetNodeId(), $this->outcomes);
    }

    /**
     * Check if has specific target
     */
    public function hasTarget(string $nodeId): bool
    {
        return in_array($nodeId, $this->getTargetNodeIds());
    }

    /**
     * Get position X
     */
    public function getPositionX(): int
    {
        return $this->positionX;
    }

    /**
     * Set position X
     */
    public function setPositionX(int $x): self
    {
        $this->positionX = $x;
        return $this;
    }

    /**
     * Get position Y
     */
    public function getPositionY(): int
    {
        return $this->positionY;
    }

    /**
     * Set position Y
     */
    public function setPositionY(int $y): self
    {
        $this->positionY = $y;
        return $this;
    }

    /**
     * Set position
     */
    public function setPosition(int $x, int $y): self
    {
        $this->positionX = $x;
        $this->positionY = $y;
        return $this;
    }

    /**
     * Get delay
     */
    public function getDelay(): ?DateTime
    {
        return $this->delay;
    }

    /**
     * Set delay (interval from now)
     */
    public function setDelay(?DateTime $delay): self
    {
        $this->delay = $delay;
        return $this;
    }

    /**
     * Enabled status
     */
    public function isEnabled(): bool
    {
        return $this->isEnabled;
    }

    /**
     * Enable/disable
     */
    public function setEnabled(bool $enabled): self
    {
        $this->isEnabled = $enabled;
        return $this;
    }

    /**
     * Check if is trigger type
     */
    public function isTrigger(): bool
    {
        return $this->type === self::TYPE_TRIGGER;
    }

    /**
     * Check if is action type
     */
    public function isAction(): bool
    {
        return $this->type === self::TYPE_ACTION;
    }

    /**
     * Check if is condition type
     */
    public function isCondition(): bool
    {
        return $this->type === self::TYPE_CONDITION;
    }

    /**
     * Check if is delay type
     */
    public function isDelay(): bool
    {
        return $this->type === self::TYPE_DELAY;
    }

    /**
     * Validate node
     */
    public function validate(): array
    {
        $errors = [];

        if (empty($this->name)) {
            $errors[] = "Node {$this->id}: name is required";
        }

        if (!in_array($this->type, [self::TYPE_TRIGGER, self::TYPE_ACTION, self::TYPE_CONDITION, self::TYPE_DELAY, self::TYPE_NOTIFICATION])) {
            $errors[] = "Node {$this->id}: invalid type";
        }

        if ($this->type === self::TYPE_ACTION && empty($this->subType)) {
            $errors[] = "Node {$this->id}: action requires sub-type";
        }

        if ($this->type === self::TYPE_DELAY && !$this->delay) {
            $errors[] = "Node {$this->id}: delay requires delay value";
        }

        $hasOutcomes = !empty($this->outcomes);
        if (!$this->isTrigger() && !$hasOutcomes && $this->isEnabled) {
            $errors[] = "Node {$this->id}: connected nodes required";
        }

        return $errors;
    }

    /**
     * Serialize to JSON
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'sub_type' => $this->subType,
            'config' => $this->config,
            'outcomes' => array_map(fn($o) => $o->jsonSerialize(), $this->outcomes),
            'position' => ['x' => $this->positionX, 'y' => $this->positionY],
            'delay' => $this->delay?->format(DateTimeInterface::ATOM),
            'is_enabled' => $this->isEnabled,
        ];
    }

    /**
     * Create from array
     */
    public static function fromArray(array $data): self
    {
        $node = new self(
            $data['id'],
            $data['name'],
            $data['type'],
            $data['sub_type'] ?? ''
        );

        if (isset($data['config'])) {
            $node->setAllConfig($data['config']);
        }

        if (isset($data['position'])) {
            $node->setPosition($data['position']['x'], $data['position']['y']);
        }

        if (isset($data['delay'])) {
            $node->setDelay(new DateTime($data['delay']));
        }

        if (isset($data['is_enabled'])) {
            $node->setEnabled($data['is_enabled']);
        }

        return $node;
    }
}