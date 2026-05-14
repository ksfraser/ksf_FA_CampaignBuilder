# Architecture - ksf_FA_CampaignBuilder

## Document Information
- **Module**: ksf_FA_CampaignBuilder
- **Version**: 1.0.0
- **Date**: 2026-05-11
- **Status**: Implemented
- **Author**: KSFII Development Team

---

## 1. Module Overview

ksf_FA_CampaignBuilder provides FrontAccounting integration for campaignbuilder functionality.

### 1.1 Namespace
`Ksfraser\FA\Campaignbuilder`

### 1.2 FA Module Structure
```
ksf_FA_CampaignBuilder/
├── hooks.php           # Module hooks
├── pages/              # UI pages
├── src/                # Adapters
└── Integration/        # DB adapters
```

---

## 2. Hooks Integration

### 2.1 Module Registration

```php
class hooks_facampaignbuilder extends hooks {
    var $module_name = 'fa_campaignbuilder';
    
    function install_options($app) {
        // Menu items
    }
    
    function install_access() {
        // Security areas
    }
}
```

### 2.2 Security Areas

| Constant | Description |
|----------|-------------|
| SA_CAMPAIGNBUILDER_VIEW | View access |
| SA_CAMPAIGNBUILDER_EDIT | Edit access |

---

## 3. Database Adapters

| Adapter | Description |
|---------|-------------|
| DebtorAdapter | FA debtor integration |
| EmployeeAdapter | HRM employee link |
| GLAdapter | GL code mapping |

---

## 4. Page Templates

| Page | Description |
|------|-------------|
| campaignbuilder-list.php | List view |
| campaignbuilder-edit.php | Edit form |
| campaignbuilder-view.php | Detail view |

---

*Document Version: 1.0.0*
*Last Updated: 2026-05-11*
