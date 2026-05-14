# AGENTS.md - ksf_FA_CampaignBuilder#

## Architecture Overview#

**FA Module** for Marketing Campaign Management - create, track, and analyze campaigns with CRM integration.

### Core Principles#
- **SOLID**, **DRY**, **TDD**, **DI**, **SRP**#

## Repository Structure#

```
ksf_FA_CampaignBuilder/
├── sql/#
│   ├── fa_campaigns.sql#
│   ├── fa_campaign_leads.sql#
│   └── fa_campaign_metrics.sql#
├── includes/#
│   ├── campaigns_db.inc#
│   ├── leads_db.inc#
│   └── metrics_db.inc#
├── pages/#
├── hooks.php#
├── composer.json#
└── ProjectDocs/#
```

## Dependencies#

- **ksf_FA_CampaignBuilder_Core** (business logic)#
- **ksf_FA_CRM** (link campaigns to leads)#
- **FrontAccounting 2.4+**#
