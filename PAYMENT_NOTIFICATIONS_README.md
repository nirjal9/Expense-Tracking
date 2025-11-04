# Payment Notifications System

## Overview

The Payment Notifications System automatically tracks expenses by parsing email and SMS notifications from payment gateways like eSewa, Khalti, and banks. This system eliminates the need for manual expense entry and provides real-time expense tracking.

## Features

### 🚀 **Automatic Expense Tracking**
- **Email Integration**: Parse payment notifications from Gmail
- **SMS Integration**: Process bank and payment gateway SMS notifications
- **Webhook Support**: Real-time processing via webhooks
- **Smart Parsing**: Extract transaction details using advanced regex patterns

### 🧠 **Intelligent Categorization**
- **Auto-Categorization**: Automatically assign categories based on merchant names
- **Learning System**: Improves accuracy by learning from user corrections
- **Confidence Scoring**: Shows how confident the system is in its categorization
- **Merchant Mapping**: Maintains a database of merchant-to-category mappings

### ✅ **Approval Workflow**
- **Pending Approval**: Auto-created expenses require user approval
- **Bulk Operations**: Approve or reject multiple expenses at once
- **Custom Categories**: Override auto-suggested categories
- **Rejection Reasons**: Track why expenses were rejected

### 📊 **Analytics & Monitoring**
- **Statistics Dashboard**: View processing statistics and accuracy rates
- **Performance Metrics**: Track categorization accuracy and learning progress
- **Source Tracking**: Monitor which sources (email/SMS) are most effective

## Architecture

### Core Components

1. **GmailService**: Handles Gmail API integration and email fetching
2. **EmailParserService**: Parses email content to extract transaction data
3. **SMSParserService**: Parses SMS content to extract transaction data
4. **AutoCategorizationService**: Smart categorization with learning capabilities
5. **ExpenseCreationService**: Creates expense records from parsed data
6. **PaymentNotificationService**: Main orchestrator service

### Database Schema

#### New Tables
- `merchant_category_mappings`: Stores merchant-to-category mappings
- `auto_created_expenses`: Tracks auto-created expenses and their status

#### Enhanced Tables
- `expenses`: Added fields for auto-creation tracking

## Installation & Setup

### 1. Dependencies

The system uses the following Laravel packages:
- Google API Client (for Gmail integration)
- Standard Laravel components

### 2. Environment Configuration

Add these to your `.env` file:

```env
# Gmail API Configuration
GMAIL_CREDENTIALS_PATH=storage/app/gmail-credentials.json
GMAIL_TOKEN_PATH=storage/app/gmail-token.json

# Payment Notifications Configuration
PAYMENT_NOTIFICATIONS_WEBHOOK_SECRET=your_webhook_secret_here
PAYMENT_NOTIFICATIONS_AUTO_APPROVE_THRESHOLD=0.9
PAYMENT_NOTIFICATIONS_DUPLICATE_CHECK_WINDOW=24
PAYMENT_NOTIFICATIONS_MAX_EMAILS_PER_BATCH=10
PAYMENT_NOTIFICATIONS_ENABLE_LEARNING=true
```

### 3. Gmail API Setup

