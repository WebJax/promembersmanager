# Pro Members Manager

Professional WordPress plugin for membership management with advanced statistics, CSV exports, and member tracking.

## Description

Pro Members Manager is an advanced WordPress plugin designed for organizations that need to maintain detailed membership information. This plugin provides a comprehensive solution for managing members, tracking their status, generating statistics, and exporting data.

## Features

- **Intuitive Member Management**: Add, edit, and delete members with ease through a user-friendly interface
- **Custom Fields**: Collect and store customized information about your members
- **Role-based Access Control**: Secure member data with customizable user roles and permissions
- **Advanced Statistics**: Generate insights about your membership with built-in analytics
- **CSV Import/Export**: Easily move data in and out of the system
- **Member Status Tracking**: Track active, inactive, and pending members
- **Shortcodes**: Display member information on the frontend of your website
- **Responsive Design**: Works seamlessly on desktop and mobile devices
- **Dashboard Widgets**: Quick access to key membership metrics
- **Payment Integration**: Track membership fees and payment statuses
- **Member Types**: Support for different membership categories (private, pension, union)
- **Renewal Tracking**: Monitor both automatic and manual renewal options

## Installation

1. Download the plugin ZIP file
2. Go to WordPress Admin > Plugins > Add New > Upload Plugin
3. Upload the ZIP file and click "Install Now"
4. Activate the plugin through the 'Plugins' menu

Alternatively, extract the ZIP file and upload the `promembersmanager` folder to the `/wp-content/plugins/` directory of your WordPress installation.

## Usage

### Admin Interface

Access the plugin from the WordPress admin menu under "Pro Members". From there you can:
- View and manage members
- Export data to CSV
- View statistics and reports
- Configure plugin settings

### Shortcodes

Use these shortcodes to display member information on your site:

- `[pro_members_list]`: Displays a list of members
  - Parameters:
    - `limit`: Maximum number of members to display (default: all)
    - `orderby`: Field to sort by (default: name)
    - `order`: Sort direction, ASC or DESC (default: ASC)
    - `type`: Filter by member type (private, pension, union)
    - `renewal`: Filter by renewal type (auto, manual)

### API Usage

For developers, the plugin provides several API functions:

```php
// Get all members
$members = PMM_Member_Manager::get_instance()->get_members();

// Get members count by type
$count_by_type = PMM_Member_Manager::get_instance()->get_members_count(['group_by' => 'member_type']);

// Export members to CSV
$csv_handler = new PMM_CSV_Handler();
$csv_handler->export_members($members);
```

### Template Customization

The plugin's frontend templates can be overridden in your theme by copying the templates from `promembersmanager/templates/` to `your-theme/promembersmanager-templates/`.

## Requirements

- WordPress 5.0 or higher
- PHP 7.2 or higher
- MySQL 5.6 or higher
- WooCommerce 4.0 or higher (for payment integration features)

## Frequently Asked Questions

### Can I import members from a CSV file?
Yes, the plugin provides functionality to import member data from properly formatted CSV files.

### Is the plugin GDPR compliant?
The plugin is designed with privacy in mind, but you are responsible for using it in compliance with GDPR and other privacy regulations relevant to your organization.

### Can I customize what data is collected about members?
Yes, the plugin allows for custom fields to be added to capture the specific information your organization needs.

### How can I display membership statistics?
You can use the built-in dashboard for statistics or use the Stats_Manager class to generate custom reports.

### Does the plugin work with WooCommerce?
Yes, the plugin integrates with WooCommerce for handling membership purchases and renewals.

## Changelog

### 1.1.0 (May 2025)
- Added detailed statistics dashboard
- Improved CSV export with additional filtering options
- Fixed compatibility issues with latest WordPress version
- Added template customization options

### 1.0.0
- Initial release with core functionality
- Member management system
- CSV export capabilities
- Basic statistics and reporting

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

### Development Setup

1. Clone the repository
2. Install dependencies: `npm install`
3. Run the build process: `npm run build`

### Coding Standards

This project follows the WordPress coding standards. Please ensure your contributions adhere to these standards.

## License

This plugin is licensed under the GPL v2 or later.

## Credits

Developed by Jacob Thygesen for Dianalund.

## Support

For support inquiries, please visit [https://dianalund.dk](https://dianalund.dk) or email support@dianalund.dk.

## Documentation

For detailed documentation and developer guides, please visit our [Documentation](https://dianalund.dk/pro-members-manager/docs/).
