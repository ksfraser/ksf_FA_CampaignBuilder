<?php
/**
 * CampaignService
 *
 * Executes campaigns, processes triggers and actions
 *
 * @package Ksfraser\CampaignBuilder\Service
 * @author KSFII
 * @license MIT
 */

declare(strict_types=1);

namespace Ksfraser\CampaignBuilder\Service;

use Ksfraser\CampaignBuilder\Entity\Campaign;
use Ksfraser\CampaignBuilder\Entity\CampaignNode;
use Ksfraser\CampaignBuilder\Entity\CampaignTrigger;
use Ksfraser\CampaignBuilder\Entity\CampaignOutcome;
use Ksfraser\CampaignBuilder\Repository\CampaignRepositoryInterface;
use Psr\Log\LoggerInterface;

/**
 * CampaignService - Campaign execution engine
 */
class CampaignService
{
    private CampaignRepositoryInterface $repository;
    private ?LoggerInterface $logger;
    private array $actionHandlers;
    private array $eventListeners;

    public function __construct(
        CampaignRepositoryInterface $repository,
        ?LoggerInterface $logger = null
    ) {
        $this->repository = $repository;
        $this->logger = $logger;
        $this->actionHandlers = [];
        $this->eventListeners = [];
    }

    /**
     * Register action handler
     */
    public function registerActionHandler(string $actionType, callable $handler): self
    {
        $this->actionHandlers[$actionType] = $handler;
        return $this;
    }

    /**
     * Register event listener
     */
    public function on(string $eventType, callable $listener): self
    {
        if (!isset($this->eventListeners[$eventType])) {
            $this->eventListeners[$eventType] = [];
        }
        $this->eventListeners[$eventType][] = $listener;
        return $this;
    }

    /**
     * Process event - check all active campaigns
     */
    public function processEvent(string $eventType, array $eventData): array
    {
        $results = [];
        $campaigns = $this->repository->findActiveCampaigns();

        $this->log("Processing event: {$eventType} for " . count($campaigns) . " campaigns");

        foreach ($campaigns as $campaign) {
            $result = $this->processCampaignEvent($campaign, $eventType, $eventData);
            if ($result) {
                $results[] = $result;
            }
        }

        return $results;
    }

    /**
     * Process single campaign event
     */
    private function processCampaignEvent(Campaign $campaign, string $eventType, array $eventData): ?array
    {
        if (!$campaign->isActive()) {
            return null;
        }

        foreach ($campaign->getTriggers() as $trigger) {
            if (!$trigger->isEnabled()) {
                continue;
            }

            if ($trigger->getEventType() === $eventType && $trigger->matchesFilters($eventData)) {
                $this->log("Campaign {$campaign->getId()} triggered by {$eventType}");

                $this->repository->enrollContact(
                    $campaign->getId(),
                    $eventData['contact_id'] ?? uniqid('c_'),
                    $eventData
                );

                return [
                    'campaign_id' => $campaign->getId(),
                    'contact_id' => $eventData['contact_id'] ?? null,
                    'trigger' => $trigger->getId(),
                ];
            }
        }

        return null;
    }

    /**
     * Execute campaign for enrolled contact
     */
    public function executeCampaign(string $campaignId, string $contactId, array $contactData = []): array
    {
        $campaign = $this->repository->getCampaign($campaignId);
        if (!$campaign) {
            throw new \RuntimeException("Campaign not found: {$campaignId}");
        }

        if (!$campaign->isActive()) {
            throw new \RuntimeException("Campaign is not active: {$campaignId}");
        }

        $this->log("Executing campaign {$campaignId} for contact {$contactId}");

        $executedNodes = [];
        $entryPoints = $campaign->getEntryPoints();

        foreach ($entryPoints as $node) {
            $this->executeNode($campaign, $node, $contactId, $contactData, $executedNodes);
        }

        return $executedNodes;
    }

