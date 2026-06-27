# SaaS Data and Privacy Policy

**Last updated:** [Date]

This SaaS Data and Privacy Policy explains how [Company Name] handles data within the [Platform Name] multi-tenant platform. It supplements the general Privacy Policy with SaaS-specific data practices.

## 1. Multi-Tenant Data Isolation

### Tenant Separation
- Each subscriber operates within an **isolated tenant** — a logically separated workspace
- Data belonging to one tenant is **never accessible** to another tenant
- Tenant isolation is enforced at the application and database level
- All queries are scoped to the authenticated tenant context

### Access Controls
- The subscriber (owner) controls who has access to their tenant
- Role-based access control (RBAC) determines what each user can see and do
- User sessions are authenticated and scoped to a single tenant
- End users (customer portal) have read-only access limited to their own records

## 2. Data Collection by Role

| Data Category | Subscriber (Owner) | Team Users | End Users |
|---|---|---|---|
| Name and email | Yes | Yes | Optional |
| Phone number | Optional | Optional | Optional |
| Billing information | Yes | No | No |
| Business data | Full access | Role-based | Own records only |
| Usage analytics | Aggregated | Aggregated | None |

## 3. Data Storage and Security

- **Hosting:** Enterprise-grade cloud infrastructure with SOC 2-compliant providers
- **Encryption in transit:** TLS 1.2 or higher for all connections
- **Encryption at rest:** AES-256 encryption for all stored data
- **Backups:** Automated daily backups with point-in-time recovery capability
- **Access controls:** Infrastructure access is limited to authorized personnel with MFA

## 4. Data Retention

| Scenario | Retention Period | Notes |
|---|---|---|
| Active subscription | Duration of subscription | Full access to all data |
| Canceled subscription | 90 days | Read-only; export available |
| Post-retention deletion | Permanent | Irreversible; cannot be recovered |
| Billing and invoices | As required by law | Typically 5-7 years |
| System logs | 12 months | Anonymized where possible |

- Subscribers are notified **30 days** and **7 days** before data deletion
- During the retention period, subscribers can reactivate or export their data

## 5. Your Data Rights

Regardless of your jurisdiction, we support the following rights:

- **Right to Access:** Request a copy of all personal data we hold about you
- **Right to Rectification:** Correct any inaccurate or incomplete personal data
- **Right to Deletion:** Request deletion of your account and all associated data
- **Right to Portability:** Export your data in standard formats (CSV, PDF)
- **Right to Restriction:** Request that we limit processing of your data
- **Right to Object:** Opt out of non-essential data processing or communications

To exercise any right, contact us at privacy@[yourdomain].com or use **Settings > Privacy** in the platform. We will respond within **30 days**.

## 6. Data Processing

### What We Process and Why
- **Service delivery:** Processing your data to provide platform functionality
- **Security:** Monitoring for unauthorized access, fraud, and abuse
- **Improvement:** Using anonymized, aggregated analytics to improve the platform
- **Communication:** Sending transactional emails (billing, alerts, security notices)

### What We Never Do
- Sell your data to third parties
- Use your business data for advertising
- Share your data with other tenants
- Access your tenant data without your explicit consent (except for critical security incidents)

## 7. Sub-processors

We use a limited number of sub-processors to deliver the service:
- **Cloud hosting provider** — infrastructure and storage
- **Payment processor** — subscription billing
- **Email service provider** — transactional emails
- **Error tracking service** — application monitoring

A current list of sub-processors is available upon request. We will notify subscribers at least **15 days** before adding a new sub-processor.

## 8. Breach Notification

In the event of a data breach:
1. We will investigate and contain the incident immediately
2. Affected subscribers will be notified within **72 hours** of discovery
3. The notification will include the nature of the breach, data affected, and remediation steps
4. We will cooperate with relevant authorities as required by law

## 9. Changes to This Policy

We may update this policy from time to time. Significant changes will be communicated via email at least **15 days** before taking effect.

## 10. Contact

For data privacy inquiries, contact us at:
- **Email:** privacy@[yourdomain].com
- **In-app:** Settings > Privacy > Contact DPO
