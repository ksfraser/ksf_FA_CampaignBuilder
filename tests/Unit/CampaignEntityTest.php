<?php
/**
 * CampaignEntityTest
 *
 * @package Ksfraser\CampaignBuilder\Tests
 * @author KSFII
 * @license MIT
 */

declare(strict_types=1);

namespace Ksfraser\CampaignBuilder\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Ksfraser\CampaignBuilder\Entity\Campaign;
use Ksfraser\CampaignBuilder\Entity\CampaignNode;
use Ksfraser\CampaignBuilder\Entity\CampaignTrigger;
use Ksfraser\CampaignBuilder\Entity\CampaignOutcome;
use DateTime;

final class CampaignEntityTest extends TestCase
{
    private Campaign $campaign;

    protected function setUp(): void
    {
        $this->campaign = new Campaign('camp_001', 'Test Campaign', 'Test campaign description');
    }

    public function testCampaignCreation(): void
    {
        $this->assertEquals('camp_001', $this->campaign->getId());
        $this->assertEquals('Test Campaign', $this->campaign->getName());
        $this->assertEquals('Test campaign description', $this->campaign->getDescription());
        $this->assertEquals('draft', $this->campaign->getStatus());
        $this->assertFalse($this->campaign->isActive());
    }

    public function testCampaignActivate(): void
    {
        $this->campaign->activate();
        
        $this->assertTrue($this->campaign->isActive());
        $this->assertEquals('active', $this->campaign->getStatus());
    }

    public function testCampaignDeactivate(): void
    {
        $this->campaign->activate();
        $this->campaign->deactivate();
        
        $this->assertFalse($this->campaign->isActive());
        $this->assertEquals('paused', $this->campaign->getStatus());
    }

    public function testAddNode(): void
    {
        $node = new CampaignNode('node_1', 'Send Email', CampaignNode::TYPE_ACTION, 'email');
        
        $this->campaign->addNode($node);
        
        $this->assertTrue($this->campaign->hasNode('node_1'));
        $this->assertEquals(1, $this->campaign->getNodeCount());
    }

    public function testRemoveNode(): void
    {
        $node = new CampaignNode('node_1', 'Send Email', CampaignNode::TYPE_ACTION, 'email');
        $this->campaign->addNode($node);
        
        $this->campaign->removeNode('node_1');
        
        $this->assertFalse($this->campaign->hasNode('node_1'));
        $this->assertEquals(0, $this->campaign->getNodeCount());
    }

    public function testAddTrigger(): void
    {
        $trigger = new CampaignTrigger('trig_1', 'Form Submit', CampaignTrigger::EVENT_FORM_SUBMIT);
        
        $this->campaign->addTrigger($trigger);
        
        $this->assertTrue($this->campaign->hasTriggers());
    }

    public function testNodeValidation(): void
    {
        $this->campaign->addTrigger(new CampaignTrigger('trig_1', 'Form Submit', CampaignTrigger::EVENT_FORM_SUBMIT));
        
        $nodeNoOutcomes = new CampaignNode('node_1', 'Test', CampaignNode::TYPE_ACTION, 'email');
        $this->campaign->addNode($nodeNoOutcomes);
        
        $errors = $this->campaign->validate();
        
        $this->assertNotEmpty(array_filter($errors, fn($e) => strpos($e, 'connected nodes required') !== false));
    }

    public function testCampaignValidationFailure(): void
    {
        $emptyNameCampaign = new Campaign('', '');
        
        $errors = $emptyNameCampaign->validate();
        
        $this->assertNotEmpty(array_filter($errors, fn($e) => strpos($e, 'name is required') !== false));
    }