    /**
     * Execute single node
     */
    private function executeNode(
        Campaign $campaign,
        CampaignNode $node,
        string $contactId,
        array &$contactData,
        array &$executedNodes
    ): void {
        $nodeId = $node->getId();

        if (isset($executedNodes[$nodeId])) {
            return;
        }

        if (!$node->isEnabled()) {
            return;
        }

        $executedNodes[$nodeId] = [
            'node_id' => $nodeId,
            'type' => $node->getType(),
            'executed_at' => date('Y-m-d H:i:s'),
        ];

        $this->log("Executing node: {$nodeId} ({$node->getType()})");

        switch ($node->getType()) {
            case CampaignNode::TYPE_DELAY:
                $delay = $node->getDelay();
                if ($delay) {
                    $scheduledAt = (new DateTime())->add(new \DateInterval('PT' . $delay->format('i') . 'M'));
                    $this->repository->scheduleExecution(
                        $campaign->getId(),
                        $nodeId,
                        $contactId,
                        $scheduledAt
                    );
                    return;
                }
                break;

            case CampaignNode::TYPE_ACTION:
                $this->executeAction($node, $contactId, $contactData);
                break;

            case CampaignNode::TYPE_NOTIFICATION:
                $this->executeNotification($node, $contactId, $contactData);
                break;
        }

        foreach ($node->getOutcomes() as $outcome) {
            $targetNodeId = $outcome->getTargetNodeId();
            $targetNode = $campaign->getNode($targetNodeId);

            if ($targetNode && ($outcome->isDefault() || $outcome->shouldFollow($contactData))) {
                $this->executeNode($campaign, $targetNode, $contactId, $contactData, $executedNodes);
            }
        }
    }

    /**
     * Execute action
     */
    private function executeAction(CampaignNode $node, string $contactId, array &$contactData): void
    {
        $subType = $node->getSubType();
        $config = $node->getConfig();

        $this->log("Executing action: {$subType} for contact {$contactId}");

        if (isset($this->actionHandlers[$subType])) {
            $handler = $this->actionHandlers[$subType];
            $handler($contactId, $config, $contactData);
            return;
        }

        switch ($subType) {
            case 'score':
                $change = $config['change'] ?? 0;
                $this->repository->updateContactScore($contactId, $change);
                break;

            case 'segment':
                $segmentId = $config['segment_id'] ?? '';
                if ($segmentId) {
                    $this->repository->addToSegment($contactId, $segmentId);
                }
                break;

            case 'email':
                $emailId = $config['email_id'] ?? '';
                if ($emailId) {
                    $this->sendEmail($emailId, $contactId, $config);
                }
                break;

            case 'webhook':
                $webhookUrl = $config['url'] ?? '';
                if ($webhookUrl) {
                    $this->callWebhook($webhookUrl, $contactId, $contactData);
                }
                break;

            default:
                $this->log("Unknown action type: {$subType}");
        }
    }

    /**
     * Execute notification
     */
    private function executeNotification(CampaignNode $node, string $contactId, array $contactData): void
    {
        $config = $node->getConfig();

        $this->log("Sending notification for contact {$contactId}");

        if (!empty($this->eventListeners['notification'])) {
            foreach ($this->eventListeners['notification'] as $listener) {
                $listener($contactId, $config, $contactData);
            }
        }
    }

    /**
     * Send email action
     */
    private function sendEmail(string $emailId, string $contactId, array $config): void
    {
        $this->repository->queueEmail($emailId, $contactId);
        $this->log("Queued email {$emailId} for contact {$contactId}");
    }

    /**
     * Call webhook
     */
    private function callWebhook(string $url, string $contactId, array $data): void
    {
        $data['contact_id'] = $contactId;
        $data['campaign_event'] = 'webhook';

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);
        curl_close($ch);

        $this->log("Called webhook: {$url}");
    }

    /**
     * Validate campaign before activation
     */
    public function validateCampaign(Campaign $campaign): array
    {
        return $campaign->validate();
    }

    /**
     * Activate campaign
     */
    public function activateCampaign(string $campaignId): void
    {
        $campaign = $this->repository->getCampaign($campaignId);
        if (!$campaign) {
            throw new \RuntimeException("Campaign not found");
        }

        $errors = $this->validateCampaign($campaign);
        if (!empty($errors)) {
            throw new \RuntimeException("Campaign validation failed: " . implode(', ', $errors));
        }

        $campaign->activate();
        $this->repository->saveCampaign($campaign);

        $this->log("Campaign activated: {$campaignId}");
    }

    /**
     * Pause campaign
     */
    public function pauseCampaign(string $campaignId): void
    {
        $campaign = $this->repository->getCampaign($campaignId);
        if ($campaign) {
            $campaign->deactivate();
            $this->repository->saveCampaign($campaign);
            $this->log("Campaign paused: {$campaignId}");
        }
    }

    /**
     * Internal log
     */
    private function log(string $message): void
    {
        if ($this->logger) {
            $this->logger->info($message);
        }
    }
}