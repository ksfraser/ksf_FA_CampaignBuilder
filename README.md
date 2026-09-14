# ksf_FA_CampaignBuilder - Visual Campaign Builder for FrontAccounting

**ksf_FA_CampaignBuilder** - Visual drag-drop campaign builder for marketing automation.

## Features

- Visual workflow editor with drag-drop
- Triggers: form submit, page view, link click, email open/click, manual, date-based
- Actions: email, score, segment, webhook, notifications
- Conditions: if/else branches with scoring
- Delays: wait for duration or until specific date
- Contact enrollment and tracking
- Campaign analytics

## Module Structure

```
ksf_FA_CampaignBuilder/
├── src/Ksfraser/CampaignBuilder/
│   ├── Entity/
│   │   ├── Campaign.php
│   │   ├── CampaignNode.php
│   │   ├── CampaignTrigger.php
│   │   └── CampaignOutcome.php
│   ├── Service/
│   │   └── CampaignService.php
│   └── Repository/
│       └── CampaignRepositoryInterface.php
├── tests/Unit/
├── includes/
├── pages/
└── sql/
```

## Usage

```php
use Ksfraser\CampaignBuilder\Entity\Campaign;
use Ksfraser\CampaignBuilder\Entity\CampaignNode;
use Ksfraser\CampaignBuilder\Service\CampaignService;

// Create campaign
$campaign = new Campaign('camp_001', 'Welcome Series');

// Add trigger
$trigger = new CampaignTrigger('trig_1', 'Form Submit', 'form_submit');
$trigger->addFilter('form_id', 'equals', 'contact_form');
$campaign->addTrigger($trigger);

// Add workflow nodes
$node1 = new CampaignNode('email_1', 'Send Welcome Email', 'action', 'email');
$node1->setConfig('email_id', 'welcome_email');
$campaign->addNode($node1);

// Execute
$service = new CampaignService($repository);
$service->activateCampaign('camp_001');
$service->processEvent('form_submit', ['form_id' => 'contact_form', 'contact_id' => 'c_123']);
```

## Integration

- **ksf_FA_Forms** - Triggers from form submissions
- **ksf_FA_EmailManager** - Email actions
- **ksf_Marketing** - Lead scoring
- **ksf_FA_Tracking** - Page view/link click triggers

## Requirements

- FrontAccounting 2.4+
- PHP 8.1+

## License

MIT