    public function testCampaignValidationSuccess(): void
    {
        $node = new CampaignNode('node_1', 'Trigger', CampaignNode::TYPE_TRIGGER, 'form_submit');
        $outcome = new CampaignOutcome('out_1', 'node_2');
        $node->addOutcome($outcome);
        
        $actionNode = new CampaignNode('node_2', 'Send Email', CampaignNode::TYPE_ACTION, 'email');
        
        $this->campaign->addTrigger(new CampaignTrigger('trig_1', 'Form Submit', CampaignTrigger::EVENT_FORM_SUBMIT));
        $this->campaign->addNode($node);
        $this->campaign->addNode($actionNode);
        
        $errors = $this->campaign->validate();
        
        $this->assertEmpty($errors);
    }

    public function testScheduleCampaign(): void
    {
        $startDate = new DateTime('2025-06-01');
        $endDate = new DateTime('2025-12-31');
        
        $this->campaign->setStartsAt($startDate);
        $this->campaign->setEndsAt($endDate);
        
        $this->assertTrue($this->campaign->isScheduled());
        $this->assertFalse($this->campaign->isExpired());
    }

    public function testJsonSerialization(): void
    {
        $node = new CampaignNode('node_1', 'Test', CampaignNode::TYPE_ACTION, 'email');
        $this->campaign->addNode($node);
        
        $json = json_encode($this->campaign);
        $decoded = json_decode($json, true);
        
        $this->assertEquals('camp_001', $decoded['id']);
        $this->assertEquals('Test Campaign', $decoded['name']);
        $this->assertCount(1, $decoded['nodes']);
    }

    public function testNodePosition(): void
    {
        $node = new CampaignNode('node_1', 'Test', CampaignNode::TYPE_ACTION, 'email');
        $node->setPosition(100, 200);
        
        $this->assertEquals(100, $node->getPositionX());
        $this->assertEquals(200, $node->getPositionY());
    }

    public function testOutcomeConnection(): void
    {
        $node1 = new CampaignNode('node_1', 'Trigger', CampaignNode::TYPE_TRIGGER, 'form_submit');
        $node2 = new CampaignNode('node_2', 'Action', CampaignNode::TYPE_ACTION, 'email');
        
        $outcome = new CampaignOutcome('out_1', 'node_2', 'Yes');
        $node1->addOutcome($outcome);
        
        $this->assertTrue($node1->hasTarget('node_2'));
        $this->assertEquals(['node_2'], $node1->getTargetNodeIds());
    }

    public function testConditionalOutcome(): void
    {
        $node1 = new CampaignNode('node_1', 'Condition', CampaignNode::TYPE_CONDITION, 'score');
        $outcome = new CampaignOutcome('out_1', 'node_2', 'High Score');
        $outcome->setCondition('score > 50');
        $outcome->setAsDefault(false);
        
        $node1->addOutcome($outcome);
        
        $this->assertTrue($outcome->shouldFollow(['score' => 75]));
        $this->assertFalse($outcome->shouldFollow(['score' => 25]));
    }

    public function testTriggerFilters(): void
    {
        $trigger = new CampaignTrigger('trig_1', 'Form Submit', CampaignTrigger::EVENT_FORM_SUBMIT);
        $trigger->addFilter('source', 'equals', 'landing_page');
        
        $this->assertTrue($trigger->matchesFilters(['source' => 'landing_page']));
        $this->assertFalse($trigger->matchesFilters(['source' => 'other']));
    }

    public function testNodeDelay(): void
    {
        $node = new CampaignNode('node_1', 'Delay', CampaignNode::TYPE_DELAY);
        $node->setDelay(new DateTime('+1 day'));
        
        $this->assertNotNull($node->getDelay());
    }

    public function testEntryPoints(): void
    {
        $triggerNode = new CampaignNode('node_1', 'Trigger', CampaignNode::TYPE_TRIGGER, 'form_submit');
        $actionNode = new CampaignNode('node_2', 'Action', CampaignNode::TYPE_ACTION, 'email');
        
        $this->campaign->addNode($triggerNode);
        $this->campaign->addNode($actionNode);
        
        $entryPoints = $this->campaign->getEntryPoints();
        
        $this->assertCount(1, $entryPoints);
        $this->assertEquals('node_1', array_values($entryPoints)[0]->getId());
    }
}