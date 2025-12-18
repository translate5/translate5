#!/bin/bash
# Unified build script for HtmlEditor and all plugins
# Builds main editor and all plugin webpack configurations

set -e  # Exit on error

# Colors for output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${BLUE}========================================${NC}"
echo -e "${BLUE}Building HtmlEditor and Plugins${NC}"
echo -e "${BLUE}========================================${NC}"
echo ""

# Track overall success
BUILD_FAILED=0

# Function to build a project
build_project() {
    local name=$1
    local path=$2

    echo -e "${YELLOW}Building: ${name}${NC}"
    echo "Location: ${path}"

    if [ ! -d "$path" ]; then
        echo -e "${RED}✗ Directory not found: ${path}${NC}"
        BUILD_FAILED=1
        return 1
    fi

    if [ ! -f "$path/package.json" ]; then
        echo -e "${RED}✗ No package.json found in: ${path}${NC}"
        BUILD_FAILED=1
        return 1
    fi

    cd "$path"

    # Check if node_modules exists, if not run npm install
    if [ ! -d "node_modules" ]; then
        echo "  → Installing dependencies..."
        npm install
    fi

    # Run the build
    echo "  → Building..."
    npm run build

    if [ $? -eq 0 ]; then
        echo -e "${GREEN}✓ ${name} built successfully${NC}"
        echo ""
        return 0
    else
        echo -e "${RED}✗ ${name} build failed${NC}"
        echo ""
        BUILD_FAILED=1
        return 1
    fi
}

# Store the root directory
ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Build HtmlEditor (main app)
build_project "HtmlEditor (Main)" "$ROOT_DIR"

# Build TrackChanges plugin
build_project "TrackChanges Plugin" "$ROOT_DIR/../../PrivatePlugins/TrackChanges/javascripts"

# Add more plugins here as needed
# Example:
# build_project "AnotherPlugin" "$ROOT_DIR/../../PrivatePlugins/AnotherPlugin/javascripts"
# build_project "PublicPlugin" "$ROOT_DIR/../../Plugins/SomePlugin/javascripts"

# Return to original directory
cd "$ROOT_DIR"

echo -e "${BLUE}========================================${NC}"
if [ $BUILD_FAILED -eq 0 ]; then
    echo -e "${GREEN}✓ All builds completed successfully!${NC}"
    echo -e "${BLUE}========================================${NC}"
    exit 0
else
    echo -e "${RED}✗ Some builds failed. Check output above.${NC}"
    echo -e "${BLUE}========================================${NC}"
    exit 1
fi

