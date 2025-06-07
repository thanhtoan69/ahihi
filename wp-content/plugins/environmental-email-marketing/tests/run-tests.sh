#!/bin/bash

# Test runner script for Environmental Email Marketing Plugin
# This script sets up the test environment and runs all tests

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}Environmental Email Marketing Plugin - Test Suite${NC}"
echo "=================================================="

# Check if PHPUnit is available
if ! command -v phpunit &> /dev/null; then
    echo -e "${RED}PHPUnit is not installed. Please install PHPUnit to run tests.${NC}"
    echo "You can install it via Composer: composer global require phpunit/phpunit"
    exit 1
fi

# Check if WordPress test library is set up
WP_TESTS_DIR=${WP_TESTS_DIR:-/tmp/wordpress-tests-lib}

if [ ! -d "$WP_TESTS_DIR" ]; then
    echo -e "${YELLOW}WordPress test library not found at $WP_TESTS_DIR${NC}"
    echo "Setting up WordPress test environment..."
    
    # Download WordPress test setup script
    wget -O /tmp/install-wp-tests.sh https://raw.githubusercontent.com/wp-cli/scaffold-command/v2.0.13/templates/install-wp-tests.sh
    chmod +x /tmp/install-wp-tests.sh
    
    # Run setup (you may need to modify these parameters)
    /tmp/install-wp-tests.sh wordpress_test root '' localhost latest
fi

# Set environment variables
export WP_TESTS_DIR
export WP_TESTS_CONFIG_FILE_PATH="$WP_TESTS_DIR/wp-tests-config.php"

echo -e "${GREEN}Running Plugin Tests...${NC}"
echo ""

# Run individual test suites
echo -e "${YELLOW}1. Database Manager Tests${NC}"
phpunit --configuration phpunit.xml tests/test-database-manager.php

echo -e "${YELLOW}2. Subscriber Manager Tests${NC}"
phpunit --configuration phpunit.xml tests/test-subscriber-manager.php

echo -e "${YELLOW}3. Campaign Manager Tests${NC}"
phpunit --configuration phpunit.xml tests/test-campaign-manager.php

echo -e "${YELLOW}4. Template Engine Tests${NC}"
phpunit --configuration phpunit.xml tests/test-template-engine.php

echo -e "${YELLOW}5. Analytics Tracker Tests${NC}"
phpunit --configuration phpunit.xml tests/test-analytics-tracker.php

echo -e "${YELLOW}6. Frontend Tests${NC}"
phpunit --configuration phpunit.xml tests/test-frontend.php

echo -e "${YELLOW}7. Integration Tests${NC}"
phpunit --configuration phpunit.xml tests/test-integration.php

echo ""
echo -e "${GREEN}Running Full Test Suite...${NC}"
phpunit --configuration phpunit.xml

echo ""
echo -e "${GREEN}All tests completed!${NC}"

# Generate coverage report if requested
if [[ "$1" == "--coverage" ]]; then
    echo -e "${YELLOW}Generating coverage report...${NC}"
    phpunit --configuration phpunit.xml --coverage-html tests/coverage
    echo -e "${GREEN}Coverage report generated in tests/coverage/index.html${NC}"
fi

echo ""
echo -e "${GREEN}Test Summary:${NC}"
echo "- Database operations tested"
echo "- Subscriber management tested"  
echo "- Campaign functionality tested"
echo "- Template rendering tested"
echo "- Analytics tracking tested"
echo "- Frontend interactions tested"
echo "- Component integration tested"
echo ""
echo -e "${GREEN}Plugin is ready for deployment!${NC}"
