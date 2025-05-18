# Invoice Management System

A comprehensive invoice management system built with Laravel and Filament, designed to handle recurring invoices, payment tracking, and client management.

## Features

- **Invoice Management**
  - Create and manage invoices with detailed line items
  - Support for recurring invoices
  - Automatic invoice numbering
  - Multiple tax rates and discounts
  - PDF generation and email sending

- **Payment Tracking**
  - Record and track payments
  - Multiple payment methods support
  - Automatic invoice status updates
  - Payment history and reconciliation

- **Client Management**
  - Client profiles and contact information
  - Client-specific billing details
  - Payment history per client

- **Recurring Invoices**
  - Set up recurring billing schedules
  - Multiple frequency options (daily, weekly, monthly, quarterly, yearly)
  - Custom invoice number prefixes for recurring invoices
  - Automatic generation of recurring invoices

## Installation

1. Clone the repository:
   ```bash
   git clone https://github.com/yourusername/invoice-system.git
   cd invoice-system
   ```

2. Install dependencies:
   ```bash
   composer install
   npm install
   ```

3. Set up environment:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. Configure your database in `.env`:
   ```
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=your_database
   DB_USERNAME=your_username
   DB_PASSWORD=your_password
   ```

5. Run migrations:
   ```bash
   php artisan migrate
   ```

6. Build assets:
   ```bash
   npm run build
   ```

7. Start the development server:
   ```bash
   php artisan serve
   ```

## Scheduler Setup

The application uses Laravel's scheduler to automatically generate recurring invoices. Here's how to set it up:

### Production Environment

1. **Using Crontab (Linux/Unix)**
   ```bash
   # Add this entry to your crontab
   * * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
   
   # To edit crontab, run:
   crontab -e
   ```

2. **Using Laravel Forge**
   - Go to your server in Forge dashboard
   - Click on "Scheduler"
   - Add a new scheduled task
   - Set frequency to "Every Minute"
   - Command: `php artisan schedule:run`

### Local Development

For local development and testing, you can run the scheduler manually:

```bash
# Run the scheduler (keeps running)
php artisan schedule:work

# Or test the recurring invoice generation directly
php artisan invoices:generate-recurring
```

### Scheduled Tasks

The following tasks are scheduled to run automatically:

- **Recurring Invoice Generation**: Runs daily at midnight (00:00)
  - Generates new invoices based on recurring invoice settings
  - Logs output to `storage/logs/recurring-invoices.log`

### Troubleshooting

If scheduled tasks are not running:

1. Check if the scheduler is running:
   ```bash
   # Check crontab entries
   crontab -l
   
   # Check scheduler logs
   tail -f storage/logs/recurring-invoices.log
   ```

2. Verify the scheduler is working:
   ```bash
   # Test the scheduler
   php artisan schedule:list
   
   # Run a specific task
   php artisan invoices:generate-recurring
   ```

3. Common issues:
   - Incorrect path in crontab entry
   - Missing write permissions for log files
   - PHP not in system PATH
   - Incorrect timezone settings

## Usage

1. **Creating an Invoice**
   - Navigate to Invoices in the admin panel
   - Click "Create Invoice"
   - Select a client
   - Add line items with products or services
   - Set tax rates and discounts
   - Save the invoice

2. **Setting up Recurring Invoices**
   - Create a new invoice
   - Enable recurring option
   - Set frequency and interval
   - Set start and end dates
   - Save the invoice

3. **Recording Payments**
   - Go to the invoice details
   - Click "Record Payment"
   - Enter payment details
   - Save the payment

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.
