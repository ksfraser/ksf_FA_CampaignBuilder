<?php
/**
 * CampaignTrigger Entity
 *
 * Defines what starts a campaign (triggers)
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
 * CampaignTrigger - What initiates campaign flow
 */
class CampaignTrigger implements JsonSerializable
{
    private string $id;
    private string $name;
    private string $eventType;
    private array $filters;
    private array $config;
    private bool $isEnabled;

    public const EVENT_FORM_SUBMIT = 'form_submit';
    public const EVENT_PAGE_VIEW = 'page_view';
    public const EVENT_LINK_CLICK = 'link_click';
    public const EVENT_EMAIL_OPEN = 'email_open';
    public const EVENT_EMAIL_CLICK = 'email_click';
    public const EVENT_MANUAL = 'manual';
    public const EVENT_DATE = 'date';
    public const EVENT_SCORE = 'score';
    public const EVENT_SEGMENT = 'segment';

    public function __construct(
        string $id,
        string $name,
        string $eventType
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->eventType = $eventType;
        $this->filters = [];
        $this->config = [];
        $this->isEnabled = true;
    }

    /**
     * Get trigger ID
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Get name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set name
     */
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Get event type
     */
    public function getEventType(): string
    {
        return $this->eventType;
    }

    /**
     * Get filters
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     * Add filter
     */
    public function addFilter(string $field, string $operator, $value): self
    {
        $this->filters[] = [
            'field' => $field,
            'operator' => $operator,
            'value' => $value,
        ];
        return $this;
    }

    /**
     * Check if matches filters
     */
    public function matchesFilters(array $data): bool
    {
        foreach ($this->filters as $filter) {
            $field = $filter['field'];
            $operator = $filter['operator'];
            $expected = $filter['value'];
            $actual = $data[$field] ?? null;

            $matches = match ($operator) {
                'equals' => $actual === $expected,
                'not_equals' => $actual !== $expected,
                'contains' => strpos($actual, $expected) !== false,
                'starts_with' => strpos($actual, $expected) === 0,
                'ends_with' => strrpos($actual, $expected) === strlen($actual) - strlen($expected),
                'greater_than' => $actual > $expected,
                'less_than' => $actual < $expected,
                'is_empty' => empty($actual),
                'is_not_empty' => !empty($actual),
                default => true,
            };

            if (!$matches) {
                return false;
            }
        }
        return true;
    }

    /**
     * Get config
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * Set config value
     */
    public function setConfig(string $key, $value): self
    {
        $this->config[$key] = $value;
        return $this;
    }

    /**
     * Enabled
     */
    public function isEnabled(): bool
    {
        return $this->isEnabled;
    }

    /**
     * Set enabled
     */
    public function setEnabled(bool $enabled): self
    {
        $this->isEnabled = $enabled;
        return $this;
    }

    /**
     * Validate trigger
     */
    public function validate(): array
    {
        $errors = [];

        if (empty($this->name)) {
            $errors[] = 'Trigger name is required';
        }

        if (empty($this->eventType)) {
            $errors[] = 'Event type is required';
        }

        return $errors;
    }

    /**
     * Serialize
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'event_type' => $this->eventType,
            'filters' => $this->filters,
            'config' => $this->config,
            'is_enabled' => $this->isEnabled,
        ];
    }

    /**
     * Create from array
     */
    public static function fromArray(array $data): self
    {
        $trigger = new self(
            $data['id'],
            $data['name'],
            $data['event_type']
        );

        if (!empty($data['filters'])) {
            foreach ($data['filters'] as $f) {
                $trigger->addFilter($f['field'], $f['operator'], $f['value']);
            }
        }

        if (!empty($data['config'])) {
            foreach ($data['config'] as $k => $v) {
                $trigger->setConfig($k, $v);
            }
        }

        if (isset($data['is_enabled'])) {
            $trigger->setEnabled($data['is_enabled']);
        }

        return $trigger;
    }
}