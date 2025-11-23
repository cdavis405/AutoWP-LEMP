# Contributing to AutoWP

Thank you for your interest in contributing to AutoWP! This document provides guidelines for contributing to the project.

## Code of Conduct

This project adheres to a code of conduct that values respect, professionalism, and inclusivity. By participating, you are expected to uphold this code.

## How to Contribute

### Reporting Issues

- Check if the issue already exists in the GitHub Issues
- Provide a clear title and detailed description
- Include steps to reproduce the issue
- Add screenshots if applicable
- Specify your environment (PHP version, WordPress version, etc.)

### Submitting Pull Requests

1. Fork the repository
2. Create a new branch (`git checkout -b feature/your-feature-name`)
3. Make your changes
4. Test thoroughly
5. Commit your changes (`git commit -m 'Add some feature'`)
6. Push to the branch (`git push origin feature/your-feature-name`)
7. Open a Pull Request

### Coding Standards

- Follow WordPress Coding Standards
- Use PHPDoc for documentation
- Write meaningful commit messages
- Test your code before submitting

### Pull Request Guidelines

- Keep PRs focused on a single feature or fix
- Update documentation if needed
- Add or update tests when applicable
- Ensure all CI checks pass
- Request review from maintainers

## Development Setup

### Prerequisites

- PHP 8.0+
- Composer
- Node.js 18+
- Local WordPress development environment (Local, XAMPP, etc.)

### Setup

```bash
# Clone repository
git clone https://github.com/yourusername/autowp-lemp.git
cd autowp-lemp

# Install dependencies
composer install  # If using Composer
npm install      # If using npm

# Copy theme to WordPress
cp -r wp-content/themes/autowp-theme /path/to/wordpress/wp-content/themes/
```

### Testing

```bash
# PHP syntax check
find wp-content/themes/autowp-theme -name "*.php" -exec php -l {} \;

# PHPCS
phpcs --standard=phpcs.xml wp-content/themes/autowp-theme/

# WordPress Coding Standards
phpcs --standard=WordPress wp-content/themes/autowp-theme/
```

## Project Structure

```
wp-content/themes/autowp-theme/
├── assets/          # CSS, JS, images
├── inc/             # PHP includes
├── parts/           # Template parts
├── templates/       # Block templates
├── functions.php    # Theme functions
├── style.css        # Main stylesheet
└── theme.json       # Theme configuration
```

## Feature Requests

We welcome feature requests! Please:

1. Check if the feature already exists or is planned
2. Clearly describe the feature and its benefits
3. Provide use cases
4. Be open to discussion and feedback

## Questions?

If you have questions, feel free to:

- Open a GitHub Discussion
- Create an issue with the "question" label
- Contact the maintainers

## License

By contributing, you agree that your contributions will be licensed under the GNU General Public License v2 or later.

Thank you for contributing to AutoWP!