1. **Create Google Cloud Project**:
   - Go to [Google Cloud Console](https://console.cloud.google.com/)
   - Create a new project or select existing one
   - Enable Gmail API

2. **Create Credentials**:
   - Go to "Credentials" in the API & Services section
   - Create OAuth 2.0 Client ID
   - Download the credentials JSON file
   - Save as `storage/app/gmail-credentials.json`

3. **Configure OAuth Scopes**:
   - Add `https://www.googleapis.com/auth/gmail.readonly` scope
   - Set authorized redirect URIs

### 4. Run Migrations

```bash
php artisan migrate
```

## Usage

### 1. Access the Dashboard

Navigate to `/payment-notifications` to access the main dashboard.

### 2. Gmail Authentication

1. Click "Connect Gmail" button
2. Complete OAuth flow
3. Grant necessary permissions
4. System will automatically process emails

### 3. Process Notifications

#### Email Processing
- Click "Process Emails" to fetch and process recent payment notifications
- System automatically parses emails and creates expense records
- Expenses are created with "pending approval" status

#### SMS Processing
- Use the API endpoint to submit SMS content
- System parses SMS and creates expense records
- Supports various bank and payment gateway formats

#### Webhook Processing
- External services can send notifications via webhook
- Real-time processing without user intervention
- Supports signature verification for security

### 4. Approve/Reject Expenses

1. View pending expenses in the dashboard
2. Review auto-suggested categories
3. Approve or reject with custom categories
4. System learns from your corrections

## API Endpoints

### Authentication
- `GET /payment-notifications/gmail/auth-url` - Get Gmail OAuth URL
- `POST /payment-notifications/gmail/authenticate` - Complete Gmail authentication

### Processing
- `POST /payment-notifications/process-emails` - Process email notifications
- `POST /payment-notifications/process-sms` - Process SMS notifications
- `POST /webhooks/payment-notifications` - Webhook endpoint

### Management
- `GET /payment-notifications/auto-created-expenses` - Get auto-created expenses
- `POST /payment-notifications/expenses/{id}/approve` - Approve expense
- `POST /payment-notifications/expenses/{id}/reject` - Reject expense

### Testing
- `POST /payment-notifications/test-parsing` - Test parsing with sample content
- `GET /payment-notifications/statistics` - Get system statistics

## Supported Payment Gateways

### Email Notifications
- **eSewa**: Payment confirmations and transaction receipts
- **Khalti**: Payment notifications and receipts
- **Banks**: Transaction confirmations from major Nepali banks

### SMS Notifications
- **NMB Bank**: Debit/credit notifications
- **Nabil Bank**: Transaction alerts
- **Himalayan Bank**: Payment confirmations
- **Machhapuchhre Bank**: Transaction notifications

## Parsing Patterns

### eSewa Email Pattern
```
Payment of Rs. 500.00 to ABC Store successful. Transaction ID: 12345
```

### Bank SMS Pattern
```
Rs. 1,500.00 debited from A/C **1234 on 15-Jan-24 at ABC Store. Avl Bal: Rs. 25,000
```

### Khalti Pattern
```
Payment of Rs. 200.00 to Restaurant XYZ successful via Khalti. Txn ID: KHT123456
```

## Auto-Categorization

### Merchant-Based Mapping
The system maintains a database of merchant-to-category mappings:

```php
$merchantCategories = [
    'ABC Store' => 'Shopping',
    'Restaurant XYZ' => 'Food & Dining',
    'Petrol Pump' => 'Transportation',
    'Hospital ABC' => 'Healthcare'
];
```

### Keyword-Based Categorization
Uses keyword matching for unknown merchants:

```php
$categoryKeywords = [
    'Food & Dining' => ['restaurant', 'cafe', 'food', 'dining', 'pizza'],
    'Transportation' => ['taxi', 'bus', 'petrol', 'fuel', 'uber'],
    'Shopping' => ['store', 'shop', 'mall', 'market', 'clothing']
];
```

### Learning System
- Learns from user corrections
- Improves accuracy over time
- Maintains confidence scores
- Tracks usage frequency

## Configuration Options

### Auto-Approval Threshold
Set the confidence threshold for automatic approval:

```env
PAYMENT_NOTIFICATIONS_AUTO_APPROVE_THRESHOLD=0.9
```

### Duplicate Detection
Configure the time window for duplicate detection:

```env
PAYMENT_NOTIFICATIONS_DUPLICATE_CHECK_WINDOW=24
```

### Batch Processing
Set maximum emails to process per batch:

```env
PAYMENT_NOTIFICATIONS_MAX_EMAILS_PER_BATCH=10
```

## Security Considerations

### Webhook Security
- Signature verification using HMAC-SHA256
- Rate limiting on webhook endpoints
- IP whitelisting for trusted sources

### Data Privacy
- Gmail access is read-only
- No sensitive data is stored permanently
- User can revoke access anytime

### Error Handling
- Comprehensive error logging
- Graceful fallbacks for parsing failures
- User-friendly error messages

## Troubleshooting

### Common Issues

#### Gmail Authentication Failed
1. Check credentials file path
2. Verify OAuth scopes
3. Ensure redirect URI is correct
4. Check Google Cloud Console settings

#### Parsing Not Working
1. Test with sample content using the test endpoint
2. Check regex patterns in parser classes
3. Verify email/SMS format matches expected patterns
4. Review error logs

#### Low Categorization Accuracy
1. Add more merchant mappings
2. Improve keyword lists
3. Use the learning system more frequently
4. Review and correct auto-suggestions

### Debug Mode

Enable debug logging:

```env
LOG_LEVEL=debug
```

Check logs in `storage/logs/laravel.log` for detailed information.

## Performance Optimization

### Caching
- Merchant mappings are cached for 1 hour
- Gmail tokens are cached to avoid re-authentication
- Parsing results are cached for duplicate detection

### Batch Processing
- Process multiple emails in batches
- Use database transactions for consistency
- Implement queue processing for large volumes

### Database Optimization
- Indexed fields for fast lookups
- Efficient queries with proper relationships
- Regular cleanup of old data

## Future Enhancements

### Planned Features
- **More Payment Gateways**: Support for additional services
- **Advanced ML**: Machine learning for better categorization
- **Mobile App Integration**: SMS reading from mobile devices
- **Bulk Import**: CSV import for historical data
- **Analytics Dashboard**: Advanced reporting and insights

### Integration Opportunities
- **Bank APIs**: Direct integration with bank APIs
- **Accounting Software**: Export to QuickBooks, Xero
- **Tax Preparation**: Integration with tax software
- **Budgeting Apps**: Sync with budgeting applications

## Support

### Getting Help
1. Check this documentation first
2. Review error logs for specific issues
3. Test with sample content using the test endpoint
4. Verify configuration settings

### Contributing
1. Follow Laravel coding standards
2. Add tests for new features
3. Update documentation
4. Submit pull requests

---

## Quick Start Checklist

- [ ] Set up Google Cloud Project and Gmail API
- [ ] Download and configure Gmail credentials
- [ ] Run database migrations
- [ ] Configure environment variables
- [ ] Test Gmail authentication
- [ ] Process sample emails/SMS
- [ ] Review and approve auto-created expenses
- [ ] Monitor system statistics

**🎉 You're ready to use automatic expense tracking!**






























